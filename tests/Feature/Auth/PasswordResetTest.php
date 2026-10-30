<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_email_reset_request_screen_is_not_exposed(): void
    {
        $this->get('/forgot-password')->assertNotFound();
        $this->assertFalse(Route::has('password.request'));
        $this->assertFalse(Route::has('password.email'));
    }

    public function test_legacy_request_does_not_send_mail_create_tokens_or_enable_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create()->refresh();
        $passwordHash = $user->password;
        $this->post('/forgot-password', ['email' => $user->email])->assertNotFound();
        Notification::assertNothingSent();
        $this->assertDatabaseCount(config('auth.passwords.users.table'), 0);
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertFalse($user->must_change_password);
        $this->assertGuest();
    }

    public function test_legacy_token_reset_screen_is_not_exposed(): void
    {
        $this->get('/reset-password/test-token')->assertNotFound();
        $this->assertFalse(Route::has('password.reset'));
        $this->assertFalse(Route::has('password.store'));
    }

    public function test_even_a_valid_broker_token_cannot_change_password_via_legacy_endpoint(): void
    {
        Notification::fake();
        $user = User::factory()->create()->refresh();
        $passwordHash = $user->password;
        // This token and its table exist only in the isolated SQLite test database.
        $token = Password::broker()->createToken($user);
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertNotFound();
        $this->assertSame($passwordHash, $user->fresh()->password);
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Password::broker()->tokenExists($user, $token));
        Notification::assertNothingSent();
        $this->assertGuest();
    }
}
