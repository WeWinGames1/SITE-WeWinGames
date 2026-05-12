<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SpringBigService;
use Illuminate\Console\Command;

class SyncSpringBigMembers extends Command
{
    protected $signature = 'springbig:sync-members
                            {--limit= : Limit the number of users to sync}
                            {--user= : Sync a specific user by ID}
                            {--tier= : Filter by tier (free, silver, gold, platinum, canceled)}
                            {--create : Force createMember (skip lookup)}
                            {--dry-run : Show what would be synced without making API calls}';

    protected $description = 'Sync members to SpringBig with their current subscription tier (custom_group_list). Looks up by email/phone first, then updates or creates.';

    public function handle(SpringBigService $springBigService): int
    {
        if (! $springBigService->isEnabled()) {
            $this->error('SpringBig integration is not enabled.');
            $this->line('Set SPRINGBIG_ENABLED=true and configure API credentials in .env');

            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $userId = $this->option('user');
        $tierFilter = $this->option('tier');
        $forceCreate = $this->option('create');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No API calls will be made');
        }

        if ($forceCreate) {
            $this->info('Mode: CREATE (skip lookup, force new member)');
        } else {
            $this->info('Mode: SYNC (lookup by email/phone, then update or create)');
        }
        $this->newLine();

        // Build query
        $query = User::query()->orderBy('id');

        if ($userId) {
            $query->where('id', $userId);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $users = $query->get();

        // Filter by tier if specified
        if ($tierFilter) {
            $users = $users->filter(function ($user) use ($springBigService, $tierFilter) {
                return $springBigService->getTierForUser($user) === strtolower($tierFilter);
            });
        }

        $total = $users->count();

        if ($total === 0) {
            $this->warn('No users found to sync.');

            return Command::SUCCESS;
        }

        $this->info("Syncing {$total} users to SpringBig...");
        $this->newLine();

        // Show tier breakdown in dry-run
        if ($dryRun) {
            $tierCounts = $users->groupBy(fn ($user) => $springBigService->getTierForUser($user))
                ->map->count();

            $this->table(
                ['Tier', 'Count'],
                $tierCounts->map(fn ($count, $tier) => [$tier, $count])->values()->toArray()
            );
            $this->newLine();
        }

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $success = 0;
        $notFound = 0;
        $failed = 0;
        $errors = [];
        $synced = [];
        $notFoundUsers = [];

        foreach ($users as $user) {
            $tier = $springBigService->getTierForUser($user);

            if ($dryRun) {
                $synced[] = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'tier' => $tier,
                ];
                $success++;
            } else {
                try {
                    // Default: syncMember (lookup then update/create)
                    // --create flag: force createMember (skip lookup)
                    if ($forceCreate) {
                        $result = $springBigService->createMember($user);
                    } else {
                        $result = $springBigService->syncMember($user);
                    }

                    if ($result) {
                        $success++;
                        $synced[] = [
                            'id' => $user->id,
                            'email' => $user->email,
                            'tier' => $tier,
                        ];
                    } else {
                        // null = not found in SpringBig (for syncMember)
                        $notFound++;
                        $notFoundUsers[] = [
                            'id' => $user->id,
                            'email' => $user->email,
                            'phone' => $user->phone ?? 'N/A',
                            'tier' => $tier,
                        ];
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "User {$user->id} ({$user->email}): {$e->getMessage()}";
                }
            }

            $progressBar->advance();

            // Rate limiting delay
            if (! $dryRun) {
                usleep(100000); // 100ms
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Users', $total],
                ['Updated', $success],
                ['Not Found in SpringBig', $notFound],
                ['Errors', $failed],
            ]
        );

        // Show synced users with tiers (limit to 20 for readability)
        if (count($synced) > 0 && count($synced) <= 20) {
            $this->newLine();
            $this->info('Synced users:');
            $this->table(
                ['ID', 'Email', 'Tier (custom_group_list)'],
                $synced
            );
        }

        // Show not found users
        if (count($notFoundUsers) > 0) {
            $this->newLine();
            $this->warn('Users NOT FOUND in SpringBig:');
            $this->table(
                ['ID', 'Email', 'Phone', 'Tier'],
                $notFoundUsers
            );
        }

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('Errors:');
            foreach (array_slice($errors, 0, 10) as $error) {
                $this->error("  - {$error}");
            }
            if (count($errors) > 10) {
                $this->warn('  ... and '.(count($errors) - 10).' more errors');
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
