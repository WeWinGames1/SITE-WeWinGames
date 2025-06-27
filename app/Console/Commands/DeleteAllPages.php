<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Page;

class DeleteAllPages extends Command
{
    protected $signature = 'pages:delete-all';
    protected $description = 'Delete all pages from the database';

    public function handle()
    {
        if ($this->confirm('Are you sure you want to delete ALL pages? This cannot be undone.')) {
            $count = Page::count();
            Page::truncate();
            $this->info("Deleted $count pages.");
        } else {
            $this->info('Operation cancelled.');
        }
        return 0;
    }
}