<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\SimpleCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FaqController extends Controller
{
    /**
     * Display a listing of the FAQs.
     */
    public function index()
    {
        $faqs = Faq::ordered()->paginate(20);
        $categories = Faq::getCategories();

        return Inertia::render('admin/Faqs/Index', [
            'faqs' => $faqs,
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new FAQ.
     */
    public function create()
    {
        $categories = Faq::getCategories();

        return Inertia::render('admin/Faqs/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created FAQ in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        Faq::create($validated);

        // Clear FAQ cache
        SimpleCacheService::invalidateRelated('faq');

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ created successfully.');
    }

    /**
     * Show the form for editing the specified FAQ.
     */
    public function edit(Faq $faq)
    {
        $categories = Faq::getCategories();

        return Inertia::render('admin/Faqs/Edit', [
            'faq' => $faq,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified FAQ in storage.
     */
    public function update(Request $request, Faq $faq)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string',
            'category' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $faq->update($validated);

        // Clear FAQ cache
        SimpleCacheService::invalidateRelated('faq');

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ updated successfully.');
    }

    /**
     * Remove the specified FAQ from storage.
     */
    public function destroy(Faq $faq)
    {
        $faq->delete();

        // Clear FAQ cache
        SimpleCacheService::invalidateRelated('faq');

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ deleted successfully.');
    }

    /**
     * Toggle the active status of a FAQ
     */
    public function toggle(Faq $faq)
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        // Clear FAQ cache
        SimpleCacheService::invalidateRelated('faq');

        return redirect()->route('admin.faqs.index')
            ->with('success', 'FAQ status updated successfully.');
    }

    /**
     * Update the sort order of FAQs
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'faqs' => 'required|array',
            'faqs.*.id' => 'required|exists:faqs,id',
            'faqs.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['faqs'] as $item) {
            Faq::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        // Clear FAQ cache
        SimpleCacheService::invalidateRelated('faq');

        return response()->json(['message' => 'Order updated successfully']);
    }
}
