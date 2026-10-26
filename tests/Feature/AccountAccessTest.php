<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Permission::findOrCreate('users.manage', 'web');
        Role::findOrCreate('admin', 'web')->givePermissionTo('users.manage');
        $user = User::factory()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_admin_reset_replaces_old_password_and_requires_password_update_before_direct_urls(): void
    {
        $admin = $this->admin();
        $target = User::factory()->create(['password' => Hash::make('old-password'), 'remember_token' => 'keep-token']);
        $target->assignRole(Role::findOrCreate('none', 'web'));

        $this->actingAs($admin)->post(route('users.resetPassword', $target->id))->assertRedirect(route('users.edit', $target->id));
        $target->refresh();
        $this->assertTrue($target->must_change_password);
        $this->assertSame('keep-token', $target->remember_token);
        $this->assertTrue(Hash::check('132456', $target->password));
        $this->assertFalse(Hash::check('old-password', $target->password));
        $this->assertTrue($target->hasRole('none'));

        auth()->logout();
        $this->withSession(['url.intended' => route('dashboard')])->post('/login', ['email' => $target->email, 'password' => '132456'])->assertRedirect(route('password.change.show'));
        $this->get(route('dashboard'))->assertRedirect(route('password.change.show'));
        $this->get(route('password.change.show'))->assertOk();
        $this->put(route('password.change.update'), ['password' => '132456', 'password_confirmation' => '132456'])->assertSessionHasErrors('password');
        $this->put(route('password.change.update'), ['password' => 'new-private-password', 'password_confirmation' => 'new-private-password'])->assertRedirect(route('welcome'));

        $target->refresh();
        $this->assertFalse($target->must_change_password);
        $this->assertTrue(Hash::check('new-private-password', $target->password));
        $this->assertSame('keep-token', $target->remember_token);
        $this->assertSame(2, \DB::table('account_access_audits')->where('target_user_id', $target->id)->count());

        $this->post('/logout')->assertRedirect('/');
        $this->post('/login', ['email' => $target->email, 'password' => '132456'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $target->email, 'password' => 'new-private-password'])->assertRedirect(route('welcome'));
    }

    public function test_inactive_admin_role_and_direct_permission_only_are_insufficient(): void
    {
        Permission::findOrCreate('users.manage', 'web');
        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('none', 'web');
        $inactive = User::factory()->create(['is_active' => false]);
        $inactive->assignRole('admin');
        $target = User::factory()->create();
        $this->actingAs($inactive)->post(route('users.resetPassword', $target->id))->assertForbidden();

        $direct = User::factory()->create();
        $direct->givePermissionTo('users.manage');
        $this->actingAs($direct);
        User::factory()->create(['email' => 'collision@example.test']);
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('users.create'))->assertForbidden();
        $this->get(route('users.edit', $target->id))->assertForbidden();
        $this->post(route('users.resetPassword', $target->id))->assertForbidden();
        $this->post(route('users.store'), ['name' => 'Denied', 'email' => 'collision@example.test'])->assertForbidden();
        $this->put(route('users.update', $target->id), ['email' => 'collision@example.test'])->assertForbidden();
        $this->assertFalse($target->refresh()->must_change_password);
    }

    public function test_password_reset_flag_cannot_be_mass_assigned(): void
    {
        $user = User::create(['name' => 'Mass assignment', 'email' => 'mass@example.test', 'password' => Hash::make('password'), 'must_change_password' => true]);
        $this->assertFalse((bool) $user->must_change_password);
    }

    public function test_last_active_admin_cannot_be_demoted_or_deactivated(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('none', 'web');
        $response = $this->actingAs($admin)->put(route('users.update', $admin->id), [
            'roles' => ['none'], 'is_active' => 0,
        ]);
        $response->assertStatus(422);
        $this->assertTrue($admin->refresh()->is_active);
        $this->assertTrue($admin->hasRole('admin'));
    }

    public function test_administrative_creation_normalizes_email_and_rejects_duplicates(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('none', 'web');
        User::factory()->create(['email' => 'existing@example.test']);
        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate', 'email' => ' EXISTING@example.test ', 'is_active' => 1,
            'roles' => [], 'password' => 'strong-password', 'password_confirmation' => 'strong-password',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseMissing('users', ['name' => 'Duplicate']);
    }

    public function test_administrative_creation_supports_none_and_combined_roles_without_creating_a_person(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('none', 'web');
        Role::findOrCreate('planificador', 'web');
        Role::findOrCreate('investigador', 'web');
        Role::findOrCreate('supervisor', 'web');
        DB::table('grades')->insert(['id' => 700, 'grade_name' => 'Grado de prueba', 'code' => 700, 'created_at' => now(), 'updated_at' => now()]);
        $committeeMembers = DB::table('committee_members')->count();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Cuenta sin roles', 'email' => '  SIN.ROLES@example.test ', 'grade' => 700,
            'is_active' => 1, 'roles' => [], 'password' => 'strong-password', 'password_confirmation' => 'strong-password',
        ])->assertRedirect();
        $noneUser = User::where('email', 'sin.roles@example.test')->firstOrFail();
        $this->assertSame(700, (int) $noneUser->grade);
        $this->assertTrue($noneUser->hasRole('none'));
        $this->assertTrue(Hash::check('strong-password', $noneUser->password));
        $noneAudit = DB::table('account_access_audits')->where('target_user_id', $noneUser->id)->first();
        $this->assertStringNotContainsString('strong-password', json_encode($noneAudit));
        $this->assertStringNotContainsString('password', json_encode($noneAudit->after_data));

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'None más supervisor', 'email' => 'none-supervisor@example.test', 'grade' => 700,
            'is_active' => 1, 'roles' => ['none', 'supervisor'],
            'password' => 'another-strong-password', 'password_confirmation' => 'another-strong-password',
        ])->assertRedirect();
        $noneSupervisor = User::where('email', 'none-supervisor@example.test')->firstOrFail();
        $this->assertTrue($noneSupervisor->hasRole('supervisor'));
        $this->assertFalse($noneSupervisor->hasRole('none'));

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'None explícito', 'email' => 'none-explicito@example.test', 'grade' => 700,
            'is_active' => 1, 'roles' => ['none'],
            'password' => 'another-strong-password', 'password_confirmation' => 'another-strong-password',
        ])->assertRedirect();
        $explicitNone = User::where('email', 'none-explicito@example.test')->firstOrFail();
        $this->assertTrue($explicitNone->hasRole('none'));

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Cuenta combinada', 'email' => 'combinada@example.test', 'grade' => 700,
            'is_active' => 1, 'roles' => ['planificador', 'investigador'],
            'password' => 'another-strong-password', 'password_confirmation' => 'another-strong-password',
        ])->assertRedirect();
        $combined = User::where('email', 'combinada@example.test')->firstOrFail();
        $this->assertTrue($combined->hasAllRoles(['planificador', 'investigador']));
        $this->assertFalse($combined->hasRole('none'));
        $this->assertDatabaseCount('committee_members', $committeeMembers);
    }

    public function test_empty_roles_field_means_none_and_audits_role_change(): void
    {
        $admin = $this->admin();
        Role::findOrCreate('none', 'web');
        $target = User::factory()->create();
        $target->assignRole(Role::findOrCreate('planificador', 'web'));

        $this->actingAs($admin)->put(route('users.update', $target->id), [
            'roles' => '', 'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertTrue($target->hasRole('none'));
        $audit = DB::table('account_access_audits')->where('target_user_id', $target->id)->first();
        $this->assertSame(['planificador'], json_decode($audit->before_data, true)['roles']);
        $this->assertSame(['none'], json_decode($audit->after_data, true)['roles']);
    }
}
