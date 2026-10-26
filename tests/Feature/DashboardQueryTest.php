<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\User;
use App\Services\DashboardQuery;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 5, 31)->startOfDay());
        $this->seed([ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function manual(string $phase = 'planning', array $attributes = []): Manual
    {
        $manual = Manual::create($attributes + [
            'manual_name' => 'Manual de consulta', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', $phase)->value('id'),
            'research_advances' => 2, 'experimentation_advances' => 2,
            'is_active' => true, 'is_published' => false, 'process_started_on' => today()->subMonths(2),
        ]);
        app(ManualWorkflow::class)->configure($manual);

        return $manual;
    }

    public function test_summary_counts_active_future_publications_once_and_does_not_hydrate_tracking(): void
    {
        $this->manual();
        $this->manual('cgft');
        $this->manual('cgft', ['is_published' => true, 'published_on' => today()->addYear()]);
        $this->manual('research', ['is_active' => false]);
        DB::enableQueryLog();
        $source = app(DashboardQuery::class);
        $summary = $source->summary($source->query(Request::create('/dashboard')), ManualPhase::orderBy('position')->get());
        $queries = collect(DB::getQueryLog())->pluck('query')->implode('\n');
        DB::disableQueryLog();
        $this->assertSame(3, $summary['total']);
        $this->assertSame(2, $summary['inProcess']);
        $this->assertSame(1, $summary['published']);
        $this->assertSame(1, $summary['counts']['cgft']);
        $this->assertEquals($summary['total'], $summary['counts']->sum() + $summary['published']);
        $this->assertStringNotContainsString('manual_phase_suphases', $queries);
        $this->assertStringNotContainsString('committee', $queries);
    }

    public function test_cgft_and_published_have_distinct_filters_with_shared_pdf_criteria(): void
    {
        $cgft = $this->manual('cgft', ['manual_name' => 'CGFT pendiente']);
        $published = $this->manual('cgft', ['manual_name' => 'Publicado futuro', 'is_published' => true, 'published_on' => today()->addYear()]);
        $cgftData = app(DashboardController::class)->data(Request::create('/dashboard', 'GET', ['phase' => 'cgft']));
        $exportData = app(DashboardController::class)->data(Request::create('/reports/manuals', 'GET', ['phase' => 'cgft']), true);
        $this->assertSame([$cgft->id], $cgftData['manuals']->pluck('id')->all());
        $this->assertSame($cgftData['manuals']->pluck('id')->all(), $exportData['manuals']->pluck('id')->all());
        $this->assertSame(0, $cgftData['published']);
        $data = app(DashboardController::class)->data(Request::create('/dashboard', 'GET', ['phase' => 'published']));
        $this->assertSame([$published->id], $data['manuals']->pluck('id')->all());
        $this->assertSame(0, $data['counts']->sum());
    }

    public function test_name_map_type_filters_are_shared_and_exclude_inactive_rows(): void
    {
        $types = ManualType::get();
        $otherMapType = $types->first(fn ($type) => $type->doctrinal_map_id !== $types->first()->doctrinal_map_id);
        $wanted = $this->manual('research', ['manual_name' => 'Consulta buscada']);
        $this->manual('research', ['manual_name' => 'Consulta otro mapa', 'manual_types_id' => $otherMapType->id]);
        $this->manual('research', ['manual_name' => 'Consulta inactiva', 'is_active' => false]);
        $request = Request::create('/dashboard', 'GET', ['q' => 'Consulta', 'map' => $wanted->manualType->doctrinal_map_id, 'type' => $wanted->manual_types_id]);
        $data = app(DashboardController::class)->data($request);
        $this->assertSame(1, $data['total']);
        $this->assertSame([$wanted->id], $data['manuals']->pluck('id')->all());
        $this->assertSame(1, $data['mapCounts']->sum());
    }

    public function test_listing_is_paginated_but_summary_and_export_cover_all_selected_rows(): void
    {
        for ($i = 0; $i < 23; $i++) {
            $this->manual('planning', ['manual_name' => sprintf('Manual %02d', $i)]);
        }
        $response = $this->get(route('dashboard', ['q' => 'Manual']));
        $response->assertOk()->assertViewHas('total', 23)->assertViewHas('manuals', fn ($rows) => $rows->count() === 20 && $rows->total() === 23)
            ->assertSee('page=2', false);
        $export = app(DashboardController::class)->data(Request::create('/reports/manuals', 'GET', ['q' => 'Manual']), true);
        $this->assertCount(23, $export['manuals']);
        $this->assertSame(23, $export['total']);
        $this->assertFalse($export['manuals']->first()->relationLoaded('manualCommitteeMembers'));
    }

    public function test_attention_uses_calendar_boundaries_excludes_planning_and_published(): void
    {
        $late = $this->manual('research');
        $late->phaseStates()->where('manual_phase_id', $late->manual_phases_id)->update(['started_on' => '2026-02-28']);
        $equal = $this->manual('edition');
        $equal->phaseStates()->where('manual_phase_id', $equal->manual_phases_id)->update(['started_on' => '2026-03-01']);
        $this->manual('planning', ['research_started_on' => today()->subYears(2)]);
        $this->manual('cgft', ['is_published' => true, 'published_on' => today()->addYear(), 'research_started_on' => today()->subYears(2)]);
        $this->manual('experimentation', ['research_started_on' => today()->subYear()]);
        $source = app(DashboardQuery::class);
        $summary = $source->summary($source->query(Request::create('/dashboard')), ManualPhase::get());
        $this->assertSame(1, $summary['overdue']);
        $this->assertTrue($late->fresh()->load(DashboardQuery::RELATIONS)->timing()['phase_overdue']);
        $this->assertFalse($equal->fresh()->load(DashboardQuery::RELATIONS)->timing()['phase_overdue']);
    }

    public function test_dashboard_and_pdf_render_publication_filter_without_observations(): void
    {
        $this->manual('cgft', ['manual_name' => 'Publicación visible', 'is_published' => true, 'published_on' => today()->addYear(), 'observations' => 'Nota reservada fuera del reporte']);
        $this->get(route('dashboard', ['phase' => 'published']))->assertOk()->assertSee('Publicación visible')->assertDontSee('Nota reservada fuera del reporte');
        $request = Request::create('/reports/manuals', 'GET', ['phase' => 'published']);
        $view = view('report.dashboardPDF', app(DashboardController::class)->data($request, true))->render();
        $this->assertStringContainsString('Publicación visible', $view);
        $this->assertStringNotContainsString('Nota reservada fuera del reporte', $view);
        $this->get(route('reports.manuals', ['phase' => 'published']))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_invalid_filters_are_rejected_and_empty_selection_renders(): void
    {
        $this->getJson(route('dashboard', ['phase' => 'inventada']))->assertUnprocessable();
        $this->get(route('dashboard', ['q' => 'Sin coincidencias']))->assertOk()->assertViewHas('total', 0)->assertSee('No hay manuales para estos filtros.');
    }

    public function test_classification_includes_unknown_without_assigning_defaults_and_is_filterable(): void
    {
        $this->manual('planning', ['classification' => 'generated']);
        $update = $this->manual('research', ['classification' => 'update']);
        $unknown = $this->manual('cgft', ['is_published' => true, 'published_on' => today()->addYear()]);
        $this->manual('planning', ['classification' => 'generated', 'is_active' => false]);
        $data = app(DashboardController::class)->data(Request::create('/dashboard'));
        $this->assertEquals(['generated' => 1, 'update' => 1, 'unknown' => 1], $data['classificationCounts']->all());
        $this->assertSame($data['total'], $data['classificationCounts']->sum());
        $data = app(DashboardController::class)->data(Request::create('/dashboard', 'GET', ['classification' => 'update']));
        $this->assertSame([$update->id], $data['manuals']->pluck('id')->all());
        $data = app(DashboardController::class)->data(Request::create('/reports/manuals', 'GET', ['classification' => 'unknown']), true);
        $this->assertSame([$unknown->id], $data['manuals']->pluck('id')->all());
        $this->assertNull($unknown->fresh()->classification);
        $this->getJson(route('dashboard', ['classification' => 'falso']))->assertUnprocessable();
        $this->get(route('dashboard', ['classification' => 'unknown']))->assertOk()->assertSee('Publicación futura declarada')->assertSee('tiempos hasta hoy');
    }

    public function test_unknown_start_and_future_publication_do_not_present_projected_days_as_elapsed(): void
    {
        $this->manual('cgft', ['is_published' => true, 'published_on' => today()->addYear(), 'process_started_on' => null]);
        $this->get(route('dashboard', ['phase' => 'published']))->assertOk()->assertSee('Sin inicio conocido')->assertSee('tiempos hasta hoy');
    }

    public function test_direct_publication_without_tracking_does_not_report_zero_as_noncompliance(): void
    {
        Manual::create([
            'manual_name' => 'Publicación histórica sin recorrido', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', 'cgft')->value('id'),
            'classification' => 'generated', 'is_active' => true, 'is_published' => true,
            'published_on' => today(), 'research_advances' => 2, 'experimentation_advances' => 2,
        ]);
        $this->get(route('dashboard', ['phase' => 'published']))->assertOk()->assertSee('Sin seguimiento previo')->assertDontSee('0,00 %');
        $data = app(DashboardController::class)->data(Request::create('/reports/manuals', 'GET', ['phase' => 'published']), true);
        $view = view('report.dashboardPDF', $data)->render();
        $this->assertStringContainsString('Sin seguimiento previo', $view);
        $this->assertStringNotContainsString('0,00 %', $view);
    }
}
