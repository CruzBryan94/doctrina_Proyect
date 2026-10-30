<?php

namespace Tests\Feature;

use App\Models\CatalogSubphase;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualPhaseSuphase;
use App\Models\ManualType;
use App\Models\User;
use App\Services\EditionActivity;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\CommitteeTypeSeeder;
use Database\Seeders\Grade_Seeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditionWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Manual $manual;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([Grade_Seeder::class, CommitteeTypeSeeder::class, ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
        $this->user = User::factory()->create();
        $this->user->assignRole('admin');
        $this->actingAs($this->user);
        $phase = ManualPhase::where('slug', 'edition')->first();
        $this->manual = Manual::create(['manual_name' => 'Edición de prueba', 'manual_types_id' => ManualType::first()->id, 'manual_phases_id' => $phase->id, 'research_advances' => 2, 'experimentation_advances' => 2, 'process_started_on' => today()->subDays(60), 'is_active' => true, 'is_published' => false]);
        app(ManualWorkflow::class)->configure($this->manual);
        $this->manual->phaseStates()->update(['started_on' => today()->subDays(50)]);
        $previous = ManualPhase::where('position', '<', $phase->position)->pluck('id');
        $this->manual->manualPhaseSuphases()->whereIn('manual_phases_id', $previous)->update(['is_completed' => true, 'completation_date' => today()->subDays(45)]);
        $this->manual = $this->manual->fresh()->load(ManualWorkflow::RELATIONS);
    }

    private function patchActivity(string $key, array $data = [])
    {
        return $this->patchJson(route('manuals.activity', $this->manual->id), $data + ['action' => 'activity', 'version' => $this->manual->fresh()->lock_version, 'activity_id' => CatalogSubphase::where('activity_key', $key)->value('id'), 'is_completed' => false, 'completation_date' => null]);
    }

    private function completed(string $key): array
    {
        return ['is_completed' => true, 'completation_date' => today()->toDateString()] + match ($key) {
            EditionActivity::START => ['follow_up' => 'Corrección idiomática iniciada y observaciones registradas.'],
            EditionActivity::REVIEW => ['follow_up' => 'Revisión final realizada con observaciones resueltas.', 'due_on' => today()->addDay()->toDateString()],
            default => ['started_on' => today()->subDay()->toDateString(), 'due_on' => today()->addDays(3)->toDateString(), 'responsible_user_id' => $this->user->id],
        };
    }

    public function test_drafts_are_independent_nullable_and_preserve_omitted_fields(): void
    {
        $this->patchActivity(EditionActivity::START, ['follow_up' => 'Primer seguimiento'])->assertOk()->assertJsonPath('progress', 0);
        $this->patchActivity(EditionActivity::REVIEW, ['follow_up' => 'Segundo seguimiento', 'due_on' => today()->addDays(10)->toDateString()])->assertOk();
        $this->patchActivity(EditionActivity::REVIEW, ['due_on' => null])->assertOk()->assertJsonPath('activity_state.follow_up', 'Segundo seguimiento')->assertJsonPath('activity_state.due_on', null);
        $this->patchActivity(EditionActivity::START)->assertOk()->assertJsonPath('activity_state.follow_up', 'Primer seguimiento');
        $this->patchActivity(EditionActivity::START, ['follow_up' => str_repeat('a', 501)])->assertUnprocessable();
        $this->patchActivity(EditionActivity::START, ['responsible_user_id' => $this->user->id])->assertUnprocessable();
        $this->assertSame(4, $this->manual->events()->where('action', 'activity')->count());
    }

    public function test_requirements_are_checked_and_cannot_be_cleared_on_completed_activity(): void
    {
        foreach (array_keys(EditionActivity::NAMES) as $key) {
            $this->patchActivity($key, ['is_completed' => true, 'completation_date' => today()->toDateString()])->assertUnprocessable();
        }
        $this->patchActivity(EditionActivity::START, $this->completed(EditionActivity::START))->assertOk()->assertJsonPath('progress', 33.33);
        $this->patchActivity(EditionActivity::START, ['is_completed' => true, 'completation_date' => today()->toDateString(), 'follow_up' => null])->assertUnprocessable();
        $this->patchActivity(EditionActivity::START, ['follow_up' => null])->assertOk()->assertJsonPath('progress', 0);
    }

    public function test_three_completed_activities_enable_only_explicit_confirmed_send_to_cgft(): void
    {
        foreach (array_keys(EditionActivity::NAMES) as $key) {
            $this->patchActivity($key, $this->completed($key))->assertOk()->assertJsonPath('phase', 'edition');
        }
        $this->assertEquals(100, $this->manual->fresh()->load(ManualWorkflow::RELATIONS)->progress());
        $this->get(route('manuals.index', ['phase' => 'edition']))->assertOk()->assertSee('Enviar a CGFT')->assertViewHas('summary', fn ($summary) => (int) $summary->ready === 1);
        $url = route('manuals.activity', $this->manual->id);
        $this->patchJson($url, ['action' => 'advance', 'version' => $this->manual->fresh()->lock_version])->assertUnprocessable();
        $this->patchJson($url, ['action' => 'advance', 'version' => $this->manual->fresh()->lock_version, 'confirmed' => true])->assertOk()->assertJsonPath('phase', 'cgft')->assertJsonPath('published', false);
    }

    public function test_deadlines_future_late_completion_and_invalid_chronology(): void
    {
        $this->patchActivity(EditionActivity::REVIEW, $this->completed(EditionActivity::REVIEW) + [])->assertOk();
        $this->patchActivity(EditionActivity::REVIEW, ['due_on' => today()->subDay()->toDateString(), 'is_completed' => true, 'completation_date' => today()->toDateString()])->assertOk()->assertJsonPath('activity_state.deadline_status', 'Fuera de plazo');
        $this->patchActivity(EditionActivity::REVIEW, ['due_on' => today()->subDay()->toDateString()])->assertOk()->assertJsonPath('activity_state.deadline_status', 'Vencida');
        $this->patchActivity(EditionActivity::LAYOUT, ['started_on' => today()->toDateString(), 'due_on' => today()->subDay()->toDateString()])->assertUnprocessable();
        $this->patchActivity(EditionActivity::LAYOUT, ['started_on' => today()->addDay()->toDateString()])->assertUnprocessable();
        $this->patchActivity(EditionActivity::REVIEW, ['due_on' => today()->subDays(55)->toDateString()])->assertUnprocessable();
    }

    public function test_responsible_must_exist_and_be_active_for_new_assignment(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $this->patchActivity(EditionActivity::LAYOUT, ['responsible_user_id' => 999999])->assertUnprocessable();
        $this->patchActivity(EditionActivity::LAYOUT, ['responsible_user_id' => $inactive->id])->assertUnprocessable();
        $this->patchActivity(EditionActivity::LAYOUT, $this->completed(EditionActivity::LAYOUT))->assertOk();
        $this->user->update(['is_active' => false]);
        $other = User::factory()->create();
        $other->assignRole('admin');
        $this->actingAs($other);
        $this->patchActivity(EditionActivity::LAYOUT, ['is_completed' => true, 'completation_date' => today()->toDateString(), 'responsible_user_id' => $this->user->id])->assertOk();
        $this->getJson(route('manuals.responsibleOptions', ['id' => $this->manual->id, 'q' => $this->user->email]))->assertOk()->assertJsonCount(0, 'items');
        $other->syncRoles(['supervisor']);
        $this->patchActivity(EditionActivity::START, ['follow_up' => 'No permitido'])->assertForbidden();
        $this->getJson(route('manuals.responsibleOptions', $this->manual->id))->assertForbidden();
    }

    public function test_shared_progress_and_ready_counts_reject_tampered_incomplete_metadata(): void
    {
        foreach (array_keys(EditionActivity::NAMES) as $key) {
            $this->patchActivity($key, $this->completed($key))->assertOk();
        }
        $this->manual->manualPhaseSuphases()->where('catalog_subphases_id', CatalogSubphase::where('activity_key', EditionActivity::LAYOUT)->value('id'))->update(['responsible_user_id' => null]);
        $manual = $this->manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertEquals(66.67, $manual->progress());
        $this->get(route('manuals.index', ['phase' => 'edition']))->assertViewHas('summary', fn ($summary) => (int) $summary->ready === 0);
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'confirmed' => true, 'version' => $manual->lock_version])->assertUnprocessable();
    }

    public function test_edition_metadata_requires_current_version_and_start_changes_respect_activity_dates(): void
    {
        $this->patchActivity(EditionActivity::LAYOUT, ['started_on' => today()->subDays(20)->toDateString(), 'due_on' => today()->addDays(4)->toDateString()])->assertOk();
        $this->patchActivity(EditionActivity::START, ['version' => 0, 'follow_up' => 'Antiguo'])->assertConflict();
        $this->patchJson(route('manuals.activity', $this->manual->id), ['action' => 'start', 'version' => $this->manual->fresh()->lock_version, 'started_on' => today()->subDays(10)->toDateString()])->assertUnprocessable();
    }

    public function test_catalog_upgrade_preserves_old_rows_and_closed_editions(): void
    {
        $phase = ManualPhase::where('slug', 'edition')->firstOrFail();
        $oldIds = [];
        for ($i = 1; $i <= 6; $i++) {
            $oldIds[] = CatalogSubphase::create(['activity_key' => 'edition.'.$i, 'suphase_name' => 'Actividad histórica '.$i, 'manual_phases_id' => $phase->id, 'position' => $i])->id;
        }
        $this->manual->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->delete();
        foreach ($oldIds as $id) {
            ManualPhaseSuphase::create(['manuals_id' => $this->manual->id, 'manual_phases_id' => $phase->id, 'catalog_subphases_id' => $id, 'is_completed' => true, 'completation_date' => today()->subDays(4)]);
        }
        $closed = $this->manual->replicate();
        $closed->manual_phases_id = ManualPhase::where('slug', 'cgft')->value('id');
        $closed->save();
        app(ManualWorkflow::class)->configure($closed);
        $closed->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->delete();
        foreach ($oldIds as $id) {
            ManualPhaseSuphase::create(['manuals_id' => $closed->id, 'manual_phases_id' => $phase->id, 'catalog_subphases_id' => $id, 'is_completed' => true, 'completation_date' => today()->subDays(4)]);
        }
        $before = ManualPhaseSuphase::whereIn('catalog_subphases_id', $oldIds)->get()->keyBy('id')->map->completation_date;
        $migration = require database_path('migrations/2026_10_23_000004_upgrade_edition_activities.php');
        $migration->up();
        $this->assertEquals(3, $this->manual->fresh()->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->count());
        $this->assertEquals(6, $this->manual->fresh()->legacyActivities()->count());
        $this->assertEquals(6, $closed->fresh()->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->count());
        $this->assertEquals(1, $closed->fresh()->phaseStates()->where('manual_phase_id', $phase->id)->value('catalog_version'));
        foreach ($before as $id => $date) {
            $this->assertEquals($date, ManualPhaseSuphase::findOrFail($id)->completation_date);
        }
        $this->assertEquals(0, CatalogSubphase::whereIn('id', $oldIds)->where('is_active', true)->count());
        $closed->phaseStates()->update(['started_on' => today()->subDays(3)]);
        $this->patchJson(route('manuals.activity', $closed->id), ['action' => 'retreat', 'confirmed' => true, 'version' => $closed->fresh()->lock_version])->assertOk()->assertJsonPath('phase', 'edition')->assertJsonPath('progress', 0);
        $this->assertEquals(6, $closed->fresh()->legacyActivities()->count());
        $this->assertEquals(3, $closed->fresh()->manualPhaseSuphases()->where('manual_phases_id', $phase->id)->count());
        $this->assertEquals(2, $closed->fresh()->phaseStates()->where('manual_phase_id', $phase->id)->value('catalog_version'));
    }
}
