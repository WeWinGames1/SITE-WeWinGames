<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
    }

    public function test_email_verification_screen_shows_user_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertStatus(200);
        $response->assertInertia(
            fn ($page) => $page
                ->component('auth/VerifyEmail')
                ->has('email')
                ->where('email', 'test@example.com')
        );
    }

    public function test_email_can_be_verified(): void
    {
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_email_is_not_verified_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)->get($verificationUrl);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_can_be_verified_without_authentication(): void
    {
        // Simulates clicking verification link from in-app email browser
        // (Gmail, iOS Mail, etc.) which doesn't share session cookies
        $user = User::factory()->unverified()->create();

        Event::fake();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Not using actingAs() - simulating unauthenticated request
        $response = $this->get($verificationUrl);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');

        // User should be logged in after verification
        $this->assertAuthenticatedAs($user);
    }

    public function test_email_can_be_verified_with_bypass_code(): void
    {
        Config::set('auth.email_verification_bypass_code', 'ABC123');

        $user = User::factory()->unverified()->create();

        Event::fake();

        $response = $this->actingAs($user)->post('/verify-email/code', [
            'code' => 'ABC123',
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    }

    public function test_bypass_code_is_case_insensitive(): void
    {
        Config::set('auth.email_verification_bypass_code', 'ABC123');

        $user = User::factory()->unverified()->create();

        Event::fake();

        $response = $this->actingAs($user)->post('/verify-email/code', [
            'code' => 'abc123',
        ]);

        Event::assertDispatched(Verified::class);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_invalid_bypass_code_fails(): void
    {
        Config::set('auth.email_verification_bypass_code', 'ABC123');

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post('/verify-email/code', [
            'code' => 'WRONG',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_bypass_code_fails_when_disabled(): void
    {
        Config::set('auth.email_verification_bypass_code', null);

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->post('/verify-email/code', [
            'code' => 'ABC123',
        ]);

        $response->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_can_update_email_during_verification(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'old@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->post('/verify-email/update-email', [
            'email' => 'new@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-email-updated');

        $user->refresh();
        $this->assertEquals('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_update_requires_correct_password(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'old@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->post('/verify-email/update-email', [
            'email' => 'new@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertEquals('old@example.com', $user->fresh()->email);
    }

    public function test_email_update_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $user = User::factory()->unverified()->create([
            'email' => 'old@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->post('/verify-email/update-email', [
            'email' => 'taken@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertEquals('old@example.com', $user->fresh()->email);
    }

    public function test_bypass_enabled_flag_passed_to_view(): void
    {
        Config::set('auth.email_verification_bypass_code', 'ABC123');

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertInertia(
            fn ($page) => $page
                ->component('auth/VerifyEmail')
                ->where('bypassEnabled', true)
        );
    }

    public function test_bypass_disabled_flag_passed_to_view(): void
    {
        Config::set('auth.email_verification_bypass_code', null);

        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/verify-email');

        $response->assertInertia(
            fn ($page) => $page
                ->component('auth/VerifyEmail')
                ->where('bypassEnabled', false)
        );
    }
}
