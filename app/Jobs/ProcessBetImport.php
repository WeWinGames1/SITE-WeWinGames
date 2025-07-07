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
    public function __construct(string $filePath, array $mappings, string $importId, int $userId)
    {
        $this->filePath = $filePath;
        $this->mappings = $mappings;
        $this->importId = $importId;
        $this->userId = $userId;
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

            // Parse CSV with mappings
            $parsedData = $csvService->parseWithMappings($this->filePath, $this->mappings);
            $totalRows = count($parsedData);

            // Update total count
            $this->updateProgress(['total' => $totalRows]);

            $successCount = 0;
            $errorCount = 0;
            $errorLog = [];
            $chunkSize = 100;

            // Process in chunks for better memory management
            foreach (array_chunk($parsedData, $chunkSize) as $chunkIndex => $chunk) {
                $chunkErrors = [];
                $chunkSuccesses = 0;

                foreach ($chunk as $index => $row) {
                    $rowNumber = ($chunkIndex * $chunkSize) + $index + 2; // +2 for header and 0-index

                    try {
                        // Import the bet
                        $betService->importSingleBet($row, $this->userId);
                        $successCount++;
                        $chunkSuccesses++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $chunkErrors[] = [
                            'row' => $rowNumber,
                            'message' => $e->getMessage(),
                            'data' => $row,
                        ];

                        // Keep only first 100 errors in log
                        if (count($errorLog) < 100) {
                            $errorLog[] = [
                                'row' => $rowNumber,
                                'message' => $e->getMessage(),
                            ];
                        }
                    }
                }

                // Update progress after each chunk
                $processed = ($chunkIndex + 1) * $chunkSize;
                $processed = min($processed, $totalRows); // Don't exceed total

                $this->updateProgress([
                    'processed' => $processed,
                    'success' => $successCount,
                    'errors' => $errorCount,
                    'percentage' => round(($processed / $totalRows) * 100),
                    'error_log' => $errorLog,
                ]);

                // Log chunk results
                if (count($chunkErrors) > 0) {
                    Log::warning("Bet import chunk {$chunkIndex} had errors", [
                        'import_id' => $this->importId,
                        'chunk_errors' => count($chunkErrors),
                        'chunk_successes' => $chunkSuccesses,
                    ]);
                }
            }

            // Mark as completed
            $finalStatus = $errorCount > 0 ? 'completed_with_errors' : 'completed';

            $this->updateProgress([
                'status' => $finalStatus,
                'processed' => $totalRows,
                'percentage' => 100,
                'completed_at' => now()->toIso8601String(),
            ]);

            // Store error details for download if any
            if ($errorCount > 0) {
                $this->storeErrorReport($errorLog, $parsedData);
            }

            // Log final results
            Log::info('Bet import completed', [
                'import_id' => $this->importId,
                'total' => $totalRows,
                'success' => $successCount,
                'errors' => $errorCount,
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
            // Cleanup uploaded file
            if (Storage::disk('local')->exists($this->filePath)) {
                Storage::disk('local')->delete($this->filePath);
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
