<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FixBetDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bets:fix-dates {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix betting dates that have 2-digit years (24, 25) to 4-digit years (2024, 2025)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $this->info('Checking for bets with incorrect year formats...');

        // Get counts of problematic years
        $problemYears = DB::table('bets')
            ->selectRaw('YEAR(betting_date) as year, COUNT(*) as count')
            ->whereRaw('YEAR(betting_date) < 100')
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        if ($problemYears->isEmpty()) {
            $this->info('No bets with incorrect year formats found!');
            return Command::SUCCESS;
        }

        $this->info('Found bets with 2-digit years:');
        foreach ($problemYears as $row) {
            $this->info("  Year {$row->year}: {$row->count} bets");
        }

        if (!$dryRun) {
            $this->info('Fixing dates...');
            
            // Fix each problematic year
            foreach ($problemYears as $row) {
                $oldYear = $row->year;
                $newYear = 2000 + $oldYear; // Convert 24 to 2024, 25 to 2025, etc.
                
                $this->info("Converting year {$oldYear} to {$newYear}...");
                
                // Update betting_date
                $affected = DB::update("
                    UPDATE bets 
                    SET betting_date = DATE_ADD(betting_date, INTERVAL ? YEAR)
                    WHERE YEAR(betting_date) = ?
                ", [$newYear - $oldYear, $oldYear]);
                
                $this->info("  Updated {$affected} betting_date records");
                
                // Also update game_date if it exists and has the same issue
                $affectedGame = DB::update("
                    UPDATE bets 
                    SET game_date = DATE_ADD(game_date, INTERVAL ? YEAR)
                    WHERE game_date IS NOT NULL AND YEAR(game_date) = ?
                ", [$newYear - $oldYear, $oldYear]);
                
                if ($affectedGame > 0) {
                    $this->info("  Updated {$affectedGame} game_date records");
                }
            }
            
            $this->info('Date fixes complete!');
            
            // Show new year distribution
            $this->info('New year distribution:');
            $newDistribution = DB::table('bets')
                ->selectRaw('YEAR(betting_date) as year, COUNT(*) as count')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->get();
                
            foreach ($newDistribution as $row) {
                $this->info("  Year {$row->year}: {$row->count} bets");
            }
        } else {
            $this->info('Dry run complete. Run without --dry-run to apply fixes.');
        }

        return Command::SUCCESS;
    }
}