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
        $commonMappings = [
            'sport' => ['sport', 'sports', 'sport_name', 'category', 'sport_type'],
            'home_team' => ['home_team', 'home', 'team_1', 'team1', 'host', 'hometeam', 'home team'],
            'away_team' => ['away_team', 'away', 'team_2', 'team2', 'visitor', 'awayteam', 'away team', 'visiting_team'],
            'game_date' => ['game_date', 'date', 'match_date', 'event_date', 'betting_date', 'gamedate', 'game date', 'kickoff', 'month'],
            'bet_type' => ['bet_type', 'bettype', 'bet type', 'wager_type', 'wager type', 'type', 'market', 'markets', 'bet_market'],
            'selection' => ['selection', 'wager_name', 'wager name', 'wager', 'pick', 'bet', 'tip', 'tips', 'choice', 'team', 'side', 'prediction'],
            'odds' => ['odds', 'price', 'decimal_odds', 'wager_odds', 'line', 'betting_odds'],
            'stake' => ['stake', 'wager_amount', 'wager amount', 'amount', 'bet_amount', 'risk', 'unit', 'units', 'wagered'],
            'status' => ['status', 'result', 'outcome', 'bet_status', 'win_loss', 'win/loss', 'settled'],
            'operator' => ['operator', 'code', 'bookmaker', 'sportsbook', 'book', 'bookie', 'betting_site', 'site'],
            'description' => ['description', 'notes', 'comment', 'remarks', 'desc', 'details', 'info', 'level'],
            'placed_at' => ['placed_at', 'placed', 'bet_date', 'bet_time', 'placed_date', 'placedat', 'placed at', 'wagered_at'],
            'league' => ['league', 'competition', 'tournament', 'division', 'conference', 'comp', 'championship'],
            'referrer' => ['referrer', 'referer', 'source', 'ref', 'affiliate', 'tracking', 'campaign'],
            'profit' => ['profit', 'profits', 'pnl', 'p&l', 'return', 'win_amount', 'winning_amount', 'winning amount', 'net', 'roi(net)', 'roi'],
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
        
        // Second pass: check for "game" column and fuzzy matches for unmapped fields
        $gameColumn = null;
        
        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));
            $normalized = str_replace(['_', '-'], ' ', $normalized);
            
            // Check if this is a "game" column
            if (in_array($normalized, ['game', 'games', 'match', 'matchup', 'fixture', 'event'])) {
                $gameColumn = $header;
                // Map it to both home_team and away_team if they're not already mapped
                if (!isset($mappings['home_team']) && !isset($mappings['away_team'])) {
                    $mappings['game'] = $header;
                    $mappings['home_team'] = $header;
                    $mappings['away_team'] = $header;
                }
                continue;
            }
            
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
    public function validateImport(string $filePath, array $columnMappings): array
    {
        $this->columnMappings = $columnMappings;
        $this->errors = [];
        $this->warnings = [];
        
        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            $validRows = [];
            $invalidRows = [];
            $rowNumber = 1; // Start after header
            
            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
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
            
            return [
                'success' => true,
                'total_rows' => count($csv) - 1,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'summary' => [
                    'total' => count($csv) - 1,
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
        
        foreach ($this->columnMappings as $field => $csvColumn) {
            if (isset($record[$csvColumn])) {
                // Special handling for "game" field that contains both teams
                if ($field === 'game') {
                    $gameValue = $this->cleanValue($record[$csvColumn]);
                    if ($gameValue) {
                        $teams = $this->parseGameColumn($gameValue);
                        if ($teams) {
                            $mapped['away_team'] = $teams['away'];
                            $mapped['home_team'] = $teams['home'];
                        }
                    }
                } else {
                    $mapped[$field] = $this->cleanValue($record[$csvColumn]);
                }
            }
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
        // Parse dates
        if (isset($data['game_date']) && !empty($data['game_date'])) {
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
                        $date = \Carbon\Carbon::createFromFormat($format, $data['game_date']);
                        $data['game_date'] = $date->format('Y-m-d H:i:s');
                        $parsed = true;
                        break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
                
                if (!$parsed) {
                    // Last resort - let Carbon try to parse it
                    $data['game_date'] = \Carbon\Carbon::parse($data['game_date'])->format('Y-m-d H:i:s');
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
        
        // Parse numeric values
        if (isset($data['odds'])) {
            $data['odds'] = $this->parseNumeric($data['odds']);
        }
        
        if (isset($data['stake'])) {
            $data['stake'] = $this->parseMonetary($data['stake']);
        }
        
        if (isset($data['profit'])) {
            $data['profit'] = $this->parseMonetary($data['profit']);
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
    private function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        // Handle American odds
        if (is_string($value) && (str_starts_with($value, '+') || str_starts_with($value, '-'))) {
            return $this->convertAmericanToDecimal($value);
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
                return [
                    'away' => trim($parts[0]),
                    'home' => trim($parts[1]),
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
            'home_team.required' => 'Home team is required',
            'away_team.required' => 'Away team is required',
            'game_date.required' => 'Game date is required',
            'game_date.date' => 'Game date must be a valid date',
            'bet_type.required' => 'Bet type is required',
            'selection.required' => 'Selection is required',
            'odds.required' => 'Odds are required',
            'odds.numeric' => 'Odds must be a number',
            'stake.required' => 'Stake is required',
            'stake.numeric' => 'Stake must be a number',
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
        
        // Check odds range
        if (isset($data['odds']) && ($data['odds'] < 1.01 || $data['odds'] > 100)) {
            $warnings[] = 'Unusual odds value';
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
     * Get validation rules for import
     */
    public function getValidationRules(): array
    {
        return [
            'sport' => 'required|string|max:255',
            'home_team' => 'required|string|max:255',
            'away_team' => 'required|string|max:255',
            'game_date' => 'required|string', // Changed from 'date' to 'string' for more flexible parsing
            'bet_type' => 'required|string|max:50',
            'selection' => 'required|string|max:255',
            'odds' => 'required|numeric|min:1.01|max:1000',
            'stake' => 'required|numeric|min:0.01|max:100000',
            'operator' => 'nullable|string|max:255',
            'status' => 'nullable|in:pending,won,lost,void,push',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
            'league' => 'nullable|string|max:255',
            'referrer' => 'nullable|string|max:255',
            'profit' => 'nullable|numeric',
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
                'sport' => 'The sport being bet on (e.g., NFL, NBA, MLB)',
                'home_team' => 'The home team name. Can be extracted from a "Game" column if it contains both teams',
                'away_team' => 'The away/visiting team name. Can be extracted from a "Game" column if it contains both teams',
                'game_date' => 'Date when the game is played. Accepts various formats like YYYY-MM-DD, MM/DD/YYYY, etc.',
                'bet_type' => 'The type of bet placed (e.g., Spread, Moneyline, Over/Under, Prop)',
                'selection' => 'Your specific bet selection/pick (e.g., "Chiefs -3.5", "Over 220.5", "Team A ML")',
                'odds' => 'The betting odds in decimal format (e.g., 1.91, 2.50). American odds (+150, -110) will be converted',
                'stake' => 'The amount of money wagered on this bet',
            ],
            'optional' => [
                'operator' => 'The sportsbook or betting site where the bet was placed (e.g., DraftKings, FanDuel)',
                'status' => 'Current status of the bet: pending (not settled), won, lost, void, or push',
                'description' => 'Any additional notes, analysis, or details about the bet',
                'placed_at' => 'The exact date/time when the bet was placed (if different from game date)',
                'league' => 'The league or competition (e.g., Premier League, Champions League, Eastern Conference)',
                'referrer' => 'Source or tipster who recommended this bet (for tracking purposes)',
                'profit' => 'The profit/loss amount from this bet (will be calculated if not provided)',
            ],
        ];
    }
    
    /**
     * Generate sample CSV template
     */
    public function generateSampleCsv(): string
    {
        $headers = [
            'Sport',
            'Home Team',
            'Away Team',
            'Game Date',
            'Bet Type',
            'Selection',
            'Odds',
            'Stake',
            'Operator',
            'Status',
            'Description',
        ];
        
        $sampleData = [
            [
                'NFL',
                'Kansas City Chiefs',
                'Buffalo Bills',
                '2024-01-15',
                'Spread',
                'Chiefs -3.5',
                '1.91',
                '100',
                'DraftKings',
                'pending',
                'AFC Championship game',
            ],
            [
                'NBA',
                'Los Angeles Lakers',
                'Boston Celtics',
                '2024-01-16',
                'Moneyline',
                'Lakers ML',
                '2.25',
                '50',
                'FanDuel',
                'won',
                'Regular season game',
            ],
            [
                'MLB',
                'New York Yankees',
                'Boston Red Sox',
                '2024-05-01',
                'Over/Under',
                'Over 8.5',
                '1.85',
                '75',
                'BetMGM',
                'lost',
                'Rivalry game',
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