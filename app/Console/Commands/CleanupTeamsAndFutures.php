<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Bet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTeamsAndFutures extends Command
{
    protected $signature = 'teams:cleanup-futures {--fix : Actually perform the cleanup}';
    protected $description = 'Clean up futures/championship entries from teams table and fix remaining duplicates';

    // Patterns that indicate futures/championship bets, not actual teams
    private $futuresPatterns = [
        'Championship',
        'Superbowl',
        'Super Bowl',
        'World Series',
        'Stanley Cup',
        'NBA Finals',
        'World Cup',
        'Champions League',
        'Masters Tournament',
        'Open Championship',
        'PGA Championship',
        'Ryder Cup',
        'Olympics',
        'MVP',
        'Rookie of the Year',
        'Coach of the Year',
        'Defensive Player',
        'Offensive Player',
        'Winner',
        'Title',
        'Season',
        '2023',
        '2024',
        '2025',
        'Future',
        'Outright',
        'To Win',
        'Top ',
        'Division',
        'Conference',
        'Playoffs',
    ];

    public function handle()
    {
        $this->info('Analyzing Teams for Cleanup...');
        $this->newLine();
        
        // 1. Find futures entries in teams table
        $this->analyzeFuturesInTeams();
        
        // 2. Find remaining duplicates
        $this->analyzeRemainingDuplicates();
        
        // 3. Find teams with same name but different leagues
        $this->analyzeTeamsAcrossLeagues();
        
        if ($this->option('fix')) {
            if ($this->confirm('Do you want to proceed with cleanup?')) {
                $this->performCleanup();
            }
        } else {
            $this->newLine();
            $this->warn('Run with --fix option to perform cleanup');
        }
        
        return Command::SUCCESS;
    }
    
    private function analyzeFuturesInTeams()
    {
        $this->info('1. Futures/Championship Entries in Teams Table');
        $this->info('==============================================');
        
        $query = Team::query();
        
        // Build query to find futures
        foreach ($this->futuresPatterns as $pattern) {
            $query->orWhere('name', 'LIKE', "%{$pattern}%");
        }
        
        $futuresTeams = $query->get();
        
        $this->line("Found {$futuresTeams->count()} potential futures entries:");
        
        if ($futuresTeams->count() > 0) {
            $sample = $futuresTeams->take(20);
            $tableData = [];
            foreach ($sample as $team) {
                $sport = \App\Models\Sport::find($team->sport_id);
                $league = \App\Models\League::find($team->league_id);
                
                // Check how many bets reference this
                $betCount = Bet::where('team_one_id', $team->id)
                    ->orWhere('team_two_id', $team->id)
                    ->count();
                
                $tableData[] = [
                    $team->id,
                    substr($team->name, 0, 40),
                    $sport ? $sport->name : 'N/A',
                    $league ? $league->name : 'N/A',
                    $betCount
                ];
            }
            
            $this->table(['ID', 'Name', 'Sport', 'League', 'Bet Count'], $tableData);
            
            if ($futuresTeams->count() > 20) {
                $this->line("... and " . ($futuresTeams->count() - 20) . " more");
            }
        }
        
        $this->newLine();
    }
    
    private function analyzeRemainingDuplicates()
    {
        $this->info('2. Remaining Duplicate Teams');
        $this->info('============================');
        
        // Find teams with same name but different IDs
        $duplicates = DB::table('teams as t1')
            ->join('teams as t2', function($join) {
                $join->on('t1.name', '=', 't2.name')
                    ->on('t1.sport_id', '=', 't2.sport_id')
                    ->whereRaw('t1.id < t2.id');
            })
            ->select('t1.name', 't1.sport_id', DB::raw('COUNT(DISTINCT t2.id) + 1 as count'))
            ->groupBy('t1.name', 't1.sport_id')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();
        
        if ($duplicates->isEmpty()) {
            $this->info('✅ No duplicate teams found within same sport');
        } else {
            $tableData = [];
            foreach ($duplicates as $dup) {
                $sport = \App\Models\Sport::find($dup->sport_id);
                $tableData[] = [
                    substr($dup->name, 0, 40),
                    $sport ? $sport->name : 'N/A',
                    $dup->count
                ];
            }
            $this->table(['Team Name', 'Sport', 'Count'], $tableData);
        }
        
        $this->newLine();
    }
    
    private function analyzeTeamsAcrossLeagues()
    {
        $this->info('3. Teams Split Across Multiple Entries');
        $this->info('======================================');
        
        // Find teams with same name and sport but different league assignments
        $splitTeams = Team::select('name', 'sport_id', DB::raw('COUNT(*) as count'))
            ->groupBy('name', 'sport_id')
            ->having('count', '>', 1)
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
        
        foreach ($splitTeams as $split) {
            $teams = Team::where('name', $split->name)
                ->where('sport_id', $split->sport_id)
                ->with(['league', 'sport'])
                ->get();
            
            $this->line("Team: {$split->name}");
            $tableData = [];
            foreach ($teams as $team) {
                $betCount = Bet::where('team_one_id', $team->id)
                    ->orWhere('team_two_id', $team->id)
                    ->count();
                    
                $tableData[] = [
                    $team->id,
                    $team->league ? $team->league->name : '(No League)',
                    $betCount
                ];
            }
            $this->table(['ID', 'League', 'Bet Count'], $tableData);
            $this->newLine();
        }
    }
    
    private function performCleanup()
    {
        $this->info('Performing Cleanup...');
        $this->newLine();
        
        DB::transaction(function () {
            // 1. Remove futures from teams table
            $this->removeFuturesEntries();
            
            // 2. Merge remaining duplicates
            $this->mergeRemainingDuplicates();
            
            // 3. Clear caches
            \Artisan::call('cache:clear');
        });
        
        $this->info('✅ Cleanup completed!');
    }
    
    private function removeFuturesEntries()
    {
        $this->info('Removing futures entries from teams...');
        
        $query = Team::query();
        
        // Build query to find futures
        foreach ($this->futuresPatterns as $pattern) {
            $query->orWhere('name', 'LIKE', "%{$pattern}%");
        }
        
        $futuresTeams = $query->get();
        $removedCount = 0;
        
        foreach ($futuresTeams as $team) {
            // Check if this "team" is referenced in any bets
            $betCount = Bet::where('team_one_id', $team->id)
                ->orWhere('team_two_id', $team->id)
                ->count();
            
            if ($betCount > 0) {
                // Update bets to remove the reference
                Bet::where('team_one_id', $team->id)->update(['team_one_id' => null]);
                Bet::where('team_two_id', $team->id)->update(['team_two_id' => null]);
            }
            
            // Delete the futures entry
            $team->delete();
            $removedCount++;
        }
        
        $this->info("✅ Removed {$removedCount} futures entries");
    }
    
    private function mergeRemainingDuplicates()
    {
        $this->info('Merging remaining duplicate teams...');
        
        // Get all groups of duplicates
        $duplicateGroups = Team::select('name', 'sport_id', DB::raw('MIN(id) as keep_id'))
            ->groupBy('name', 'sport_id')
            ->having(DB::raw('COUNT(*)'), '>', 1)
            ->get();
        
        $mergedCount = 0;
        
        foreach ($duplicateGroups as $group) {
            // Get all teams in this group
            $teams = Team::where('name', $group->name)
                ->where('sport_id', $group->sport_id)
                ->orderBy('id')
                ->get();
            
            $keepTeam = $teams->first();
            $mergeTeams = $teams->slice(1);
            
            // Prefer the team with a league assignment
            foreach ($teams as $team) {
                if ($team->league_id && !$keepTeam->league_id) {
                    $keepTeam = $team;
                    break;
                }
            }
            
            // Merge all others into the keep team
            foreach ($teams as $team) {
                if ($team->id === $keepTeam->id) continue;
                
                // Update all bet references
                Bet::where('team_one_id', $team->id)->update(['team_one_id' => $keepTeam->id]);
                Bet::where('team_two_id', $team->id)->update(['team_two_id' => $keepTeam->id]);
                
                // Update parlay references
                DB::table('bet_teams')->where('team_id', $team->id)->update(['team_id' => $keepTeam->id]);
                
                // Delete the duplicate
                $team->delete();
                $mergedCount++;
            }
        }
        
        $this->info("✅ Merged {$mergedCount} duplicate teams");
    }
}