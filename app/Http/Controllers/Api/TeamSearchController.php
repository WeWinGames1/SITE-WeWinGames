<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeamSearchController extends Controller
{
    /**
     * Search teams for Select2 dropdown
     */
    public function search(Request $request)
    {
        $query = Team::with(['sport', 'league']);
        
        // Search by name or alias
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('abbreviation', 'LIKE', "%{$search}%")
                    ->orWhereHas('aliases', function ($q) use ($search) {
                        $q->where('alias', 'LIKE', "%{$search}%");
                    });
            });
        }
        
        // Filter by sport
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        
        // Filter by league
        if ($request->filled('league_id')) {
            $query->where('league_id', $request->league_id);
        }
        
        // Only active teams
        $query->where('is_active', true);
        
        // Limit results for performance (increased to show more teams)
        $teams = $query->orderBy('name')
            ->limit(100)
            ->get();
        
        // Format for Select2
        $results = $teams->map(function ($team) {
            return [
                'id' => $team->id,
                'text' => $team->name,
                'name' => $team->name,
                'abbreviation' => $team->abbreviation,
                'sport' => $team->sport ? $team->sport->name : null,
                'league' => $team->league ? $team->league->name : null,
                'logo_url' => $team->logo_url ? Storage::url($team->logo_url) : null,
                'has_logo' => !empty($team->logo_url),
            ];
        });
        
        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => false // For simplicity, not implementing pagination
            ]
        ]);
    }
    
    /**
     * Update team logo via AJAX
     */
    public function updateLogo(Request $request, Team $team)
    {
        $request->validate([
            'logo' => 'required|image|max:10240',
        ]);
        
        // Delete old logo if exists
        if ($team->logo_url && Storage::disk('public')->exists($team->logo_url)) {
            Storage::disk('public')->delete($team->logo_url);
        }
        
        // Store new logo
        $logoPath = $request->file('logo')->store('team-logos', 'public');
        
        $team->update([
            'logo_url' => $logoPath,
        ]);
        
        return response()->json([
            'success' => true,
            'logo_url' => Storage::url($logoPath),
        ]);
    }
}