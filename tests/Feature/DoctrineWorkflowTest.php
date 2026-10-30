<?php

namespace Tests\Feature;

use App\Models\CatalogSubphase;
use App\Models\CommitteeMember;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\MilitaryUnit;
use App\Models\User;
use App\Services\ManualWorkflow;
use Database\Seeders\CatalogSubphasesSeeder;
use Database\Seeders\CommitteeTypeSeeder;
use Database\Seeders\Grade_Seeder;
use Database\Seeders\ManualPhasesSeeder;
use Database\Seeders\ManualTypesSeeder;
use Database\Seeders\MilitaryUnitsSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctrineWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([Grade_Seeder::class, CommitteeTypeSeeder::class, MilitaryUnitsSeeder::class, ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
    }

    private function actor(array $roles = ['admin']): User
    {
        $user = User::factory()->create();
        $user->syncRoles($roles);
        $this->actingAs($user);

        return $user;
    }

    private function manual(string $slug = 'planning', int $advances = 2): Manual
    {
        $phase = ManualPhase::where('slug', $slug)->firstOrFail();
        $manual = Manual::create([
            'manual_name' => 'Manual de prueba', 'manual_types_id' => ManualType::first()->id,
            'manual_phases_id' => $phase->id, 'research_advances' => $advances, 'experimentation_advances' => min(5, $advances),
            'process_started_on' => today()->subDays(60), 'is_active' => true, 'is_published' => false,
        ]);
        app(ManualWorkflow::class)->configure($manual);
        $manual->load(ManualWorkflow::RELATIONS);
        foreach (ManualPhase::where('position', '<=', $phase->position)->get() as $previous) {
            $manual->phaseStates->firstWhere('manual_phase_id', $previous->id)->update(['started_on' => today()->subDays(50)]);
            if ($previous->position < $phase->position) {
                $manual->manualPhaseSuphases()->where('manual_phases_id', $previous->id)->update(['is_completed' => true, 'completation_date' => today()->subDays(45)]);
            }
        }

        foreach ($manual->manualPhaseSuphases->filter(fn ($row) => $row->catalogSubphase->manualPhase->slug === 'edition') as $row) {
            if ($row->fresh()->is_completed) {
                $row->update(match ($row->catalogSubphase->activity_key) {
                    \App\Services\EditionActivity::START => ['follow_up' => 'Inicio documentado de prueba'],
                    \App\Services\EditionActivity::REVIEW => ['follow_up' => 'Revisión documentada de prueba', 'due_on' => today()->subDays(45)],
                    default => ['started_on' => today()->subDays(50), 'due_on' => today()->subDays(45), 'responsible_user_id' => auth()->id()],
                });
            }
        }

        return $manual->fresh()->load(ManualWorkflow::RELATIONS);
    }

    private function activity(Manual $manual, array $data = [])
    {
        $row = isset($data['activity_id']) ? $manual->manualPhaseSuphases->firstWhere('catalog_subphases_id', $data['activity_id']) : $manual->manualPhaseSuphases->firstWhere('manual_phases_id', $manual->manual_phases_id);
        if ($row && $manual->manualPhase->slug === 'edition' && ($data['is_completed'] ?? true)) {
            $data += match ($row->catalogSubphase->activity_key) {
                \App\Services\EditionActivity::START => ['follow_up' => 'Inicio de corrección de prueba'],
                \App\Services\EditionActivity::REVIEW => ['follow_up' => 'Revisión final de prueba', 'due_on' => today()->toDateString()],
                default => ['started_on' => today()->toDateString(), 'due_on' => today()->addDay()->toDateString(), 'responsible_user_id' => auth()->id()],
            };
        }

        return $this->patchJson(route('manuals.activity', $manual->id), $data + [
            'action' => 'activity', 'activity_id' => $row?->catalog_subphases_id ?? ($data['activity_id'] ?? 0),
            'is_completed' => true, 'completation_date' => today()->toDateString(), 'version' => $manual->lock_version,
        ]);
    }

    public function test_catalogs_have_five_phases_two_maps_and_variable_templates(): void
    {
        $this->assertDatabaseCount('manual_phases', 5);
        $this->assertDatabaseCount('doctrinal_maps', 2);
        $this->assertDatabaseCount('catalog_subphases', 35);
        $this->assertEquals(['planning', 'research', 'experimentation', 'edition', 'cgft'], ManualPhase::orderBy('position')->pluck('slug')->all());
        $this->assertDatabaseHas('catalog_subphases', ['suphase_name' => 'LEGALIZADO']);
        $research = ManualPhase::where('slug', 'research')->first();
        $this->assertSame(array_column(CatalogSubphasesSeeder::researchDefinitions(), 1), $research->catalogSubphases->pluck('suphase_name')->all());
    }

    public function test_research_limit_is_six_and_experimentation_limit_is_five(): void
    {
        $this->actor();
        $type = ManualType::first();
        $payload = ['classification' => 'generated', 'manual_name' => 'Límites por fase', 'doctrinal_map_id' => $type->doctrinal_map_id, 'manual_types_id' => $type->id, 'research_advances' => 7, 'experimentation_advances' => 5, 'process_started_on' => today()->toDateString()];
        $this->postJson(route('manuals.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('research_advances');
        $payload['research_advances'] = 6;
        $this->post(route('manuals.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $manual = Manual::first()->load(ManualWorkflow::RELATIONS);
        $this->assertSame(6, $manual->research_advances);
        $this->assertSame(5, $manual->experimentation_advances);
        $this->assertCount(35, $manual->manualPhaseSuphases);
        foreach ([1, 6, 8] as $invalid) {
            $payload['experimentation_advances'] = $invalid;
            $this->postJson(route('manuals.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('experimentation_advances');
        }
    }

    public function test_research_data_migration_preserves_ids_dates_and_other_phases_and_caps_test_data(): void
    {
        $this->actor();
        $active = $this->manual('research', 6);
        $later = $this->manual('edition', 6);
        $researchId = $active->manual_phases_id;
        $newKeys = ['research.three-months', 'research.validation-meeting.1', 'research.validation-meeting.2', 'research.validation-meeting.3'];
        $newIds = CatalogSubphase::whereIn('activity_key', $newKeys)->pluck('id');
        \App\Models\ManualPhaseSuphase::whereIn('catalog_subphases_id', $newIds)->delete();
        CatalogSubphase::whereIn('id', $newIds)->delete();
        CatalogSubphase::where('activity_key', 'research.3')->update(['suphase_name' => 'ENTREGA DEL PERFIL DEL PROYECTO Y CRONOGRAMA']);
        $legacy = [];
        foreach ([7, 8] as $number) {
            $legacy[] = CatalogSubphase::create(['manual_phases_id' => $researchId, 'activity_key' => 'research.'.($number + 3), 'suphase_name' => 'AVANCE ANTIGUO '.$number, 'position' => $number + 3, 'advance_number' => $number]);
        }
        foreach ([$active, $later] as $manual) {
            $manual->update(['research_advances' => 8]);
            foreach ($legacy as $activity) {
                $manual->manualPhaseSuphases()->create(['manual_phases_id' => $researchId, 'catalog_subphases_id' => $activity->id, 'is_completed' => true, 'completation_date' => today()->subDays(45)]);
            }
        }
        $keptId = CatalogSubphase::where('activity_key', 'research.4')->first()->id;
        $active->manualPhaseSuphases()->where('catalog_subphases_id', $keptId)->update(['is_completed' => true, 'completation_date' => today()->subDays(10)]);
        $kept = $active->manualPhaseSuphases()->where('catalog_subphases_id', $keptId)->first();
        $otherRows = \App\Models\ManualPhaseSuphase::where('manual_phases_id', '!=', $researchId)->orderBy('id')->get()->toArray();
        $users = User::orderBy('id')->get()->toArray();
        $migration = require database_path('migrations/2026_10_21_000002_correct_research_activities.php');
        $migration->up();
        $this->assertDatabaseCount('manuals', 2);
        $this->assertDatabaseCount('catalog_subphases', 35);
        $this->assertEquals($users, User::orderBy('id')->get()->toArray());
        $this->assertEquals($otherRows, \App\Models\ManualPhaseSuphase::where('manual_phases_id', '!=', $researchId)->orderBy('id')->get()->toArray());
        $this->assertDatabaseHas('manual_phase_suphases', ['id' => $kept->id, 'catalog_subphases_id' => $keptId, 'is_completed' => true, 'completation_date' => $kept->getRawOriginal('completation_date')]);
        $this->assertDatabaseHas('catalog_subphases', ['id' => $keptId, 'suphase_name' => '1. AVANCE DE LA INVESTIGACIÓN']);
        foreach ([$active, $later] as $manual) {
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->assertSame(6, $manual->research_advances);
            $this->assertSame(1, $manual->lock_version);
            $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $researchId);
            $this->assertCount(17, $rows);
            $this->assertFalse($rows->contains(fn ($row) => $row->catalogSubphase->advance_number > 6));
            $added = $rows->filter(fn ($row) => in_array($row->catalogSubphase->activity_key, $newKeys));
            $this->assertCount(4, $added);
            $this->assertTrue($added->every(fn ($row) => $row->is_completed === ($manual->id === $later->id)));
            $event = $manual->events()->where('action', 'research_catalog_updated')->first();
            $this->assertSame(8, $event->before['research_advances']);
            $this->assertSame(6, $event->after['research_advances']);
            $this->assertTrue(collect($event->before['activities'])->contains('catalog_subphases_id', $legacy[1]->id));
        }
        $later = $later->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('edition', $later->manualPhase->slug);
        $this->assertEquals(100, $later->progress($researchId));
        $this->assertTrue($later->manualPhaseSuphases->where('manual_phases_id', $researchId)->every(fn ($row) => $row->completation_date->toDateString() === today()->subDays(45)->toDateString()));
    }

    public function test_alert_modal_is_available_in_auth_main_and_legacy_screens(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('id="artemis-alert"', false);
        $this->get(route('register'))->assertOk()->assertSee('id="artemis-alert"', false);
        $this->actor();
        foreach (['dashboard', 'manuals.index', 'members.index', 'members.newMember', 'militaryUnits.index', 'militaryUnits.newMilitaryUnit'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('id="artemis-alert"', false)->assertSee('js/alerts.js', false);
        }
    }

    public function test_creation_starts_in_planning_with_zero_and_no_committees(): void
    {
        $this->actor(['planificador']);
        $type = ManualType::first();
        $this->post(route('manuals.store'), [
            'classification' => 'generated', 'manual_name' => 'Nuevo manual', 'doctrinal_map_id' => $type->doctrinal_map_id,
            'manual_types_id' => $type->id, 'research_advances' => 2, 'experimentation_advances' => 5,
            'process_started_on' => today()->toDateString(),
        ])->assertRedirect(route('manuals.index', ['phase' => 'planning', 'published' => 0]));
        $manual = Manual::first()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('planning', $manual->manualPhase->slug);
        $this->assertEquals(0, $manual->progress());
        $this->assertCount(0, $manual->manualCommitteeMembers);
        $this->assertCount(31, $manual->manualPhaseSuphases);
    }

    public function test_wrong_map_and_invalid_advance_quantities_are_rejected(): void
    {
        $this->actor();
        $type = ManualType::first();
        $this->postJson(route('manuals.store'), [
            'manual_name' => 'Incorrecto', 'doctrinal_map_id' => 2, 'manual_types_id' => $type->id,
            'research_advances' => 1, 'experimentation_advances' => 9, 'process_started_on' => today()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors(['manual_types_id', 'research_advances', 'experimentation_advances']);
        $this->assertDatabaseCount('manuals', 0);
    }

    public function test_every_functional_role_can_read_reports_but_none_cannot(): void
    {
        foreach (['supervisor', 'planificador', 'investigador', 'experimentador', 'edición', 'cgft'] as $role) {
            $this->actor([$role]);
            $this->get(route('dashboard'))->assertOk();
            $this->get(route('reports.manuals'))->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->get(route('users.index'))->assertForbidden();
        }
        $this->actor(['none']);
        $this->get(route('dashboard'))->assertForbidden();
        $this->get(route('reports.manuals'))->assertForbidden();
        $this->get(route('manuals.index'))->assertForbidden();
    }

    public function test_roles_are_combined_and_supervisor_cannot_write(): void
    {
        $manual = $this->manual('research');
        $this->actor(['planificador', 'investigador']);
        $this->get(route('manuals.index', ['phase' => 'research']))->assertOk()->assertSee('Manual de prueba');
        $this->activity($manual)->assertOk();
        $this->get(route('manuals.index', ['phase' => 'edition']))->assertOk();
        $this->actor(['supervisor']);
        $this->activity($manual->fresh()->load(ManualWorkflow::RELATIONS))->assertForbidden();
        $this->get(route('manuals.editOneManual', $manual->id))->assertForbidden();
    }

    public function test_checkbox_requires_date_and_start_and_is_atomic(): void
    {
        $this->actor();
        $manual = $this->manual();
        $this->activity($manual, ['completation_date' => null])->assertUnprocessable();
        $this->assertEquals(0, $manual->fresh()->progress());
        $manual->phaseStates()->update(['started_on' => null]);
        $this->activity($manual)->assertUnprocessable();
        $this->assertEquals(0, $manual->fresh()->progress());
    }

    public function test_single_cell_updates_only_it_and_rejects_stale_version(): void
    {
        $this->actor();
        $manual = $this->manual();
        $this->activity($manual)->assertOk()->assertJsonPath('progress', 50);
        $this->assertSame(1, $manual->fresh()->manualPhaseSuphases()->where('is_completed', true)->count());
        $this->activity($manual)->assertConflict();
    }

    public function test_completed_planning_advances_and_new_phase_has_no_start(): void
    {
        $this->actor(['planificador']);
        $manual = $this->manual();
        foreach ($manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id) as $row) {
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual, ['activity_id' => $row->catalog_subphases_id])->assertOk();
        }
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('planning', $manual->manualPhase->slug);
        $this->assertEquals(100, $manual->progress());
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'version' => $manual->lock_version])->assertUnprocessable()->assertJsonValidationErrors('confirmed');
        $this->assertSame('planning', $manual->fresh()->manualPhase->slug);
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'version' => $manual->lock_version, 'confirmed' => true])->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('research', $manual->manualPhase->slug);
        $this->assertNull($manual->currentStart());
        $this->assertEquals(0, $manual->progress());
        $this->get(route('manuals.editOneManual', $manual->id))->assertForbidden();
    }

    public function test_edition_advances_to_cgft_without_publishing(): void
    {
        $this->actor(['edición']);
        $manual = $this->manual('edition');
        foreach ($manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id) as $row) {
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual, ['activity_id' => $row->catalog_subphases_id])->assertOk();
        }
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('edition', $manual->manualPhase->slug);
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'version' => $manual->lock_version, 'confirmed' => true])->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('cgft', $manual->manualPhase->slug);
        $this->assertFalse($manual->is_published);
    }

    public function test_cgft_publishes_and_correction_unpublishes_with_audit(): void
    {
        $this->actor(['cgft']);
        $manual = $this->manual('cgft');
        $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id);
        foreach ($rows as $row) {
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual, ['activity_id' => $row->catalog_subphases_id])->assertOk();
        }
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertFalse($manual->is_published);
        $this->assertEquals(100, $manual->progress());
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'version' => $manual->lock_version, 'confirmed' => true])->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertTrue($manual->is_published);
        $this->assertNotNull($manual->published_on);
        $this->activity($manual, ['activity_id' => $rows->last()->catalog_subphases_id, 'is_completed' => false, 'completation_date' => null])->assertOk();
        $this->assertFalse($manual->fresh()->is_published);
        $this->assertNull($manual->fresh()->published_on);
        $this->assertDatabaseHas('manual_events', ['manual_id' => $manual->id, 'action' => 'activity', 'reason' => null]);
    }

    public function test_retreat_is_explicit_and_limited_to_previous_phase(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'start', 'started_on' => today()->subDays(40)->toDateString(), 'version' => 0])->assertOk()->assertJsonPath('phase', 'research');
        $manual = $manual->fresh();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'retreat', 'version' => $manual->lock_version])->assertUnprocessable()->assertJsonValidationErrors('confirmed');
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'retreat', 'version' => $manual->lock_version, 'confirmed' => true])->assertOk()->assertJsonPath('phase', 'planning');
        $manual = $manual->fresh();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'retreat', 'version' => $manual->lock_version, 'confirmed' => true])->assertUnprocessable();
    }

    public function test_unchecking_needs_no_reason_and_clears_date_with_audit(): void
    {
        $user = $this->actor();
        $manual = $this->manual();
        $this->activity($manual)->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->activity($manual, ['is_completed' => false, 'completation_date' => null])->assertOk()->assertJsonPath('progress', 0)->assertJsonPath('phase', 'planning');
        $row = $manual->fresh()->manualPhaseSuphases->first();
        $this->assertFalse($row->is_completed);
        $this->assertNull($row->completation_date);
        $event = $manual->events()->latest('id')->first();
        $this->assertEquals($user->id, $event->user_id);
        $this->assertNull($event->reason);
        $this->assertTrue($event->before['activity']['is_completed']);
        $this->assertFalse($event->after['is_completed']);
    }

    public function test_confirmed_transition_still_requires_completed_activities(): void
    {
        $this->actor();
        $manual = $this->manual();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'version' => $manual->lock_version, 'confirmed' => true])->assertUnprocessable()->assertJsonValidationErrors('phase');
        $this->assertSame('planning', $manual->fresh()->manualPhase->slug);
    }

    public function test_foreign_and_nonapplicable_activities_are_rejected(): void
    {
        $this->actor();
        $manual = $this->manual('research', 2);
        $other = CatalogSubphase::whereHas('manualPhase', fn ($q) => $q->where('slug', 'research'))->where('advance_number', 6)->first();
        $this->activity($manual, ['activity_id' => $other->id])->assertUnprocessable();
        $this->activity($manual, ['activity_id' => CatalogSubphase::whereHas('manualPhase', fn ($q) => $q->where('slug', 'cgft'))->first()->id])->assertUnprocessable();
    }

    public function test_calendar_alerts_exclude_planning_and_stop_on_publication(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $state = $manual->phaseStates->firstWhere('manual_phase_id', $manual->manual_phases_id);
        $state->update(['started_on' => today()->subMonthsNoOverflow(3)]);
        $this->assertFalse($manual->fresh()->load(ManualWorkflow::RELATIONS)->timing()['phase_overdue']);
        $state->update(['started_on' => today()->subMonthsNoOverflow(3)->subDay()]);
        $manual->update(['research_started_on' => today()->subYearNoOverflow()->subDay()]);
        $timing = $manual->fresh()->load(ManualWorkflow::RELATIONS)->timing();
        $this->assertTrue((bool) $timing['phase_overdue']);
        $this->assertTrue((bool) $timing['research_overdue']);
        $planning = $this->manual();
        $planning->phaseStates()->update(['started_on' => today()->subYear()]);
        $this->assertFalse($planning->fresh()->load(ManualWorkflow::RELATIONS)->timing()['phase_overdue']);
        $manual->update(['is_published' => true, 'published_on' => today()]);
        $this->assertFalse($manual->fresh()->load(ManualWorkflow::RELATIONS)->timing()['research_overdue']);
    }

    public function test_admin_can_assign_multiple_roles_and_none_is_removed(): void
    {
        $this->actor();
        $target = User::factory()->create();
        $target->assignRole('none');
        $this->put(route('users.update', $target->id), ['roles' => ['none', 'planificador', 'investigador'], 'is_active' => 1, 'grade' => 17])->assertRedirect();
        $this->assertEqualsCanonicalizing(['planificador', 'investigador'], $target->fresh()->getRoleNames()->all());
    }

    public function test_inactive_accounts_cannot_sign_in(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    private function editPayload(Manual $manual): array
    {
        return [
            'manual_name' => 'Manual editado', 'manual_types_id' => $manual->manual_types_id,
            'doctrinal_map_id' => $manual->manualType->doctrinal_map_id,
            'research_advances' => $manual->research_advances,
            'experimentation_advances' => $manual->experimentation_advances,
            'version' => $manual->lock_version,
        ];
    }

    public function test_edit_adds_only_new_advances_and_preserves_phase_and_history(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $this->activity($manual)->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['research_advances'] = 4;
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('research', $manual->manualPhase->slug);
        $this->assertEquals(15, $manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id)->count());
        $this->assertEquals(6.67, $manual->progress());
        $this->assertEquals(1, $manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id)->where('is_completed', true)->count());
    }

    public function test_cannot_remove_advance_with_history_or_modify_a_finished_phase(): void
    {
        $this->actor();
        $manual = $this->manual('research', 4);
        $row = $manual->manualPhaseSuphases->first(fn ($r) => $r->manual_phases_id === $manual->manual_phases_id && $r->catalogSubphase->advance_number === 4);
        $this->activity($manual, ['activity_id' => $row->catalog_subphases_id])->assertOk();
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['research_advances'] = 2;
        $this->putJson(route('manuals.updateOneManual', $manual->id), $payload)->assertUnprocessable();
        $this->assertSame(4, $manual->fresh()->research_advances);
        $finished = $this->manual('edition', 4);
        $payload = $this->editPayload($finished);
        $payload['research_advances'] = 5;
        $this->putJson(route('manuals.updateOneManual', $finished->id), $payload)->assertUnprocessable();
    }

    public function test_committee_edit_is_differential_and_allows_same_person_in_two_committees(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $member = CommitteeMember::create(['grades_id' => 1, 'full_name' => 'Prueba comité', 'identification' => '1234567890']);
        $payload = $this->editPayload($manual);
        $payload['members'] = [1 => [$member->id], 2 => [$member->id]];
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertEquals(2, $manual->manualCommitteeMembers()->count());
        $retained = $manual->manualCommitteeMembers()->where('committee_type_id', 1)->first()->id;
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['present_members'] = [2];
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('manual_committee_members', ['id' => $retained]);
        $this->assertEquals(1, $manual->manualCommitteeMembers()->count());
    }

    public function test_research_and_validation_units_are_independent_optional_and_nullable(): void
    {
        $this->actor(['investigador']);
        $manual = $this->manual('research');
        $units = MilitaryUnit::orderBy('id')->take(2)->pluck('id');
        $this->get(route('manuals.editOneManual', $manual->id))->assertOk()->assertSee('id="search-units-1"', false)->assertSee('id="search-units-2"', false);
        $payload = $this->editPayload($manual);
        $payload['units'] = [1 => [$units[0]], 2 => [$units[1]]];
        $payload['present_units'] = [1, 2];
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $retained = $manual->manualMilitaryUnits()->where('committee_type_id', 2)->first()->id;
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['units'] = [1 => [$units[1]]];
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('manual_military_units', ['id' => $retained, 'military_units_id' => $units[1]]);
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['units'] = [2 => null];
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('manual_military_units', ['id' => $retained]);
        $this->assertSame(1, $manual->manualMilitaryUnits()->where('committee_type_id', 1)->count());
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $payload = $this->editPayload($manual);
        $payload['units'] = null;
        $this->put(route('manuals.updateOneManual', $manual->id), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, $manual->manualMilitaryUnits()->count());
    }

    public function test_research_unit_validation_rejects_other_phase_and_unknown_units(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $payload = $this->editPayload($manual);
        $payload['units'] = [2 => [999999]];
        $this->putJson(route('manuals.updateOneManual', $manual->id), $payload)->assertUnprocessable()->assertJsonValidationErrors('units.2.0');
        $payload['units'] = [3 => [MilitaryUnit::first()->id]];
        $this->putJson(route('manuals.updateOneManual', $manual->id), $payload)->assertUnprocessable();
        $this->assertSame(0, $manual->manualMilitaryUnits()->count());
    }

    public function test_validation_units_demo_seed_preserves_assignments_and_is_repeatable(): void
    {
        $this->actor();
        $member = CommitteeMember::create(['grades_id' => 1, 'full_name' => 'Comité de prueba', 'identification' => '0012345678']);
        $empty = $this->manual('research');
        $existing = $this->manual('research');
        foreach ([$empty, $existing] as $manual) {
            $manual->manualCommitteeMembers()->create(['committee_members_id' => $member->id, 'committee_type_id' => 2]);
        }
        $unit = MilitaryUnit::first();
        $existing->manualMilitaryUnits()->create(['committee_type_id' => 2, 'military_units_id' => $unit->id]);
        $this->seed(\Database\Seeders\ResearchValidationUnitsSeeder::class);
        $this->seed(\Database\Seeders\ResearchValidationUnitsSeeder::class);
        $this->assertSame(1, $empty->manualMilitaryUnits()->where('committee_type_id', 2)->count());
        $this->assertSame(1, $empty->fresh()->lock_version);
        $this->assertSame(0, $existing->fresh()->lock_version);
        $this->assertSame($unit->id, $existing->manualMilitaryUnits()->first()->military_units_id);
        $this->assertSame(1, $empty->events()->where('action', 'validation_units_seeded')->count());
        $this->assertSame(1, MilitaryUnit::where('unit_acronym', 'PR-VAL-INV')->count());
    }

    public function test_research_cannot_finish_without_its_own_committees(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $rows = $manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id);
        foreach ($rows->slice(0, -1) as $row) {
            $row->update(['is_completed' => true, 'completation_date' => today()]);
        }
        $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
        $this->activity($manual, ['activity_id' => $rows->last()->catalog_subphases_id])->assertUnprocessable()->assertJsonValidationErrors('committees');
        $this->assertFalse($rows->last()->fresh()->is_completed);
    }

    public function test_assignment_search_matches_member_name_surname_identification_and_unit(): void
    {
        $this->actor(['investigador']);
        $manual = $this->manual('research');
        $member = CommitteeMember::create(['grades_id' => 1, 'full_name' => 'Ana Patricia Salazar', 'identification' => '0912345678']);
        $unit = MilitaryUnit::create(['unit_name' => 'Batallón de Apoyo Logístico', 'unit_acronym' => 'BAL9']);
        foreach (['Ana', 'salazar', '091234', 'Salazar Ana'] as $query) {
            $this->getJson(route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'members', 'q' => $query]))
                ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $member->id);
        }
        foreach (['BAL9', 'Apoyo', 'Logístico Batallón'] as $query) {
            $this->getJson(route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'units', 'q' => $query]))
                ->assertOk()->assertJsonCount(1, 'items')->assertJsonPath('items.0.id', $unit->id);
        }
        $this->getJson(route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'members', 'q' => 'Sin coincidencias']))->assertOk()->assertJsonCount(0, 'items');
    }

    public function test_matrix_summary_counts_all_filtered_pages_and_configured_activities(): void
    {
        $this->actor();
        $ready = $this->manual('research');
        $ready->update(['manual_name' => 'Especial listo']);
        $ready->manualPhaseSuphases()->where('manual_phases_id', $ready->manual_phases_id)->update(['is_completed' => true, 'completation_date' => today()]);
        app(ManualWorkflow::class)->synchronizeSubstates($ready->fresh()->load(ManualWorkflow::RELATIONS));
        $developing = $this->manual('research', 6);
        app(ManualWorkflow::class)->synchronizeSubstates($developing->fresh()->load(ManualWorkflow::RELATIONS));
        foreach (range(1, 25) as $number) {
            $copy = $ready->replicate();
            $copy->manual_name = 'Sin inicio '.$number;
            $copy->save();
        }
        $inactive = $ready->replicate();
        $inactive->is_active = false;
        $inactive->save();
        $this->manual('edition');
        $this->get(route('manuals.index', ['phase' => 'research', 'page' => 2]))->assertOk()->assertViewHas('summary', fn ($summary) => (int) $summary->total === 27 && (int) $summary->development === 1 && (int) $summary->validation === 1 && (int) $summary->ready === 1)
            ->assertDontSee('actividades de referencia');
        $this->get(route('manuals.index', ['phase' => 'research', 'q' => 'Especial']))->assertOk()->assertViewHas('summary', fn ($summary) => (int) $summary->total === 1 && (int) $summary->validation === 1 && (int) $summary->ready === 1);
        $this->get(route('manuals.index', ['phase' => 'research', 'q' => 'No existe']))->assertOk()->assertViewHas('summary', fn ($summary) => (int) $summary->total === 0 && (int) $summary->ready === 0);
    }

    public function test_matrix_summary_omits_substates_for_other_phases_and_published_are_not_ready(): void
    {
        $this->actor();
        $manual = $this->manual('cgft');
        $manual->manualPhaseSuphases()->where('manual_phases_id', $manual->manual_phases_id)->update(['is_completed' => true, 'completation_date' => today()]);
        $manual->update(['is_published' => true, 'published_on' => today()]);
        $this->get(route('manuals.index', ['phase' => 'cgft', 'published' => 1]))->assertOk()
            ->assertViewHas('summary', fn ($summary) => (int) $summary->total === 1 && (int) $summary->ready === 0)
            ->assertDontSee('data-matrix-count="development"', false)->assertDontSee('data-matrix-count="validation"', false);
        $this->get(route('manuals.index', ['phase' => 'planning']))->assertOk()
            ->assertSee('Total de manuales')->assertSee('Listos para Investigación')->assertDontSee('data-matrix-count="development"', false);
    }

    public function test_research_and_experimentation_substates_follow_their_boundary_activity(): void
    {
        $this->actor();
        foreach (['research', 'experimentation'] as $slug) {
            $manual = $this->manual($slug);
            $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'start', 'started_on' => today()->subDays(40)->toDateString(), 'version' => 0])
                ->assertOk()->assertJsonPath('substate.code', 'development');
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $boundary = $manual->manualPhaseSuphases->first(fn ($row) => $row->catalogSubphase->activity_key === $slug.'.12');
            $this->activity($manual, ['activity_id' => $boundary->catalog_subphases_id, 'completation_date' => today()->subDays(2)->toDateString()])
                ->assertOk()->assertJsonPath('substate.code', 'validation')->assertJsonPath('substate.started_on', today()->subDays(2)->toDateString())->assertJsonPath('phase', $slug);
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual)->assertOk()->assertJsonPath('substate.code', 'validation');
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual, ['activity_id' => $boundary->catalog_subphases_id, 'completation_date' => today()->subDay()->toDateString()])
                ->assertOk()->assertJsonPath('substate.started_on', today()->subDay()->toDateString());
            $manual = $manual->fresh()->load(ManualWorkflow::RELATIONS);
            $this->activity($manual, ['activity_id' => $boundary->catalog_subphases_id, 'is_completed' => false, 'completation_date' => null])
                ->assertOk()->assertJsonPath('substate.code', 'development');
            $state = $manual->fresh()->phaseStates->firstWhere('manual_phase_id', $manual->manual_phases_id);
            $this->assertSame('development', $state->substate);
            $this->assertNull($state->validation_started_on);
            $event = $manual->events()->first();
            $this->assertSame('validation', $event->before['substate']['code']);
            $this->assertSame('development', $event->after['substate']['code']);
        }
    }

    public function test_substate_is_absent_before_start_and_in_other_phases(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $manual->phaseStates()->where('manual_phase_id', $manual->manual_phases_id)->update(['started_on' => null]);
        app(ManualWorkflow::class)->synchronizeSubstates($manual->fresh()->load(ManualWorkflow::RELATIONS));
        $this->assertNull($manual->fresh()->currentSubstate());
        $this->assertNull($this->manual('planning')->currentSubstate());
        $this->assertNull($this->manual('edition')->currentSubstate());
    }

    public function test_substate_migration_backfills_existing_and_historical_phase_data(): void
    {
        $this->actor();
        $research = $this->manual('research');
        $later = $this->manual('edition');
        $unstarted = $this->manual('experimentation');
        $unstarted->phaseStates()->where('manual_phase_id', $unstarted->manual_phases_id)->update(['started_on' => null]);
        $migration = require database_path('migrations/2026_10_21_000001_add_manual_phase_substates.php');
        $migration->down();
        $migration->up();
        $this->assertSame('development', $research->fresh()->currentSubstate()['code']);
        $this->assertNull($unstarted->fresh()->currentSubstate());
        foreach (['research', 'experimentation'] as $slug) {
            $phase = ManualPhase::where('slug', $slug)->first();
            $state = $later->fresh()->phaseStates->firstWhere('manual_phase_id', $phase->id);
            $this->assertSame('validation', $state->substate);
            $this->assertSame(today()->subDays(45)->toDateString(), $state->validation_started_on->toDateString());
        }
        $this->assertNull($later->fresh()->currentSubstate());
    }

    public function test_assignment_search_pages_results_without_loading_the_entire_catalog(): void
    {
        $this->actor();
        $manual = $this->manual('experimentation');
        foreach (range(1, 28) as $number) {
            CommitteeMember::create(['grades_id' => 1, 'full_name' => sprintf('Integrante %02d', $number), 'identification' => (string) (9000000000 + $number)]);
        }
        $url = route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'members']);
        $first = $this->getJson($url)->assertOk()->assertJsonCount(25, 'items')->assertJsonPath('more', true)->json('items');
        $second = $this->getJson($url.'&page=2')->assertOk()->assertJsonCount(3, 'items')->assertJsonPath('more', false)->json('items');
        $this->assertCount(28, array_unique(array_column(array_merge($first, $second), 'id')));
    }

    public function test_assignment_search_respects_phase_permissions_and_validates_inputs(): void
    {
        $manual = $this->manual('research');
        $url = route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'members']);
        $this->getJson($url)->assertUnauthorized();
        foreach (['supervisor', 'planificador', 'experimentador', 'none'] as $role) {
            $this->actor([$role]);
            $this->getJson($url)->assertForbidden();
        }
        $this->actor(['investigador']);
        $this->getJson($url)->assertOk();
        $this->getJson($url.'&page=0')->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->getJson($url.'&q='.str_repeat('a', 101))->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson(route('manuals.assignmentOptions', ['id' => $manual->id, 'kind' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('kind');
        $this->actor();
        $planning = $this->manual();
        $this->getJson(route('manuals.assignmentOptions', ['id' => $planning->id, 'kind' => 'members']))->assertForbidden();
    }

    public function test_manual_form_loads_assigned_members_only_and_retains_old_selections(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $member = CommitteeMember::create(['grades_id' => 1, 'full_name' => 'Integrante asignado', 'identification' => '1234567890']);
        $other = CommitteeMember::create(['grades_id' => 1, 'full_name' => 'Integrante sin asignar', 'identification' => '1234567891']);
        $manual->manualCommitteeMembers()->create(['committee_members_id' => $member->id, 'committee_type_id' => 1]);
        $this->get(route('manuals.editOneManual', $manual->id))->assertOk()->assertSee('Integrante asignado')->assertDontSee('Integrante sin asignar')->assertSee('Buscar por cédula, apellido o nombre');
        $this->withSession(['_old_input' => ['members' => [1 => [$other->id]]]])->get(route('manuals.editOneManual', $manual->id))
            ->assertOk()->assertSee('Integrante sin asignar')->assertDontSee('Integrante asignado');
    }
}
