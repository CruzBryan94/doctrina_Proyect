<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_verification_screen_is_not_exposed(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user)->get('/verify-email')->assertNotFound();
        $this->assertFalse(Route::has('verification.notice'));
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_legacy_verification_link_cannot_verify_an_account(): void
    {
        $user = User::factory()->unverified()->create();
        Event::fake([Verified::class]);
        $this->actingAs($user)->get('/verify-email/'.$user->id.'/'.sha1($user->email))->assertNotFound();
        $this->assertFalse(Route::has('verification.verify'));
        $this->assertNull($user->fresh()->email_verified_at);
        Event::assertNotDispatched(Verified::class);
    }

    public function test_verification_notifications_are_not_exposed(): void
    {
        $user = User::factory()->unverified()->create();
        Notification::fake();
        $this->actingAs($user)->post('/email/verification-notification')->assertNotFound();
        $this->assertFalse(Route::has('verification.send'));
        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->email_verified_at);
    }
}
