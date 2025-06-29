<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeProduct;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class StripeProductController extends Controller
{
    private ?StripeService $stripeService = null;
    
    public function __construct()
    {
        try {
            $this->stripeService = new StripeService();
        } catch (\Exception $e) {
            // Stripe is not configured, but we can still show the page
            $this->stripeService = null;
        }
    }

    /**
     * Display the Stripe products management page
     */
    public function index()
    {
        $products = StripeProduct::ordered()->get();
        
        return Inertia::render('admin/StripeProducts/Index', [
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'tier' => $product->tier,
                    'billing_period' => $product->billing_period,
                    'price' => $product->price,
                    'formatted_price' => $product->formatted_price,
                    'stripe_product_id' => $product->stripe_product_id,
                    'stripe_price_id' => $product->stripe_price_id,
                    'is_active' => $product->is_active,
                    'is_connected' => $product->isConnectedToStripe(),
                    'features' => $product->features ?? [],
                    'badge_text' => $product->badge_text,
                    'sort_order' => $product->sort_order,
                ];
            }),
            'tiers' => ['Bronze', 'Silver', 'Gold', 'Platinum'],
            'billing_periods' => ['daily', 'weekly', 'monthly'],
        ]);
    }

    /**
     * Store a new product configuration
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tier' => 'required|in:Bronze,Silver,Gold,Platinum',
            'billing_period' => 'required|in:daily,weekly,monthly',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            // Check if combination already exists
            $exists = StripeProduct::where('tier', $validated['tier'])
                ->where('billing_period', $validated['billing_period'])
                ->exists();

            if ($exists) {
                return back()->withErrors(['tier' => 'This tier and billing period combination already exists.']);
            }

            $product = StripeProduct::create($validated);

            return redirect()->route('admin.stripe-products.index')
                ->with('success', 'Product configuration created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create product: ' . $e->getMessage()]);
        }
    }

    /**
     * Update a product configuration
     */
    public function update(Request $request, StripeProduct $stripeProduct)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'features' => 'nullable|array',
            'features.*' => 'string',
            'badge_text' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
            'is_active' => 'boolean',
        ]);

        try {
            $stripeProduct->update($validated);

            return redirect()->route('admin.stripe-products.index')
                ->with('success', 'Product configuration updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update product: ' . $e->getMessage()]);
        }
    }

    /**
     * Fetch available products from Stripe
     */
    public function fetchFromStripe()
    {
        try {
            if (!$this->stripeService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe API is not configured. Please set STRIPE_SECRET in your .env file.',
                ], 422);
            }
            
            $products = $this->stripeService->fetchProducts();
            
            return response()->json([
                'success' => true,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch prices for a specific Stripe product
     */
    public function fetchPrices(Request $request)
    {
        $request->validate([
            'product_id' => 'required|string',
        ]);

        try {
            if (!$this->stripeService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe API is not configured. Please set STRIPE_SECRET in your .env file.',
                ], 422);
            }
            
            $prices = $this->stripeService->fetchPricesForProduct($request->product_id);
            
            return response()->json([
                'success' => true,
                'prices' => $prices->map(function ($price) {
                    return [
                        'id' => $price['id'],
                        'unit_amount' => $price['unit_amount'],
                        'unit_amount_dollars' => $this->stripeService ? $this->stripeService->centsToDollars($price['unit_amount']) : ($price['unit_amount'] / 100),
                        'currency' => $price['currency'],
                        'recurring' => $price['recurring'],
                        'active' => $price['active'],
                    ];
                }),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Connect a local product to Stripe
     */
    public function connectToStripe(Request $request, StripeProduct $stripeProduct)
    {
        $validated = $request->validate([
            'stripe_product_id' => 'required|string',
            'stripe_price_id' => 'required|string',
        ]);

        try {
            if (!$this->stripeService) {
                return back()->withErrors(['error' => 'Stripe API is not configured. Please set STRIPE_SECRET in your .env file.']);
            }
            
            // Verify the product and price exist in Stripe
            $stripeProductData = $this->stripeService->fetchProduct($validated['stripe_product_id']);
            $stripePriceData = $this->stripeService->fetchPrice($validated['stripe_price_id']);

            // Verify the price belongs to the product
            if ($stripePriceData['product'] !== $stripeProductData['id']) {
                return response()->json([
                    'success' => false,
                    'error' => 'The selected price does not belong to the selected product.',
                ], 422);
            }

            // Update the local product
            $stripeProduct->update([
                'stripe_product_id' => $validated['stripe_product_id'],
                'stripe_price_id' => $validated['stripe_price_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product successfully connected to Stripe.',
                'product' => [
                    'id' => $stripeProduct->id,
                    'stripe_product_id' => $stripeProduct->stripe_product_id,
                    'stripe_price_id' => $stripeProduct->stripe_price_id,
                    'is_connected' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to connect to Stripe: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new product and price in Stripe
     */
    public function createInStripe(Request $request, StripeProduct $stripeProduct)
    {
        try {
            if (!$this->stripeService) {
                return back()->withErrors(['error' => 'Stripe API is not configured. Please set STRIPE_SECRET in your .env file.']);
            }
            
            DB::beginTransaction();

            // Create product in Stripe
            $stripeProductData = $this->stripeService->createProduct([
                'name' => $stripeProduct->name,
                'description' => "WeWinGames {$stripeProduct->tier} {$stripeProduct->billing_period} subscription",
                'metadata' => [
                    'tier' => $stripeProduct->tier,
                    'billing_period' => $stripeProduct->billing_period,
                ],
            ]);

            // Determine recurring interval
            $interval = match($stripeProduct->billing_period) {
                'daily' => 'day',
                'weekly' => 'week',
                'monthly' => 'month',
                default => 'month',
            };

            // Create price in Stripe
            $stripePriceData = $this->stripeService->createPrice([
                'product_id' => $stripeProductData['id'],
                'unit_amount' => $this->stripeService->dollarsToCents($stripeProduct->price),
                'currency' => 'usd',
                'recurring' => [
                    'interval' => $interval,
                    'interval_count' => 1,
                ],
                'metadata' => [
                    'tier' => $stripeProduct->tier,
                    'billing_period' => $stripeProduct->billing_period,
                ],
            ]);

            // Update local product with Stripe IDs
            $stripeProduct->update([
                'stripe_product_id' => $stripeProductData['id'],
                'stripe_price_id' => $stripePriceData['id'],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product and price created successfully in Stripe.',
                'product' => [
                    'id' => $stripeProduct->id,
                    'stripe_product_id' => $stripeProduct->stripe_product_id,
                    'stripe_price_id' => $stripeProduct->stripe_price_id,
                    'is_connected' => true,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => 'Failed to create in Stripe: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect a product from Stripe (remove the connection, not delete from Stripe)
     */
    public function disconnectFromStripe(StripeProduct $stripeProduct)
    {
        try {
            $stripeProduct->update([
                'stripe_product_id' => null,
                'stripe_price_id' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Product disconnected from Stripe.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to disconnect: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a product configuration (only if not connected to Stripe)
     */
    public function destroy(StripeProduct $stripeProduct)
    {
        if ($stripeProduct->isConnectedToStripe()) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot delete a product that is connected to Stripe. Disconnect it first.',
            ], 422);
        }

        try {
            $stripeProduct->delete();

            return redirect()->route('admin.stripe-products.index')
                ->with('success', 'Product configuration deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete product: ' . $e->getMessage()]);
        }
    }
}
