<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Game;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Repositories\Contracts\BetRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

class BetImportService
{
    private array $errors = [];
    private array $successCount = ['bets' => 0, 'games' => 0, 'teams' => 0];
    private array $columnMappings = [];

    public function __construct(
        private BetRepositoryInterface $betRepository
    ) {}

    /**
     * Set column mappings for import
     */
    public function setColumnMappings(array $mappings): void
    {
        $this->columnMappings = $mappings;
    }

    public function importFromCsv(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $records = $csv->getRecords();
            $totalRecords = 0;
            
            DB::beginTransaction();
            
            foreach ($records as $offset => $record) {
                $totalRecords++;
                $this->processRecord($record, $offset + 2); // +2 because header is line 1
            }
            
            if (!empty($this->errors)) {
                DB::rollBack();
                
                return [
                    'success' => false,
                    'message' => 'Import failed due to validation errors',
                    'errors' => $this->errors,
                    'processed' => $totalRecords,
                ];
            }
            
            DB::commit();
            
            Log::info('CSV import completed successfully', [
                'file' => $filePath,
                'stats' => $this->successCount,
            ]);
            
            return [
                'success' => true,
                'message' => 'Import completed successfully',
                'stats' => $this->successCount,
                'processed' => $totalRecords,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'errors' => $this->errors,
            ];
        }
    }

    private function processRecord(array $record, int $lineNumber): void
    {
        // Validate record
        $validation = $this->validateRecord($record);
        if (!$validation['valid']) {
            $this->errors[] = [
                'line' => $lineNumber,
                'errors' => $validation['errors'],
                'data' => $record,
            ];
            return;
        }

        try {
            // Get or create sport
            $sport = Sport::firstOrCreate(
                ['name' => $record['sport']],
                ['slug' => \Str::slug($record['sport'])]
            );

            // Get or create teams
            $homeTeam = Team::firstOrCreate(
                ['name' => $record['home_team'], 'sport_id' => $sport->id],
                ['slug' => \Str::slug($record['home_team'])]
            );
            
            $awayTeam = Team::firstOrCreate(
                ['name' => $record['away_team'], 'sport_id' => $sport->id],
                ['slug' => \Str::slug($record['away_team'])]
            );

            // Get or create operator
            $operator = Operator::firstOrCreate(
                ['name' => $record['operator']],
                ['slug' => \Str::slug($record['operator'])]
            );

            // Create or update game
            $game = Game::updateOrCreate(
                [
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'game_date' => $record['game_date'],
                ],
                [
                    'sport_id' => $sport->id,
                    'status' => $record['game_status'] ?? 'scheduled',
                ]
            );
            $this->successCount['games']++;

            // Create bet
            $betData = [
                'user_id' => auth()->id() ?? 1, // Default to user 1 if not authenticated
                'game_id' => $game->id,
                'sport_id' => $sport->id,
                'operator_id' => $operator->id,
                'bet_type' => $record['bet_type'],
                'selection' => $record['selection'],
                'odds' => (float) $record['odds'],
                'stake' => (float) $record['stake'],
                'potential_return' => (float) $record['stake'] * (float) $record['odds'],
                'status' => $record['status'] ?? 'pending',
                'description' => $record['description'] ?? null,
                'placed_at' => $record['placed_at'] ?? now(),
            ];

            // Calculate profit if bet is settled
            if (in_array($betData['status'], ['won', 'lost'])) {
                $betData['profit'] = $betData['status'] === 'won' 
                    ? ($betData['potential_return'] - $betData['stake'])
                    : -$betData['stake'];
            }

            $this->betRepository->create($betData);
            $this->successCount['bets']++;

        } catch (\Exception $e) {
            $this->errors[] = [
                'line' => $lineNumber,
                'errors' => ['processing' => $e->getMessage()],
                'data' => $record,
            ];
            
            Log::error('Error processing CSV record', [
                'line' => $lineNumber,
                'error' => $e->getMessage(),
                'record' => $record,
            ]);
        }
    }

    private function validateRecord(array $record): array
    {
        $rules = [
            'sport' => 'required|string|max:255',
            'home_team' => 'required|string|max:255',
            'away_team' => 'required|string|max:255',
            'operator' => 'required|string|max:255',
            'bet_type' => 'required|string|max:50',
            'selection' => 'required|string|max:255',
            'odds' => 'required|numeric|min:1.01',
            'stake' => 'required|numeric|min:0.01',
            'game_date' => 'required|date',
            'status' => 'nullable|in:pending,won,lost,void,cashout',
        ];

        $validator = Validator::make($record, $rules);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->toArray(),
        ];
    }

    public function getSampleCsvFormat(): array
    {
        return [
            'headers' => [
                'sport',
                'home_team',
                'away_team',
                'game_date',
                'operator',
                'bet_type',
                'selection',
                'odds',
                'stake',
                'status',
                'description',
                'placed_at',
                'game_status'
            ],
            'example' => [
                'sport' => 'NFL',
                'home_team' => 'Kansas City Chiefs',
                'away_team' => 'Buffalo Bills',
                'game_date' => '2024-01-15',
                'operator' => 'DraftKings',
                'bet_type' => 'Spread',
                'selection' => 'Chiefs -3.5',
                'odds' => '1.91',
                'stake' => '100',
                'status' => 'pending',
                'description' => 'Divisional round playoff game',
                'placed_at' => '2024-01-14 15:30:00',
                'game_status' => 'scheduled'
            ]
        ];
    }

    /**
     * Import a single bet (used by queue job)
     */
    public function importSingleBet(array $record, int $userId): void
    {
        // Process the record using existing method
        $this->processRecord($record, $userId, 1);
        
        // Check if there were errors
        if (!empty($this->errors)) {
            $lastError = end($this->errors);
            throw new \Exception($lastError['errors']['processing'] ?? 'Import failed');
        }
    }
}