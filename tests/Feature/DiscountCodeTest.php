<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountCodeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected StripeProduct $product1;

    protected StripeProduct $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create test products
        $this->product1 = StripeProduct::create([
            'name' => 'Gold Monthly',
            'stripe_product_id' => 'prod_test1',
            'stripe_price_id' => 'price_test1',
            'price' => 65,
            'tier' => 'Gold',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);

        $this->product2 = StripeProduct::create([
            'name' => 'Silver Monthly',
            'stripe_product_id' => 'prod_test2',
            'stripe_price_id' => 'price_test2',
            'price' => 45,
            'tier' => 'Silver',
            'billing_period' => 'monthly',
            'is_active' => true,
        ]);
    }

    public function test_discount_code_with_product_restrictions_validates_correctly()
    {
        // Create discount code restricted to product1
        $discount = DiscountCode::create([
            'code' => 'GOLDONLY',
            'description' => 'Gold plan only discount',
            'discount_type' => 'percentage',
            'discount_amount' => 20,
            'apply_to' => 'forever',
            'applicable_products' => [$this->product1->stripe_product_id],
            'is_active' => true,
            'max_uses_per_customer' => 1,
            'created_by' => $this->user->id,
        ]);

        // Test validation with correct product
        $response = $this->actingAs($this->user)
            ->postJson(route('subscription.validate-coupon'), [
                'code' => 'GOLDONLY',
                'product_id' => $this->product1->stripe_product_id,
            ]);

        $response->assertJson([
            'valid' => true,
            'discount' => [
                'percent_off' => 20,
                'amount_off' => null,
            ],
        ]);

        // Test validation with wrong product
        $response = $this->actingAs($this->user)
            ->postJson(route('subscription.validate-coupon'), [
                'code' => 'GOLDONLY',
                'product_id' => $this->product2->stripe_product_id,
            ]);

        $response->assertJson([
            'valid' => false,
            'message' => 'This discount code does not apply to the selected product',
        ]);
    }

    public function test_discount_code_without_restrictions_works_for_all_products()
    {
        // Create unrestricted discount code
        $discount = DiscountCode::create([
            'code' => 'ALLPLANS',
            'description' => 'All plans discount',
            'discount_type' => 'percentage',
            'discount_amount' => 15,
            'apply_to' => 'forever',
            'applicable_products' => null, // No restrictions
            'is_active' => true,
            'max_uses_per_customer' => 1,
            'created_by' => $this->user->id,
        ]);

        // Test with product1
        $response = $this->actingAs($this->user)
            ->postJson(route('subscription.validate-coupon'), [
                'code' => 'ALLPLANS',
                'product_id' => $this->product1->stripe_product_id,
            ]);

        $response->assertJson(['valid' => true]);

        // Test with product2
        $response = $this->actingAs($this->user)
            ->postJson(route('subscription.validate-coupon'), [
                'code' => 'ALLPLANS',
                'product_id' => $this->product2->stripe_product_id,
            ]);

        $response->assertJson(['valid' => true]);
    }

    public function test_firstmonth50_applies_to_all_products()
    {
        // Create FIRSTMONTH50 discount
        $discount = DiscountCode::create([
            'code' => 'FIRSTMONTH50',
            'description' => '50% off first month',
            'discount_type' => 'percentage',
            'discount_amount' => 50,
            'apply_to' => 'first_payment',
            'applicable_products' => null, // Applies to all
            'is_active' => true,
            'max_uses_per_customer' => 1,
            'created_by' => $this->user->id,
        ]);

        // Test with any product
        $response = $this->actingAs($this->user)
            ->postJson(route('subscription.validate-coupon'), [
                'code' => 'FIRSTMONTH50',
                'product_id' => $this->product1->stripe_product_id,
            ]);

        $response->assertJson([
            'valid' => true,
            'discount' => [
                'percent_off' => 50,
                'amount_off' => null,
            ],
        ]);
    }
}
