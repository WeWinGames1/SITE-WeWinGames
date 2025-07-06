<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;

class CsvImportService
{
    private array $requiredColumns = [];
    private array $optionalColumns = [];
    private array $columnMappings = [];
    private array $staticValues = [];
    private array $validationRules = [];
    private array $errors = [];
    private array $warnings = [];
    
    /**
     * Analyze CSV file and return column information
     */
    public function analyzeCsv(string $filePath): array
    {
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $headers = $csv->getHeader();
            $stmt = Statement::create()->limit(5);
            $records = $stmt->process($csv);
            
            $sampleData = [];
            foreach ($records as $record) {
                $sampleData[] = $record;
            }
            
            return [
                'success' => true,
                'headers' => $headers,
                'sample_data' => $sampleData,
                'total_rows' => count($csv) - 1, // Minus header row
                'detected_mappings' => $this->detectColumnMappings($headers),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to analyze CSV: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Detect possible column mappings based on header names
     */
    private function detectColumnMappings(array $headers): array
    {
        $mappings = [];
        // Priority order matters - exact matches for CSV format should come first
        $commonMappings = [
            'sport' => ['sport', 'sports', 'sport_name', 'category', 'sport_type'],
            'league' => ['league', 'competition', 'tournament', 'division', 'conference', 'comp', 'championship', 'event'],
            'month' => ['month', 'month_name'],
            'game_date' => ['date', 'game_date', 'match_date', 'event_date', 'betting_date', 'gamedate', 'game date', 'kickoff'],
            'game' => ['game', 'games', 'match', 'matchup', 'fixture', 'contest', 'game/player'],
            'bet_type' => ['bet type', 'bet_type', 'bettype', 'type', 'market', 'markets', 'bet_market'],
            'wager_type' => ['wager type', 'wager_type', 'wagertype', 'wagering_type', 'bet style'],
            'wager_name' => ['wager name', 'wager_name', 'wagername', 'selection', 'pick', 'bet', 'tip', 'tips', 'bet description'],
            'odds' => ['odds', 'american odds', 'price', 'wager_odds', 'line', 'betting_odds'],
            'level' => ['level', 'tier', 'membership', 'subscription_level', 'confidence level', 'plan'],
            'code' => ['code', 'tracking code', 'source code', 'capper', 'referrer_code', 'affiliate_code'],
            'status' => ['status', 'result', 'outcome', 'bet_status', 'bet outcome'],
            'roi' => ['roi(net)', 'roi', 'roi_net', 'return on investment', 'net_roi'],
            'wager' => ['wager', 'stake', 'wager_amount', 'wager amount', 'amount staked', 'bet_amount'],
            'profits' => ['profits', 'profit', 'net gain', 'pnl', 'p&l', 'net gain/loss'],
            'winning_amount' => ['winning amount', 'winning_amount', 'winningamount', 'total returned', 'payout'],
        ];
        
        // First pass: exact matches only (to avoid incorrect fuzzy matches)
        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));
            $normalized = str_replace(['_', '-'], ' ', $normalized); // Normalize underscores and hyphens
            
            foreach ($commonMappings as $field => $variations) {
                if (in_array($normalized, $variations)) {
                    $mappings[$field] = $header;
                    break;
                }
            }
        }
        
        // Special handling for exact case-sensitive matches from the CSV format
        $exactMappings = [
            'Sport' => 'sport',
            'League' => 'league', 
            'Month' => 'month',
            'Date' => 'game_date',
            'Home Team' => 'home_team',
            'Away Team' => 'away_team',
            'Game' => 'game',  // For backward compatibility
            'Bet Type' => 'bet_type',
            'Wager Type' => 'wager_type',
            'Wager Name' => 'wager_name',
            'odds' => 'odds',
            'level' => 'level',
            'code' => 'code',
            'Status' => 'status',
            'ROI(net)' => 'roi',
            'Wager' => 'wager',
            'Profits' => 'profits',
            'Winning Amount' => 'winning_amount'
        ];
        
        // Also add lowercase versions (excluding date to avoid conflict)
        $exactMappingsLower = [
            'sport' => 'sport',
            'league' => 'league',
            'month' => 'month',
            'home team' => 'home_team',
            'away team' => 'away_team',
            'game' => 'game',
            'bet type' => 'bet_type',
            'wager type' => 'wager_type',
            'wager name' => 'wager_name',
            'roi(net)' => 'roi',
            'profits' => 'profits',
            'winning amount' => 'winning_amount'
        ];
        
        foreach ($headers as $header) {
            $trimmedHeader = trim($header);
            $lowerHeader = strtolower($trimmedHeader);
            
            // Try exact case match first
            if (isset($exactMappings[$trimmedHeader]) && !isset($mappings[$exactMappings[$trimmedHeader]])) {
                $mappings[$exactMappings[$trimmedHeader]] = $header;
            }
            // Then try lowercase match
            elseif (isset($exactMappingsLower[$lowerHeader]) && !isset($mappings[$exactMappingsLower[$lowerHeader]])) {
                $mappings[$exactMappingsLower[$lowerHeader]] = $header;
            }
        }
        
        // Second pass: fuzzy matches for unmapped fields
        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));
            $normalized = str_replace(['_', '-'], ' ', $normalized);
            
            // Only do fuzzy matching for fields that aren't already mapped
            foreach ($commonMappings as $field => $variations) {
                if (!isset($mappings[$field]) && $this->fuzzyMatch($normalized, $variations)) {
                    $mappings[$field] = $header;
                    break;
                }
            }
        }
        
        return $mappings;
    }
    
    /**
     * Fuzzy match header against variations
     */
    private function fuzzyMatch(string $header, array $variations): bool
    {
        foreach ($variations as $variation) {
            if (Str::contains($header, $variation) || 
                Str::contains($variation, $header) ||
                levenshtein($header, $variation) <= 2) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Validate CSV data with column mappings
     */
    public function validateImport(string $filePath, array $columnMappings, array $staticValues = []): array
    {
        $this->columnMappings = $columnMappings;
        $this->staticValues = $staticValues;
        $this->errors = [];
        $this->warnings = [];
        
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $validRows = [];
            $invalidRows = [];
            $rowNumber = 1; // Start after header
            $totalRowsProcessed = 0;
            
            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
                $totalRowsProcessed++;
                $mappedData = $this->mapRowData($record);
                $validation = $this->validateRow($mappedData, $rowNumber);
                
                if ($validation['valid']) {
                    $validRows[] = [
                        'row' => $rowNumber,
                        'data' => $mappedData,
                        'warnings' => $validation['warnings'] ?? [],
                    ];
                } else {
                    $invalidRows[] = [
                        'row' => $rowNumber,
                        'data' => $mappedData,
                        'errors' => $validation['errors'],
                    ];
                }
                
                // Limit preview to first 100 rows
                if (count($validRows) + count($invalidRows) >= 100) {
                    break;
                }
            }
            
            // Count total rows properly
            $totalRows = iterator_count($csv->getRecords());
            
            return [
                'success' => true,
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'summary' => [
                    'total' => $totalRows,
                    'valid' => count($validRows),
                    'invalid' => count($invalidRows),
                    'preview_limit' => 100,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
            ];
        }
    }
    
    /**
     * Map row data using column mappings
     */
    private function mapRowData(array $record): array
    {
        $mapped = [];
        
        // First map columns from CSV
        foreach ($this->columnMappings as $field => $csvColumn) {
            if (isset($record[$csvColumn])) {
                $value = $this->cleanValue($record[$csvColumn]);
                $mapped[$field] = $value;
            }
        }
        
        // Then apply static values (these override any mapped values)
        foreach ($this->staticValues as $field => $value) {
            $mapped[$field] = $value;
        }
        
        // Apply data transformations
        return $this->transformData($mapped);
    }
    
    /**
     * Clean individual values
     */
    private function cleanValue($value): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }
        return $value;
    }
    
    /**
     * Transform data based on field type
     */
    private function transformData(array $data): array
    {
        // Always parse teams from game column when present
        if (isset($data['game']) && !empty($data['game'])) {
            $teams = $this->parseGameColumn($data['game']);
            if ($teams) {
                $data['home_team'] = $teams['home'];
                $data['away_team'] = $teams['away'];
            } else {
                // For individual sports like Golf, the entire game field is the player name
                $data['home_team'] = $data['game'];
                $data['away_team'] = null;
            }
        }
        
        // Parse dates - handle both 'date' and 'game_date' fields
        $dateField = isset($data['date']) ? 'date' : (isset($data['game_date']) ? 'game_date' : null);
        
        if ($dateField && !empty($data[$dateField])) {
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
                        $date = \Carbon\Carbon::createFromFormat($format, $data[$dateField]);
                        $data['game_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if (!$parsed) {
                    // Last resort - let Carbon try to parse it
                    $data['game_date'] = \Carbon\Carbon::parse($data[$dateField])->format('Y-m-d H:i:s');
                }
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }
        
        // Parse placed_at date
        if (isset($data['placed_at']) && !empty($data['placed_at'])) {
            try {
                $data['placed_at'] = \Carbon\Carbon::parse($data['placed_at'])->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                // Keep original value if parse fails
            }
        }
        
        // Parse numeric values - keep American odds format
        if (isset($data['odds'])) {
            $data['odds'] = $this->parseNumeric($data['odds'], true); // Keep American odds format
        }
        
        // Handle both 'wager' and 'stake' fields for backward compatibility
        if (isset($data['wager'])) {
            $parsedValue = $this->parseMonetary($data['wager']);
            $data['wager'] = $parsedValue; // Update wager field with parsed value
            $data['stake'] = $parsedValue; // Also set stake for backward compatibility
        } elseif (isset($data['stake'])) {
            $data['stake'] = $this->parseMonetary($data['stake']);
        }
        
        // Handle profit/profits fields
        if (isset($data['profits'])) {
            $profitValue = $data['profits'];
            // Handle empty or dash values
            if ($profitValue === '-' || $profitValue === '' || $profitValue === null) {
                $data['profits'] = 0;
            } else {
                $data['profits'] = $this->parseMonetary($profitValue);
            }
        } elseif (isset($data['profit'])) {
            $data['profit'] = $this->parseMonetary($data['profit']);
        }
        
        // Parse winning amount
        if (isset($data['winning_amount'])) {
            $winningValue = $data['winning_amount'];
            // Handle empty or dash values
            if ($winningValue === '-' || $winningValue === '' || $winningValue === null) {
                $data['winning_amount'] = 0;
            } else {
                $data['winning_amount'] = $this->parseMonetary($winningValue);
            }
        }
        
        // Parse ROI if provided as percentage
        if (isset($data['roi'])) {
            $roiValue = $data['roi'];
            // Handle empty or dash values
            if ($roiValue === '-' || $roiValue === '' || $roiValue === null) {
                $data['roi'] = 0;
            } elseif (is_string($roiValue) && str_ends_with($roiValue, '%')) {
                $data['roi'] = (float) str_replace('%', '', $roiValue);
            } else {
                $data['roi'] = $this->parseNumeric($roiValue);
            }
        }
        
        // Normalize status
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }
        
        // Parse teams from combined field if needed
        if (!isset($data['home_team']) && !isset($data['away_team']) && isset($data['teams'])) {
            $teams = $this->parseTeams($data['teams']);
            $data['home_team'] = $teams['home'] ?? null;
            $data['away_team'] = $teams['away'] ?? null;
            unset($data['teams']);
        }
        
        // Trim all string values
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = trim($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Parse numeric value
     */
    private function parseNumeric($value, $keepAmericanOdds = false): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Handle American odds
        if (is_string($value) && (str_starts_with($value, '+') || str_starts_with($value, '-'))) {
            if ($keepAmericanOdds) {
                // Keep American odds as-is
                $cleaned = preg_replace('/[^0-9+-]/', '', $value);
                return is_numeric($cleaned) ? (float) $cleaned : null;
            } else {
                return $this->convertAmericanToDecimal($value);
            }
        }
        
        // Remove non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
    
    /**
     * Parse monetary value
     */
    private function parseMonetary($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Remove currency symbols and thousands separators
        $cleaned = preg_replace('/[^0-9.-]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
    
    /**
     * Convert American odds to decimal
     */
    private function convertAmericanToDecimal(string $americanOdds): float
    {
        $odds = (int) $americanOdds;
        
        if ($odds > 0) {
            return ($odds / 100) + 1;
        } else {
            return (100 / abs($odds)) + 1;
        }
    }
    
    /**
     * Normalize status values
     */
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
            'pending' => 'pending',
            'open' => 'pending',
            'active' => 'pending',
            'placed' => 'placed',  // For E/W bets that placed but didn't win
            'cashout' => 'cashout',
            'cash out' => 'cashout',
        ];
        
        return $statusMap[$status] ?? 'pending';
    }
    
    /**
     * Parse teams from combined field
     */
    private function parseTeams(string $teams): array
    {
        // Common separators
        $separators = [' @ ', ' vs ', ' v ', ' - ', ' at '];
        
        foreach ($separators as $separator) {
            if (str_contains($teams, $separator)) {
                $parts = explode($separator, $teams);
                return [
                    'away' => trim($parts[0] ?? ''),
                    'home' => trim($parts[1] ?? ''),
                ];
            }
        }
        
        return ['home' => $teams, 'away' => ''];
    }
    
    /**
     * Parse game column in format "Away Team @ Home Team"
     */
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
        
        // Try other common separators as fallback
        return $this->parseTeams($game);
    }
    
    /**
     * Validate a single row
     */
    private function validateRow(array $data, int $rowNumber): array
    {
        // Custom messages for better error reporting
        $messages = [
            'sport.required' => 'Sport is required',
            'league.required' => 'League is required',
            'month.required' => 'Month is required',
            'game_date.required' => 'Date is required',
            'game.required' => 'Game is required',
            'bet_type.required' => 'Bet Type is required',
            'wager_type.required' => 'Wager Type is required',
            'wager_name.required' => 'Wager Name is required',
            'odds.required' => 'odds are required',
            'odds.numeric' => 'odds must be a number',
            'level.required' => 'level is required',
            'code.required' => 'code is required',
            'status.required' => 'Status is required',
            'roi.required' => 'ROI(net) is required',
            'roi.numeric' => 'ROI(net) must be a number',
            'wager.required' => 'Wager is required',
            'wager.numeric' => 'Wager must be a number',
            'profits.required' => 'Profits is required',
            'profits.numeric' => 'Profits must be a number',
            'winning_amount.required' => 'Winning Amount is required',
            'winning_amount.numeric' => 'Winning Amount must be a number',
            'home_team.required' => 'Unable to parse home team from Game column',
            'away_team.required' => 'Unable to parse away team from Game column',
        ];
        
        $rules = $this->getValidationRules();
        $validator = Validator::make($data, $rules, $messages);
        
        if ($validator->fails()) {
            // Format errors for better display
            $errors = [];
            foreach ($validator->errors()->toArray() as $field => $fieldErrors) {
                $errors[$field] = implode(', ', $fieldErrors);
            }
            
            return [
                'valid' => false,
                'errors' => $errors,
            ];
        }
        
        // Additional business logic validation
        $warnings = [];
        
        // Check for duplicate bets
        if ($this->isDuplicateBet($data)) {
            $warnings[] = 'This bet may be a duplicate';
        }
        
        // Check odds range for American odds
        if (isset($data['odds'])) {
            // American odds can be negative (e.g., -130) or positive (e.g., +150)
            if ($data['odds'] < -10000 || $data['odds'] > 10000) {
                $warnings[] = 'Unusual odds value';
            }
        }
        
        // Check stake amount
        if (isset($data['stake']) && $data['stake'] > 10000) {
            $warnings[] = 'High stake amount';
        }
        
        return [
            'valid' => true,
            'warnings' => $warnings,
        ];
    }
    
    /**
     * Parse CSV with column mappings for job processing
     */
    public function parseWithMappings(string $filePath, array $mappings): array
    {
        // Set column mappings
        $this->columnMappings = $mappings;
        
        // Read and parse CSV file
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        
        $records = [];
        foreach ($csv->getRecords() as $record) {
            // Map columns according to provided mappings
            $mappedRecord = [];
            foreach ($mappings as $field => $csvColumn) {
                if (isset($record[$csvColumn])) {
                    $mappedRecord[$field] = $record[$csvColumn];
                }
            }
            
            // Transform data (parse dates, clean values, etc.)
            $mappedRecord = $this->transformData($mappedRecord);
            
            $records[] = $mappedRecord;
        }
        
        return $records;
    }

    /**
     * Get validation rules for import
     */
    public function getValidationRules(): array
    {
        return [
            'sport' => 'required|string|max:255',
            'league' => 'required|string|max:255',
            'month' => 'required|string|max:50',
            'game_date' => 'required|string', // Changed from 'date' to 'string' for more flexible parsing
            'game' => 'required|string|max:500',
            'bet_type' => 'required|string|max:50',
            'wager_type' => 'required|string|max:50',
            'wager_name' => 'required|string|max:255',
            'odds' => ['required', 'numeric', 'between:-10000,10000'], // Allow negative odds
            'level' => 'required|string|max:50',
            'code' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'roi' => 'nullable|numeric',
            'wager' => 'required|numeric|min:0.01|max:100000',
            'profits' => 'nullable|numeric',
            'winning_amount' => 'nullable|numeric|min:0',
            // Home/Away teams are parsed from game column
            'home_team' => 'required|string|max:255',
            'away_team' => 'nullable|string|max:255',
            // Optional fields
            'stake' => 'nullable|numeric|min:0.01|max:100000',
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
        ];
    }
    
    /**
     * Check if bet is duplicate
     */
    private function isDuplicateBet(array $data): bool
    {
        // This would check against existing bets in database
        // For now, return false
        return false;
    }
    
    /**
     * Get required and optional columns
     */
    public function getColumnRequirements(): array
    {
        return [
            'required' => [
                'sport' => 'The sport being bet on (e.g., Baseball, Combat Sports, Golf)',
                'league' => 'The league or event name (e.g., MLB, UFC, PGA)',
                'month' => 'Calendar month the bet is placed or settles',
                'game_date' => 'Date of the event or bet (MM/DD/YYYY)',
                'game' => 'The specific matchup or contest (e.g., Yankees @ Red Sox, Dustin Poirier vs. Islam Makhachev)',
                'bet_type' => 'General type of bet (Moneyline, Spread, Player Prop, etc)',
                'wager_type' => 'Specific betting style (Straight, Outright, Each Way, Parlay)',
                'wager_name' => 'Detailed description of the bet (e.g., "Chicago Cubs (S Imanaga) ML," "Ilia Topuria to win by KO")',
                'odds' => 'American odds (e.g., -120, +150)',
                'level' => 'Subscription or confidence level (Bronze, Silver, Gold, Platinum)',
                'code' => 'Unique/internal code for tracking bet source, system, or capper (e.g., BB, TPP, Golf Brad)',
                'status' => 'Outcome of the bet ("Won", "Lost", "Placed", "Pending")',
                'roi' => 'Net Return on Investment as % of the stake',
                'wager' => 'Dollar amount staked on the bet',
                'profits' => 'Net gain or loss (USD) for the bet (can be negative)',
                'winning_amount' => 'Total returned if the bet wins (Wager + Profits; $0 if lost)',
            ],
            'optional' => [],
        ];
    }
    
    /**
     * Generate sample CSV template
     */
    public function generateSampleCsv(): string
    {
        $headers = [
            'Sports',
            'League',
            'Date/Time',
            'Betting Date',
            'Matches',
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
        ];
        
        $sampleData = [
            [
                'NFL',
                'National Football League',
                '2024-01-15 13:00',
                '2024-01-14',
                'Kansas City Chiefs @ Buffalo Bills',
                '13:00',
                'Spread',
                'Chiefs -3.5',
                '1.91',
                'Premium',
                'ESPN',
                'Won',
                '91%',
                '$100',
                '$191',
                '$91',
            ],
            [
                'NBA',
                'National Basketball Association',
                '2024-01-16 19:30',
                '2024-01-16',
                'Boston Celtics @ Los Angeles Lakers',
                '19:30',
                'Moneyline',
                'Lakers ML',
                '2.25',
                'Gold',
                'Twitter',
                'Won',
                '125%',
                '$50',
                '$112.50',
                '$62.50',
            ],
            [
                'MLB',
                'Major League Baseball',
                '2024-05-01 19:00',
                '2024-05-01',
                'Boston Red Sox @ New York Yankees',
                '19:00',
                'Over/Under',
                'Over 8.5',
                '1.85',
                'Silver',
                'BetMGM',
                'Lost',
                '-100%',
                '$75',
                '$0',
                '-$75',
            ],
        ];
        
        $filename = 'bet_import_template_' . now()->format('Y-m-d') . '.csv';
        $path = 'temp/' . $filename;
        
        Storage::disk('local')->put($path, implode(',', $headers) . "\n");
        
        foreach ($sampleData as $row) {
            Storage::disk('local')->append($path, implode(',', $row) . "\n");
        }
        
        return Storage::disk('local')->path($path);
    }
}