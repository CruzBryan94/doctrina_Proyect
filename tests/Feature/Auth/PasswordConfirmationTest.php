<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_confirmation_screen_is_not_exposed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/confirm-password')->assertNotFound();
        $this->assertFalse(Route::has('password.confirm'));
    }

    public function test_correct_password_does_not_enable_legacy_confirmation(): void
    {
        $user = User::factory()->create();
        $passwordHash = $user->password;
        $this->actingAs($user)->post('/confirm-password', ['password' => 'password'])
            ->assertNotFound()->assertSessionMissing('auth.password_confirmed_at');
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_does_not_enable_legacy_confirmation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/confirm-password', ['password' => 'wrong-password'])
            ->assertNotFound()->assertSessionMissing('auth.password_confirmed_at');
        $this->assertAuthenticatedAs($user);
    }
}
