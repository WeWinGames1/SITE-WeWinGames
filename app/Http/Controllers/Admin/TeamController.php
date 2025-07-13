<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Sport;
use App\Models\League;
use App\Models\TeamAlias;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Team::with(['sport', 'league', 'aliases'])
            ->withCount(['betsAsTeamOne', 'betsAsTeamTwo']);

        // Filter by sport
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }

        // Filter by league
        if ($request->filled('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('aliases', function ($q) use ($search) {
                        $q->where('alias', 'like', "%{$search}%");
                    });
            });
        }

        $teams = $query->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('admin/Teams/Index', [
            'teams' => $teams,
            'sports' => Sport::where('is_active', true)->orderBy('name')->get(),
            'leagues' => League::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['sport_id', 'league_id', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $sports = Sport::where('is_active', true)->orderBy('name')->get();
        $leagues = League::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('admin/Teams/Create', [
            'sports' => $sports,
            'leagues' => $leagues,
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
            'league_id' => 'nullable|exists:leagues,id',
            'abbreviation' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:10240',
            'is_active' => 'boolean',
            'aliases' => 'nullable|array',
            'aliases.*' => 'string|max:255',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $validated['logo_url'] = $request->file('logo')->store('team-logos', 'public');
        }

        // Create team
        $team = Team::create($validated);

        // Create aliases
        if (!empty($validated['aliases'])) {
            foreach ($validated['aliases'] as $alias) {
                if (!empty(trim($alias))) {
                    $team->aliases()->create(['alias' => trim($alias)]);
                }
            }
        }

        return redirect()->route('admin.teams.index')
            ->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team)
    {
        return redirect()->route('admin.teams.edit', $team);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team)
    {
        $team->load(['sport', 'league', 'aliases']);
        $sports = Sport::where('is_active', true)->orderBy('name')->get();
        $leagues = League::where('is_active', true)->orderBy('name')->get();

        return Inertia::render('admin/Teams/Edit', [
            'team' => $team,
            'sports' => $sports,
            'leagues' => $leagues,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sport_id' => 'required|exists:sports,id',
            'league_id' => 'nullable|exists:leagues,id',
            'abbreviation' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:10240',
            'is_active' => 'boolean',
            'aliases' => 'nullable|array',
            'aliases.*' => 'string|max:255',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($team->logo_url && Storage::disk('public')->exists($team->logo_url)) {
                Storage::disk('public')->delete($team->logo_url);
            }
            $validated['logo_url'] = $request->file('logo')->store('team-logos', 'public');
        }

        // Update team
        $team->update($validated);

        // Update aliases
        $team->aliases()->delete();
        if (!empty($validated['aliases'])) {
            foreach ($validated['aliases'] as $alias) {
                if (!empty(trim($alias))) {
                    $team->aliases()->create(['alias' => trim($alias)]);
                }
            }
        }

        return redirect()->route('admin.teams.index')
            ->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team)
    {
        // Delete logo if exists
        if ($team->logo_url && Storage::disk('public')->exists($team->logo_url)) {
            Storage::disk('public')->delete($team->logo_url);
        }

        $team->delete();

        return redirect()->route('admin.teams.index')
            ->with('success', 'Team deleted successfully.');
    }

    /**
     * Get leagues for a specific sport
     */
    public function getLeaguesBySport(Sport $sport)
    {
        $leagues = $sport->leagues()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($leagues);
    }
}
