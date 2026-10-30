<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\Grade_Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(Grade_Seeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/');
        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue($user->hasRole('none'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertEquals(17, $user->grade);
        $this->assertTrue((bool) $user->is_active);
        $this->get('/')->assertOk()->assertSee('Cuenta creada correctamente');
        $this->get('/dashboard')->assertForbidden();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->post('/register', [
            'name' => 'Test User', 'email' => 'TEST@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_rejects_short_or_unconfirmed_passwords(): void
    {
        foreach ([['short', 'short'], ['password', 'different']] as [$password, $confirmation]) {
            $this->post('/register', [
                'name' => 'Test User', 'email' => 'test@example.com',
                'password' => $password, 'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors('password');
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_registration_does_not_accept_privileged_fields(): void
    {
        $this->post('/register', [
            'name' => 'Test User', 'email' => 'TEST@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'admin', 'grade' => 1, 'google_id' => 'injected',
        ])->assertRedirect('/');

        $user = User::where('email', 'test@example.com')->firstOrFail();
        $this->assertTrue($user->hasRole('none'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertNull($user->google_id);
        $this->assertNull($user->grade);
    }
}
