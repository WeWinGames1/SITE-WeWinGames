<?php

namespace App\Console\Commands;

use App\Models\StripeProduct;
use Illuminate\Console\Command;
use Stripe\StripeClient;

class UpdateStripePrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:update-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Stripe product prices to match new pricing structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stripe = new StripeClient(config('cashier.secret'));

        // New pricing structure
        $newPrices = [
            'silver' => ['monthly' => 45, 'weekly' => 17, 'daily' => 5],
            'gold' => ['monthly' => 65, 'weekly' => 29, 'daily' => 8],
            'platinum' => ['monthly' => 80, 'weekly' => 49, 'daily' => 12],
        ];

        $this->info('Updating Stripe prices...');

        // Get all active products
        $products = StripeProduct::where('is_active', true)->get();

        foreach ($products as $product) {
            $tier = strtolower($product->tier);
            $period = $product->billing_period;

            if (! isset($newPrices[$tier][$period])) {
                $this->warn("No price defined for {$tier} {$period}");

                continue;
            }

            $newPrice = $newPrices[$tier][$period];

            try {
                // Check if we need to update
                if ($product->price == $newPrice) {
                    $this->info("Price already correct for {$tier} {$period}: \${$newPrice}");

                    continue;
                }

                // Archive the current price
                if ($product->stripe_price_id) {
                    try {
                        $stripe->prices->update($product->stripe_price_id, [
                            'active' => false,
                        ]);
                        $this->info("Archived old price for {$tier} {$period}");
                    } catch (\Exception $e) {
                        $this->warn('Could not archive price: '.$e->getMessage());
                    }
                }

                // Create new price
                $interval = match ($period) {
                    'monthly' => 'month',
                    'weekly' => 'week',
                    'daily' => 'day',
                    default => 'month'
                };

                $stripePrice = $stripe->prices->create([
                    'product' => $product->stripe_product_id,
                    'unit_amount' => $newPrice * 100, // Convert to cents
                    'currency' => 'usd',
                    'recurring' => [
                        'interval' => $interval,
                        'interval_count' => 1,
                    ],
                    'metadata' => [
                        'tier' => $tier,
                        'period' => $period,
                    ],
                ]);

                // Update local database
                $product->update([
                    'stripe_price_id' => $stripePrice->id,
                    'price' => $newPrice,
                ]);

                $this->info("✓ Updated {$tier} {$period}: \${$product->price} → \${$newPrice}");

            } catch (\Exception $e) {
                $this->error("Failed to update {$tier} {$period}: ".$e->getMessage());
            }
        }

        $this->info('Price update complete!');

        // Show summary
        $this->table(
            ['Tier', 'Period', 'New Price'],
            collect($products)->map(function ($product) use ($newPrices) {
                $tier = strtolower($product->tier);
                $period = $product->billing_period;

                return [
                    ucfirst($tier),
                    ucfirst($period),
                    '$'.($newPrices[$tier][$period] ?? 'N/A'),
                ];
            })
        );
    }
}
