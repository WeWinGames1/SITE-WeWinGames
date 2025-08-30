<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StandardizeMembershipLevels extends Command
{
    protected $signature = 'bets:standardize-membership {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Standardize membership level capitalization in the bets table';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('Analyzing membership level data...');

        // Get counts before standardization
        $beforeCounts = DB::table('bets')
            ->select('membership', DB::raw('COUNT(*) as count'))
            ->groupBy('membership')
            ->orderBy('membership')
            ->get();

        $this->table(['Membership (Current)', 'Count'],
            $beforeCounts->map(fn ($row) => [$row->membership ?? 'NULL', $row->count])->toArray()
        );

        // Define the mapping for standardization
        $standardMappings = [
            'bronze' => 'Bronze',
            'Bronze' => 'Bronze',
            'BRONZE' => 'Bronze',
            'silver' => 'Silver',
            'Silver' => 'Silver',
            'SILVER' => 'Silver',
            'gold' => 'Gold',
            'Gold' => 'Gold',
            'GOLD' => 'Gold',
            'platinum' => 'Platinum',
            'Platinum' => 'Platinum',
            'PLATINUM' => 'Platinum',
        ];

        if ($dryRun) {
            $this->info("\n--- DRY RUN MODE ---");
            $this->info('The following updates would be made:');

            foreach ($standardMappings as $from => $to) {
                if ($from !== $to) {
                    $count = DB::table('bets')->where('membership', $from)->count();
                    if ($count > 0) {
                        $this->line("Update {$count} records from '{$from}' to '{$to}'");
                    }
                }
            }
        } else {
            $this->info("\n--- UPDATING DATABASE ---");
            $totalUpdated = 0;

            foreach ($standardMappings as $from => $to) {
                if ($from !== $to) {
                    $updated = DB::table('bets')
                        ->where('membership', $from)
                        ->update(['membership' => $to]);

                    if ($updated > 0) {
                        $this->line("Updated {$updated} records from '{$from}' to '{$to}'");
                        $totalUpdated += $updated;
                    }
                }
            }

            $this->info("Total records updated: {$totalUpdated}");

            // Show counts after standardization
            $this->info("\n--- AFTER STANDARDIZATION ---");
            $afterCounts = DB::table('bets')
                ->select('membership', DB::raw('COUNT(*) as count'))
                ->groupBy('membership')
                ->orderBy('membership')
                ->get();

            $this->table(['Membership (Standardized)', 'Count'],
                $afterCounts->map(fn ($row) => [$row->membership ?? 'NULL', $row->count])->toArray()
            );
        }

        if ($dryRun) {
            $this->warn("\nThis was a dry run. No changes were made to the database.");
            $this->info('Run the command without --dry-run to apply the changes.');
        } else {
            $this->success("\nMembership levels have been standardized!");
        }
    }
}
