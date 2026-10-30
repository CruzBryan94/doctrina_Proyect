<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_home_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_home_without_dashboard_permission_shows_welcome(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertOk()->assertViewIs('welcome');
    }

    public function test_home_with_dashboard_permission_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));
        $this->actingAs($user)->get('/')->assertRedirect(route('dashboard'));
    }

    public function test_home_cannot_bypass_required_password_change(): void
    {
        $user = User::factory()->create(['must_change_password' => true, 'password' => Hash::make('123456')]);
        $this->actingAs($user)->get('/')->assertRedirect(route('password.change.show'));
    }
}
