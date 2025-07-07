<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use App\Services\SimpleCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the testimonials.
     */
    public function index()
    {
        $testimonials = Testimonial::ordered()->paginate(10);

        return Inertia::render('admin/Testimonials/Index', [
            'testimonials' => $testimonials,
        ]);
    }

    /**
     * Show the form for creating a new testimonial.
     */
    public function create()
    {
        return Inertia::render('admin/Testimonials/Create');
    }

    /**
     * Store a newly created testimonial in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'stars' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
            'review_date' => 'required|date',
            'published' => 'boolean',
            'sort_order' => 'integer',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('testimonials', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        Testimonial::create($validated);

        // Clear testimonials cache
        SimpleCacheService::invalidateRelated('testimonial');

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial created successfully.');
    }

    /**
     * Show the form for editing the specified testimonial.
     */
    public function edit(Testimonial $testimonial)
    {
        return Inertia::render('admin/Testimonials/Edit', [
            'testimonial' => $testimonial,
        ]);
    }

    /**
     * Update the specified testimonial in storage.
     */
    public function update(Request $request, Testimonial $testimonial)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'stars' => 'required|integer|min:1|max:5',
            'review' => 'required|string',
            'review_date' => 'required|date',
            'published' => 'boolean',
            'sort_order' => 'integer',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($testimonial->image && file_exists(public_path($testimonial->image))) {
                unlink(public_path($testimonial->image));
            }

            $path = $request->file('image')->store('testimonials', 'public');
            $validated['image'] = '/storage/'.$path;
        }

        $testimonial->update($validated);

        // Clear testimonials cache
        SimpleCacheService::invalidateRelated('testimonial');

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial updated successfully.');
    }

    /**
     * Remove the specified testimonial from storage.
     */
    public function destroy(Testimonial $testimonial)
    {
        // Delete image if exists
        if ($testimonial->image && file_exists(public_path($testimonial->image))) {
            unlink(public_path($testimonial->image));
        }

        $testimonial->delete();

        // Clear testimonials cache
        SimpleCacheService::invalidateRelated('testimonial');

        return redirect()->route('admin.testimonials.index')
            ->with('success', 'Testimonial deleted successfully.');
    }

    /**
     * Update the sort order of testimonials
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'testimonials' => 'required|array',
            'testimonials.*.id' => 'required|exists:testimonials,id',
            'testimonials.*.sort_order' => 'required|integer',
        ]);

        foreach ($validated['testimonials'] as $item) {
            Testimonial::where('id', $item['id'])
                ->update(['sort_order' => $item['sort_order']]);
        }

        // Clear testimonials cache
        SimpleCacheService::invalidateRelated('testimonial');

        return response()->json(['message' => 'Order updated successfully']);
    }
}
