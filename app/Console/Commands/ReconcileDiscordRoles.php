<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\DiscordService;
use App\Services\SubscriptionSyncService;
use Illuminate\Console\Command;

class ReconcileDiscordRoles extends Command
{
    protected $signature = 'discord:reconcile
        {--stripe : Re-pull subscription status from Stripe for linked users before auditing}
        {--fix : Add/remove roles for linked users whose Discord roles do not match the database}
        {--strip-unlinked : Remove paid roles from members not linked to any site account (review the report first)}';

    protected $description = 'Audit Discord roles against the subscription database and optionally fix them';

    public function handle(DiscordService $discord, SubscriptionSyncService $subscriptions): int
    {
        if (! $discord->isConfigured()) {
            $this->error('Discord is not fully configured. Check your .env settings.');

            return self::FAILURE;
        }

        $expired = $subscriptions->expireManualSubscriptions(dryRun: ! $this->option('fix'));
        $this->line(sprintf(
            '%s %d expired manual subscription(s).',
            $this->option('fix') ? 'Canceled' : 'Found',
            $expired->count()
        ));

        if ($this->option('stripe')) {
            $this->resyncFromStripe($subscriptions);
        }

        $audit = $discord->audit();

        if ($audit === null) {
            $this->error('Could not list guild members. Make sure the bot has the "Server Members Intent" enabled in the Discord developer portal.');

            return self::FAILURE;
        }

        $this->info("Scanned {$audit['members_scanned']} Discord members. {$audit['linked_not_in_guild']} linked users are not in the server.");

        $this->newLine();
        $this->info(count($audit['linked']).' linked member(s) with wrong roles:');
        $this->table(
            ['User', 'Email', 'Discord', 'DB tier', 'Add', 'Remove'],
            array_map(fn (array $row): array => [
                $row['user_id'],
                $row['email'],
                $row['discord_username'],
                $row['tier'] ?? 'none',
                $this->labels($discord, $row['add']),
                $this->labels($discord, $row['remove']),
            ], $audit['linked'])
        );

        $this->newLine();
        $this->info(count($audit['unlinked']).' unlinked member(s) holding roles they should not:');
        $this->table(
            ['Discord ID', 'Discord', 'Matched user (by username)', 'Remove'],
            array_map(fn (array $row): array => [
                $row['discord_id'],
                $row['discord_username'],
                $row['matched_user_email'] ?? '—',
                $this->labels($discord, $row['remove']),
            ], $audit['unlinked'])
        );

        if ($this->option('fix')) {
            $result = $discord->applyAuditRows($audit['linked']);
            $this->info("Linked members fixed: {$result['fixed']}, failed: {$result['failed']}");
        }

        if ($this->option('strip-unlinked')) {
            $result = $discord->applyAuditRows($audit['unlinked']);
            $this->info("Unlinked members stripped: {$result['fixed']}, failed: {$result['failed']}");
        }

        if (! $this->option('fix') && ! $this->option('strip-unlinked')) {
            $this->warn('Report only. Re-run with --fix and/or --strip-unlinked to apply.');
        }

        return self::SUCCESS;
    }

    private function resyncFromStripe(SubscriptionSyncService $subscriptions): void
    {
        $users = User::query()
            ->whereNotNull('discord_id')
            ->whereNotNull('stripe_id')
            ->whereHas('subscriptions', fn ($query) => $query->whereIn('stripe_status', SubscriptionSyncService::LIVE_STATUSES))
            ->get();

        $this->line("Re-pulling {$users->count()} linked customer(s) from Stripe...");

        foreach ($users as $user) {
            try {
                foreach ($subscriptions->syncFromStripe($user) as $change) {
                    $this->line("  #{$user->id} {$user->email} {$change}");
                }
            } catch (\Throwable $e) {
                $this->error("  #{$user->id} {$user->email}: {$e->getMessage()}");
            }
        }
    }

    /**
     * @param  array<int, string>  $roleIds
     */
    private function labels(DiscordService $discord, array $roleIds): string
    {
        return implode(', ', array_map(fn (string $id): string => $discord->roleLabel($id), $roleIds)) ?: '—';
    }
}
