<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_account_can_complete_required_password_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $oldHash = $user->password;
        $rememberToken = $user->remember_token;
        $this->actingAs($user)->get(route('password.change.show'))->assertOk();
        $this->put(route('password.change.update'), [
            'password' => 'new-private-password', 'password_confirmation' => 'new-private-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('welcome'));
        $user->refresh();
        $this->assertNotSame($oldHash, $user->password);
        $this->assertTrue(Hash::check('new-private-password', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertSame($rememberToken, $user->remember_token);
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('account_access_audits', [
            'actor_user_id' => $user->id, 'target_user_id' => $user->id,
            'action' => 'password_change_completed',
        ]);
    }

    public function test_mismatched_confirmation_preserves_password_and_pending_flag(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $oldHash = $user->password;
        $this->actingAs($user)->from(route('password.change.show'))
            ->put(route('password.change.update'), [
                'password' => 'new-private-password', 'password_confirmation' => 'different-password',
            ])->assertSessionHasErrors('password')->assertRedirect(route('password.change.show'));
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseCount('account_access_audits', 0);
    }

    public function test_account_without_reset_authorization_cannot_change_password(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $oldHash = $user->password;
        $this->actingAs($user)->get(route('password.change.show'))->assertRedirect(route('welcome'));
        $this->put(route('password.change.update'), [
            'password' => 'new-private-password', 'password_confirmation' => 'new-private-password',
        ])->assertForbidden();
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertDatabaseCount('account_access_audits', 0);
    }

    public function test_guest_cannot_access_required_password_change(): void
    {
        $this->get(route('password.change.show'))->assertRedirect(route('login'));
        $this->put(route('password.change.update'), [
            'password' => 'new-private-password', 'password_confirmation' => 'new-private-password',
        ])->assertRedirect(route('login'));
        $this->assertDatabaseCount('account_access_audits', 0);
    }

    public function test_inactive_account_cannot_complete_required_password_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true, 'is_active' => false]);
        $oldHash = $user->password;
        $this->actingAs($user)->get(route('password.change.show'))->assertForbidden();
        $this->put(route('password.change.update'), [
            'password' => 'new-private-password', 'password_confirmation' => 'new-private-password',
        ])->assertForbidden();
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseCount('account_access_audits', 0);
    }

    public function test_legacy_password_update_endpoint_cannot_bypass_required_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $oldHash = $user->password;
        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'new-private-password', 'password_confirmation' => 'new-private-password',
        ])->assertNotFound();
        $this->assertSame($oldHash, $user->fresh()->password);
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseCount('account_access_audits', 0);
    }
}
