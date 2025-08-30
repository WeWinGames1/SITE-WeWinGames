<?php

namespace App\Console\Commands;

use App\Models\Bet;
use App\Models\Team;
use Illuminate\Console\Command;

class CleanupOddsTeams extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'teams:cleanup-odds 
                           {--dry-run : Run without making changes}
                           {--limit=0 : Limit number of teams to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove teams that have odds values as names and unlink associated bets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info('Searching for teams with odds values as names...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find teams with numeric/odds names
        $query = Team::where('name', 'REGEXP', '^[+-]?[0-9]+\.?[0-9]*$');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $badTeams = $query->get();
        $totalBadTeams = Team::where('name', 'REGEXP', '^[+-]?[0-9]+\.?[0-9]*$')->count();

        $this->info("Found {$totalBadTeams} teams with odds values as names");

        if ($badTeams->isEmpty()) {
            $this->info('No teams to clean up!');

            return Command::SUCCESS;
        }

        $stats = [
            'teams_deleted' => 0,
            'bets_updated' => 0,
            'team_one_unlinked' => 0,
            'team_two_unlinked' => 0,
        ];

        $bar = $this->output->createProgressBar($badTeams->count());
        $bar->start();

        foreach ($badTeams as $team) {
            // Find bets linked to this bad team
            $betsAsTeamOne = Bet::where('team_one_id', $team->id)->count();
            $betsAsTeamTwo = Bet::where('team_two_id', $team->id)->count();

            if (! $dryRun) {
                // Unlink bets from this bad team
                if ($betsAsTeamOne > 0) {
                    Bet::where('team_one_id', $team->id)->update(['team_one_id' => null]);
                    $stats['team_one_unlinked'] += $betsAsTeamOne;
                    $stats['bets_updated'] += $betsAsTeamOne;
                }

                if ($betsAsTeamTwo > 0) {
                    Bet::where('team_two_id', $team->id)->update(['team_two_id' => null]);
                    $stats['team_two_unlinked'] += $betsAsTeamTwo;
                    $stats['bets_updated'] += $betsAsTeamTwo;
                }

                // Delete the bad team
                $team->delete();
                $stats['teams_deleted']++;
            } else {
                // Dry run - just count
                $stats['team_one_unlinked'] += $betsAsTeamOne;
                $stats['team_two_unlinked'] += $betsAsTeamTwo;
                $stats['bets_updated'] += $betsAsTeamOne + $betsAsTeamTwo;
                $stats['teams_deleted']++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        // Display results
        $this->info('Cleanup complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Teams Deleted', $stats['teams_deleted']],
                ['Bets Updated', $stats['bets_updated']],
                ['Team One Unlinked', $stats['team_one_unlinked']],
                ['Team Two Unlinked', $stats['team_two_unlinked']],
            ]
        );

        if ($stats['bets_updated'] > 0) {
            $this->warn("Note: {$stats['bets_updated']} bets were unlinked from bad teams.");
            $this->warn("You'll need to re-run the mapping commands to link them to correct teams:");
            $this->info('  php artisan bets:map-teams');
        }

        return Command::SUCCESS;
    }
}
