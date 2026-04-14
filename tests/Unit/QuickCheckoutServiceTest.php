<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\QuickCheckoutService;
use App\Services\RegistrationSecurityService;
use App\Services\SendGridService;
use App\Services\SpringBigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuickCheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuickCheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create service with mocked dependencies
        $securityService = $this->createMock(RegistrationSecurityService::class);
        $securityService->method('canRegister')->willReturn(['allowed' => true]);
        // logRegistrationAttempt returns void, no need to mock return value

        $sendGridService = $this->createMock(SendGridService::class);
        $springBigService = $this->createMock(SpringBigService::class);

        $this->service = new QuickCheckoutService(
            $securityService,
            $sendGridService,
            $springBigService
        );
    }

    public function test_find_user_by_token_returns_user_with_valid_token(): void
    {
        $token = bin2hex(random_bytes(32));

        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => $token,
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $foundUser = $this->service->findUserByToken($token);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    public function test_find_user_by_token_returns_null_for_expired_token(): void
    {
        $token = bin2hex(random_bytes(32));

        User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => $token,
            'completion_token_expires_at' => now()->subHour(),
        ]);

        $foundUser = $this->service->findUserByToken($token);

        $this->assertNull($foundUser);
    }

    public function test_find_user_by_token_returns_null_for_wrong_status(): void
    {
        $token = bin2hex(random_bytes(32));

        User::factory()->create([
            'status' => 'active', // Wrong status
            'registration_type' => 'quick_checkout',
            'completion_token' => $token,
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $foundUser = $this->service->findUserByToken($token);

        $this->assertNull($foundUser);
    }

    public function test_find_user_by_token_returns_null_for_wrong_registration_type(): void
    {
        $token = bin2hex(random_bytes(32));

        User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'standard', // Wrong type
            'completion_token' => $token,
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $foundUser = $this->service->findUserByToken($token);

        $this->assertNull($foundUser);
    }

    public function test_complete_registration_sets_password_correctly(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $result = $this->service->completeRegistration($user, 'NewSecurePassword123!');

        $this->assertTrue($result['success']);
        $user->refresh();
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->password));
    }

    public function test_complete_registration_sets_status_to_active(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->service->completeRegistration($user, 'NewSecurePassword123!');

        $user->refresh();
        $this->assertEquals('active', $user->status);
    }

    public function test_complete_registration_clears_completion_token(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->service->completeRegistration($user, 'NewSecurePassword123!');

        $user->refresh();
        $this->assertNull($user->completion_token);
        $this->assertNull($user->completion_token_expires_at);
    }

    public function test_complete_registration_verifies_email(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
            'email_verified_at' => null,
        ]);

        $this->service->completeRegistration($user, 'NewSecurePassword123!');

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_complete_registration_sets_discord_username(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->service->completeRegistration($user, 'NewSecurePassword123!', 'MyDiscord');

        $user->refresh();
        $this->assertEquals('mydiscord', $user->discord_username);
    }

    public function test_complete_registration_handles_null_discord_username(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => bin2hex(random_bytes(32)),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->service->completeRegistration($user, 'NewSecurePassword123!', null);

        $user->refresh();
        $this->assertNull($user->discord_username);
    }

    public function test_resend_completion_email_returns_false_for_active_user(): void
    {
        $user = User::factory()->create([
            'status' => 'active',
            'registration_type' => 'standard',
        ]);

        $result = $this->service->resendCompletionEmail($user);

        $this->assertFalse($result);
    }

    public function test_resend_completion_email_returns_true_for_pending_user(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
        ]);

        $result = $this->service->resendCompletionEmail($user);

        $this->assertTrue($result);
        $user->refresh();
        $this->assertNotNull($user->completion_token);
    }
}
