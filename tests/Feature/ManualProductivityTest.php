<?php

namespace Tests\Feature;

use App\Http\Controllers\HistoricalDashboardController;
use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\User;
use App\Services\ManualProductivity;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManualProductivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 24)->setTime(12, 0));
        $this->seed([ManualPhasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function manual(array $attributes = []): Manual
    {
        return Manual::create($attributes + [
            'manual_name' => 'Manual histórico', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', 'cgft')->value('id'),
            'research_advances' => 2, 'experimentation_advances' => 2,
            'is_active' => true, 'is_published' => false,
        ]);
    }

    private function visit(Manual $manual, string $phase, ?string $entered, ?string $exited = null, array $attributes = []): int
    {
        return DB::table('manual_phase_visits')->insertGetId($attributes + [
            'manual_id' => $manual->id, 'manual_phase_id' => ManualPhase::where('slug', $phase)->value('id'),
            'entered_on' => $entered, 'exited_on' => $exited,
            'entry_basis' => $entered ? 'phase_start' : 'unknown', 'source' => 'live',
            'entered_recorded_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function source(): ManualProductivity
    {
        return app(ManualProductivity::class);
    }

    public function test_presence_intersects_period_including_start_before_and_boundaries_without_duplicate_reentries(): void
    {
        $manual = $this->manual();
        $this->visit($manual, 'research', '2025-12-01', '2026-02-01');
        $this->visit($manual, 'research', '2026-03-01', '2026-03-20');
        $this->visit($manual, 'edition', '2026-03-21');
        $old = $this->manual();
        $this->visit($old, 'research', '2025-01-01', '2025-12-31');
        $boundary = $this->manual();
        $this->visit($boundary, 'research', '2025-01-01', '2026-01-01');
        $summary = $this->source()->summary([], '2026-01-01', '2026-12-31');
        $this->assertEquals(2, $summary['phases']['research']);
        $this->assertEquals(1, $summary['phases']['edition']);
        $listing = $this->source()->listing([], '2026-01-01', '2026-12-31', 'phase', 'research')->get();
        $this->assertCount(2, $listing);
        $this->assertEquals(2, $listing->firstWhere('manual_id', $manual->id)->matching_visits);
    }

    public function test_cgft_uses_effective_entry_not_recording_date_and_only_forward_transfers(): void
    {
        $manual = $this->manual();
        $event = ManualEvent::create(['manual_id' => $manual->id, 'action' => 'advance', 'reason' => 'Envío documentado']);
        $this->visit($manual, 'cgft', '2026-03-10', '2026-03-12', ['entry_basis' => 'transition', 'entry_event_id' => $event->id, 'entered_recorded_at' => '2026-03-20 10:00:00']);
        $reentry = ManualEvent::create(['manual_id' => $manual->id, 'action' => 'advance']);
        $this->visit($manual, 'cgft', '2026-03-14', null, ['entry_basis' => 'transition', 'entry_event_id' => $reentry->id]);
        $direct = $this->manual();
        $this->visit($direct, 'cgft', '2026-03-11');
        $retreat = ManualEvent::create(['manual_id' => $direct->id, 'action' => 'retreat']);
        $this->visit($direct, 'cgft', '2026-03-11', null, ['entry_basis' => 'transition', 'entry_event_id' => $retreat->id]);
        $summary = $this->source()->summary([], '2026-03-01', '2026-03-15');
        $this->assertSame(1, $summary['cgft']);
        $row = $this->source()->listing([], '2026-03-01', '2026-03-15', 'cgft', null)->first();
        $this->assertSame('2026-03-10', $row->effective_on);
        $this->assertEquals(2, $row->matching_visits);
        $this->assertSame(0, $this->source()->summary([], '2026-03-16', '2026-03-25')['cgft']);

        $series = $this->source()->monthlySeries([], '2026-03-01', '2026-04-30');
        $this->assertSame(1, $series[0]['cgft']);
        $this->assertSame(0, $series[1]['cgft']);
    }

    public function test_unknown_boundaries_are_not_invented_and_coverage_exposes_gaps(): void
    {
        $unknown = $this->manual();
        $this->visit($unknown, 'research', null);
        $closed = $this->manual();
        $event = ManualEvent::create(['manual_id' => $closed->id, 'action' => 'advance']);
        $this->visit($closed, 'research', '2025-10-01', null, ['exit_event_id' => $event->id]);
        $this->manual();
        $this->manual(['is_published' => true]);
        $summary = $this->source()->summary([], '2026-01-01', '2026-12-31');
        $this->assertEquals(0, $summary['phases']['research'] ?? 0);
        $this->assertEquals(1, $this->source()->summary([], '2025-10-01', '2025-10-01')['phases']['research']);
        $coverage = $this->source()->coverage([]);
        $this->assertSame(4, $coverage['total']);
        $this->assertSame(2, $coverage['without_visits']);
        $this->assertSame(1, $coverage['undated_visits']);
        $this->assertSame(1, $coverage['undated_exits']);
        $this->assertSame(1, $coverage['publications_without_date']);
    }

    public function test_future_publications_count_in_declared_year_but_open_visits_do_not_project_future_presence(): void
    {
        $future = $this->manual(['is_published' => true, 'published_on' => '2027-04-01']);
        $this->visit($this->manual(), 'edition', '2026-09-01');
        $this->manual(['is_published' => true, 'published_on' => '2027-04-01', 'is_active' => false]);
        $summary = $this->source()->summary([], '2027-01-01', '2027-12-31');
        $this->assertSame(1, $summary['publications']);
        $this->assertEquals(0, $summary['phases']->sum());
        $this->assertSame([$future->id], $this->source()->listing([], '2027-01-01', '2027-12-31', 'publications', null)->pluck('manual_id')->all());
        $series = $this->source()->monthlySeries([], '2027-01-01', '2027-12-31');
        $this->assertSame(1, $series[3]['publications']);
        $this->assertSame(1, $series[3]['future_publications']);
        $this->get(route('dashboard.history', ['year' => 2027]))->assertOk()->assertSee('con fecha futura');
    }

    public function test_scope_filters_apply_equally_to_period_comparison_coverage_and_list(): void
    {
        $type = ManualType::first();
        $otherMapType = ManualType::where('doctrinal_map_id', '!=', $type->doctrinal_map_id)->first();
        $wanted = $this->manual(['manual_name' => 'Consulta buscada', 'classification' => 'update', 'published_on' => '2026-04-01', 'is_published' => true]);
        $this->manual(['manual_name' => 'Consulta otro mapa', 'classification' => 'update', 'manual_types_id' => $otherMapType->id, 'published_on' => '2026-04-01', 'is_published' => true]);
        $this->manual(['manual_name' => 'Consulta desconocida', 'published_on' => '2026-04-01', 'is_published' => true]);
        $filters = ['map' => $type->doctrinal_map_id, 'type' => $type->id, 'classification' => 'update', 'q' => 'Consulta'];
        $this->assertSame(1, $this->source()->summary($filters, '2026-01-01', '2026-12-31')['publications']);
        $this->assertSame(1, $this->source()->coverage($filters)['total']);
        $this->assertSame([$wanted->id], $this->source()->listing($filters, '2026-01-01', '2026-12-31', 'publications', null)->pluck('manual_id')->all());
        $this->assertSame(1, $this->source()->summary(['classification' => 'unknown'], '2026-01-01', '2026-12-31')['publications']);
    }

    public function test_year_default_and_custom_range_comparison_use_calendar_dates(): void
    {
        $this->assertSame(['from' => '2026-01-01', 'to' => '2026-12-31', 'previous_from' => '2025-01-01', 'previous_to' => '2025-12-31', 'year' => 2026], $this->source()->periods([]));
        $period = $this->source()->periods(['from' => '2024-02-29', 'to' => '2024-03-10']);
        $this->assertSame('2023-02-28', $period['previous_from']);
        $this->assertSame('2023-03-10', $period['previous_to']);
    }

    public function test_screen_paginated_pdf_full_selection_share_summary_and_exclude_notes(): void
    {
        for ($i = 0; $i < 27; $i++) {
            $this->manual(['manual_name' => sprintf('Publicación %02d', $i), 'is_published' => true, 'published_on' => '2026-04-01', 'observations' => 'Nota exclusiva que no se debe exportar']);
        }
        $request = Request::create('/dashboard/history', 'GET', ['year' => 2026]);
        $screen = app(HistoricalDashboardController::class)->data($request);
        $pdf = app(HistoricalDashboardController::class)->data($request, true);
        $this->assertCount(25, $screen['manuals']);
        $this->assertCount(27, $pdf['manuals']);
        $this->assertEquals($screen['summary'], $pdf['summary']);
        $this->get(route('dashboard.history', ['year' => 2026]))->assertOk()->assertSee('page=2', false)->assertDontSee('Nota exclusiva que no se debe exportar');
        $view = view('report.historyPDF', $pdf)->render();
        $this->assertStringContainsString('27 manuales distintos', $view);
        $this->assertStringNotContainsString('Nota exclusiva que no se debe exportar', $view);
        $this->get(route('reports.history', ['year' => 2026]))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_routes_preserve_permissions_and_validation(): void
    {
        $this->getJson(route('dashboard.history', ['from' => '2026-03-20', 'to' => '2026-03-01']))->assertUnprocessable();
        $this->getJson(route('dashboard.history', ['from' => '2026-03-20']))->assertUnprocessable();
        $this->getJson(route('dashboard.history', ['metric' => 'phase']))->assertUnprocessable();
        $user = User::factory()->create();
        $user->assignRole('supervisor');
        $this->actingAs($user)->get(route('dashboard.history'))->assertOk();
        $user->syncRoles(['none']);
        $this->actingAs($user)->get(route('dashboard.history'))->assertForbidden();
        $this->get(route('reports.history'))->assertForbidden();
        $user->syncRoles(['admin']);
        $user->update(['is_active' => false]);
        $this->actingAs($user)->get(route('dashboard.history'))->assertForbidden();
    }
}
