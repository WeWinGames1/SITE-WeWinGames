<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SportPreference;
use App\Models\Sport;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SportPreferenceController extends Controller
{
    /**
     * Display the sport preferences page.
     */
    public function index()
    {
        $preferences = SportPreference::orderBy('priority', 'asc')->get();
        
        // Get all unique sports from bets table
        $allSports = \App\Models\Bet::distinct()
            ->pluck('sport')
            ->filter()
            ->sort()
            ->values();
        
        return Inertia::render('admin/SportPreferences', [
            'preferences' => $preferences,
            'availableSports' => $allSports,
        ]);
    }

    /**
     * Update sport preferences.
     */
    public function update(Request $request)
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.sport_name' => 'required|string',
            'preferences.*.priority' => 'required|integer|min:0',
            'preferences.*.is_active' => 'required|boolean',
        ]);

        // Clear existing preferences
        SportPreference::truncate();

        // Insert new preferences
        foreach ($request->preferences as $preference) {
            SportPreference::create([
                'sport_name' => $preference['sport_name'],
                'priority' => $preference['priority'],
                'is_active' => $preference['is_active'],
            ]);
        }

        return redirect()->back()->with('success', 'Sport preferences updated successfully!');
    }

    /**
     * Add a new sport preference.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sport_name' => 'required|string|unique:sport_preferences,sport_name',
            'priority' => 'required|integer|min:0',
            'is_active' => 'required|boolean',
        ]);

        SportPreference::create([
            'sport_name' => $request->sport_name,
            'priority' => $request->priority,
            'is_active' => $request->is_active,
        ]);

        return redirect()->back()->with('success', 'Sport preference added successfully!');
    }

    /**
     * Remove a sport preference.
     */
    public function destroy(SportPreference $sportPreference)
    {
        $sportPreference->delete();

        return redirect()->back()->with('success', 'Sport preference removed successfully!');
    }
}