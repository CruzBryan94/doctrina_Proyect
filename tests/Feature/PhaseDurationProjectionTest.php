<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\PhaseDurationAudit;
use App\Models\User;
use App\Services\ManualProjection;
use App\Services\ManualWorkflow;
use App\Services\PhaseDurationService;
use Carbon\Carbon;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseDurationProjectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-05-31')->startOfDay());
        $this->seed([ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $user = User::factory()->create();
        $user->assignRole('admin');
        $this->actingAs($user);
    }

    private function manual(string $slug, ?string $startedOn = null, array $extra = []): Manual
    {
        $manual = Manual::create($extra + [
            'manual_name' => 'Manual cronograma', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', $slug)->value('id'), 'classification' => 'generated',
            'is_active' => true, 'is_published' => false, 'research_advances' => 2, 'experimentation_advances' => 2,
            'process_started_on' => '2026-01-01',
        ]);
        app(ManualWorkflow::class)->configure($manual);
        if ($startedOn) {
            $manual->phaseStates()->where('manual_phase_id', $manual->manual_phases_id)->update(['started_on' => $startedOn]);
            if ($slug === 'research') {
                $manual->update(['research_started_on' => $startedOn]);
            }
        }
        return $manual->fresh()->load(['manualPhase', 'phaseStates']);
    }

    public function test_month_end_threshold_uses_calendar_months_and_only_alerts_after_equality(): void
    {
        $late = $this->manual('research', '2026-02-28');
        $equal = $this->manual('research', '2026-02-28', ['manual_name' => 'Igual']);
        $service = app(PhaseDurationService::class);
        $service->update(ManualPhase::where('slug', 'research')->first(), 3, 3, auth()->id());
        $this->assertSame('2026-05-28', $late->timing()['phase_deadline']);
        $this->assertTrue($late->timing()['phase_overdue']);
        $this->travelTo(Carbon::parse('2026-05-28')->startOfDay());
        $this->assertFalse($equal->fresh()->load(['manualPhase', 'phaseStates'])->timing()['phase_overdue']);
        $this->travelTo(Carbon::parse('2026-05-29')->startOfDay());
        $this->assertTrue($equal->fresh()->load(['manualPhase', 'phaseStates'])->timing()['phase_overdue']);
    }

    public function test_duration_update_is_global_audited_and_does_not_change_manual_dates_or_tracking(): void
    {
        $first = $this->manual('experimentation', '2026-03-01');
        $second = $this->manual('experimentation', '2026-03-15', ['manual_name' => 'Otro manual']);
        $before = [$first->process_started_on?->toDateString(), $second->process_started_on?->toDateString(),
            $first->phaseStates()->get()->map(fn ($row) => $row->started_on?->toDateString())->all(), $first->manualPhaseSuphases()->pluck('completation_date')->all()];
        $phase = ManualPhase::where('slug', 'experimentation')->first();
        app(PhaseDurationService::class)->update($phase, 2, 3, auth()->id());
        $this->assertSame(2, $first->fresh()->load(['manualPhase', 'phaseStates'])->timing()['phase_duration_months']);
        $this->assertSame(2, $second->fresh()->load(['manualPhase', 'phaseStates'])->timing()['phase_duration_months']);
        $this->assertSame($before, [$first->fresh()->process_started_on?->toDateString(), $second->fresh()->process_started_on?->toDateString(),
            $first->phaseStates()->get()->map(fn ($row) => $row->started_on?->toDateString())->all(), $first->manualPhaseSuphases()->pluck('completation_date')->all()]);
        $audit = PhaseDurationAudit::firstOrFail();
        $this->assertSame([3, 2], [(int) $audit->before_months, (int) $audit->after_months]);
        $this->assertSame(auth()->id(), $audit->user_id);
    }

    public function test_no_start_has_no_projection_and_future_publication_is_excluded(): void
    {
        $unknown = $this->manual('experimentation');
        $futurePublished = $this->manual('cgft', '2026-01-01', ['is_published' => true, 'published_on' => '2027-01-01']);
        $service = new ManualProjection();
        $durationMap = app(PhaseDurationService::class)->all();
        $asOf = Carbon::parse('2026-05-31');
        $this->assertSame([], $service->build($unknown, $durationMap, $asOf)['phases']);
        $this->assertSame([], $service->build($futurePublished, $durationMap, $asOf)['phases']);
    }

    public function test_late_phase_projects_following_phases_from_query_date_as_example(): void
    {
        $manual = $this->manual('research', '2026-02-28');
        $result = (new ManualProjection())->build($manual, app(PhaseDurationService::class)->all(), Carbon::parse('2026-05-31'));
        $this->assertTrue($result['example']);
        $this->assertTrue($result['phases'][0]['overdue']);
        $this->assertSame('2026-05-31', $result['phases'][1]['start']->toDateString());
        $this->assertTrue($result['phases'][1]['example']);
    }

    public function test_old_planning_start_has_no_alert_but_starts_example_phases_from_today(): void
    {
        $planning = $this->manual('planning', '2025-01-01');
        $result = (new ManualProjection())->build($planning, app(PhaseDurationService::class)->all(), Carbon::parse('2026-05-31'));
        $this->assertFalse($result['late']);
        $this->assertTrue($result['example']);
        $this->assertFalse($result['phases'][0]['overdue']);
        $this->assertSame('2026-05-31', $result['phases'][1]['start']->toDateString());
        $this->assertTrue($result['phases'][1]['example']);
    }

    public function test_phase_catalog_is_loaded_once_for_multiple_manual_projections(): void
    {
        $one = $this->manual('research', '2026-05-01');
        $two = $this->manual('experimentation', '2026-05-01', ['manual_name' => 'Segundo']);
        $calculator = new ManualProjection();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $calculator->build($one, app(PhaseDurationService::class)->all(), today()->copy());
        $calculator->build($two, app(PhaseDurationService::class)->all(), today()->copy());
        $phaseQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains(strtolower($query['query']), 'from "manual_phases"') || str_contains(strtolower($query['query']), 'from `manual_phases`'));
        DB::disableQueryLog();
        $this->assertCount(1, $phaseQueries);
    }

    public function test_admin_pages_render_and_duration_write_checks_bounds_and_stale_values(): void
    {
        $phase = ManualPhase::where('slug', 'research')->first();
        $known = $this->manual('experimentation', '2026-03-01', ['manual_name' => 'Con inicio']);
        $unknown = $this->manual('experimentation', null, ['manual_name' => 'Sin inicio conocido']);
        $this->manual('cgft', '2026-01-01', ['manual_name' => 'Publicado futuro', 'is_published' => true, 'published_on' => '2027-01-01']);
        $this->get(route('phase-durations.index'))->assertOk()->assertSee('Duración global por fase')->assertSee('1–120');
        $this->get(route('dashboard.schedule'))->assertOk()->assertSee('Cronograma mensual')->assertSee('Con inicio')->assertSee('Sin inicio conocido')
            ->assertSee('projection-bar phase-experimentation actual', false)->assertSee('projection-bar phase-experimentation projected', false)
            ->assertDontSee('Publicado futuro')->assertDontSee('value="published"', false);
        $this->get(route('dashboard.schedule', ['year' => 2027, 'start_month' => 10, 'months' => 6, 'q' => 'Con inicio']))
            ->assertOk()->assertViewHas('calendar', fn ($calendar) => $calendar->count() === 6
                && $calendar->first()->format('Y-m') === '2027-10' && $calendar->last()->format('Y-m') === '2028-03')
            ->assertSee('Con inicio')->assertDontSee('Sin inicio conocido');
        $this->put(route('phase-durations.update', $phase->id), ['duration_months' => 5, 'old_months' => 3])->assertRedirect();
        $this->put(route('phase-durations.update', $phase->id), ['duration_months' => 4, 'old_months' => 3])->assertStatus(409);
        $this->put(route('phase-durations.update', $phase->id), ['duration_months' => 121, 'old_months' => 5])->assertSessionHasErrors('duration_months');
        $this->assertSame(5, app(PhaseDurationService::class)->forPhase('research'));
    }
}
