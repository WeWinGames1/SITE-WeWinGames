<?php

namespace App\Jobs;

use App\Services\BetImportService;
use App\Services\CsvImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessBetImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $filePath;

    protected array $mappings;

    protected string $importId;

    protected int $userId;
    
    protected bool $skipErrors;
    
    protected array $staticValues;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 3600; // 1 hour

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, array $mappings, string $importId, int $userId, bool $skipErrors = false, array $staticValues = [])
    {
        $this->filePath = $filePath;
        $this->mappings = $mappings;
        $this->importId = $importId;
        $this->userId = $userId;
        $this->skipErrors = $skipErrors;
        $this->staticValues = $staticValues;
    }

    /**
     * Execute the job.
     */
    public function handle(CsvImportService $csvService, BetImportService $betService): void
    {
        try {
            // Initialize progress
            $this->updateProgress([
                'status' => 'processing',
                'total' => 0,
                'processed' => 0,
                'success' => 0,
                'errors' => 0,
                'percentage' => 0,
                'error_log' => [],
            ]);

            // Get full file path
            $fullPath = Storage::disk('local')->path($this->filePath);
            
            // Set up the bet import service with mappings and options
            $betService->setColumnMappings($this->mappings);
            $betService->setStaticValues($this->staticValues);
            $betService->setSkipErrors($this->skipErrors);
            
            // Use the service's import method directly for better consistency
            $result = $betService->importFromCsv($fullPath);
            
            // Update progress with results
            $this->updateProgress([
                'status' => 'completed',
                'total' => $result['processed'] ?? 0,
                'processed' => $result['processed'] ?? 0,
                'success' => $result['successCount']['bets'] ?? 0,
                'errors' => count($result['errors'] ?? []),
                'percentage' => 100,
                'error_log' => array_slice($result['errors'] ?? [], 0, 100),
                'completed_at' => now()->toIso8601String(),
            ]);
            
            // Store error report if there are errors
            if (count($result['errors'] ?? []) > 0) {
                $this->storeErrorReportFromResults($result['errors']);
            }
            
            Log::info('Bet import completed via job', [
                'import_id' => $this->importId,
                'total' => $result['processed'] ?? 0,
                'success' => $result['successCount']['bets'] ?? 0,
                'errors' => count($result['errors'] ?? []),
            ]);
            

        } catch (\Exception $e) {
            Log::error('Bet import failed', [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->updateProgress([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'failed_at' => now()->toIso8601String(),
            ]);

            throw $e;
        } finally {
            // Cleanup uploaded file after successful processing
            try {
                if (Storage::disk('local')->exists($this->filePath)) {
                    Storage::disk('local')->delete($this->filePath);
                }
            } catch (\Exception $e) {
                // Log but don't fail if cleanup fails
                Log::warning('Failed to cleanup import file', [
                    'file' => $this->filePath,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Update import progress in cache
     */
    protected function updateProgress(array $data): void
    {
        $key = "import_progress_{$this->importId}";
        $current = Cache::get($key, []);
        $updated = array_merge($current, $data);

        // Keep progress for 24 hours
        Cache::put($key, $updated, now()->addHours(24));
    }

    /**
     * Store error report from results for download
     */
    protected function storeErrorReportFromResults(array $errors): void
    {
        $reportPath = "imports/errors/{$this->importId}_errors.csv";
        
        $csv = fopen('php://temp', 'w');
        
        // Headers
        fputcsv($csv, ['Row', 'Error', 'Data']);
        
        // Error rows
        foreach ($errors as $error) {
            $errorMessages = $error['errors'] ?? ['Unknown error'];
            // Handle nested arrays in error messages
            if (is_array($errorMessages)) {
                $flatErrors = [];
                foreach ($errorMessages as $field => $messages) {
                    if (is_array($messages)) {
                        $flatErrors[] = $field . ': ' . implode(', ', $messages);
                    } else {
                        $flatErrors[] = $messages;
                    }
                }
                $errorString = implode('; ', $flatErrors);
            } else {
                $errorString = (string) $errorMessages;
            }
            
            fputcsv($csv, [
                $error['line'] ?? 'Unknown',
                $errorString,
                json_encode($error['data'] ?? [])
            ]);
        }
        
        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);
        
        Storage::disk('local')->put($reportPath, $csvContent);
        
        // Store path in cache for download
        Cache::put("import_error_report_{$this->importId}", $reportPath, now()->addDays(7));
    }
    
    /**
     * Store error report for download
     */
    protected function storeErrorReport(array $errorLog, array $allData): void
    {
        $reportPath = "imports/errors/{$this->importId}_errors.csv";

        $csv = fopen('php://temp', 'w');

        // Headers
        fputcsv($csv, ['Row', 'Error', 'Sport', 'Home Team', 'Away Team', 'Game Date', 'Bet Type', 'Selection', 'Odds', 'Stake']);

        // Error rows
        foreach ($errorLog as $error) {
            $rowData = $allData[$error['row'] - 2] ?? []; // -2 for header and 0-index

            fputcsv($csv, [
                $error['row'],
                $error['message'],
                $rowData['sport'] ?? '',
                $rowData['home_team'] ?? '',
                $rowData['away_team'] ?? '',
                $rowData['game_date'] ?? '',
                $rowData['bet_type'] ?? '',
                $rowData['selection'] ?? '',
                $rowData['odds'] ?? '',
                $rowData['stake'] ?? '',
            ]);
        }

        rewind($csv);
        $csvContent = stream_get_contents($csv);
        fclose($csv);

        Storage::disk('local')->put($reportPath, $csvContent);

        // Store path in cache for download
        Cache::put("import_error_report_{$this->importId}", $reportPath, now()->addDays(7));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bet import job failed', [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
        ]);

        $this->updateProgress([
            'status' => 'failed',
            'error_message' => 'Import job failed: '.$exception->getMessage(),
            'failed_at' => now()->toIso8601String(),
        ]);
    }
}
