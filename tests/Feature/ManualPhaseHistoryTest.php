<?php

namespace Tests\Feature;

use App\Models\Manual;
use App\Models\ManualEvent;
use App\Models\ManualPhase;
use App\Models\ManualPhaseVisit;
use App\Models\ManualType;
use App\Models\User;
use App\Services\EditionActivity;
use App\Services\ManualPhaseHistory;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ManualPhaseHistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-20 10:00:00');
        $this->seed([ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $actor = User::factory()->create();
        $actor->assignRole('admin');
        $this->actingAs($actor);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function manual(string $slug = 'edition', ?string $start = '2026-03-01', bool $published = false): Manual
    {
        $manual = Manual::create([
            'manual_name' => 'Historia aislada', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => ManualPhase::where('slug', $slug)->value('id'), 'research_advances' => 2, 'experimentation_advances' => 2,
            'process_started_on' => null, 'is_active' => true, 'is_published' => $published, 'published_on' => $published ? '2027-01-01' : null,
        ]);
        if (! $published) {
            app(ManualWorkflow::class)->configure($manual, true);
            $manual->phaseStates()->where('manual_phase_id', $manual->manual_phases_id)->update(['started_on' => $start]);
        }
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        app(ManualWorkflow::class)->event($manual, 'created', [], $manual->toArray());

        return $manual;
    }

    private function completeEdition(Manual $manual): void
    {
        foreach ($manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id) as $row) {
            $row->update(['is_completed' => true, 'completation_date' => '2026-03-10'] + match ($row->catalogSubphase->activity_key) {
                EditionActivity::START => ['follow_up' => 'Inicio acreditado'],
                EditionActivity::REVIEW => ['follow_up' => 'Revisión acreditada', 'due_on' => '2026-03-10'],
                default => ['started_on' => '2026-03-01', 'due_on' => '2026-03-10', 'responsible_user_id' => auth()->id()],
            });
        }
    }

    private function change(Manual $manual, string $action, array $data = [])
    {
        return $this->patchJson(route('manuals.activity', $manual->id), $data + ['action' => $action, 'version' => $manual->fresh()->lock_version, 'confirmed' => true]);
    }

    public function test_advanced_entry_records_only_its_known_phase_without_previous_fiction(): void
    {
        $manual = $this->manual();
        $visit = ManualPhaseVisit::where('manual_id', $manual->id)->sole();
        $this->assertSame('edition', $visit->phase->slug);
        $this->assertSame('2026-03-01', $visit->entered_on->toDateString());
        $this->assertSame('2026-03-20', $visit->entered_recorded_at->toDateString());
        $this->assertSame('phase_start', $visit->entry_basis);
        $this->assertSame('live', $visit->source);
    }

    public function test_effective_cgft_transfer_uses_completion_date_and_reentries_remain_separate(): void
    {
        $manual = $this->manual();
        $this->completeEdition($manual);
        $this->change($manual, 'advance')->assertOk()->assertJsonPath('phase', 'cgft');
        $cgft = ManualPhaseVisit::where('manual_id', $manual->id)->whereHas('phase', fn ($q) => $q->where('slug', 'cgft'))->sole();
        $this->assertSame('2026-03-10', $cgft->entered_on->toDateString());
        $this->assertSame('2026-03-20', $cgft->entered_recorded_at->toDateString());
        $this->assertSame('transition', $cgft->entry_basis);
        $this->assertSame('2026-03-10', $cgft->entryEvent->after['effective_on']);
        $this->change($manual, 'start', ['started_on' => '2026-03-12'])->assertOk();
        $this->assertSame('2026-03-10', $cgft->fresh()->entered_on->toDateString());
        $this->change($manual, 'retreat')->assertOk()->assertJsonPath('phase', 'edition');
        $this->assertSame('2026-03-20', $cgft->fresh()->exited_on->toDateString());
        $this->assertSame(2, ManualPhaseVisit::where('manual_id', $manual->id)->whereHas('phase', fn ($q) => $q->where('slug', 'edition'))->count());
        $this->assertSame(1, ManualPhaseVisit::overlappingPeriod('2026-03-01', '2026-03-31')->whereHas('phase', fn ($q) => $q->where('slug', 'edition'))->distinct()->count('manual_id'));
        $this->change($manual, 'advance')->assertOk();
        $lastEdition = ManualPhaseVisit::where('manual_id', $manual->id)->whereHas('phase', fn ($q) => $q->where('slug', 'edition'))->latest('id')->first();
        $this->assertSame('2026-03-20', $lastEdition->exited_on->toDateString());
        $this->assertSame(2, ManualPhaseVisit::where('manual_id', $manual->id)->whereHas('phase', fn ($q) => $q->where('slug', 'cgft'))->count());
    }

    public function test_unknown_initial_start_can_be_completed_and_corrected_with_original_event_audit(): void
    {
        $manual = $this->manual('edition', null);
        $visit = ManualPhaseVisit::where('manual_id', $manual->id)->sole();
        $this->assertNull($visit->entered_on);
        $this->change($manual, 'start', ['started_on' => '2026-03-05'])->assertOk();
        $this->change($manual, 'start', ['started_on' => '2026-03-03'])->assertOk();
        $this->assertSame('2026-03-03', $visit->fresh()->entered_on->toDateString());
        $event = $manual->events()->where('action', 'start')->first();
        $this->assertSame('2026-03-05', $event->before['started_on']);
        $this->assertSame('2026-03-03', $event->after['started_on']);
        $this->assertSame(0, ManualPhaseVisit::overlappingPeriod('2027-01-01', '2027-12-31')->count());
    }

    public function test_direct_publication_does_not_simulate_cgft_or_allow_tracking_operations(): void
    {
        $manual = $this->manual('cgft', null, true);
        $this->assertSame(0, ManualPhaseVisit::where('manual_id', $manual->id)->count());
        foreach (['start', 'retreat', 'advance'] as $action) {
            $this->change($manual, $action, ['started_on' => '2026-03-01'])->assertUnprocessable()->assertJsonValidationErrors('phase');
        }
        $this->assertTrue($manual->fresh()->is_published);
        $this->assertSame(0, $manual->fresh()->lock_version);
        $this->assertSame(0, ManualPhaseVisit::where('manual_id', $manual->id)->count());
    }

    public function test_declared_future_publication_is_separate_from_real_cgft_departure(): void
    {
        $manual = $this->manual('cgft');
        $manual->manualPhaseSuphases()->where('manual_phases_id', $manual->manual_phases_id)->update(['is_completed' => true, 'completation_date' => '2026-03-10']);
        $this->change($manual, 'advance', ['published_on' => '2027-01-01'])->assertOk()->assertJsonPath('published', true);
        $this->assertSame('2027-01-01', $manual->fresh()->published_on->toDateString());
        $this->assertSame('2026-03-10', ManualPhaseVisit::where('manual_id', $manual->id)->sole()->exited_on->toDateString());
        $this->assertSame('2027-01-01', $manual->events()->where('action', 'advance')->first()->after['published_on']);
    }

    public function test_history_failure_rolls_back_manual_transition_and_event_together(): void
    {
        $manual = $this->manual();
        $this->completeEdition($manual);
        $this->mock(ManualPhaseHistory::class, fn ($mock) => $mock->shouldReceive('record')->once()->andThrow(new \RuntimeException('Test isolation failure')));
        $this->change($manual, 'advance')->assertStatus(500);
        $this->assertSame('edition', $manual->fresh()->manualPhase->slug);
        $this->assertSame(0, $manual->fresh()->lock_version);
        $this->assertSame(0, $manual->events()->where('action', 'advance')->count());
        $this->assertNull(ManualPhaseVisit::where('manual_id', $manual->id)->sole()->exit_event_id);
    }

    public function test_reconstruction_is_dry_by_default_idempotent_and_preserves_unknown_transfer_dates(): void
    {
        $manual = Manual::create(['manual_name' => 'Antecedente', 'manual_types_id' => ManualType::first()->id, 'manual_phases_id' => ManualPhase::where('slug', 'research')->value('id'), 'research_advances' => 2, 'experimentation_advances' => 2, 'is_active' => true]);
        $planning = ManualPhase::where('slug', 'planning')->value('id');
        ManualEvent::create(['manual_id' => $manual->id, 'action' => 'created', 'before' => [], 'after' => ['manual_phases_id' => $planning, 'process_started_on' => '2026-02-01']]);
        $transfer = ManualEvent::create(['manual_id' => $manual->id, 'action' => 'advance', 'before' => ['phase' => 'planning'], 'after' => ['phase' => 'research']]);
        $beforeEvents = $manual->events()->get()->toArray();
        $this->artisan('manuals:reconstruct-history', ['--manual' => $manual->id])->assertSuccessful();
        $this->assertDatabaseCount('manual_phase_visits', 0);
        $this->artisan('manuals:reconstruct-history', ['--manual' => $manual->id, '--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('manual_phase_visits', 2);
        $first = ManualPhaseVisit::where('manual_phase_id', $planning)->sole();
        $this->assertSame('2026-02-01', $first->entered_on->toDateString());
        $this->assertNull($first->exited_on);
        $this->assertSame($transfer->id, $first->exit_event_id);
        $this->assertSame(0, ManualPhaseVisit::overlappingPeriod('2026-03-01', '2026-03-31')->count());
        $this->assertSame(1, ManualPhaseVisit::overlappingPeriod('2026-02-01', '2026-02-28')->count());
        $this->assertNull(ManualPhaseVisit::where('manual_phase_id', $manual->manual_phases_id)->sole()->entered_on);
        $this->artisan('manuals:reconstruct-history', ['--manual' => $manual->id, '--apply' => true])->assertSuccessful();
        $this->assertDatabaseCount('manual_phase_visits', 2);
        $this->assertEquals($beforeEvents, $manual->events()->get()->toArray());
    }
}
