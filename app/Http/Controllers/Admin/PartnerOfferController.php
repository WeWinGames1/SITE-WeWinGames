<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\PartnerOffer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PartnerOfferController extends Controller
{
    public function index()
    {
        $offers = PartnerOffer::with('discountCode')
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('admin/PartnerOffers/Index', [
            'offers' => $offers,
        ]);
    }

    public function create()
    {
        $discountCodes = DiscountCode::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'description']);

        return Inertia::render('admin/PartnerOffers/Create', [
            'discountCodes' => $discountCodes,
            'types' => ['sportsbook', 'prediction', 'casino'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:partner_offers,slug',
            'logo' => 'nullable|string|max:500',
            'offer_text' => 'required|string|max:500',
            'description' => 'nullable|string',
            'external_url' => 'required|url|max:1000',
            'internal_url' => 'nullable|string|max:500',
            'discount_code_id' => 'nullable|exists:discount_codes,id',
            'type' => 'required|in:sportsbook,prediction,casino',
            'badge_text' => 'nullable|string|max:50',
            'show_on_picks' => 'boolean',
            'show_on_offers_page' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (PartnerOffer::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug.'-'.$counter;
            $counter++;
        }

        PartnerOffer::create($validated);

        return redirect()->route('admin.partner-offers.index')
            ->with('success', 'Partner offer created successfully.');
    }

    public function edit(PartnerOffer $partnerOffer)
    {
        $discountCodes = DiscountCode::where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'description']);

        return Inertia::render('admin/PartnerOffers/Edit', [
            'offer' => $partnerOffer->load('discountCode'),
            'discountCodes' => $discountCodes,
            'types' => ['sportsbook', 'prediction', 'casino'],
        ]);
    }

    public function update(Request $request, PartnerOffer $partnerOffer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:partner_offers,slug,'.$partnerOffer->id,
            'logo' => 'nullable|string|max:500',
            'offer_text' => 'required|string|max:500',
            'description' => 'nullable|string',
            'external_url' => 'required|url|max:1000',
            'internal_url' => 'nullable|string|max:500',
            'discount_code_id' => 'nullable|exists:discount_codes,id',
            'type' => 'required|in:sportsbook,prediction,casino',
            'badge_text' => 'nullable|string|max:50',
            'show_on_picks' => 'boolean',
            'show_on_offers_page' => 'boolean',
            'sort_order' => 'integer|min:0',
            'is_active' => 'boolean',
        ]);

        // Generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);

            // Ensure unique slug (excluding current record)
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (PartnerOffer::where('slug', $validated['slug'])->where('id', '!=', $partnerOffer->id)->exists()) {
                $validated['slug'] = $baseSlug.'-'.$counter;
                $counter++;
            }
        }

        $partnerOffer->update($validated);

        return redirect()->route('admin.partner-offers.index')
            ->with('success', 'Partner offer updated successfully.');
    }

    public function destroy(PartnerOffer $partnerOffer)
    {
        $partnerOffer->delete();

        return redirect()->route('admin.partner-offers.index')
            ->with('success', 'Partner offer deleted successfully.');
    }

    public function toggleActive(PartnerOffer $partnerOffer)
    {
        $partnerOffer->update(['is_active' => ! $partnerOffer->is_active]);

        return back()->with('success', 'Partner offer status updated.');
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'offers' => 'required|array',
            'offers.*.id' => 'required|exists:partner_offers,id',
            'offers.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['offers'] as $item) {
            PartnerOffer::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Sort order updated.');
    }

    public function trackClick(Request $request, PartnerOffer $partnerOffer)
    {
        // Validate source_page input
        $validSources = ['picks_sidebar', 'offers_page', 'homepage', 'checkout', 'unknown'];
        $source = $request->input('source', 'unknown');
        if (! in_array($source, $validSources)) {
            $source = 'unknown';
        }

        // Increment simple counter
        $partnerOffer->incrementClickCount();

        // Log detailed click for analytics
        $partnerOffer->clicks()->create([
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'user_agent' => substr($request->userAgent() ?? '', 0, 500),
            'referer' => substr($request->header('referer') ?? '', 0, 500),
            'source_page' => $source,
        ]);

        return response()->json(['success' => true]);
    }
}
