<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\StripeProduct;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DiscountCodeController extends Controller
{
    public function __construct(
        private StripeService $stripeService
    ) {}

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
                $q->where('code', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }
        
        $discountCodes = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();
        
        // Transform for frontend
        $discountCodes->through(function ($code) {
            return [
                'id' => $code->id,
                'code' => $code->code,
                'description' => $code->description,
                'discount_type' => $code->discount_type,
                'discount_amount' => $code->discount_amount,
                'formatted_discount' => $code->formatted_discount,
                'apply_to' => $code->apply_to,
                'months_count' => $code->months_count,
                'max_uses' => $code->max_uses,
                'max_uses_per_customer' => $code->max_uses_per_customer,
                'times_used' => $code->times_used,
                'valid_from' => $code->valid_from,
                'valid_until' => $code->valid_until,
                'is_active' => $code->is_active,
                'is_valid' => $code->isValid(),
                'redemptions_count' => $code->redemptions->count(),
                'created_at' => $code->created_at,
                'creator' => $code->creator ? [
                    'id' => $code->creator->id,
                    'name' => $code->creator->name,
                ] : null,
            ];
        });
        
        $products = StripeProduct::active()->ordered()->get(['id', 'name', 'tier', 'billing_period', 'stripe_product_id']);
        
        return Inertia::render('Admin/Discounts/Index', [
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
        if (!empty($validated['applicable_products'])) {
            $stripeProductIds = StripeProduct::whereIn('id', $validated['applicable_products'])
                ->pluck('stripe_product_id')
                ->filter()
                ->toArray();
            $validated['applicable_products'] = $stripeProductIds;
        }
        
        // Add creator
        $validated['created_by'] = auth()->id();
        
        // Create in Stripe if requested
        if ($request->input('create_in_stripe', false)) {
            try {
                $stripeCoupon = $this->createStripeDiscount($validated);
                $validated['stripe_coupon_id'] = $stripeCoupon['id'];
            } catch (\Exception $e) {
                return back()->withErrors(['stripe' => 'Failed to create in Stripe: ' . $e->getMessage()]);
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
            'description' => 'nullable|string|max:255',
            'max_uses' => 'nullable|integer|min:1',
            'valid_until' => 'nullable|date',
            'is_active' => 'boolean',
        ]);
        
        // Don't allow changing usage limit if already exceeded
        if (isset($validated['max_uses']) && $validated['max_uses'] < $discountCode->times_used) {
            return back()->withErrors(['max_uses' => 'Cannot set max uses below current usage count.']);
        }
        
        $discountCode->update($validated);
        
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
        
        if (!$discountCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid discount code.',
            ]);
        }
        
        if (!$discountCode->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'This discount code is no longer valid.',
            ]);
        }
        
        // Check user-specific validation if provided
        if ($request->filled('user_id')) {
            $user = \App\Models\User::find($request->user_id);
            if (!$discountCode->canBeUsedBy($user)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'You have already used this discount code.',
                ]);
            }
        }
        
        // Check product applicability if provided
        if ($request->filled('product_id')) {
            $product = StripeProduct::find($request->product_id);
            if ($product && $product->stripe_product_id && !$discountCode->appliesToProduct($product->stripe_product_id)) {
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
        if (!empty($data['max_uses'])) {
            $couponData['max_redemptions'] = $data['max_uses'];
        }
        
        // Set validity period
        if (!empty($data['valid_until'])) {
            $couponData['redeem_by'] = Carbon::parse($data['valid_until'])->timestamp;
        }
        
        return $this->stripeService->createCoupon($couponData);
    }
}
