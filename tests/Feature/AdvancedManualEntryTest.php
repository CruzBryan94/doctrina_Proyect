<?php

namespace Tests\Feature;

use App\Models\{Manual, ManualPhase, ManualType, User, CommitteeMember};
use App\Services\ManualWorkflow;
use Database\Seeders\{Grade_Seeder, CommitteeTypeSeeder, ManualPhasesSeeder, CatalogSubphasesSeeder, ManualTypesSeeder, RoleSeeder};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedManualEntryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed([Grade_Seeder::class, CommitteeTypeSeeder::class, ManualPhasesSeeder::class, CatalogSubphasesSeeder::class, ManualTypesSeeder::class, RoleSeeder::class]);
    }

    private function actor(string|array $roles): User
    {
        $user = User::factory()->create();
        $user->syncRoles((array) $roles);
        $this->actingAs($user);
        return $user;
    }

    private function payload(string $phase = 'experimentation'): array
    {
        $type = ManualType::first();
        return ['manual_name' => 'Alta aislada de prueba', 'manual_types_id' => $type->id, 'doctrinal_map_id' => $type->doctrinal_map_id,
            'classification' => 'generated', 'entry_phase' => $phase, 'research_advances' => 2, 'experimentation_advances' => 2];
    }

    public function test_advanced_entry_has_no_invented_previous_history_or_start_and_requires_current_phase_to_advance(): void
    {
        $this->actor('experimentador');
        $this->post(route('manuals.store'), $this->payload())->assertRedirect()->assertSessionHasNoErrors();
        $manual = Manual::first()->load(ManualWorkflow::RELATIONS);
        $this->assertNull($manual->process_started_on);
        $this->assertNull($manual->currentStart());
        $this->assertNull($manual->timing()['total_days']);
        $prior = ManualPhase::where('slug', 'research')->first();
        $this->assertFalse($manual->phaseStates->contains('manual_phase_id', $prior->id));
        $this->assertFalse($manual->manualPhaseSuphases->contains('manual_phases_id', $prior->id));
        $this->assertEquals(0, $manual->progress());
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'confirmed' => true, 'version' => 0])->assertUnprocessable();
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'start', 'started_on' => today()->toDateString(), 'version' => 0])->assertOk();
        $manual->manualPhaseSuphases()->where('manual_phases_id', $manual->manual_phases_id)->update(['is_completed' => true, 'completation_date' => today()]);
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'confirmed' => true, 'version' => 1])->assertUnprocessable()->assertJsonValidationErrors('committees');
        $person = CommitteeMember::create(['full_name' => 'Persona de prueba', 'identification' => '00007777', 'grades_id' => 1]);
        foreach ([3, 4] as $type) {
            $manual->manualCommitteeMembers()->create(['committee_members_id' => $person->id, 'committee_type_id' => $type]);
        }
        $this->patchJson(route('manuals.activity', $manual->id), ['action' => 'advance', 'confirmed' => true, 'version' => 1])->assertOk()->assertJsonPath('phase', 'edition');
        $this->assertDatabaseCount('manual_events', 3);
    }

    public function test_future_published_entry_is_immediate_and_preserves_previous_publication_as_separate_record(): void
    {
        $this->actor('publicados');
        $payload = $this->payload('published') + ['published_on' => today()->addYear()->toDateString()];
        $this->post(route('manuals.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $previous = Manual::first();
        $this->assertTrue($previous->is_published);
        $this->assertDatabaseCount('manual_phase_suphases', 0);
        $this->assertDatabaseCount('manual_phase_states', 0);
        $payload['classification'] = 'update';
        $this->post(route('manuals.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('manuals', 2);
        $this->assertSame('generated', $previous->fresh()->classification);
        $this->assertTrue($previous->fresh()->is_published);
        $this->get(route('manuals.editOneManual', $previous->id))->assertOk()->assertSee('Fecha de publicación declarada');
        unset($payload['entry_phase']);
        $this->put(route('manuals.updateOneManual', $previous->id), $payload + ['version' => 0])->assertRedirect()->assertSessionHasNoErrors();
        $this->putJson(route('manuals.updateOneManual', $previous->id), $payload + ['version' => 0])->assertConflict();
    }

    public function test_roles_consult_all_phases_but_writes_and_published_metadata_are_separate(): void
    {
        $this->actor('admin');
        $this->post(route('manuals.store'), $this->payload('published') + ['published_on' => today()->toDateString()])->assertRedirect();
        $manual = Manual::first();
        foreach (['supervisor', 'planificador', 'investigador', 'experimentador', 'edición', 'cgft', 'publicados'] as $role) {
            $this->actor($role);
            $this->get(route('manuals.index', ['phase' => 'edition']))->assertOk();
            $this->get(route('manuals.history', $manual->id))->assertOk();
        }
        $this->actor('cgft');
        $this->get(route('manuals.editOneManual', $manual->id))->assertForbidden();
        $payload = $this->payload('published');
        unset($payload['entry_phase']);
        $this->putJson(route('manuals.updateOneManual', $manual->id), $payload + ['version' => 0])->assertForbidden();
        foreach (['supervisor', 'none', 'publicados'] as $role) {
            $this->actor($role);
            $this->postJson(route('manuals.store'), $this->payload())->assertForbidden();
        }
        $user = $this->actor('investigador');
        $user->update(['is_active' => false]);
        $this->get(route('manuals.index'))->assertForbidden();
    }

    public function test_classification_and_publication_date_are_required_and_map_type_is_validated(): void
    {
        $this->actor('admin');
        $payload = $this->payload('published');
        unset($payload['classification']);
        $this->postJson(route('manuals.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors(['classification', 'published_on']);
        $payload = $this->payload();
        $payload['doctrinal_map_id'] = 9999;
        $this->postJson(route('manuals.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors(['doctrinal_map_id', 'manual_types_id']);
        $this->assertDatabaseCount('manuals', 0);
    }

    public function test_entry_forms_and_remote_assignments_respect_phase_permissions(): void
    {
        $this->actor('investigador');
        $this->get(route('manuals.newManual', ['entry_phase' => 'research']))->assertOk()->assertSee('Comités de Investigación');
        $this->getJson(route('manuals.newAssignmentOptions', ['entry_phase' => 'research', 'kind' => 'members']))->assertOk();
        $this->getJson(route('manuals.newAssignmentOptions', ['entry_phase' => 'experimentation', 'kind' => 'members']))->assertForbidden();
        $this->get(route('manuals.newManual', ['entry_phase' => 'planning']))->assertForbidden();
    }
}
