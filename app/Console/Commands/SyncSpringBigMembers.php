<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SpringBigService;
use Illuminate\Console\Command;

class SyncSpringBigMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'springbig:sync-members
                            {--limit= : Limit the number of users to sync}
                            {--user= : Sync a specific user by ID}
                            {--dry-run : Show what would be synced without making API calls}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all members to SpringBig';

    /**
     * Execute the console command.
     */
    public function handle(SpringBigService $springBigService): int
    {
        if (! $springBigService->isEnabled()) {
            $this->error('SpringBig integration is not enabled.');
            $this->line('Please set SPRINGBIG_ENABLED=true and configure SPRINGBIG_API_KEY and SPRINGBIG_AUTH_TOKEN in your .env file.');

            return Command::FAILURE;
        }

        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        $userId = $this->option('user');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No API calls will be made');
        }

        // Build query
        $query = User::query()->orderBy('id');

        if ($userId) {
            $query->where('id', $userId);
        }

        if ($limit) {
            $query->limit((int) $limit);
        }

        $users = $query->get();
        $total = $users->count();

        if ($total === 0) {
            $this->warn('No users found to sync.');

            return Command::SUCCESS;
        }

        $this->info("Syncing {$total} users to SpringBig...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        $success = 0;
        $failed = 0;
        $errors = [];

        foreach ($users as $user) {
            if ($dryRun) {
                $this->line(" Would sync: {$user->name} ({$user->email})");
                $success++;
            } else {
                try {
                    $result = $springBigService->createMember($user);

                    if ($result) {
                        $success++;
                    } else {
                        $failed++;
                        $errors[] = "User {$user->id} ({$user->email}): Failed to sync";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "User {$user->id} ({$user->email}): {$e->getMessage()}";
                }
            }

            $progressBar->advance();

            // Add a small delay to avoid rate limiting
            if (! $dryRun) {
                usleep(100000); // 100ms delay
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
                ['Successful', $success],
                ['Failed', $failed],
            ]
        );

        if (count($errors) > 0) {
            $this->newLine();
            $this->warn('Errors encountered:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
