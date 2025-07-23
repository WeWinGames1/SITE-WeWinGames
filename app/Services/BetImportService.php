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
            // If already in Y-m-d H:i:s format, return as is
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $dateValue)) {
                return $dateValue;
            }
            
            // If already in Y-m-d format, append time
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                return $dateValue . ' 00:00:00';
            }

            // Try parsing with Carbon - it handles many formats
            return \Carbon\Carbon::parse($dateValue)->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            // If Carbon fails, try manual parsing for MM-DD-YYYY format
            if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $dateValue, $matches)) {
                $month = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
                $day = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                $year = $matches[3];
                return "$year-$month-$day 00:00:00";
            }

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
            ];
        }
    }

    private function processRecord(array $record, int $lineNumber): void
    {
        // Map the record using column mappings if provided
        if (! empty($this->columnMappings)) {
            $mappedRecord = [];
            foreach ($this->columnMappings as $field => $csvColumn) {
                if (isset($record[$csvColumn])) {
                    $mappedRecord[$field] = $record[$csvColumn];
                }
            }
            $record = $mappedRecord;
        }

        // Apply static values (these override any mapped values)
        foreach ($this->staticValues as $field => $value) {
            $record[$field] = $value;
        }

        // Transform data before validation
        $record = $this->transformRecordData($record);

        // Validate record
        $validation = $this->validateRecord($record);
        if (! $validation['valid']) {
            $this->errors[] = [
                'line' => $lineNumber,
                'errors' => $validation['errors'],
                'data' => $record,
            ];

            // If skipErrors is false, throw exception to stop processing
            if (!$this->skipErrors) {
                throw new \Exception('Validation failed at line ' . $lineNumber);
            }
            
            return;
        }

        try {
            // Get or create sport
            $sport = Sport::firstOrCreate(
                ['name' => $record['sport']],
                ['slug' => \Str::slug($record['sport'])]
            );

            // Get teams - should already be parsed in transformRecordData
            $homeTeamName = $record['home_team'] ?? null;
            $awayTeamName = $record['away_team'] ?? null;

            // Format matches field based on sport type and available teams first
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
                'sport' => $record['sport'],
                'sports' => $record['sport'], // Keep old column for compatibility
                'league' => $record['league'] ?? null,
                'month' => $record['month'] ?? null,
                'game' => $record['game'] ?? $matchesField, // New column
                'matches' => $matchesField,
                'bet_type' => $record['bet_type'] ?? null, // New column
                'markets' => $record['bet_type'], // Keep old column for compatibility
                'wager_type' => $record['wager_type'] ?? null,
                'wager_name' => $record['wager_name'] ?? $record['selection'] ?? '', // New column
                'team_one' => $homeTeamName ?? '',
                'team_one_logo' => $homeTeam ? $homeTeam->logo : null,
                'team_two' => $awayTeamName ?? '',
                'team_two_logo' => $awayTeam ? $awayTeam->logo : null,
                'tips' => $record['wager_name'] ?? $record['selection'] ?? '',
                'betting_date' => $this->ensureRequiredDate($record['betting_date'] ?? null, $record['game_date'] ?? null),
                'game_date' => $this->ensureRequiredDate($record['game_date'] ?? null, $record['betting_date'] ?? null),
                'odds' => (float) $record['odds'], // New column
                'wager_odds' => (float) $record['odds'], // Keep old column for compatibility
                'wager_amount' => (float) ($record['wager_amount'] ?? $record['stake'] ?? 0),
                'status' => $record['status'] ?? 'pending',
                'membership' => $record['level'] ?? $record['membership'] ?? 'Bronze',
                'level' => $record['level'] ?? null,
                'code' => $record['code'] ?? null,
                'referrer' => $record['referrer'] ?? $record['code'] ?? null,
                'user_id' => $record['user_id'] ?? null,
                'sport_id' => $sport->id ?? null,
                'game_id' => $game->id ?? null,
            ];

            // Calculate winning and profit amounts based on American odds
            if (in_array($betData['status'], ['won', 'lost', 'placed'])) {
                // Check if this is an Each-Way bet
                $isEachWay = isset($betData['wager_type']) &&
                    strtolower($betData['wager_type']) === 'each way';

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

        // Always parse teams from game column when present
        if (isset($record['game']) && ! empty($record['game'])) {
            $teams = $this->parseGameColumn($record['game']);
            if ($teams) {
                $record['home_team'] = $teams['home'];
                $record['away_team'] = $teams['away'];
            } else {
                // For individual sports like Golf, the entire game field is the player name
                $record['home_team'] = $record['game'];
                $record['away_team'] = null;
            }
        }

        // Handle both 'date' and 'game_date' fields from CSV
        $dateField = isset($record['date']) ? 'date' : (isset($record['game_date']) ? 'game_date' : null);

        // Parse game_date
        if ($dateField && isset($record[$dateField]) && ! empty($record[$dateField])) {
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
                        $date = \Carbon\Carbon::createFromFormat($format, trim($record[$dateField]));
                        $record['game_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (! $parsed) {
                    // Last resort - let Carbon try to parse it
                    $record['game_date'] = \Carbon\Carbon::parse($record[$dateField])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }

        // Parse betting_date
        if (isset($record['betting_date']) && ! empty($record['betting_date'])) {
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
                        $date = \Carbon\Carbon::createFromFormat($format, trim($record['betting_date']));
                        $record['betting_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }

                if (! $parsed) {
                    // Last resort - let Carbon try to parse it
                    $record['betting_date'] = \Carbon\Carbon::parse($record['betting_date'])->format('Y-m-d H:i:s');
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
            'bet_type' => 'required|string|max:50',
            'wager_name' => 'required|string|max:250',  // Updated to 250
            'odds' => ['required', 'numeric', 'between:-100000,100000'], // Allow negative odds
            'wager_amount' => 'required|numeric|min:0.01',
            'game_date' => 'required|string', // Changed from 'date' to 'string' for more flexible parsing
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
            'roi' => 'nullable|numeric',
            'profits' => 'nullable|numeric',
            'winning_amount' => 'nullable|numeric|min:0',
            // Game is required - we'll parse teams from it during import
            'game' => 'required|string|max:250',  // Updated to 250
            'home_team' => 'required|string|max:255',  // Required - parsed from game
            'away_team' => 'nullable|string|max:255',  // Optional for individual sports
            'wager_type' => 'nullable|string|max:250',  // Updated to 250
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
            'referrer' => 'nullable|string|max:255',
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
                'bet_type',
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
                'bet_type' => 'Spread',
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
