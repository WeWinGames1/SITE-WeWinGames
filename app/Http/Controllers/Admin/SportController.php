<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Sport::withCount(['leagues', 'teams']);

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        $sports = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('admin/Sports/Index', [
            'sports' => $sports,
            'filters' => $request->only(['search', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('admin/Sports/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sports',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Sport::create($validated);

        return redirect()->route('admin.sports.index')
            ->with('success', 'Sport created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Sport $sport)
    {
        return redirect()->route('admin.sports.edit', $sport);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sport $sport)
    {
        $sport->loadCount(['leagues', 'teams']);

        return Inertia::render('admin/Sports/Edit', [
            'sport' => $sport,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Sport $sport)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sports,name,' . $sport->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $sport->update($validated);

        return redirect()->route('admin.sports.index')
            ->with('success', 'Sport updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sport $sport)
    {
        $sport->delete();

        return redirect()->route('admin.sports.index')
            ->with('success', 'Sport deleted successfully.');
    }
}
