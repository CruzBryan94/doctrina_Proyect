<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Spatie\Permission\Models\Permission;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('home', 'web'));

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_without_permissions_can_sign_in_and_reach_welcome(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email, 'password' => 'password',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->get('/register')->assertRedirect('/');
        $this->get('/login')->assertRedirect('/');
    }

    public function test_login_links_to_registration_without_google(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee(route('register'))
            ->assertDontSee('google-auth');

        $this->assertFalse(\Illuminate\Support\Facades\Route::has('google.redirect'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('google.callback'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }
}
