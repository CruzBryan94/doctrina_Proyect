<?php

namespace Tests\Feature;

use App\Models\CatalogSubphase;
use App\Models\CommitteeMember;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
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
            'manual_phases_id' => $phase->id, 'research_advances' => $advances, 'experimentation_advances' => $advances,
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

        return $manual->fresh()->load(ManualWorkflow::RELATIONS);
    }

    private function activity(Manual $manual, array $data = [])
    {
        $row = $manual->manualPhaseSuphases->firstWhere('manual_phases_id', $manual->manual_phases_id);

        return $this->patchJson(route('manuals.activity', $manual->id), $data + [
            'action' => 'activity', 'activity_id' => $row->catalog_subphases_id,
            'is_completed' => true, 'completation_date' => today()->toDateString(), 'version' => $manual->lock_version,
        ]);
    }

    public function test_catalogs_have_five_phases_two_maps_and_variable_templates(): void
    {
        $this->assertDatabaseCount('manual_phases', 5);
        $this->assertDatabaseCount('doctrinal_maps', 2);
        $this->assertDatabaseCount('catalog_subphases', 38);
        $this->assertEquals(['planning', 'research', 'experimentation', 'edition', 'cgft'], ManualPhase::orderBy('position')->pluck('slug')->all());
        $this->assertDatabaseHas('catalog_subphases', ['suphase_name' => 'LEGALIZADO']);
    }

    public function test_creation_starts_in_planning_with_zero_and_no_committees(): void
    {
        $this->actor(['planificador']);
        $type = ManualType::first();
        $this->post(route('manuals.store'), [
            'manual_name' => 'Nuevo manual', 'doctrinal_map_id' => $type->doctrinal_map_id,
            'manual_types_id' => $type->id, 'research_advances' => 2, 'experimentation_advances' => 8,
            'process_started_on' => today()->toDateString(),
        ])->assertRedirect(route('manuals.index', ['phase' => 'planning']));
        $manual = Manual::first()->load(ManualWorkflow::RELATIONS);
        $this->assertSame('planning', $manual->manualPhase->slug);
        $this->assertEquals(0, $manual->progress());
        $this->assertCount(0, $manual->manualCommitteeMembers);
        $this->assertCount(32, $manual->manualPhaseSuphases);
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
        $this->get(route('manuals.index', ['phase' => 'edition']))->assertForbidden();
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
        $this->assertTrue($manual->is_published);
        $this->assertNotNull($manual->published_on);
        $this->activity($manual, ['activity_id' => $rows->last()->catalog_subphases_id, 'is_completed' => false, 'completation_date' => null, 'reason' => 'Corregir legalización'])->assertOk();
        $this->assertFalse($manual->fresh()->is_published);
        $this->assertNull($manual->fresh()->published_on);
        $this->assertDatabaseHas('manual_events', ['manual_id' => $manual->id, 'reason' => 'Corregir legalización']);
    }

    public function test_retreat_is_explicit_and_limited_to_previous_phase(): void
    {
        $this->actor();
        $manual = $this->manual('research');
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'start', 'started_on' => today()->subDays(40)->toDateString(), 'version' => 0])->assertOk()->assertJsonPath('phase', 'research');
        $manual = $manual->fresh();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'retreat', 'version' => $manual->lock_version, 'reason' => 'Revisar factibilidad'])->assertOk()->assertJsonPath('phase', 'planning');
        $manual = $manual->fresh();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'retreat', 'version' => $manual->lock_version, 'reason' => 'Otro retroceso'])->assertUnprocessable();
    }

    public function test_foreign_and_nonapplicable_activities_are_rejected(): void
    {
        $this->actor();
        $manual = $this->manual('research', 2);
        $other = CatalogSubphase::whereHas('manualPhase', fn ($q) => $q->where('slug', 'research'))->where('advance_number', 8)->first();
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
        $this->assertEquals(10, $manual->manualPhaseSuphases->where('manual_phases_id', $manual->manual_phases_id)->count());
        $this->assertEquals(10, $manual->progress());
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
}
