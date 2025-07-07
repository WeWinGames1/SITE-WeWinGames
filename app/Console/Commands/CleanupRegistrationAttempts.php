<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupRegistrationAttempts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:cleanup 
                            {--days=30 : Number of days to keep registration attempts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old registration attempts and expired IP blocks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        // Clean up old registration attempts
        $deletedAttempts = DB::table('registration_attempts')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deletedAttempts} registration attempts older than {$days} days.");

        // Clean up expired IP blocks
        $deletedBlocks = DB::table('ip_blacklist')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Removed {$deletedBlocks} expired IP blocks.");

        // Update statistics
        $totalAttempts = DB::table('registration_attempts')->count();
        $failedAttempts = DB::table('registration_attempts')->where('successful', false)->count();
        $blockedIPs = DB::table('ip_blacklist')->count();

        $this->info("\nCurrent Statistics:");
        $this->info("Total registration attempts: {$totalAttempts}");
        $this->info("Failed attempts: {$failedAttempts}");
        $this->info("Currently blocked IPs: {$blockedIPs}");

        return Command::SUCCESS;
    }
}
