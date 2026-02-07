<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\DiscordService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncDiscordRolesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user
    ) {}

    /**
     * Execute the job.
     */
    public function handle(DiscordService $discordService): void
    {
        if (! $discordService->isConfigured()) {
            Log::warning('Discord not configured, skipping role sync', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        if (! $this->user->discord_id) {
            Log::debug('User has no Discord ID, skipping role sync', [
                'user_id' => $this->user->id,
            ]);

            return;
        }

        $success = $discordService->syncRoles($this->user);

        if (! $success) {
            Log::warning('Failed to sync Discord roles', [
                'user_id' => $this->user->id,
                'discord_id' => $this->user->discord_id,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Discord role sync job failed', [
            'user_id' => $this->user->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
