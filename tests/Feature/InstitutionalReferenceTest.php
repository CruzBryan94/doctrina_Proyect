<?php

namespace Tests\Feature;

use App\Models\DoctrinalMap;
use App\Models\InstitutionalReference;
use App\Models\InstitutionalReferenceAudit;
use App\Models\Manual;
use App\Models\ManualPhase;
use App\Models\ManualType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstitutionalReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([\Database\Seeders\ManualPhasesSeeder::class, \Database\Seeders\ManualTypesSeeder::class, \Database\Seeders\RoleSeeder::class]);
    }

    private function actor(array $roles, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->syncRoles($roles);
        return $user;
    }

    private function manual(string $phase, array $attributes = []): Manual
    {
        $type = ManualType::firstOrFail();
        return Manual::create($attributes + [
            'manual_name' => 'Referencia test', 'manual_types_id' => $type->id,
            'manual_phases_id' => ManualPhase::where('slug', $phase)->value('id'),
            'classification' => 'generated', 'is_active' => true, 'is_published' => false,
        ]);
    }

    public function test_consultation_aggregates_full_active_scope_including_future_publications_and_updates_once(): void
    {
        $mapId = ManualType::firstOrFail()->doctrinal_map_id;
        $this->manual('planning');
        $this->manual('research', ['classification' => 'update']);
        $this->manual('cgft', ['is_published' => true, 'published_on' => '2030-01-01', 'classification' => 'update']);
        $this->manual('edition', ['is_active' => false]);
        $this->actingAs($this->actor(['supervisor']))
            ->get(route('references.index'))
            ->assertOk()->assertSee('Sin configurar')->assertSee('Publicado')->assertSee('Ver registros de origen');
        $this->assertSame(3, app(\App\Services\InstitutionalReferenceService::class)->countsByMap()[$mapId]);
    }

    public function test_admin_sets_clears_and_audits_reference_and_stale_version_conflicts(): void
    {
        $admin = $this->actor(['admin']);
        $map = DoctrinalMap::firstOrFail();
        $this->actingAs($admin)->put(route('references.update', $map->id), ['expected_total' => 0, 'lock_version' => 0])->assertRedirect();
        $this->assertDatabaseHas('institutional_references', ['doctrinal_map_id' => $map->id, 'expected_total' => 0, 'lock_version' => 1]);
        $this->assertDatabaseHas('institutional_reference_audits', ['doctrinal_map_id' => $map->id, 'user_id' => $admin->id, 'before_total' => null, 'after_total' => 0]);
        $this->put(route('references.update', $map->id), ['expected_total' => 9, 'lock_version' => 0])->assertStatus(409);
        $this->put(route('references.update', $map->id), ['expected_total' => null, 'lock_version' => 1])->assertRedirect();
        $this->assertDatabaseHas('institutional_reference_audits', ['before_total' => 0, 'after_total' => null]);
        $this->assertSame(2, InstitutionalReference::first()->lock_version);
        $this->assertSame(2, InstitutionalReferenceAudit::count());
    }

    public function test_write_requires_active_admin_role_not_merely_manage_permission_and_validates_bounds(): void
    {
        $map = DoctrinalMap::firstOrFail();
        $manager = $this->actor(['none']);
        $manager->givePermissionTo('users.manage');
        $this->actingAs($manager)->put(route('references.update', $map->id), ['expected_total' => 4, 'lock_version' => 0])->assertForbidden();
        $this->actingAs($this->actor(['admin'], false))->put(route('references.update', $map->id), ['expected_total' => 4, 'lock_version' => 0])->assertForbidden();
        $this->actingAs($this->actor(['admin']))->put(route('references.update', $map->id), ['expected_total' => 1000001, 'lock_version' => 0])->assertSessionHasErrors('expected_total');
        $this->assertDatabaseCount('institutional_reference_audits', 0);
    }

    public function test_consultation_blocks_none_and_direct_write_route_blocks_without_role(): void
    {
        $map = DoctrinalMap::firstOrFail();
        $this->actingAs($this->actor(['none']))->get(route('references.index'))->assertForbidden();
        $this->put(route('references.update', $map->id), ['expected_total' => 1, 'lock_version' => 0])->assertForbidden();
    }
}
