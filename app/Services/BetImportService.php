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
        // Map the record using column mappings if provided
        if (!empty($this->columnMappings)) {
            $mappedRecord = [];
            foreach ($this->columnMappings as $field => $csvColumn) {
                if (isset($record[$csvColumn])) {
                    $mappedRecord[$field] = $record[$csvColumn];
                }
            }
            $record = $mappedRecord;
        }
        
        // Transform data before validation
        $record = $this->transformRecordData($record);
        
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

            // Get teams - home team is required, away team is optional
            $homeTeamName = $record['home_team'] ?? null;
            $awayTeamName = $record['away_team'] ?? null;
            
            // Get or create teams
            $homeTeam = null;
            $awayTeam = null;
            $game = null;
            
            if ($homeTeamName) {
                $homeTeam = Team::firstOrCreate(
                    ['name' => $homeTeamName, 'sport_id' => $sport->id],
                    ['slug' => \Str::slug($homeTeamName)]
                );
                
                // Only create away team if provided (not required for individual sports)
                if ($awayTeamName) {
                    $awayTeam = Team::firstOrCreate(
                        ['name' => $awayTeamName, 'sport_id' => $sport->id],
                        ['slug' => \Str::slug($awayTeamName)]
                    );

                    // Create or update game only if we have both teams
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
                }
            }

            // Get or create operator if provided
            $operator = null;
            if (!empty($record['operator'])) {
                $operator = Operator::firstOrCreate(
                    ['name' => $record['operator']],
                    ['slug' => \Str::slug($record['operator'])]
                );
            }

            // Create bet using the correct field names
            // Format matches field based on sport type and available teams
            $matchesField = '';
            if ($awayTeamName && $homeTeamName) {
                // Team sports - format as "Away @ Home" or "Fighter1 vs Fighter2"
                $isCombatSport = in_array(strtolower($record['sport']), ['ufc', 'mma', 'boxing', 'combat sports']);
                $matchesField = $isCombatSport ? 
                    "{$awayTeamName} vs {$homeTeamName}" : 
                    "{$awayTeamName} @ {$homeTeamName}";
            } elseif ($homeTeamName) {
                // Individual sports - just the player name
                $matchesField = $homeTeamName;
            }
            
            $betData = [
                'sports' => $record['sport'],
                'league' => $record['league'] ?? null,
                'month' => $record['month'] ?? null,
                'matches' => $matchesField,
                'markets' => $record['bet_type'],
                'wager_type' => $record['wager_type'] ?? null,
                'team_one' => $homeTeamName ?? '',
                'team_one_logo' => $homeTeam ? $homeTeam->logo : null,
                'team_two' => $awayTeamName ?? '',
                'team_two_logo' => $awayTeam ? $awayTeam->logo : null,
                'tips' => $record['wager_name'] ?? $record['selection'] ?? '',
                'betting_date' => $record['game_date'],
                'wager_odds' => (float) $record['odds'],
                'wager_amount' => (float) $record['stake'],
                'status' => $record['status'] ?? 'pending',
                'membership' => $record['level'] ?? $record['membership'] ?? 'Bronze',
                'level' => $record['level'] ?? null,
                'code' => $record['code'] ?? null,
                'referrer' => $record['referrer'] ?? $record['code'] ?? null,
            ];

            // Calculate winning and profit amounts based on American odds
            if (in_array($betData['status'], ['won', 'lost', 'placed'])) {
                // Check if this is an Each-Way bet
                $isEachWay = isset($betData['wager_type']) && 
                    strtolower($betData['wager_type']) === 'each way';
                
                if ($betData['status'] === 'won') {
                    // Check if we have pre-calculated values from CSV
                    if (!empty($record['winning_amount'])) {
                        $betData['winning_amount'] = (float) $record['winning_amount'];
                        $betData['profit_amount'] = !empty($record['profit']) ? (float) $record['profit'] : 
                            ($betData['winning_amount'] - $betData['wager_amount']);
                    } else {
                        // Calculate based on American odds
                        $odds = $betData['wager_odds'];
                        $stake = $betData['wager_amount'];
                        
                        if ($isEachWay) {
                            // Each-Way bet: Split stake in half
                            $winStake = $stake / 2;
                            $placeStake = $stake / 2;
                            
                            // Calculate win part
                            if ($odds > 0) {
                                $winProfit = $winStake * ($odds / 100);
                            } else {
                                $winProfit = $winStake * (100 / abs($odds));
                            }
                            
                            // Calculate place part (typically 1/4 or 1/5 of odds)
                            // Using place_fraction if available, default to 1/5
                            $placeFraction = !empty($record['place_fraction']) ? 
                                (float) $record['place_fraction'] : 0.2;
                            
                            $placeOdds = $odds > 0 ? 
                                ($odds * $placeFraction) : 
                                -abs(100 / (abs($odds) * $placeFraction));
                                
                            if ($placeOdds > 0) {
                                $placeProfit = $placeStake * ($placeOdds / 100);
                            } else {
                                $placeProfit = $placeStake * (100 / abs($placeOdds));
                            }
                            
                            $betData['profit_amount'] = $winProfit + $placeProfit;
                            $betData['winning_amount'] = $stake + $betData['profit_amount'];
                        } else {
                            // Regular bet
                            if ($odds > 0) {
                                // Positive American odds (+150)
                                $profit = $stake * ($odds / 100);
                            } else {
                                // Negative American odds (-120)
                                $profit = $stake * (100 / abs($odds));
                            }
                            
                            $betData['profit_amount'] = $profit;
                            $betData['winning_amount'] = $stake + $profit;
                        }
                    }
                } elseif ($betData['status'] === 'placed' && $isEachWay) {
                    // Each-Way bet that placed but didn't win
                    // Only the place part pays out
                    $stake = $betData['wager_amount'];
                    $placeStake = $stake / 2;
                    $winStake = $stake / 2;
                    
                    // Calculate place part payout
                    $placeFraction = !empty($record['place_fraction']) ? 
                        (float) $record['place_fraction'] : 0.2;
                    
                    $odds = $betData['wager_odds'];
                    $placeOdds = $odds > 0 ? 
                        ($odds * $placeFraction) : 
                        -abs(100 / (abs($odds) * $placeFraction));
                        
                    if ($placeOdds > 0) {
                        $placeProfit = $placeStake * ($placeOdds / 100);
                    } else {
                        $placeProfit = $placeStake * (100 / abs($placeOdds));
                    }
                    
                    // Lost the win part, won the place part
                    $betData['profit_amount'] = $placeProfit - $winStake;
                    $betData['winning_amount'] = $placeStake + $placeProfit;
                } else {
                    // Lost bet
                    $betData['winning_amount'] = 0;
                    $betData['profit_amount'] = -$betData['wager_amount'];
                }
            } else {
                $betData['winning_amount'] = !empty($record['winning_amount']) ? (float) $record['winning_amount'] : 0;
                $betData['profit_amount'] = !empty($record['profit']) ? (float) $record['profit'] : 0;
            }

            // Calculate ROI
            if ($betData['wager_amount'] > 0) {
                $betData['roi'] = ($betData['profit_amount'] / $betData['wager_amount']) * 100;
            } else {
                $betData['roi'] = 0;
            }

            // Create the bet directly using the model
            Bet::create($betData);
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

    private function transformRecordData(array $record): array
    {
        // Parse dates
        if (isset($record['game_date']) && !empty($record['game_date'])) {
            try {
                // Try multiple date formats
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d',
                    'm/d/Y',
                    'd/m/Y',
                    'm-d-Y',
                    'd-m-Y',
                    'Y/m/d',
                    'm/d/Y H:i:s',
                    'm/d/Y H:i',
                ];
                
                $parsed = false;
                foreach ($formats as $format) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat($format, trim($record['game_date']));
                        $record['game_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if (!$parsed) {
                    // Last resort - let Carbon try to parse it
                    $record['game_date'] = \Carbon\Carbon::parse($record['game_date'])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }
        
        // Parse placed_at date
        if (isset($record['placed_at']) && !empty($record['placed_at'])) {
            try {
                $record['placed_at'] = \Carbon\Carbon::parse($record['placed_at'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }
        
        // Parse numeric values
        if (isset($record['odds'])) {
            $record['odds'] = $this->parseNumeric($record['odds']);
        }
        
        if (isset($record['stake'])) {
            $record['stake'] = $this->parseMonetary($record['stake']);
        }
        
        // Normalize status
        if (isset($record['status'])) {
            $record['status'] = $this->normalizeStatus($record['status']);
        }
        
        // Trim all string values
        foreach ($record as $key => $value) {
            if (is_string($value)) {
                $record[$key] = trim($value);
            }
        }
        
        return $record;
    }
    
    private function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Keep American odds as-is (don't convert to decimal)
        if (is_string($value) && (str_starts_with($value, '+') || str_starts_with($value, '-'))) {
            $cleaned = preg_replace('/[^0-9+-]/', '', $value);
            return is_numeric($cleaned) ? (float) $cleaned : null;
        }
        
        // Remove non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
    
    private function parseMonetary($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove currency symbols and thousands separators
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
    
    private function convertAmericanToDecimal(string $americanOdds): float
    {
        $odds = (int) $americanOdds;
        
        if ($odds > 0) {
            return ($odds / 100) + 1;
        } else {
            return (100 / abs($odds)) + 1;
        }
    }
    
    private function normalizeStatus(?string $status): string
    {
        if (!$status) {
            return 'pending';
        }
        
        $status = strtolower(trim($status));
        
        $statusMap = [
            'win' => 'won',
            'won' => 'won',
            'w' => 'won',
            'loss' => 'lost',
            'lost' => 'lost',
            'lose' => 'lost',
            'l' => 'lost',
            'push' => 'push',
            'p' => 'push',
            'void' => 'void',
            'v' => 'void',
            'cashout' => 'cashout',
            'cash out' => 'cashout',
            'pending' => 'pending',
            'open' => 'pending',
            'active' => 'pending',
        ];
        
        return $statusMap[$status] ?? 'pending';
    }
    
    private function parseGameColumn(string $game): ?array
    {
        // Handle @ separator (most common in sports betting)
        if (str_contains($game, '@')) {
            $parts = explode('@', $game);
            if (count($parts) === 2) {
                return [
                    'away' => trim($parts[0]),
                    'home' => trim($parts[1]),
                ];
            }
        }
        
        // Try other common separators
        $separators = [' vs ', ' v ', ' - ', ' at ', ' vs. '];
        
        foreach ($separators as $separator) {
            if (str_contains($game, $separator)) {
                $parts = explode($separator, $game);
                if (count($parts) === 2) {
                    return [
                        'away' => trim($parts[0]),
                        'home' => trim($parts[1]),
                    ];
                }
            }
        }
        
        return null;
    }

    private function validateRecord(array $record): array
    {
        $rules = [
            'sport' => 'required|string|max:255',
            'home_team' => 'required|string|max:255',
            'away_team' => 'nullable|string|max:255',  // Optional for individual sports
            'bet_type' => 'required|string|max:50',
            'wager_name' => 'required|string|max:255',
            'odds' => 'required|numeric',
            'stake' => 'required|numeric|min:0.01',
            'game_date' => 'required|string', // Changed from 'date' to 'string' for more flexible parsing
            'status' => 'nullable|in:pending,won,lost,void,cashout,push,placed',
            'league' => 'nullable|string|max:255',
            'wager_type' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
            'referrer' => 'nullable|string|max:255',
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