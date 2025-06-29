<?php

namespace App\Services;

use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    /**
     * Fetch all products from Stripe
     */
    public function fetchProducts(): Collection
    {
        try {
            $products = [];
            $hasMore = true;
            $startingAfter = null;

            while ($hasMore) {
                $params = ['limit' => 100, 'active' => true];
                if ($startingAfter) {
                    $params['starting_after'] = $startingAfter;
                }

                $response = $this->stripe->products->all($params);
                
                foreach ($response->data as $product) {
                    $products[] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'active' => $product->active,
                        'metadata' => $product->metadata->toArray(),
                        'created' => $product->created,
                    ];
                }

                $hasMore = $response->has_more;
                if ($hasMore && count($response->data) > 0) {
                    $startingAfter = end($response->data)->id;
                }
            }

            return collect($products);
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error fetching products: ' . $e->getMessage());
            throw new \Exception('Failed to fetch products from Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Fetch all prices for a product from Stripe
     */
    public function fetchPricesForProduct(string $productId): Collection
    {
        try {
            $prices = [];
            $hasMore = true;
            $startingAfter = null;

            while ($hasMore) {
                $params = [
                    'limit' => 100,
                    'product' => $productId,
                    'active' => true,
                ];
                if ($startingAfter) {
                    $params['starting_after'] = $startingAfter;
                }

                $response = $this->stripe->prices->all($params);
                
                foreach ($response->data as $price) {
                    $prices[] = [
                        'id' => $price->id,
                        'product' => $price->product,
                        'active' => $price->active,
                        'currency' => $price->currency,
                        'unit_amount' => $price->unit_amount,
                        'unit_amount_decimal' => $price->unit_amount_decimal,
                        'type' => $price->type,
                        'recurring' => $price->recurring ? [
                            'interval' => $price->recurring->interval,
                            'interval_count' => $price->recurring->interval_count,
                        ] : null,
                        'metadata' => $price->metadata->toArray(),
                        'created' => $price->created,
                    ];
                }

                $hasMore = $response->has_more;
                if ($hasMore && count($response->data) > 0) {
                    $startingAfter = end($response->data)->id;
                }
            }

            return collect($prices);
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error fetching prices: ' . $e->getMessage());
            throw new \Exception('Failed to fetch prices from Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a single product from Stripe
     */
    public function fetchProduct(string $productId): array
    {
        try {
            $product = $this->stripe->products->retrieve($productId);
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'metadata' => $product->metadata->toArray(),
                'created' => $product->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error fetching product: ' . $e->getMessage());
            throw new \Exception('Failed to fetch product from Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Fetch a single price from Stripe
     */
    public function fetchPrice(string $priceId): array
    {
        try {
            $price = $this->stripe->prices->retrieve($priceId);
            
            return [
                'id' => $price->id,
                'product' => $price->product,
                'active' => $price->active,
                'currency' => $price->currency,
                'unit_amount' => $price->unit_amount,
                'unit_amount_decimal' => $price->unit_amount_decimal,
                'type' => $price->type,
                'recurring' => $price->recurring ? [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                ] : null,
                'metadata' => $price->metadata->toArray(),
                'created' => $price->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error fetching price: ' . $e->getMessage());
            throw new \Exception('Failed to fetch price from Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Create a product in Stripe
     */
    public function createProduct(array $data): array
    {
        try {
            $product = $this->stripe->products->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'metadata' => $product->metadata->toArray(),
                'created' => $product->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error creating product: ' . $e->getMessage());
            throw new \Exception('Failed to create product in Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Create a price for a product in Stripe
     */
    public function createPrice(array $data): array
    {
        try {
            $priceData = [
                'product' => $data['product_id'],
                'currency' => $data['currency'] ?? 'usd',
                'unit_amount' => $data['unit_amount'], // Amount in cents
            ];

            // Add recurring data if this is a subscription
            if (isset($data['recurring'])) {
                $priceData['recurring'] = [
                    'interval' => $data['recurring']['interval'], // 'day', 'week', 'month', 'year'
                    'interval_count' => $data['recurring']['interval_count'] ?? 1,
                ];
            }

            if (isset($data['metadata'])) {
                $priceData['metadata'] = $data['metadata'];
            }

            $price = $this->stripe->prices->create($priceData);
            
            return [
                'id' => $price->id,
                'product' => $price->product,
                'active' => $price->active,
                'currency' => $price->currency,
                'unit_amount' => $price->unit_amount,
                'unit_amount_decimal' => $price->unit_amount_decimal,
                'type' => $price->type,
                'recurring' => $price->recurring ? [
                    'interval' => $price->recurring->interval,
                    'interval_count' => $price->recurring->interval_count,
                ] : null,
                'metadata' => $price->metadata->toArray(),
                'created' => $price->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error creating price: ' . $e->getMessage());
            throw new \Exception('Failed to create price in Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Update a product in Stripe
     */
    public function updateProduct(string $productId, array $data): array
    {
        try {
            $updateData = [];
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['description'])) {
                $updateData['description'] = $data['description'];
            }
            if (isset($data['metadata'])) {
                $updateData['metadata'] = $data['metadata'];
            }
            if (isset($data['active'])) {
                $updateData['active'] = $data['active'];
            }

            $product = $this->stripe->products->update($productId, $updateData);
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'active' => $product->active,
                'metadata' => $product->metadata->toArray(),
                'created' => $product->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error updating product: ' . $e->getMessage());
            throw new \Exception('Failed to update product in Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Deactivate a price in Stripe (prices cannot be deleted)
     */
    public function deactivatePrice(string $priceId): bool
    {
        try {
            $this->stripe->prices->update($priceId, ['active' => false]);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error deactivating price: ' . $e->getMessage());
            throw new \Exception('Failed to deactivate price in Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Convert cents to dollars
     */
    public function centsToDollars(int $cents): float
    {
        return $cents / 100;
    }

    /**
     * Convert dollars to cents
     */
    public function dollarsToCents(float $dollars): int
    {
        return (int) round($dollars * 100);
    }

    /**
     * Create a coupon in Stripe
     */
    public function createCoupon(array $data): array
    {
        try {
            $coupon = $this->stripe->coupons->create($data);
            
            return [
                'id' => $coupon->id,
                'amount_off' => $coupon->amount_off,
                'percent_off' => $coupon->percent_off,
                'currency' => $coupon->currency,
                'duration' => $coupon->duration,
                'duration_in_months' => $coupon->duration_in_months,
                'max_redemptions' => $coupon->max_redemptions,
                'redeem_by' => $coupon->redeem_by,
                'times_redeemed' => $coupon->times_redeemed,
                'valid' => $coupon->valid,
                'metadata' => $coupon->metadata->toArray(),
                'created' => $coupon->created,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe API Error creating coupon: ' . $e->getMessage());
            throw new \Exception('Failed to create coupon in Stripe: ' . $e->getMessage());
        }
    }
}