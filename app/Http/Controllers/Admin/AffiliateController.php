<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AffiliateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $affiliates = Affiliate::query()
            ->withCount(['users', 'activeUsers'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/Affiliates/Index', [
            'affiliates' => $affiliates,
            'filters' => $request->only(['search', 'status']),
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('admin/Affiliates/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255|unique:affiliates,code',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = Affiliate::generateUniqueCode();
        }

        $affiliate = Affiliate::create($validated);

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Affiliate $affiliate)
    {
        $affiliate->loadCount(['users', 'activeUsers']);

        $customers = $affiliate->users()
            ->with('subscriptions')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('admin/Affiliates/Show', [
            'affiliate' => $affiliate,
            'customers' => $customers,
            'appUrl' => config('app.url'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Affiliate $affiliate): Response
    {
        return Inertia::render('admin/Affiliates/Edit', [
            'affiliate' => $affiliate,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Affiliate $affiliate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:affiliates,code,'.$affiliate->id,
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'commission_rate' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $affiliate->update($validated);

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Affiliate $affiliate)
    {
        if ($affiliate->users()->exists()) {
            return back()->with('error', 'Cannot delete affiliate with existing customers.');
        }

        $affiliate->delete();

        return redirect()->route('admin.affiliates.index')
            ->with('success', 'Affiliate deleted successfully.');
    }

    /**
     * Get customers for an affiliate (for modal)
     */
    public function customers(Affiliate $affiliate)
    {
        $customers = $affiliate->users()
            ->with(['subscriptions' => function ($query) {
                $query->where('stripe_status', 'active');
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'discord_username' => $user->discord_username,
                    'registered_at' => $user->created_at->format('M d, Y'),
                    'bound_at' => $user->affiliate_bound_at?->format('M d, Y'),
                    'bound_plan' => $user->affiliate_bound_plan,
                    'current_plan' => $user->getCurrentTier() ?? 'Free',
                    'is_active' => $user->hasActiveSubscription(),
                ];
            });

        return response()->json([
            'customers' => $customers,
            'total' => $customers->count(),
            'active' => $customers->where('is_active', true)->count(),
        ]);
    }
}
