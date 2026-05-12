<?php

namespace App\Console\Commands;

use App\Services\SpringBigService;
use Illuminate\Console\Command;

class SpringBigSetupExternalGroup extends Command
{
    protected $signature = 'springbig:setup-external-group
                            {--list : List existing groups and segments}
                            {--create-group : Create external group}
                            {--create-segments : Create tier segments}
                            {--all : Create both group and segments}';

    protected $description = 'Setup SpringBig external group and segments for tier management';

    public function handle(SpringBigService $springBig): int
    {
        if (! $springBig->isEnabled()) {
            $this->error('SpringBig is disabled. Set SPRINGBIG_ENABLED=true first.');

            return 1;
        }

        // List existing
        if ($this->option('list')) {
            return $this->listExisting($springBig);
        }

        // Create group
        if ($this->option('create-group') || $this->option('all')) {
            $this->createGroup($springBig);
        }

        // Create segments
        if ($this->option('create-segments') || $this->option('all')) {
            $this->createSegments($springBig);
        }

        if (! $this->option('create-group') && ! $this->option('create-segments') && ! $this->option('all')) {
            $this->info('Usage:');
            $this->line('  --list             List existing groups and segments');
            $this->line('  --create-group     Create WeWinGames external group');
            $this->line('  --create-segments  Create tier segments (free, silver, gold, platinum, canceled)');
            $this->line('  --all              Create both group and segments');
        }

        return 0;
    }

    protected function listExisting(SpringBigService $springBig): int
    {
        $this->info('Fetching external groups...');
        $groups = $springBig->getExternalGroups();

        if ($groups) {
            $this->table(['ID', 'Name', 'Description'], collect($groups)->map(fn ($g) => [
                $g['id'] ?? 'N/A',
                $g['group_name'] ?? 'N/A',
                $g['group_description'] ?? '',
            ])->toArray());
        } else {
            $this->warn('No groups found or API error.');
        }

        $this->newLine();
        $this->info('Fetching segments...');
        $segments = $springBig->getSegments();

        if ($segments) {
            $this->table(['ID', 'Name', 'Description'], collect($segments)->map(fn ($s) => [
                $s['id'] ?? 'N/A',
                $s['name'] ?? 'N/A',
                $s['desc'] ?? '',
            ])->toArray());
        } else {
            $this->warn('No segments found or API error.');
        }

        return 0;
    }

    protected function createGroup(SpringBigService $springBig): void
    {
        $this->info('Creating external group...');

        $result = $springBig->createExternalGroup(
            'WeWinGames',
            'WeWinGames subscription tier members'
        );

        if ($result && isset($result['id'])) {
            $this->newLine();
            $this->info('✓ External group created!');
            $this->newLine();
            $this->warn('Add to .env:');
            $this->line("SPRINGBIG_EXTERNAL_GROUP_ID={$result['id']}");
            $this->newLine();
        } else {
            $this->error('Failed to create external group. Check logs.');
        }
    }

    protected function createSegments(SpringBigService $springBig): void
    {
        $this->info('Creating tier segments...');

        $result = $springBig->createSegments();

        if ($result) {
            $this->newLine();
            $this->info('✓ Segments created!');
            $this->newLine();
            $this->warn('Add to .env:');

            // Parse response to get segment IDs
            // Response format may vary - adjust based on actual API response
            if (is_array($result)) {
                $tiers = ['free', 'silver', 'gold', 'platinum', 'canceled'];
                $segments = $result['group_segments'] ?? $result;

                foreach ($segments as $index => $segment) {
                    $tier = $tiers[$index] ?? "tier_{$index}";
                    $id = $segment['id'] ?? 'UNKNOWN';
                    $envKey = 'SPRINGBIG_SEGMENT_'.strtoupper($tier);
                    $this->line("{$envKey}={$id}");
                }
            }

            $this->newLine();
            $this->info('After adding to .env, set:');
            $this->line('SPRINGBIG_EXTERNAL_GROUP_ENABLED=true');
        } else {
            $this->error('Failed to create segments. Check logs.');
        }
    }
}
