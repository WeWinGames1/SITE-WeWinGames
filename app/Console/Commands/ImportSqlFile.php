<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSqlFile extends Command
{
    protected $signature = 'db:import {file : Path to SQL file}';

    protected $description = 'Import an SQL file into the database';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info("Importing SQL file: {$filePath}");

        try {
            // Read the SQL file
            $sql = file_get_contents($filePath);

            // Split by semicolon to handle multiple statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            $count = 0;
            foreach ($statements as $statement) {
                if (! empty($statement)) {
                    DB::unprepared($statement);
                    $count++;
                }
            }

            $this->info("Successfully imported {$count} SQL statements!");

            return 0;

        } catch (\Exception $e) {
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }
    }
}
