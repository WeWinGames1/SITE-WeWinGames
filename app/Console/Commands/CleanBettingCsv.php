<?php

namespace App\Console\Commands;

use App\Services\CsvCleanerService;
use Illuminate\Console\Command;

class CleanBettingCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'csv:clean-betting 
                            {input : Path to the input CSV file}
                            {output : Path to the output cleaned CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean a betting CSV file with duplicate columns and format it for import';

    /**
     * Execute the console command.
     */
    public function handle(CsvCleanerService $cleaner): int
    {
        $inputPath = $this->argument('input');
        $outputPath = $this->argument('output');

        // Check if input file exists
        if (!file_exists($inputPath)) {
            $this->error("Input file not found: {$inputPath}");
            return Command::FAILURE;
        }

        $this->info("Cleaning CSV file: {$inputPath}");
        
        // Clean the CSV file
        $result = $cleaner->cleanCsvFile($inputPath, $outputPath);
        
        if ($result['success']) {
            $this->info($result['message']);
            $this->info("Input: {$result['input']}");
            $this->info("Output: {$result['output']}");
            $this->info("Processed {$result['rows_processed']} rows");
            
            return Command::SUCCESS;
        } else {
            $this->error($result['message']);
            return Command::FAILURE;
        }
    }
}