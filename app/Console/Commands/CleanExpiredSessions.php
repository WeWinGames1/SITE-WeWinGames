<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanExpiredSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:clean {--days=1 : Number of days to keep sessions}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired sessions from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $lifetime = config('session.lifetime', 120); // in minutes
        
        // Add a buffer to the lifetime (e.g., 20% more)
        $bufferMinutes = $lifetime * 1.2;
        
        // Calculate the cutoff time
        $cutoff = now()->subMinutes($bufferMinutes)->timestamp;
        
        // Delete old sessions
        $deleted = DB::table('sessions')
            ->where('last_activity', '<', $cutoff)
            ->delete();
            
        $this->info("Deleted {$deleted} expired sessions.");
        
        // Also clean up sessions older than specified days (as a safety measure)
        $oldCutoff = now()->subDays($days)->timestamp;
        $deletedOld = DB::table('sessions')
            ->where('last_activity', '<', $oldCutoff)
            ->delete();
            
        if ($deletedOld > 0) {
            $this->info("Deleted {$deletedOld} sessions older than {$days} days.");
        }
        
        return Command::SUCCESS;
    }
}