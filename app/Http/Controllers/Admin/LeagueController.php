<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeagueController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = League::with('sport')
            ->withCount('teams');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by sport
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }

        // Filter by status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active === 'true');
        }

        $leagues = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('admin/Leagues/Index', [
            'leagues' => $leagues,
            'sports' => Sport::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'sport_id', 'is_active']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Leagues/Create', [
            'sports' => $sports,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sport_id' => 'required|exists:sports,id',
            'abbreviation' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Check for uniqueness within the sport
        $exists = League::where('sport_id', $validated['sport_id'])
            ->where('name', $validated['name'])
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'A league with this name already exists in the selected sport.',
            ])->withInput();
        }

        League::create($validated);

        return redirect()->route('admin.leagues.index')
            ->with('success', 'League created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(League $league)
    {
        return redirect()->route('admin.leagues.edit', $league);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(League $league)
    {
        $league->loadCount('teams');
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Leagues/Edit', [
            'league' => $league,
            'sports' => $sports,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, League $league)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sport_id' => 'required|exists:sports,id',
            'abbreviation' => 'nullable|string|max:10',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Check for uniqueness within the sport
        $exists = League::where('sport_id', $validated['sport_id'])
            ->where('name', $validated['name'])
            ->where('id', '!=', $league->id)
            ->exists();

        if ($exists) {
            return back()->withErrors([
                'name' => 'A league with this name already exists in the selected sport.',
            ])->withInput();
        }

        $league->update($validated);

        return redirect()->route('admin.leagues.index')
            ->with('success', 'League updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(League $league)
    {
        $league->delete();

        return redirect()->route('admin.leagues.index')
            ->with('success', 'League deleted successfully.');
    }
}
