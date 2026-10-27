<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_self_service_profile_is_not_exposed(): void
    {
        $user = User::factory()->create()->refresh();
        $this->actingAs($user)->get('/profile')->assertNotFound();
        foreach (['profile.edit', 'profile.update', 'profile.destroy'] as $name) {
            $this->assertFalse(Route::has($name));
        }
    }

    public function test_legacy_profile_patch_cannot_change_identity_or_email(): void
    {
        $user = User::factory()->create()->refresh();
        $original = $user->getRawOriginal();
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Changed name', 'email' => 'changed@example.test',
        ])->assertNotFound();
        $this->assertSame($original, $user->fresh()->getRawOriginal());
        $this->assertAuthenticatedAs($user);
    }

    public function test_legacy_profile_patch_cannot_clear_existing_email_verification(): void
    {
        $user = User::factory()->create()->refresh();
        $verifiedAt = $user->getRawOriginal('email_verified_at');
        $this->actingAs($user)->patch('/profile', [
            'name' => 'Changed name', 'email' => $user->email,
        ])->assertNotFound();
        $this->assertSame($verifiedAt, $user->fresh()->getRawOriginal('email_verified_at'));
    }

    public function test_correct_password_does_not_allow_self_service_account_deletion(): void
    {
        $user = User::factory()->create()->refresh();
        $original = $user->getRawOriginal();
        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertNotFound();
        $this->assertSame($original, $user->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_does_not_allow_self_service_account_deletion(): void
    {
        $user = User::factory()->create()->refresh();
        $original = $user->getRawOriginal();
        $this->actingAs($user)->delete('/profile', ['password' => 'wrong-password'])->assertNotFound();
        $this->assertSame($original, $user->fresh()->getRawOriginal());
        $this->assertDatabaseCount('users', 1);
        $this->assertAuthenticatedAs($user);
    }
}
