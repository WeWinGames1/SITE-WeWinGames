<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class CacheWarmUpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-up {--force : Force cache refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache with frequently accessed data';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $this->info('Starting cache warm-up process...');

        if ($this->option('force')) {
            $this->warn('Force flag detected. Clearing existing cache...');
            $cacheService->clearAll();
        }

        $startTime = microtime(true);

        try {
            $cacheService->warmUp();

            $duration = round(microtime(true) - $startTime, 2);
            $this->info("Cache warm-up completed successfully in {$duration} seconds.");

            // Display cache statistics
            $stats = $cacheService->getStats();
            if (! empty($stats)) {
                $this->table(
                    ['Metric', 'Value'],
                    collect($stats)->map(fn ($value, $key) => [
                        str_replace('_', ' ', ucfirst($key)),
                        $value,
                    ])->toArray()
                );
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache warm-up failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
