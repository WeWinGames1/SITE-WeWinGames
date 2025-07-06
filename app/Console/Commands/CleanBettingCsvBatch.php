<?php

namespace App\Console\Commands;

use App\Services\CsvCleanerService;
use Illuminate\Console\Command;

class CleanBettingCsvBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:clean-betting-batch 
                            {--dir= : Directory containing CSV files (default: current directory)}
                            {--pattern= : File pattern to match (default: *picks*.csv)}
                            {--suffix= : Suffix to add to cleaned files (default: _cleaned)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean multiple betting CSV files in a directory';

    /**
     * Execute the console command.
     */
    public function handle(CsvCleanerService $cleaner): int
    {
        $directory = $this->option('dir') ?? getcwd();
        $pattern = $this->option('pattern') ?? '*picks*.csv';
        $suffix = $this->option('suffix') ?? '_cleaned';

        // Find matching files
        $files = glob($directory . '/' . $pattern);
        
        if (empty($files)) {
            $this->warn("No files found matching pattern: {$pattern} in {$directory}");
            return Command::FAILURE;
        }

        $this->info("Found " . count($files) . " files to clean");
        
        $filesToClean = [];
        foreach ($files as $file) {
            // Skip already cleaned files
            if (strpos($file, $suffix) !== false) {
                continue;
            }
            
            $pathInfo = pathinfo($file);
            $outputFile = $pathInfo['dirname'] . '/' . 
                         $pathInfo['filename'] . $suffix . '.' . $pathInfo['extension'];
            
            $filesToClean[$file] = $outputFile;
        }

        if (empty($filesToClean)) {
            $this->warn("No files need cleaning (all files already have '{$suffix}' suffix)");
            return Command::SUCCESS;
        }

        // Clean the files
        $results = $cleaner->cleanMultipleCsvFiles($filesToClean);
        
        $successCount = 0;
        foreach ($results as $result) {
            if ($result['success']) {
                $this->info("✓ Cleaned: {$result['input']} → {$result['output']} ({$result['rows_processed']} rows)");
                $successCount++;
            } else {
                $this->error("✗ Failed: {$result['message']}");
            }
        }
        
        $this->info("\nSummary: {$successCount}/" . count($results) . " files cleaned successfully");
        
        return $successCount === count($results) ? Command::SUCCESS : Command::FAILURE;
    }
}