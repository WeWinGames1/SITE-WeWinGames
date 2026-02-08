<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Console\Command;

class SyncAllDiscordRoles extends Command
{
    protected $signature = 'discord:sync-all-roles {--dry-run : Show what would be synced without making changes}';

    protected $description = 'Sync Discord roles for all users with connected Discord accounts';

    public function handle(DiscordService $discordService): int
    {
        if (! $discordService->isConfigured()) {
            $this->error('Discord is not fully configured. Check your .env settings.');

            return self::FAILURE;
        }

        $users = User::whereNotNull('discord_id')->get();

        if ($users->isEmpty()) {
            $this->info('No users have connected their Discord accounts yet.');

            return self::SUCCESS;
        }

        $this->info("Found {$users->count()} users with Discord connected.");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No changes will be made.');
        }

        $table = [];
        $synced = 0;
        $failed = 0;

        $this->withProgressBar($users, function (User $user) use ($discordService, &$table, &$synced, &$failed) {
            $tier = $user->getCurrentTier();
            $hasSubscription = $user->subscribed();

            if ($this->option('dry-run')) {
                $table[] = [
                    $user->id,
                    $user->email,
                    $user->discord_username,
                    $hasSubscription ? $tier : 'None',
                    'Would sync',
                ];

                return;
            }

            try {
                $success = $discordService->syncRoles($user);

                if ($success) {
                    $synced++;
                    $table[] = [
                        $user->id,
                        $user->email,
                        $user->discord_username,
                        $hasSubscription ? $tier : 'None',
                        '✓ Synced',
                    ];
                } else {
                    $failed++;
                    $table[] = [
                        $user->id,
                        $user->email,
                        $user->discord_username,
                        $hasSubscription ? $tier : 'None',
                        '✗ Failed (not in server?)',
                    ];
                }
            } catch (\Exception $e) {
                $failed++;
                $table[] = [
                    $user->id,
                    $user->email,
                    $user->discord_username,
                    $hasSubscription ? $tier : 'None',
                    '✗ Error: '.substr($e->getMessage(), 0, 30),
                ];
            }

            // Rate limit protection
            usleep(500000); // 0.5 second delay between API calls
        });

        $this->newLine(2);
        $this->table(['ID', 'Email', 'Discord', 'Tier', 'Status'], $table);

        if (! $this->option('dry-run')) {
            $this->newLine();
            $this->info("Synced: {$synced} | Failed: {$failed}");
        }

        return self::SUCCESS;
    }
}
