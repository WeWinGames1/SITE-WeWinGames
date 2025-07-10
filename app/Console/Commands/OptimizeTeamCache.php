<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\Bet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OptimizeTeamCache extends Command
{
    protected $signature = 'teams:optimize-cache 
                            {--clear : Clear the cache instead of warming it}
                            {--stats : Show cache statistics}';
    
    protected $description = 'Optimize team lookup cache for better performance';

    public function handle()
    {
        if ($this->option('clear')) {
            $this->clearCache();
            return Command::SUCCESS;
        }
        
        if ($this->option('stats')) {
            $this->showStats();
            return Command::SUCCESS;
        }
        
        $this->warmCache();
        return Command::SUCCESS;
    }
    
    private function clearCache()
    {
        $this->info('Clearing team lookup cache...');
        Cache::flush(); // Note: In production, use Redis with tags
        $this->info('Team lookup cache cleared successfully.');
    }
    
    private function showStats()
    {
        $this->info('Team Cache Statistics');
        $this->info('====================');
        
        // Count teams and aliases
        $teamCount = Team::count();
        $aliasCount = DB::table('team_aliases')->count();
        $this->info("Total teams: {$teamCount}");
        $this->info("Total aliases: {$aliasCount}");
        
        // Check cache usage
        $this->info("\nMost referenced teams:");
        $topTeams = Bet::select('team_one as team', DB::raw('COUNT(*) as count'))
            ->whereNotNull('team_one')
            ->where('team_one', '!=', '')
            ->groupBy('team_one')
            ->union(
                Bet::select('team_two as team', DB::raw('COUNT(*) as count'))
                    ->whereNotNull('team_two')
                    ->where('team_two', '!=', '')
                    ->groupBy('team_two')
            )
            ->orderByDesc('count')
            ->limit(10)
            ->get();
            
        $this->table(['Team Name', 'Reference Count'], $topTeams->map(function ($item) {
            return [$item->team, $item->count];
        }));
    }
    
    private function warmCache()
    {
        $this->info('Warming team lookup cache...');
        
        // Get all unique team names from bets
        $teamNames = collect();
        
        // Get from team_one
        $teamOnes = Bet::whereNotNull('team_one')
            ->where('team_one', '!=', '')
            ->distinct()
            ->pluck('team_one');
        $teamNames = $teamNames->merge($teamOnes);
        
        // Get from team_two
        $teamTwos = Bet::whereNotNull('team_two')
            ->where('team_two', '!=', '')
            ->distinct()
            ->pluck('team_two');
        $teamNames = $teamNames->merge($teamTwos);
        
        // Get unique names
        $uniqueNames = $teamNames->unique()->filter();
        $this->info("Found {$uniqueNames->count()} unique team names in bets");
        
        // Pre-warm the cache
        $bar = $this->output->createProgressBar($uniqueNames->count());
        $bar->start();
        
        $cached = 0;
        $notFound = 0;
        
        foreach ($uniqueNames as $name) {
            // This will trigger the cache
            $team = Team::findByNameOrAlias($name);
            
            if ($team) {
                $cached++;
            } else {
                $notFound++;
            }
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->line('');
        
        $this->info("\nCache warming complete:");
        $this->info("  - Cached teams: {$cached}");
        $this->info("  - Not found: {$notFound}");
        
        // Also pre-load all teams for quick access
        $this->info("\nPre-loading team data...");
        $teams = Team::with(['sport', 'league', 'aliases'])->get();
        
        // Store in cache for quick full-list access
        Cache::put('all_teams', $teams, 3600);
        
        $this->info("Pre-loaded {$teams->count()} teams with relationships");
        $this->info("\nOptimization complete!");
    }
}