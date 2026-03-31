<?php

namespace App\Services;

use App\Events\NewBatchUpload;
use App\Models\Bet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LegacyBetImportService
{
    private array $errors = [];

    private array $successfulBets = [];

    private int $totalProcessed = 0;

    private int $successCount = 0;

    private int $errorCount = 0;

    public function importFromCsv(string $filePath): array
    {
        $this->resetCounters();

        try {
            DB::beginTransaction();

            $handle = fopen($filePath, 'r');
            if (! $handle) {
                throw new \Exception('Unable to open CSV file');
            }

            // Skip header
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                $this->totalProcessed++;
                $this->processRow($row);
            }

            fclose($handle);

            // If there are any errors, rollback
            if ($this->errorCount > 0 && $this->successCount === 0) {
                DB::rollBack();

                return [
                    'success' => false,
                    'message' => 'Import failed due to errors',
                    'errors' => $this->errors,
                    'stats' => [
                        'total' => $this->totalProcessed,
                        'success' => 0,
                        'errors' => $this->errorCount,
                    ],
                ];
            }

            DB::commit();

            // Dispatch event for successful bets
            if (! empty($this->successfulBets)) {
                NewBatchUpload::dispatch(collect($this->successfulBets));
            }

            Log::info('Legacy CSV import completed', [
                'file' => $filePath,
                'total' => $this->totalProcessed,
                'success' => $this->successCount,
                'errors' => $this->errorCount,
            ]);

            return [
                'success' => true,
                'message' => sprintf(
                    'Import completed: %d successful, %d errors out of %d total',
                    $this->successCount,
                    $this->errorCount,
                    $this->totalProcessed
                ),
                'stats' => [
                    'total' => $this->totalProcessed,
                    'success' => $this->successCount,
                    'errors' => $this->errorCount,
                ],
                'errors' => $this->errors,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Legacy CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
                'errors' => $this->errors,
            ];
        }
    }

    private function processRow(array $row): void
    {
        try {
            // Validate row has minimum required columns
            if (count($row) < 16) {
                throw new \Exception('Insufficient columns in row');
            }

            // Parse teams
            $teams = explode('@', $row[4]);
            $teamOne = trim($teams[0] ?? '');
            $teamTwo = trim($teams[1] ?? '');

            if (empty($teamOne) || empty($teamTwo)) {
                throw new \Exception('Invalid team format');
            }

            // Parse monetary values
            $wagerAmount = $this->parseMonetaryValue($row[13] ?? null);
            $winningAmount = $this->parseMonetaryValue($row[14] ?? null);
            $profitAmount = $this->parseMonetaryValue($row[15] ?? null);

            // Parse status
            $status = $this->parseStatus($row[11] ?? null);

            // Parse ROI
            $roi = $this->parseRoi($row[12] ?? null);

            // Parse date
            $bettingDate = $this->parseDate($row[3] ?? null);

            // Create bet data
            $data = [
                'sports' => $row[0] ?? 'Unknown',
                'league' => $row[1] ?? null,
                'betting_date' => $bettingDate,
                'matches' => $row[4],
                'markets' => $row[6] ?? null,
                'team_one' => $teamOne,
                'team_two' => $teamTwo,
                'tips' => $row[7] ?? null,
                'wager_odds' => $this->parseOdds($row[8] ?? null),
                'membership' => $row[9] ?? null,
                'referrer' => $row[10] ?? null,
                'status' => $status,
                'roi' => $roi,
                'wager_amount' => $wagerAmount,
                'winning_amount' => $winningAmount,
                'profit_amount' => $profitAmount,
            ];

            // Validate required fields
            $this->validateBetData($data);

            // Create bet
            $bet = Bet::create($data);
            $this->successfulBets[] = $bet;
            $this->successCount++;

        } catch (\Exception $e) {
            $this->errorCount++;
            $this->errors[] = [
                'row' => $this->totalProcessed,
                'error' => $e->getMessage(),
                'data' => $row,
            ];

            Log::error('Error processing CSV row', [
                'row' => $this->totalProcessed,
                'error' => $e->getMessage(),
                'data' => $row,
            ]);
        }
    }

    private function parseMonetaryValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove $ and commas, convert to float
        return floatval(str_replace(['$', ','], '', $value));
    }

    private function parseStatus(?string $status): string
    {
        if (! $status) {
            return 'Pending';
        }

        $status = strtolower(trim($status));

        return match ($status) {
            'win', 'won' => 'won',
            'loss', 'lose', 'lost' => 'loss',
            'push' => 'push',
            'pending' => 'pending',
            'void' => 'void',
            default => 'pending'
        };
    }

    private function parseRoi($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Remove % and convert to decimal
        $roi = floatval(str_replace('%', '', $value));

        return $roi / 100;
    }

    private function parseDate($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            Log::warning('Invalid date format', ['value' => $value]);

            return null;
        }
    }

    private function parseOdds($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return floatval($value);
    }

    private function validateBetData(array $data): void
    {
        $required = ['sports', 'team_one', 'team_two', 'status'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Required field '{$field}' is missing or empty");
            }
        }

        // Validate numeric fields
        if ($data['wager_amount'] !== null && $data['wager_amount'] < 0) {
            throw new \Exception('Wager amount cannot be negative');
        }

        if ($data['wager_odds'] !== null && $data['wager_odds'] <= 0) {
            throw new \Exception('Odds must be greater than 0');
        }
    }

    private function resetCounters(): void
    {
        $this->errors = [];
        $this->successfulBets = [];
        $this->totalProcessed = 0;
        $this->successCount = 0;
        $this->errorCount = 0;
    }

    public function getSampleCsvFormat(): array
    {
        return [
            'headers' => [
                'Sports',
                'League',
                'Date/Time',
                'Betting Date',
                'Matches (Team1 @ Team2)',
                'Time',
                'Markets',
                'Tips',
                'Wager Odds',
                'Membership',
                'Referrer',
                'Status',
                'ROI %',
                'Wager Amount',
                'Winning Amount',
                'Profit Amount',
            ],
            'example' => [
                'Sports' => 'NFL',
                'League' => 'National Football League',
                'Date/Time' => '2024-01-15 13:00',
                'Betting Date' => '2024-01-14',
                'Matches' => 'Kansas City Chiefs @ Buffalo Bills',
                'Time' => '13:00',
                'Markets' => 'Spread',
                'Tips' => 'Chiefs -3.5',
                'Wager Odds' => '1.91',
                'Membership' => 'Premium',
                'Referrer' => 'ESPN',
                'Status' => 'Won',
                'ROI %' => '91%',
                'Wager Amount' => '$100',
                'Winning Amount' => '$191',
                'Profit Amount' => '$91',
            ],
        ];
    }
}
