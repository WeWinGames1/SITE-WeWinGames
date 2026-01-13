<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Services\StripeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DiscountCodeController extends Controller
{
    private ?StripeService $stripeService = null;

    public function __construct()
    {
        try {
            $this->stripeService = new StripeService;
        } catch (\Exception $e) {
            // Stripe is not configured, but we can still show the page
            $this->stripeService = null;
        }
    }

    /**
     * Display discount codes management page
     */
    public function index(Request $request)
    {
        $query = DiscountCode::with(['creator', 'redemptions']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->valid();
            } elseif ($request->status === 'expired') {
                $query->where('valid_until', '<', Carbon::now());
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('code', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $discountCodes = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Transform for frontend
        $discountCodes->through(function ($code) {
            try {
                // Convert Stripe product IDs back to database IDs for the frontend
                // Handle case where applicable_products may reference deleted products
                $applicableProductIds = [];
                $rawProducts = $code->applicable_products;

                if (! empty($rawProducts) && is_array($rawProducts)) {
                    $applicableProductIds = StripeProduct::whereIn('stripe_product_id', $rawProducts)
                        ->pluck('id')
                        ->toArray();
                }

                return [
                    'id' => $code->id,
                    'code' => $code->code,
                    'description' => $code->description,
                    'discount_type' => $code->discount_type,
                    'discount_amount' => (float) ($code->discount_amount ?? 0),
                    'formatted_discount' => $code->formatted_discount ?? '0%',
                    'apply_to' => $code->apply_to ?? 'first_payment',
                    'months_count' => $code->months_count,
                    'max_uses' => $code->max_uses,
                    'max_uses_per_customer' => $code->max_uses_per_customer ?? 1,
                    'times_used' => $code->times_used ?? 0,
                    'valid_from' => $code->valid_from,
                    'valid_until' => $code->valid_until,
                    'applicable_products' => $applicableProductIds,
                    'minimum_amount' => $code->minimum_amount,
                    'is_active' => (bool) $code->is_active,
                    'is_valid' => $code->isValid(),
                    'redemptions_count' => $code->redemptions->count(),
                    'created_at' => $code->created_at,
                    'creator' => $code->creator ? [
                        'id' => $code->creator->id,
                        'name' => $code->creator->name,
                    ] : null,
                ];
            } catch (\Exception $e) {
                // Log the error and return minimal data to prevent page crash
                \Log::error('Error transforming discount code', [
                    'code_id' => $code->id ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);

                return [
                    'id' => $code->id ?? 0,
                    'code' => $code->code ?? 'ERROR',
                    'description' => 'Error loading this discount code',
                    'discount_type' => 'percentage',
                    'discount_amount' => 0,
                    'formatted_discount' => '0%',
                    'apply_to' => 'first_payment',
                    'months_count' => null,
                    'max_uses' => null,
                    'max_uses_per_customer' => 1,
                    'times_used' => 0,
                    'valid_from' => null,
                    'valid_until' => null,
                    'applicable_products' => [],
                    'minimum_amount' => null,
                    'is_active' => false,
                    'is_valid' => false,
                    'redemptions_count' => 0,
                    'created_at' => $code->created_at ?? now(),
                    'creator' => null,
                ];
            }
        });

        $products = StripeProduct::active()->ordered()->get(['id', 'name', 'tier', 'billing_period', 'stripe_product_id']);

        return Inertia::render('admin/Discounts/Index', [
            'discountCodes' => $discountCodes,
            'filters' => $request->only(['status', 'search']),
            'products' => $products,
        ]);
    }

    /**
     * Store a new discount code
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|unique:discount_codes,code',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_amount' => 'required|numeric|min:0',
            'apply_to' => 'required|in:first_payment,forever,specific_months',
            'months_count' => 'required_if:apply_to,specific_months|nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_customer' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:stripe_products,id',
            'minimum_amount' => 'nullable|numeric|min:0',
            'create_in_stripe' => 'boolean',
        ]);

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode();
        }

        // Convert product IDs to Stripe product IDs
        if (isset($validated['applicable_products'])) {
            if (! empty($validated['applicable_products'])) {
                $stripeProductIds = StripeProduct::whereIn('id', $validated['applicable_products'])
                    ->pluck('stripe_product_id')
                    ->filter()
                    ->toArray();
                $validated['applicable_products'] = $stripeProductIds;
            } else {
                // Empty array means no restrictions (applies to all products)
                $validated['applicable_products'] = null;
            }
        }

        // Add creator
        $validated['created_by'] = auth()->id();

        // Create in Stripe if requested
        if ($request->input('create_in_stripe', false)) {
            if (! $this->stripeService) {
                return back()->withErrors(['stripe' => 'Stripe is not configured. Please set STRIPE_SECRET in your .env file.']);
            }

            try {
                $stripeCoupon = $this->createStripeDiscount($validated);
                $validated['stripe_coupon_id'] = $stripeCoupon['id'];
            } catch (\Exception $e) {
                return back()->withErrors(['stripe' => 'Failed to create in Stripe: '.$e->getMessage()]);
            }
        }

        $discountCode = DiscountCode::create($validated);

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Discount code created successfully.');
    }

    /**
     * Update a discount code
     */
    public function update(Request $request, DiscountCode $discountCode)
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('discount_codes')->ignore($discountCode->id),
            ],
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_amount' => 'required|numeric|min:0',
            'apply_to' => 'required|in:first_payment,forever,specific_months',
            'months_count' => 'required_if:apply_to,specific_months|nullable|integer|min:1',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_customer' => 'required|integer|min:1',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:stripe_products,id',
            'minimum_amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        // Don't allow changing usage limit if already exceeded
        if (isset($validated['max_uses']) && $validated['max_uses'] < $discountCode->times_used) {
            return back()->withErrors(['max_uses' => 'Cannot set max uses below current usage count.']);
        }

        // Check if discount-affecting fields changed - if so, clear stripe_coupon_id
        // so it gets recreated with fresh values on next checkout
        // (Stripe coupons are immutable - cannot be updated, only recreated)
        $discountFieldsChanged =
            $discountCode->discount_type !== $validated['discount_type'] ||
            (float) $discountCode->discount_amount !== (float) $validated['discount_amount'] ||
            $discountCode->apply_to !== $validated['apply_to'] ||
            $discountCode->months_count !== ($validated['months_count'] ?? null);

        if ($discountFieldsChanged && $discountCode->stripe_coupon_id) {
            \Log::info('Discount code updated - clearing Stripe coupon ID for recreation', [
                'code' => $discountCode->code,
                'old_stripe_coupon_id' => $discountCode->stripe_coupon_id,
                'changes' => [
                    'discount_type' => [$discountCode->discount_type, $validated['discount_type']],
                    'discount_amount' => [$discountCode->discount_amount, $validated['discount_amount']],
                    'apply_to' => [$discountCode->apply_to, $validated['apply_to']],
                    'months_count' => [$discountCode->months_count, $validated['months_count'] ?? null],
                ],
            ]);
            $validated['stripe_coupon_id'] = null;
        }

        // Convert product IDs to Stripe product IDs
        if (isset($validated['applicable_products'])) {
            if (! empty($validated['applicable_products'])) {
                $stripeProductIds = StripeProduct::whereIn('id', $validated['applicable_products'])
                    ->pluck('stripe_product_id')
                    ->filter()
                    ->toArray();
                $validated['applicable_products'] = $stripeProductIds;
            } else {
                // Empty array means no restrictions (applies to all products)
                $validated['applicable_products'] = null;
            }
        }

        $discountCode->update($validated);

        // Auto-sync to Stripe if discount-affecting fields changed
        if ($discountFieldsChanged && $this->stripeService) {
            try {
                $this->syncDiscountToStripe($discountCode);
            } catch (\Exception $e) {
                \Log::error('Failed to auto-sync discount code to Stripe after update', [
                    'code' => $discountCode->code,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->route('admin.discounts.index')
                    ->with('warning', 'Discount code updated, but failed to sync to Stripe: '.$e->getMessage());
            }
        }

        return redirect()->route('admin.discounts.index')
            ->with('success', 'Discount code updated successfully.');
    }

    /**
     * Deactivate a discount code
     */
    public function deactivate(DiscountCode $discountCode)
    {
        $discountCode->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Discount code deactivated.',
        ]);
    }

    /**
     * Sync discount code to Stripe (API endpoint)
     */
    public function syncToStripe(DiscountCode $discountCode)
    {
        if (! $this->stripeService) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe is not configured. Please set STRIPE_SECRET in your .env file.',
            ], 500);
        }

        try {
            $stripeCouponId = $this->syncDiscountToStripe($discountCode);

            return response()->json([
                'success' => true,
                'message' => 'Discount code synced to Stripe successfully.',
                'stripe_coupon_id' => $stripeCouponId,
            ]);

        } catch (\Stripe\Exception\InvalidRequestException $e) {
            \Log::error('Failed to sync discount code to Stripe', [
                'code' => $discountCode->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to sync to Stripe: '.$e->getMessage(),
            ], 400);

        } catch (\Exception $e) {
            \Log::error('Unexpected error syncing discount code to Stripe', [
                'code' => $discountCode->code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unexpected error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Internal method to sync discount code to Stripe
     * Since Stripe coupons are immutable, this deletes the old one and creates a new one
     *
     * @return string The new Stripe coupon ID
     *
     * @throws \Exception
     */
    private function syncDiscountToStripe(DiscountCode $discountCode): string
    {
        $stripe = new \Stripe\StripeClient(config('cashier.secret'));

        // Delete old coupon if it exists
        if ($discountCode->stripe_coupon_id) {
            try {
                $stripe->coupons->delete($discountCode->stripe_coupon_id);
                \Log::info('Deleted old Stripe coupon', [
                    'code' => $discountCode->code,
                    'old_stripe_coupon_id' => $discountCode->stripe_coupon_id,
                ]);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                // Coupon doesn't exist in Stripe - that's fine, continue
                \Log::info('Old Stripe coupon not found (already deleted or never existed)', [
                    'code' => $discountCode->code,
                    'stripe_coupon_id' => $discountCode->stripe_coupon_id,
                ]);
            }
        }

        // Create new coupon with current database values
        $couponData = [
            'id' => $discountCode->code,
            'metadata' => [
                'synced_at' => now()->toIso8601String(),
                'synced_by' => auth()->user()->email,
            ],
        ];

        // Set discount type
        if ($discountCode->discount_type === 'percentage') {
            $couponData['percent_off'] = (float) $discountCode->discount_amount;
        } else {
            $couponData['amount_off'] = (int) ($discountCode->discount_amount * 100); // Convert to cents
            $couponData['currency'] = 'usd';
        }

        // Set duration based on apply_to field
        if ($discountCode->apply_to === 'first_payment') {
            $couponData['duration'] = 'once';
        } elseif ($discountCode->apply_to === 'forever') {
            $couponData['duration'] = 'forever';
        } else {
            $couponData['duration'] = 'repeating';
            $couponData['duration_in_months'] = $discountCode->months_count;
        }

        // Set redemption limit
        if ($discountCode->max_uses) {
            $couponData['max_redemptions'] = $discountCode->max_uses;
        }

        // Set validity period
        if ($discountCode->valid_until) {
            $couponData['redeem_by'] = Carbon::parse($discountCode->valid_until)->timestamp;
        }

        $stripeCoupon = $stripe->coupons->create($couponData);

        // Update the database with the new Stripe coupon ID
        $discountCode->update(['stripe_coupon_id' => $stripeCoupon->id]);

        \Log::info('Synced discount code to Stripe', [
            'code' => $discountCode->code,
            'stripe_coupon_id' => $stripeCoupon->id,
            'discount_type' => $discountCode->discount_type,
            'discount_amount' => $discountCode->discount_amount,
        ]);

        return $stripeCoupon->id;
    }

    /**
     * Get discount code details
     */
    public function show(DiscountCode $discountCode)
    {
        $discountCode->load(['redemptions.user', 'creator']);

        $redemptions = $discountCode->redemptions->map(function ($redemption) {
            return [
                'id' => $redemption->id,
                'user' => [
                    'id' => $redemption->user->id,
                    'name' => $redemption->user->name,
                    'email' => $redemption->user->email,
                ],
                'discount_applied' => $redemption->discount_applied,
                'created_at' => $redemption->created_at,
            ];
        });

        // Convert Stripe product IDs back to database IDs for the frontend
        $applicableProductIds = [];
        if (! empty($discountCode->applicable_products) && is_array($discountCode->applicable_products)) {
            $applicableProductIds = StripeProduct::whereIn('stripe_product_id', $discountCode->applicable_products)
                ->pluck('id')
                ->toArray();
        }

        return response()->json([
            'discount_code' => [
                'id' => $discountCode->id,
                'code' => $discountCode->code,
                'description' => $discountCode->description,
                'discount_type' => $discountCode->discount_type,
                'discount_amount' => $discountCode->discount_amount,
                'formatted_discount' => $discountCode->formatted_discount,
                'apply_to' => $discountCode->apply_to,
                'months_count' => $discountCode->months_count,
                'max_uses' => $discountCode->max_uses,
                'max_uses_per_customer' => $discountCode->max_uses_per_customer,
                'times_used' => $discountCode->times_used,
                'valid_from' => $discountCode->valid_from,
                'valid_until' => $discountCode->valid_until,
                'applicable_products' => $applicableProductIds,
                'minimum_amount' => $discountCode->minimum_amount,
                'is_active' => $discountCode->is_active,
                'is_valid' => $discountCode->isValid(),
                'created_at' => $discountCode->created_at,
                'creator' => $discountCode->creator ? [
                    'id' => $discountCode->creator->id,
                    'name' => $discountCode->creator->name,
                ] : null,
            ],
            'redemptions' => $redemptions,
        ]);
    }

    /**
     * Validate a discount code
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:stripe_products,id',
        ]);

        $discountCode = DiscountCode::where('code', $request->code)->first();

        if (! $discountCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid discount code.',
            ]);
        }

        if (! $discountCode->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'This discount code is no longer valid.',
            ]);
        }

        // Check user-specific validation if provided
        if ($request->filled('user_id')) {
            $user = \App\Models\User::find($request->user_id);
            if (! $discountCode->canBeUsedBy($user)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'You have already used this discount code.',
                ]);
            }
        }

        // Check product applicability if provided
        if ($request->filled('product_id')) {
            $product = StripeProduct::find($request->product_id);
            if ($product && $product->stripe_product_id && ! $discountCode->appliesToProduct($product->stripe_product_id)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'This discount code does not apply to the selected product.',
                ]);
            }
        }

        return response()->json([
            'valid' => true,
            'discount_type' => $discountCode->discount_type,
            'discount_amount' => $discountCode->discount_amount,
            'formatted_discount' => $discountCode->formatted_discount,
            'apply_to' => $discountCode->apply_to,
        ]);
    }

    /**
     * Generate a unique discount code
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (DiscountCode::where('code', $code)->exists());

        return $code;
    }

    /**
     * Create discount in Stripe
     */
    private function createStripeDiscount(array $data): array
    {
        $couponData = [
            'id' => $data['code'],
            'metadata' => [
                'created_by' => auth()->user()->email,
                'description' => $data['description'] ?? '',
            ],
        ];

        if ($data['discount_type'] === 'percentage') {
            $couponData['percent_off'] = $data['discount_amount'];
        } else {
            $couponData['amount_off'] = $this->stripeService->dollarsToCents($data['discount_amount']);
            $couponData['currency'] = 'usd';
        }

        // Set duration
        if ($data['apply_to'] === 'first_payment') {
            $couponData['duration'] = 'once';
        } elseif ($data['apply_to'] === 'forever') {
            $couponData['duration'] = 'forever';
        } else {
            $couponData['duration'] = 'repeating';
            $couponData['duration_in_months'] = $data['months_count'];
        }

        // Set redemption limit
        if (! empty($data['max_uses'])) {
            $couponData['max_redemptions'] = $data['max_uses'];
        }

        // Set validity period
        if (! empty($data['valid_until'])) {
            $couponData['redeem_by'] = Carbon::parse($data['valid_until'])->timestamp;
        }

        return $this->stripeService->createCoupon($couponData);
    }
}
