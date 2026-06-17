<?php

namespace Tests\Feature;

use App\Mail\CompleteYourAccountMail;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class QuickCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected StripeProduct $goldMonthly;

    protected StripeProduct $platinumMonthly;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test products (tier enum requires proper case: Bronze, Silver, Gold, Platinum)
        $this->goldMonthly = StripeProduct::create([
            'name' => 'Gold Monthly',
            'stripe_product_id' => 'prod_gold',
            'stripe_price_id' => 'price_gold_monthly',
            'price' => 65,
            'tier' => 'Gold',
            'billing_period' => 'monthly',
            'is_active' => true,
            'features' => ['Feature 1', 'Feature 2'],
        ]);

        $this->platinumMonthly = StripeProduct::create([
            'name' => 'Platinum Monthly',
            'stripe_product_id' => 'prod_platinum',
            'stripe_price_id' => 'price_platinum_monthly',
            'price' => 95,
            'tier' => 'Platinum',
            'billing_period' => 'monthly',
            'is_active' => true,
            'features' => ['Feature 1', 'Feature 2', 'Feature 3'],
        ]);
    }

    public function test_quick_checkout_redirects_to_login_when_feature_disabled(): void
    {
        Config::set('features.quick_checkout_enabled', false);

        $response = $this->get('/quick-checkout');

        $response->assertRedirect(route('login'));
    }

    public function test_quick_checkout_page_renders_when_feature_enabled(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        $response = $this->get('/quick-checkout');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('QuickCheckout')
            ->has('selectedPlan')
            ->has('allProducts')
            ->has('stripeKey')
        );
    }

    public function test_quick_checkout_uses_plan_from_query_params(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        $response = $this->get('/quick-checkout?plan=platinum&period=monthly');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('QuickCheckout')
            ->where('selectedPlan.tier', 'Platinum')
        );
    }

    public function test_quick_checkout_falls_back_to_gold_monthly(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        // Request valid plan/period combo that doesn't exist in DB (silver daily)
        // Should fallback to Gold monthly
        $response = $this->get('/quick-checkout?plan=silver&period=daily');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('QuickCheckout')
            ->where('selectedPlan.tier', 'Gold')
        );
    }

    public function test_quick_checkout_redirects_if_no_products_available(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        // Deactivate all products
        StripeProduct::query()->update(['is_active' => false]);

        $response = $this->get('/quick-checkout');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
    }

    public function test_complete_registration_page_requires_valid_token(): void
    {
        $response = $this->get('/complete-registration');

        $response->assertRedirect(route('login'));
    }

    public function test_complete_registration_page_redirects_expired_token(): void
    {
        $response = $this->get('/complete-registration?token=invalid_token_here_64_characters_long_padded_to_fill_requirement');

        $response->assertRedirect(route('password.request'));
    }

    public function test_complete_registration_page_renders_with_valid_token(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => str_repeat('a', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->get('/complete-registration?token='.str_repeat('a', 64));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CompleteRegistration')
            ->where('email', $user->email)
            ->where('name', $user->name)
        );
    }

    public function test_complete_registration_sets_password_and_activates_user(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => str_repeat('b', 64),
            'completion_token_expires_at' => now()->addHours(24),
            'email_verified_at' => null,
        ]);

        $response = $this->post('/complete-registration', [
            'token' => str_repeat('b', 64),
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'discord_username' => 'testuser',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertEquals('testuser', $user->discord_username);
        $this->assertNull($user->completion_token);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_complete_registration_fails_with_invalid_token(): void
    {
        $response = $this->post('/complete-registration', [
            'token' => str_repeat('x', 64),
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('password.request'));
    }

    public function test_complete_registration_validates_password(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => str_repeat('c', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->post('/complete-registration', [
            'token' => str_repeat('c', 64),
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_complete_registration_validates_discord_username(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => str_repeat('d', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->post('/complete-registration', [
            'token' => str_repeat('d', 64),
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
            'discord_username' => 'invalid username with spaces!!!',
        ]);

        $response->assertSessionHasErrors('discord_username');
    }

    public function test_validate_coupon_returns_valid_for_good_code(): void
    {
        $admin = User::factory()->create();

        DiscountCode::create([
            'code' => 'TESTCODE',
            'description' => 'Test discount',
            'discount_type' => 'percentage',
            'discount_amount' => 20,
            'apply_to' => 'first_payment',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->postJson('/quick-checkout/validate-coupon', [
            'code' => 'TESTCODE',
        ]);

        $response->assertJson([
            'valid' => true,
            'discount' => [
                'percent_off' => 20,
                'amount_off' => null,
            ],
        ]);
    }

    public function test_validate_coupon_returns_invalid_for_bad_code(): void
    {
        $response = $this->postJson('/quick-checkout/validate-coupon', [
            'code' => 'INVALIDCODE',
        ]);

        $response->assertJson(['valid' => false]);
    }

    public function test_validate_coupon_checks_product_restrictions(): void
    {
        $admin = User::factory()->create();

        DiscountCode::create([
            'code' => 'GOLDONLY',
            'description' => 'Gold only discount',
            'discount_type' => 'percentage',
            'discount_amount' => 15,
            'apply_to' => 'first_payment',
            'applicable_products' => ['prod_gold'],
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        // Valid product
        $response = $this->postJson('/quick-checkout/validate-coupon', [
            'code' => 'GOLDONLY',
            'product_id' => 'prod_gold',
        ]);
        $response->assertJson(['valid' => true]);

        // Invalid product
        $response = $this->postJson('/quick-checkout/validate-coupon', [
            'code' => 'GOLDONLY',
            'product_id' => 'prod_platinum',
        ]);
        $response->assertJson([
            'valid' => false,
            'message' => 'This discount code does not apply to the selected product',
        ]);
    }

    public function test_validate_coupon_returns_fixed_amount_discount(): void
    {
        $admin = User::factory()->create();

        DiscountCode::create([
            'code' => 'DAILYUPGRADE10',
            'description' => 'Order bump discount',
            'discount_type' => 'fixed',
            'discount_amount' => 10,
            'apply_to' => 'first_payment',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $response = $this->postJson('/quick-checkout/validate-coupon', [
            'code' => 'DAILYUPGRADE10',
        ]);

        $response->assertJson([
            'valid' => true,
            'discount' => [
                'percent_off' => null,
                'amount_off' => 1000, // $10 in cents
            ],
        ]);
    }

    public function test_resend_completion_email_works(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
            'completion_token' => str_repeat('e', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $response = $this->post('/complete-registration/resend', [
            'email' => $user->email,
        ]);

        $response->assertSessionHas('success');
        Mail::assertSent(CompleteYourAccountMail::class);
    }

    public function test_resend_completion_does_not_reveal_email_existence(): void
    {
        $response = $this->post('/complete-registration/resend', [
            'email' => 'nonexistent@example.com',
        ]);

        // Should still return success-like response
        $response->assertSessionHas('info');
    }

    public function test_user_model_needs_registration_completion(): void
    {
        $pendingUser = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'quick_checkout',
        ]);

        $activeUser = User::factory()->create([
            'status' => 'active',
            'registration_type' => 'standard',
        ]);

        $this->assertTrue($pendingUser->needsRegistrationCompletion());
        $this->assertFalse($activeUser->needsRegistrationCompletion());
    }

    public function test_user_model_has_valid_completion_token(): void
    {
        $token = str_repeat('f', 64);

        $user = User::factory()->create([
            'completion_token' => $token,
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->assertTrue($user->hasValidCompletionToken());
        $this->assertTrue($user->hasValidCompletionToken($token));
        $this->assertFalse($user->hasValidCompletionToken('wrong_token'));

        // Expired token
        $user->completion_token_expires_at = now()->subHour();
        $user->save();
        $this->assertFalse($user->hasValidCompletionToken());
    }

    public function test_user_model_generate_completion_token(): void
    {
        $user = User::factory()->create([
            'completion_token' => null,
            'completion_token_expires_at' => null,
        ]);

        $token = $user->generateCompletionToken();

        $this->assertNotNull($token);
        $this->assertEquals(64, strlen($token));
        $this->assertEquals($token, $user->completion_token);
        $this->assertNotNull($user->completion_token_expires_at);
        $this->assertTrue($user->completion_token_expires_at->isFuture());
    }

    public function test_user_model_clear_completion_token(): void
    {
        $user = User::factory()->create([
            'completion_token' => str_repeat('g', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $user->clearCompletionToken();

        $this->assertNull($user->completion_token);
        $this->assertNull($user->completion_token_expires_at);
    }

    public function test_affiliate_trial_user_can_complete_registration(): void
    {
        $user = User::factory()->create([
            'status' => 'pending_setup',
            'registration_type' => 'affiliate_trial',
            'completion_token' => str_repeat('h', 64),
            'completion_token_expires_at' => now()->addHours(24),
        ]);

        $this->assertTrue($user->needsRegistrationCompletion());

        $response = $this->post('/complete-registration', [
            'token' => str_repeat('h', 64),
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNull($user->completion_token);
        $this->assertAuthenticatedAs($user);
    }

    public function test_quick_checkout_rejects_unknown_price_id(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        $response = $this->post('/quick-checkout', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+15551234567',
            'payment_method' => 'pm_card_visa',
            'price_id' => 'price_does_not_exist',
            'website' => '',
            'timestamp' => time() - 5,
        ]);

        $response->assertSessionHasErrors('price_id');
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    public function test_quick_checkout_rejects_inactive_price_id(): void
    {
        Config::set('features.quick_checkout_enabled', true);

        $this->goldMonthly->update(['is_active' => false]);

        $response = $this->post('/quick-checkout', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+15551234567',
            'payment_method' => 'pm_card_visa',
            'price_id' => 'price_gold_monthly',
            'website' => '',
            'timestamp' => time() - 5,
        ]);

        $response->assertSessionHasErrors('price_id');
    }

    public function test_quick_checkout_blocks_concurrent_double_submit(): void
    {
        Config::set('features.quick_checkout_enabled', true);
        Config::set('services.turnstile.enabled', false);

        $email = 'concurrent@gmail.com';

        // Simulate an in-flight checkout already holding the lock.
        \Illuminate\Support\Facades\Cache::add('quick-checkout-lock:'.$email, true, 30);

        $response = $this->post('/quick-checkout', [
            'name' => 'Jane Doe',
            'email' => $email,
            'phone' => '+15557654321',
            'payment_method' => 'pm_card_visa',
            'price_id' => 'price_gold_monthly',
            'website' => '',
            'timestamp' => time() - 5,
        ]);

        $response->assertSessionHasErrors('payment');
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    public function test_forgot_password_activates_pending_setup_user(): void
    {
        $token = app('auth.password.broker')->createToken(
            $user = User::factory()->create([
                'status' => 'pending_setup',
                'registration_type' => 'quick_checkout',
                'completion_token' => str_repeat('i', 64),
                'completion_token_expires_at' => now()->addHours(24),
                'email_verified_at' => null,
            ])
        );

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertEquals('active', $user->status);
        $this->assertNull($user->completion_token);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
    }
}
