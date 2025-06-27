<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bet;

class ClearBetsTable extends Command
{
    protected $signature = 'bets:clear';
    protected $description = 'Delete all records from the bets table';

    public function handle()
    {
        if ($this->confirm('Are you sure you want to delete ALL bets? This action cannot be undone.')) {
            Bet::truncate();
            $this->info('All bets have been deleted.');
        } else {
            $this->info('Operation cancelled.');
        }
    }
}