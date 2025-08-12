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

    private array $staticValues = [];
    
    private bool $skipErrors = false;
    
    private array $skippedEachWayBets = [];
    
    private array $skippedParlayBets = [];

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

    /**
     * Normalize membership values to lowercase standard names
     */
    private function normalizeMembership(?string $membership): string
    {
        if (!$membership) {
            return 'bronze';
        }
        
        $membership = strtolower(trim($membership));
        
        // Map variations to standard names
        $membershipMap = [
            'bronze' => 'bronze',
            'silver' => 'silver',
            'gold' => 'gold',
            'platinum' => 'platinum',
            'plat' => 'platinum',
            'free' => 'bronze',
            'basic' => 'bronze',
            'premium' => 'gold',
            'pro' => 'platinum',
        ];
        
        return $membershipMap[$membership] ?? 'bronze';
    }

    /**
     * Set static values for import
     */
    public function setStaticValues(array $staticValues): void
    {
        $this->staticValues = $staticValues;
    }

    /**
     * Set whether to skip errors during import
     */
    public function setSkipErrors(bool $skipErrors): void
    {
        $this->skipErrors = $skipErrors;
    }

    /**
     * Format date for MySQL
     */
    private function formatDateForMysql($dateValue): ?string
    {
        if (empty($dateValue)) {
            return null;
        }

        try {
            // Replace non-breaking hyphens (U+2011) with regular hyphens
            $dateValue = str_replace('‑', '-', $dateValue);
            
            // If already in Y-m-d H:i:s format, return as is
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
                return $dateValue;
            }
            
            // If already in Y-m-d format, append time
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                return $dateValue . ' 00:00:00';
            }

            // IMPORTANT: Always parse dates as MM/DD/YYYY format first
            // This ensures consistency for dates like 07/12/2025
            
            // Try manual parsing for MM/DD/YY format with slashes (2-digit year)
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2})$/', $dateValue, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = (int) $matches[3];
                // If year < 50, assume 2000s; otherwise 1900s
                $fullYear = $year < 50 ? 2000 + $year : 1900 + $year;
                return "$fullYear-$month-$day 00:00:00";
            }
            
            // Try manual parsing for MM/DD/YYYY format with slashes
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateValue, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "$year-$month-$day 00:00:00";
            }
            
            // Try manual parsing for MM-DD-YY format with dashes (2-digit year)
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2})$/', $dateValue, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = (int) $matches[3];
                // If year < 50, assume 2000s; otherwise 1900s
                $fullYear = $year < 50 ? 2000 + $year : 1900 + $year;
                return "$fullYear-$month-$day 00:00:00";
            }
            
            // Try manual parsing for MM-DD-YYYY format with dashes
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateValue, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "$year-$month-$day 00:00:00";
            }

            // Only use Carbon as a fallback with explicit format
            // This prevents Carbon from auto-detecting and potentially swapping MM/DD
            return \Carbon\Carbon::createFromFormat('m/d/Y', $dateValue)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::warning('Failed to parse date in BetImportService', [
                'value' => $dateValue,
                'error' => $e->getMessage()
            ]);

            // Return null if we can't parse it
            return null;
        }
    }

    /**
     * Ensure a required date field has a valid value
     * 
     * @param mixed $primaryDate The primary date value to use
     * @param mixed $fallbackDate An optional fallback date value
     * @param string $defaultDate The default date to use if all else fails (defaults to current datetime)
     * @return string A valid MySQL datetime string
     */
    private function ensureRequiredDate($primaryDate, $fallbackDate = null, string $defaultDate = null): string
    {
        // Try primary date first
        $formatted = $this->formatDateForMysql($primaryDate);
        if ($formatted !== null) {
            return $formatted;
        }

        // Try fallback date if provided
        if ($fallbackDate !== null) {
            $formatted = $this->formatDateForMysql($fallbackDate);
            if ($formatted !== null) {
                return $formatted;
            }
        }

        // Use default date or current datetime as last resort
        return $defaultDate ?? date('Y-m-d H:i:s');
    }

    public function importFromCsv(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $totalRecords = 0;

            // Only use transaction if not skipping errors
            if (!$this->skipErrors) {
                DB::beginTransaction();
            }

            foreach ($records as $offset => $record) {
                $totalRecords++;
                
                if ($this->skipErrors) {
                    // Process each record in its own transaction when skipping errors
                    DB::beginTransaction();
                    try {
                        $this->processRecord($record, $offset + 2); // +2 because header is line 1
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollBack();
                        // Error is already logged in processRecord
                    }
                } else {
                    $this->processRecord($record, $offset + 2);
                }
            }

            if (!$this->skipErrors) {
                if (! empty($this->errors)) {
                    DB::rollBack();

                    return [
                        'success' => false,
                        'message' => 'Import failed due to validation errors',
                        'errors' => $this->errors,
                        'processed' => $totalRecords,
                        'successCount' => $this->successCount,
                    ];
                }

                DB::commit();
            }

            Log::info('CSV import completed successfully', [
                'file' => $filePath,
                'stats' => $this->successCount,
            ]);

            return [
                'success' => true,
                'message' => 'Import completed successfully',
                'stats' => $this->successCount,
                'successCount' => $this->successCount,
                'processed' => $totalRecords,
                'errors' => $this->errors,
                'skippedEachWayBets' => $this->skippedEachWayBets,
                'skippedParlayBets' => $this->skippedParlayBets,
            ];

        } catch (\Exception $e) {
            if (!$this->skipErrors) {
                DB::rollBack();
            }

            Log::error('CSV import failed', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
                'errors' => $this->errors,
                'successCount' => $this->successCount,
                'processed' => $totalRecords ?? 0,
                'skippedEachWayBets' => $this->skippedEachWayBets,
                'skippedParlayBets' => $this->skippedParlayBets,
            ];
        }
    }

    private function processRecord(array $record, int $lineNumber): void
    {
        // Skip completely empty rows
        $hasNonEmptyValue = false;
        foreach ($record as $value) {
            if (!empty(trim($value))) {
                $hasNonEmptyValue = true;
                break;
            }
        }
        
        if (!$hasNonEmptyValue) {
            \Log::info('Skipping empty row at line ' . $lineNumber);
            return;
        }
        
        // Map the record using column mappings if provided
        if (! empty($this->columnMappings)) {
            $mappedRecord = [];
            foreach ($this->columnMappings as $field => $csvColumn) {
                if (isset($record[$csvColumn])) {
                    $mappedRecord[$field] = $record[$csvColumn];
                }
            }
            
            // Debug log for date mapping
            if ($lineNumber <= 5) {
                \Log::info("BetImportService Line {$lineNumber}: Date mapping", [
                    'game_date_raw' => $mappedRecord['game_date'] ?? 'not set',
                    'betting_date_raw' => $mappedRecord['betting_date'] ?? 'not set',
                ]);
            }
            
            // Debug log for missing sport mapping
            if (empty($mappedRecord['sport']) && $lineNumber >= 1741 && $lineNumber <= 1743) {
                \Log::warning("Line {$lineNumber}: Sport field not mapped properly", [
                    'original_record' => $record,
                    'column_mappings' => $this->columnMappings,
                    'mapped_record' => $mappedRecord,
                ]);
            }
            
            $record = $mappedRecord;
        }

        // Apply static values (these override any mapped values)
        foreach ($this->staticValues as $field => $value) {
            $record[$field] = $value;
        }

        // Transform data before validation
        $record = $this->transformRecordData($record);
        
        // Debug log for date after transformation
        if ($lineNumber <= 5) {
            \Log::info("BetImportService Line {$lineNumber}: After transformation", [
                'game_date_transformed' => $record['game_date'] ?? 'not set',
                'betting_date_transformed' => $record['betting_date'] ?? 'not set',
            ]);
        }
        
        // Don't skip Each Way bets anymore - we can import them now
        // The system supports Each Way bet calculations
        
        // Allow Parlay bets - will try to parse first two teams from the game field
        // Previously these were skipped, but now we'll import them with partial team data
        /*
        if (isset($record['wager_type']) && strcasecmp($record['wager_type'], 'parlay') === 0) {
            $this->skippedParlayBets[] = [
                'line' => $lineNumber,
                'data' => $record
            ];
            return;
        }
        */

        // Check if this is a partially empty row (has some data but missing critical fields)
        $criticalFields = ['sport', 'game', 'wager_name', 'odds'];
        $missingCriticalFields = [];
        $hasAnyCriticalData = false;
        $nonEmptyFieldCount = 0;
        
        // Count all non-empty fields
        foreach ($record as $field => $value) {
            if (!empty(trim($value))) {
                $nonEmptyFieldCount++;
            }
        }
        
        foreach ($criticalFields as $field) {
            if (!empty($record[$field])) {
                $hasAnyCriticalData = true;
            } else {
                $missingCriticalFields[] = $field;
            }
        }
        
        // If row has very few non-empty fields (likely an empty or header row), skip it
        if ($nonEmptyFieldCount < 3) {
            \Log::info('Skipping row with insufficient data at line ' . $lineNumber . ' (only ' . $nonEmptyFieldCount . ' non-empty fields)');
            return;
        }
        
        // If row has no critical data at all, skip it
        if (!$hasAnyCriticalData) {
            \Log::info('Skipping row with no critical data at line ' . $lineNumber);
            return;
        }
        
        // Validate record
        $validation = $this->validateRecord($record);
        if (! $validation['valid']) {
            // Add more context to the error message
            $errorMessage = "Row {$lineNumber}: ";
            if (!empty($missingCriticalFields)) {
                $errorMessage .= "Missing critical fields: " . implode(', ', $missingCriticalFields) . ". ";
            }
            
            $this->errors[] = [
                'line' => $lineNumber,
                'errors' => $validation['errors'],
                'data' => $record,
                'message' => $errorMessage,
            ];

            // If skipErrors is false, throw exception to stop processing
            if (!$this->skipErrors) {
                throw new \Exception('Validation failed at line ' . $lineNumber);
            }
            
            return;
        }
        
        // Additional validation for Each Way bets
        if (isset($record['wager_type']) && strtolower($record['wager_type']) === 'each_way') {
            $calculationService = app(BetCalculationService::class);
            $eachWayErrors = $calculationService->validateEachWayBetData($record);
            
            if (!empty($eachWayErrors)) {
                $this->errors[] = [
                    'line' => $lineNumber,
                    'errors' => ['each_way_validation' => $eachWayErrors],
                    'data' => $record,
                ];
                
                if (!$this->skipErrors) {
                    throw new \Exception('Each Way validation failed at line ' . $lineNumber . ': ' . implode(', ', $eachWayErrors));
                }
                
                return;
            }
        }

        try {
            // Get or create sport (normalize to lowercase)
            $sportName = strtolower($record['sport']);
            $sport = Sport::firstOrCreate(
                ['name' => $sportName],
                ['slug' => \Str::slug($sportName)]
            );

            // Get teams - should already be parsed in transformRecordData
            $homeTeamName = $record['home_team'] ?? null;
            $awayTeamName = $record['away_team'] ?? null;

            // Format matches field based on sport type and available teams first
            $matchesField = '';
            if ($awayTeamName && $homeTeamName) {
                // Team sports - format as "Away @ Home" or "Fighter1 vs Fighter2"
                $isCombatSport = in_array($sportName, ['ufc', 'mma', 'boxing', 'combat sports']);
                $matchesField = $isCombatSport ?
                    "{$awayTeamName} vs {$homeTeamName}" :
                    "{$awayTeamName} @ {$homeTeamName}";
            } elseif ($homeTeamName) {
                // Individual sports - just the player name
                $matchesField = $homeTeamName;
            }

            // Get or create teams
            $homeTeam = null;
            $awayTeam = null;
            $game = null;

            if ($homeTeamName) {
                // Check if team already exists for this sport
                $homeTeam = Team::where('name', $homeTeamName)
                    ->where('sport_id', $sport->id)
                    ->first();
                    
                if (!$homeTeam) {
                    try {
                        // Generate unique slug if needed
                        $baseSlug = \Str::slug($homeTeamName);
                        $slug = $baseSlug;
                        $counter = 1;
                        
                        while (Team::where('slug', $slug)->exists()) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }
                        
                        $homeTeam = Team::create([
                            'name' => $homeTeamName,
                            'sport_id' => $sport->id,
                            'slug' => $slug
                        ]);
                    } catch (\Illuminate\Database\QueryException $e) {
                        // Handle race condition - team was created by another process
                        if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                            $homeTeam = Team::where('name', $homeTeamName)
                                ->where('sport_id', $sport->id)
                                ->first();
                        } else {
                            throw $e;
                        }
                    }
                }

                // Only create away team if provided (not required for individual sports)
                if ($awayTeamName) {
                    $awayTeam = Team::where('name', $awayTeamName)
                        ->where('sport_id', $sport->id)
                        ->first();
                        
                    if (!$awayTeam) {
                        try {
                            // Generate unique slug if needed
                            $baseSlug = \Str::slug($awayTeamName);
                            $slug = $baseSlug;
                            $counter = 1;
                            
                            while (Team::where('slug', $slug)->exists()) {
                                $slug = $baseSlug . '-' . $counter;
                                $counter++;
                            }
                            
                            $awayTeam = Team::create([
                                'name' => $awayTeamName,
                                'sport_id' => $sport->id,
                                'slug' => $slug
                            ]);
                        } catch (\Illuminate\Database\QueryException $e) {
                            // Handle race condition - team was created by another process
                            if (str_contains($e->getMessage(), 'UNIQUE constraint failed')) {
                                $awayTeam = Team::where('name', $awayTeamName)
                                    ->where('sport_id', $sport->id)
                                    ->first();
                            } else {
                                throw $e;
                            }
                        }
                    }

                    // Skip game creation for now due to required fields
                    // $game = Game::create([...]);
                    // $this->successCount['games']++;
                }
            }

            // Get or create operator if provided
            $operator = null;
            if (! empty($record['operator'])) {
                $operator = Operator::firstOrCreate(
                    ['name' => $record['operator']],
                    ['slug' => \Str::slug($record['operator'])]
                );
            }

            // Create bet using the correct field names

            $betData = [
                // Use both old and new column names for compatibility
                'sport' => strtolower($record['sport']),
                'sports' => strtolower($record['sport']), // Keep old column for compatibility
                'league' => $record['league'] ?? null,
                'month' => $record['month'] ?? null,
                'game' => $record['game'] ?? $matchesField, // New column
                'matches' => $matchesField,
                'wager_type' => $record['wager_type'] ?? null, // New column
                'markets' => $record['wager_type'], // Keep old column for compatibility
                'wager_name' => $record['wager_name'] ?? $record['selection'] ?? '', // New column
                'team_one' => $homeTeamName ?? '',
                'team_one_id' => $homeTeam ? $homeTeam->id : null,
                'team_one_logo' => $homeTeam ? $homeTeam->logo : null,
                'team_two' => $awayTeamName ?? '',
                'team_two_id' => $awayTeam ? $awayTeam->id : null,
                'team_two_logo' => $awayTeam ? $awayTeam->logo : null,
                'tips' => $record['wager_name'] ?? $record['selection'] ?? '',
                'betting_date' => $this->ensureRequiredDate($record['betting_date'] ?? null, $record['game_date'] ?? null),
                'game_date' => $this->ensureRequiredDate($record['game_date'] ?? null, $record['betting_date'] ?? null),
                'odds' => (float) $record['odds'], // New column
                'wager_odds' => (float) $record['odds'], // Keep old column for compatibility
                'wager_amount' => (float) ($record['wager_amount'] ?? $record['stake'] ?? 0),
                'status' => $record['status'] ?? 'pending',
                'membership' => $this->normalizeMembership($record['membership'] ?? 'Bronze'),
                'level' => $record['level'] ?? null, // Optional field
                'code' => $record['code'] ?? null,
                'referrer' => $record['referrer'] ?? $record['code'] ?? null,
                'user_id' => $record['user_id'] ?? null,
                'sport_id' => $sport->id ?? null,
                'game_id' => $game->id ?? null,
            ];

            // Check if this is an Each-Way bet
            $isEachWay = isset($betData['wager_type']) &&
                strtolower($betData['wager_type']) === 'each_way';
            
            // Set Each Way fields
            if ($isEachWay) {
                $betData['is_each_way'] = true;
                $betData['each_way_stake'] = $betData['wager_amount'] / 2;
                $betData['place_fraction'] = ! empty($record['place_fraction']) ? 
                    (float) $record['place_fraction'] : 0.2; // Default 1/5
                $betData['place_terms_denominator'] = ! empty($record['place_terms_denominator']) ? 
                    (int) $record['place_terms_denominator'] : 5; // Default 1/5
            }
            
            // Handle golf_place field if available
            if (isset($record['golf_place'])) {
                $betData['golf_place'] = filter_var($record['golf_place'], FILTER_VALIDATE_BOOLEAN);
            }
            
            // Handle golf_place_fraction field if available
            if (isset($record['golf_place_fraction']) && !empty($record['golf_place_fraction'])) {
                // Convert fraction string to decimal if needed
                $value = $record['golf_place_fraction'];
                if (strpos($value, '/') !== false) {
                    // It's a fraction like "1/5"
                    $parts = explode('/', $value);
                    if (count($parts) == 2 && is_numeric($parts[0]) && is_numeric($parts[1]) && $parts[1] != 0) {
                        $betData['golf_place_fraction'] = (float)$parts[0] / (float)$parts[1];
                    }
                } elseif (is_numeric($value)) {
                    // It's already a decimal
                    $betData['golf_place_fraction'] = (float)$value;
                }
            }
            
            if (in_array($betData['status'], ['won', 'lost', 'placed'])) {

                if ($betData['status'] === 'won') {
                    // Check if we have pre-calculated values from CSV
                    if (! empty($record['winning_amount'])) {
                        $betData['winning_amount'] = (float) $record['winning_amount'];
                        $betData['profit_amount'] = ! empty($record['profit']) ? (float) $record['profit'] :
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
                            $placeFraction = ! empty($record['place_fraction']) ?
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
                            $betData['place_payout'] = $placeStake + $placeProfit;
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
                    $placeFraction = ! empty($record['place_fraction']) ?
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
                    $betData['place_payout'] = $placeStake + $placeProfit;
                } else {
                    // Lost bet
                    $betData['winning_amount'] = 0;
                    $betData['profit_amount'] = -$betData['wager_amount'];
                }
            } else {
                $betData['winning_amount'] = ! empty($record['winning_amount']) ? (float) $record['winning_amount'] : 0;
                $betData['profit_amount'] = ! empty($record['profit']) ? (float) $record['profit'] : 0;
            }

            // Calculate ROI
            if ($betData['wager_amount'] > 0) {
                $betData['roi'] = ($betData['profit_amount'] / $betData['wager_amount']) * 100;
            } else {
                $betData['roi'] = 0;
            }

            // Set roi_net from CSV or calculate it
            if (! empty($record['roi'])) {
                $betData['roi_net'] = (float) str_replace('%', '', $record['roi']);
            } else {
                $betData['roi_net'] = $betData['roi'];
            }
            
            // Also set profits for compatibility
            $betData['profits'] = $betData['profit_amount'];

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
        // Log parlay detection for debugging
        if (isset($record['wager_type']) && strcasecmp($record['wager_type'], 'parlay') === 0) {
            \Log::info('Processing parlay bet', [
                'game' => $record['game'] ?? 'N/A',
                'wager_type' => $record['wager_type']
            ]);
        }

        // Always parse teams from game column when present
        if (isset($record['game']) && ! empty($record['game'])) {
            $teams = $this->parseGameColumn($record['game']);
            if ($teams) {
                $record['home_team'] = $teams['home'];
                $record['away_team'] = $teams['away'];
                
                // Log if this was a parlay
                if (isset($teams['parlay_note'])) {
                    \Log::info('Parlay teams parsed', [
                        'original_game' => $record['game'],
                        'home_team' => $teams['home'],
                        'away_team' => $teams['away'],
                        'note' => $teams['parlay_note']
                    ]);
                }
            } else {
                // For individual sports like Golf, the entire game field is the player name
                $record['home_team'] = $record['game'];
                $record['away_team'] = null;
            }
        }
        
        // Parse place terms (e.g., "1/5" -> denominator = 5)
        if (isset($record['place_terms']) && !empty($record['place_terms'])) {
            if (preg_match('/1\/(\d+)/', $record['place_terms'], $matches)) {
                $record['place_terms_denominator'] = (int) $matches[1];
                $record['place_fraction'] = 1 / (int) $matches[1];
            }
        }

        // Handle both 'date' and 'game_date' fields from CSV
        $dateField = isset($record['date']) ? 'date' : (isset($record['game_date']) ? 'game_date' : null);

        // Parse game_date
        if ($dateField && isset($record[$dateField]) && ! empty($record[$dateField])) {
            try {
                // Try multiple date formats
                // IMPORTANT: m/d/Y (MM/DD/YYYY) is prioritized over d/m/Y
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d',
                    'm/d/y',      // MM/DD/YY format (2-digit year)
                    'n/j/y',      // M/D/YY format (2-digit year)
                    'm/d/Y',      // MM/DD/YYYY format (prioritized)
                    'n/j/Y',      // M/D/YYYY format
                    'm-d-Y',      // MM-DD-YYYY format
                    'm-d-y',      // MM-DD-YY format (2-digit year)
                    'm/d/Y H:i:s',
                    'm/d/Y H:i',
                    'Y/m/d',
                    // Note: d/m/Y formats removed to prevent date swapping
                ];

                $parsed = false;
                foreach ($formats as $format) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat($format, trim($record[$dateField]));
                        
                        // Handle 2-digit year conversion
                        if (in_array($format, ['m/d/y', 'n/j/y', 'm-d-y']) && $date->year < 100) {
                            $year = $date->year;
                            if ($year < 50) {
                                $date->year = 2000 + $year;
                            } else {
                                $date->year = 1900 + $year;
                            }
                        }
                        
                        $record['game_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (! $parsed) {
                    // Last resort - use formatDateForMysql method which prioritizes MM/DD/YYYY
                    $record['game_date'] = $this->formatDateForMysql($record[$dateField]);
                }
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }

        // Parse betting_date
        if (isset($record['betting_date']) && ! empty($record['betting_date'])) {
            try {
                // Try multiple date formats
                // IMPORTANT: m/d/Y (MM/DD/YYYY) is prioritized over d/m/Y
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d',
                    'm/d/y',      // MM/DD/YY format (2-digit year)
                    'n/j/y',      // M/D/YY format (2-digit year)
                    'm/d/Y',      // MM/DD/YYYY format (prioritized)
                    'n/j/Y',      // M/D/YYYY format
                    'm-d-Y',      // MM-DD-YYYY format
                    'm-d-y',      // MM-DD-YY format (2-digit year)
                    'm/d/Y H:i:s',
                    'm/d/Y H:i',
                    'Y/m/d',
                    // Note: d/m/Y formats removed to prevent date swapping
                ];

                $parsed = false;
                foreach ($formats as $format) {
                    try {
                        $date = \Carbon\Carbon::createFromFormat($format, trim($record['betting_date']));
                        
                        // Handle 2-digit year conversion
                        if (in_array($format, ['m/d/y', 'n/j/y', 'm-d-y']) && $date->year < 100) {
                            $year = $date->year;
                            if ($year < 50) {
                                $date->year = 2000 + $year;
                            } else {
                                $date->year = 1900 + $year;
                            }
                        }
                        
                        $record['betting_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (! $parsed) {
                    // Last resort - use formatDateForMysql method which prioritizes MM/DD/YYYY
                    $record['betting_date'] = $this->formatDateForMysql($record['betting_date']);
                }
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }

        // If betting_date is not provided, use game_date as fallback
        if (empty($record['betting_date']) && !empty($record['game_date'])) {
            $record['betting_date'] = $record['game_date'];
        }

        // Parse placed_at date
        if (isset($record['placed_at']) && ! empty($record['placed_at'])) {
            try {
                $record['placed_at'] = $this->formatDateForMysql($record['placed_at']);
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

        // Handle wager vs stake field naming - map both to wager_amount for database
        if (isset($record['wager'])) {
            $record['wager_amount'] = $this->parseMonetary($record['wager']);
            $record['stake'] = $record['wager_amount']; // Keep for compatibility
        } elseif (isset($record['stake'])) {
            $record['wager_amount'] = $this->parseMonetary($record['stake']);
        }
        
        // Parse ROI - handle percentage strings
        if (isset($record['roi'])) {
            $record['roi'] = $this->parsePercentage($record['roi']);
        }
        
        // Parse profits
        if (isset($record['profits'])) {
            $record['profits'] = $this->parseMonetary($record['profits']);
        }
        
        // Parse winning amount  
        if (isset($record['winning_amount'])) {
            $record['winning_amount'] = $this->parseMonetary($record['winning_amount']);
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
    
    private function parsePercentage($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Handle string values
        $value = (string) $value;
        
        // Remove percentage sign and whitespace
        $cleaned = trim(str_replace('%', '', $value));
        
        // Handle special cases like "N/A"
        if (strcasecmp($cleaned, 'n/a') === 0 || strcasecmp($cleaned, 'na') === 0) {
            return null;
        }
        
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
        if (! $status) {
            return 'pending';
        }

        $status = strtolower(trim($status));

        $statusMap = [
            'win' => 'won',
            'won' => 'won',
            'w' => 'won',
            'loss' => 'loss',
            'lost' => 'loss',
            'lose' => 'loss',
            'l' => 'loss',
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
        // Check if this might be a parlay by looking for multiple separators or commas
        $parlayIndicators = [', ', ' + ', ' & ', ' and ', ' / '];
        $isPossibleParlay = false;
        
        foreach ($parlayIndicators as $indicator) {
            if (str_contains($game, $indicator)) {
                $isPossibleParlay = true;
                break;
            }
        }
        
        // If it's a parlay, try to extract the first two teams
        if ($isPossibleParlay) {
            // First, try to find games separated by commas or other parlay indicators
            foreach ($parlayIndicators as $indicator) {
                if (str_contains($game, $indicator)) {
                    $games = explode($indicator, $game);
                    if (count($games) >= 1) {
                        // Try to parse the first game/matchup
                        $firstGame = trim($games[0]);
                        $teams = $this->parseIndividualGame($firstGame);
                        if ($teams) {
                            // Add a note that this is from a parlay
                            $teams['parlay_note'] = 'Partial data from parlay';
                            return $teams;
                        }
                    }
                }
            }
        }
        
        // Not a parlay, parse as single game
        return $this->parseIndividualGame($game);
    }
    
    private function parseIndividualGame(string $game): ?array
    {
        // Handle @ separator (most common in sports betting)
        if (str_contains($game, '@')) {
            $parts = explode('@', $game);
            if (count($parts) === 2) {
                // Clean up team names - remove game numbers like (Game 2)
                $away = trim($parts[0]);
                $home = trim($parts[1]);

                // Remove game indicators from home team
                $home = preg_replace('/\s*\(Game \d+\)\s*/', '', $home);

                return [
                    'away' => $away,
                    'home' => $home,
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
            'league' => 'nullable|string|max:255',
            'month' => 'nullable|string|max:50',
            'wager_type' => 'required|string|max:50',
            'wager_name' => 'required|string|max:250',  // Updated to 250
            'odds' => ['required', 'numeric', 'between:-100000,100000'], // Allow negative odds
            'wager_amount' => 'required|numeric|min:0.01',
            'game_date' => 'required|string', // Changed from 'date' to 'string' for more flexible parsing
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'roi' => 'nullable|numeric',
            'profits' => 'nullable|numeric',
            'winning_amount' => 'nullable|numeric',
            // Game is required - we'll parse teams from it during import
            'game' => 'required|string|max:250',  // Updated to 250
            'home_team' => 'required|string|max:255',  // Required - parsed from game
            'away_team' => 'nullable|string|max:255',  // Optional for individual sports
            'wager_type' => 'nullable|string|max:250',  // Updated to 250
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
            'referrer' => 'nullable|string|max:255',
            'golf_place_fraction' => 'nullable|numeric|between:0,1',
        ];

        $validator = Validator::make($record, $rules);

        return [
            'valid' => ! $validator->fails(),
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
                'wager_type',
                'selection',
                'odds',
                'stake',
                'status',
                'description',
                'placed_at',
                'game_status',
            ],
            'example' => [
                'sport' => 'NFL',
                'home_team' => 'Kansas City Chiefs',
                'away_team' => 'Buffalo Bills',
                'game_date' => '2024-01-15',
                'operator' => 'DraftKings',
                'wager_type' => 'Spread',
                'selection' => 'Chiefs -3.5',
                'odds' => '1.91',
                'stake' => '100',
                'status' => 'pending',
                'description' => 'Divisional round playoff game',
                'placed_at' => '2024-01-14 15:30:00',
                'game_status' => 'scheduled',
            ],
        ];
    }

    /**
     * Import a single bet (used by queue job)
     */
    public function importSingleBet(array $record, int $userId): void
    {
        // Set user_id in the record for processing
        $record['user_id'] = $userId;

        // Process the record using existing method
        $this->processRecord($record, 1);

        // Check if there were errors
        if (! empty($this->errors)) {
            $lastError = end($this->errors);
            throw new \Exception($lastError['errors']['processing'] ?? 'Import failed');
        }
    }
}
