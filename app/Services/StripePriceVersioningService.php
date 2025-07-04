<?php

namespace App\Services;

use App\Models\StripeProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class StripePriceVersioningService
{
    private StripeClient $stripe;
    
    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }
    
    /**
     * Create a new version of a product with updated pricing
     * Existing subscriptions continue at their current price
     * New subscriptions use the new price
     */
    public function createPriceVersion(StripeProduct $currentProduct, array $data): StripeProduct
    {
        return DB::transaction(function () use ($currentProduct, $data) {
            // Mark current product as superseded
            $currentProduct->update([
                'is_current' => false,
                'superseded_at' => now(),
            ]);
            
            // Create the new price in Stripe
            $stripePrice = $this->createStripePrice($currentProduct, $data['new_price']);
            
            // Create new product version in database
            $newProduct = StripeProduct::create([
                'name' => $currentProduct->name,
                'tier' => $currentProduct->tier,
                'billing_period' => $currentProduct->billing_period,
                'price' => $data['new_price'],
                'stripe_product_id' => $currentProduct->stripe_product_id, // Same product
                'stripe_price_id' => $stripePrice->id, // New price
                'features' => $currentProduct->features,
                'badge_text' => $currentProduct->badge_text,
                'sort_order' => $currentProduct->sort_order,
                'is_active' => true,
                'is_current' => true,
                'version' => $data['version'] ?? date('Y-m'),
                'legacy_price' => $currentProduct->price,
            ]);
            
            // Update the superseded_by reference
            $currentProduct->update([
                'superseded_by_product_id' => $newProduct->id,
            ]);
            
            // Log the price migration
            DB::table('stripe_price_migrations')->insert([
                'old_stripe_price_id' => $currentProduct->stripe_price_id,
                'new_stripe_price_id' => $stripePrice->id,
                'tier' => $currentProduct->tier,
                'billing_period' => $currentProduct->billing_period,
                'old_price' => $currentProduct->price,
                'new_price' => $data['new_price'],
                'notes' => $data['notes'] ?? null,
                'effective_date' => $data['effective_date'] ?? now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            Log::info('Created new price version', [
                'product_id' => $newProduct->id,
                'old_price' => $currentProduct->price,
                'new_price' => $data['new_price'],
                'stripe_price_id' => $stripePrice->id,
            ]);
            
            return $newProduct;
        });
    }
    
    /**
     * Create a new price in Stripe for the product
     */
    private function createStripePrice(StripeProduct $product, float $newPrice): \Stripe\Price
    {
        $interval = $this->getStripeInterval($product->billing_period);
        
        return $this->stripe->prices->create([
            'product' => $product->stripe_product_id,
            'unit_amount' => (int)($newPrice * 100), // Convert to cents
            'currency' => 'usd',
            'recurring' => [
                'interval' => $interval['interval'],
                'interval_count' => $interval['interval_count'],
            ],
            'metadata' => [
                'tier' => $product->tier,
                'billing_period' => $product->billing_period,
                'version' => date('Y-m'),
            ],
        ]);
    }
    
    /**
     * Get Stripe interval configuration from billing period
     */
    private function getStripeInterval(string $billingPeriod): array
    {
        return match($billingPeriod) {
            'daily' => ['interval' => 'day', 'interval_count' => 1],
            'weekly' => ['interval' => 'week', 'interval_count' => 1],
            'monthly' => ['interval' => 'month', 'interval_count' => 1],
            default => throw new \InvalidArgumentException("Invalid billing period: {$billingPeriod}")
        };
    }
    
    /**
     * Get the current price for a tier/period combination
     * This is what new customers will see
     */
    public static function getCurrentPrice(string $tier, string $billingPeriod): ?StripeProduct
    {
        return StripeProduct::where('tier', $tier)
            ->where('billing_period', $billingPeriod)
            ->where('is_current', true)
            ->where('is_active', true)
            ->first();
    }
    
    /**
     * Get price history for a product
     */
    public static function getPriceHistory(string $tier, string $billingPeriod): \Illuminate\Support\Collection
    {
        return StripeProduct::where('tier', $tier)
            ->where('billing_period', $billingPeriod)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'price' => $product->price,
                    'version' => $product->version,
                    'is_current' => $product->is_current,
                    'created_at' => $product->created_at,
                    'superseded_at' => $product->superseded_at,
                ];
            });
    }
    
    /**
     * Check if a subscription is on a legacy price
     */
    public static function isLegacyPrice(string $stripePriceId): bool
    {
        return StripeProduct::where('stripe_price_id', $stripePriceId)
            ->where('is_current', false)
            ->exists();
    }
    
    /**
     * Get the current price for a legacy price ID
     * Useful for showing "new customers pay X" messaging
     */
    public static function getCurrentPriceForLegacy(string $legacyPriceId): ?StripeProduct
    {
        $legacyProduct = StripeProduct::where('stripe_price_id', $legacyPriceId)->first();
        
        if (!$legacyProduct) {
            return null;
        }
        
        return self::getCurrentPrice($legacyProduct->tier, $legacyProduct->billing_period);
    }
}