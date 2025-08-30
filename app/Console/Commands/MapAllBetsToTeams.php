<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MapAllBetsToTeams extends Command
{
    protected $signature = 'bets:map-all {--dry-run : Run without making changes}';

    protected $description = 'Run complete bet to team mapping process (regular bets and parlays)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info('========================================');
        $this->info('Complete Bet to Team Mapping Process');
        $this->info('========================================');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $this->newLine();

        // Step 1: Map regular bets
        $this->info('Step 1: Mapping regular bets to teams...');
        $this->info('----------------------------------------');

        $exitCode = Artisan::call('bets:map-teams', [
            '--dry-run' => $dryRun,
        ]);

        $this->line(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('Error mapping regular bets. Process aborted.');

            return Command::FAILURE;
        }

        $this->newLine(2);

        // Step 2: Map parlay bets
        $this->info('Step 2: Mapping parlay bets to teams...');
        $this->info('----------------------------------------');

        $exitCode = Artisan::call('parlays:map', [
            '--dry-run' => $dryRun,
        ]);

        $this->line(Artisan::output());

        if ($exitCode !== 0) {
            $this->error('Error mapping parlay bets.');

            return Command::FAILURE;
        }

        $this->newLine(2);

        // Step 3: Generate final report
        $this->generateFinalReport($dryRun);

        return Command::SUCCESS;
    }

    private function generateFinalReport(bool $dryRun): void
    {
        $this->info('========================================');
        $this->info('Final Mapping Report');
        $this->info('========================================');

        // Get statistics from database
        $totalBets = \App\Models\Bet::count();
        $mappedBets = \App\Models\Bet::where(function ($q) {
            $q->whereNotNull('team_one_id')
                ->orWhereNotNull('team_two_id');
        })->count();

        $fullyMappedBets = \App\Models\Bet::whereNotNull('team_one_id')
            ->whereNotNull('team_two_id')
            ->where('is_parlay', false)
            ->count();

        $parlayBets = \App\Models\Bet::where('is_parlay', true)->count();
        $parlayBetsWithTeams = \App\Models\Bet::where('is_parlay', true)
            ->whereHas('parlayTeams')
            ->count();

        $totalTeams = \App\Models\Team::count();
        $totalAliases = \App\Models\TeamAlias::count();

        $this->table(
            ['Metric', 'Count', 'Notes'],
            [
                ['Total Bets in System', number_format($totalBets), ''],
                ['Bets with Team Mapping', number_format($mappedBets), 'At least one team mapped'],
                ['Fully Mapped Regular Bets', number_format($fullyMappedBets), 'Both teams mapped'],
                ['Parlay Bets', number_format($parlayBets), ''],
                ['Parlays with Teams Mapped', number_format($parlayBetsWithTeams), ''],
                ['Total Teams in Database', number_format($totalTeams), ''],
                ['Total Team Aliases', number_format($totalAliases), 'For improved matching'],
            ]
        );

        $this->newLine();

        // Calculate overall success rate
        $overallMappingRate = $totalBets > 0
            ? round(($mappedBets / $totalBets) * 100, 2)
            : 0;

        if ($overallMappingRate >= 80) {
            $this->info("✅ Excellent overall mapping rate: {$overallMappingRate}%");
        } elseif ($overallMappingRate >= 60) {
            $this->warn("⚠️  Good overall mapping rate: {$overallMappingRate}%");
        } else {
            $this->error("❌ Low overall mapping rate: {$overallMappingRate}%");
        }

        $this->newLine();

        // Recommendations
        $this->info('Recommendations:');
        $this->info('================');

        $unmappedCount = $totalBets - $mappedBets;
        if ($unmappedCount > 0) {
            $this->line("• You have {$unmappedCount} bets without team mappings.");
            $this->line('  - Review unmapped teams and create aliases');
            $this->line('  - Run: php artisan teams:report-unmatched');
        }

        if ($totalAliases < ($totalTeams * 2)) {
            $this->line('• Consider generating more team aliases for better matching');
            $this->line('  - Run: php artisan teams:generate-aliases');
        }

        if ($parlayBets > $parlayBetsWithTeams) {
            $unmappedParlays = $parlayBets - $parlayBetsWithTeams;
            $this->line("• You have {$unmappedParlays} parlays without team mappings");
            $this->line('  - Review parlay formats and team extraction logic');
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a DRY RUN. To apply changes, run without --dry-run flag:');
            $this->info('php artisan bets:map-all');
        }
    }
}
