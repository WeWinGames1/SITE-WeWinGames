<?php

namespace App\Traits;

use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesTeamsWithUniqueSlug
{
    /**
     * Find or create a team with proper duplicate slug handling
     */
    protected function findOrCreateTeam(string $teamName, ?int $sportId = null, ?int $leagueId = null, bool $isActive = true): Team
    {
        // First try to find by name and sport
        $query = Team::where('name', $teamName);

        if ($sportId !== null) {
            $query->where('sport_id', $sportId);
        }

        $team = $query->first();

        if ($team) {
            return $team;
        }

        // Use a lock to prevent race conditions
        return DB::transaction(function () use ($teamName, $sportId, $leagueId, $isActive) {
            // Double-check within transaction
            $query = Team::where('name', $teamName);

            if ($sportId !== null) {
                $query->where('sport_id', $sportId);
            }

            $team = $query->lockForUpdate()->first();

            if ($team) {
                return $team;
            }

            // Generate a unique slug
            $baseSlug = Str::slug($teamName);
            $slug = $baseSlug;
            $counter = 1;

            while (Team::where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$counter;
                $counter++;
            }

            try {
                // Create the team with the unique slug
                return Team::create([
                    'name' => $teamName,
                    'sport_id' => $sportId,
                    'league_id' => $leagueId,
                    'slug' => $slug,
                    'is_active' => $isActive,
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // If we still get a duplicate error, it means another process created it
                // Try to find it again
                if (str_contains($e->getMessage(), 'Duplicate entry') ||
                    str_contains($e->getMessage(), 'UNIQUE constraint failed')) {

                    // Try by slug first (in case that's what conflicted)
                    $team = Team::where('slug', $baseSlug)->first();
                    if ($team && $team->name === $teamName && ($sportId === null || $team->sport_id === $sportId)) {
                        return $team;
                    }

                    // Then try by name and sport
                    $query = Team::where('name', $teamName);
                    if ($sportId !== null) {
                        $query->where('sport_id', $sportId);
                    }

                    $team = $query->first();

                    if ($team) {
                        return $team;
                    }
                }

                // If we can't find it, throw the original exception
                throw $e;
            }
        });
    }
}
