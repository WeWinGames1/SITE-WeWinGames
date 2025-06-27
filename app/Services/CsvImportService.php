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
            'sport' => ['sport', 'sports', 'sport_name', 'category'],
            'home_team' => ['home_team', 'home', 'team_1', 'team1', 'host'],
            'away_team' => ['away_team', 'away', 'team_2', 'team2', 'visitor'],
            'game_date' => ['game_date', 'date', 'match_date', 'event_date', 'betting_date'],
            'bet_type' => ['bet_type', 'type', 'market', 'markets', 'bet_market'],
            'selection' => ['selection', 'pick', 'bet', 'tip', 'tips'],
            'odds' => ['odds', 'price', 'decimal_odds', 'wager_odds'],
            'stake' => ['stake', 'amount', 'wager', 'bet_amount', 'wager_amount'],
            'status' => ['status', 'result', 'outcome', 'bet_status'],
            'operator' => ['operator', 'bookmaker', 'sportsbook', 'book'],
            'description' => ['description', 'notes', 'comment', 'remarks'],
        ];
        
        foreach ($headers as $header) {
            $normalized = strtolower(trim($header));
            
            foreach ($commonMappings as $field => $variations) {
                if (in_array($normalized, $variations) || 
                    $this->fuzzyMatch($normalized, $variations)) {
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
                $mapped[$field] = $this->cleanValue($record[$csvColumn]);
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
        if (isset($data['game_date'])) {
            try {
                $data['game_date'] = \Carbon\Carbon::parse($data['game_date'])->format('Y-m-d H:i:s');
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
     * Validate a single row
     */
    private function validateRow(array $data, int $rowNumber): array
    {
        $rules = $this->getValidationRules();
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
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
            'game_date' => 'required|date',
            'bet_type' => 'required|string|max:50',
            'selection' => 'required|string|max:255',
            'odds' => 'required|numeric|min:1.01|max:1000',
            'stake' => 'required|numeric|min:0.01|max:100000',
            'operator' => 'required|string|max:255',
            'status' => 'nullable|in:pending,won,lost,void,push',
            'description' => 'nullable|string|max:500',
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
                'sport' => 'Sport name',
                'home_team' => 'Home team name',
                'away_team' => 'Away team name',
                'game_date' => 'Game date (YYYY-MM-DD)',
                'bet_type' => 'Type of bet (Spread, Moneyline, etc.)',
                'selection' => 'Bet selection/pick',
                'odds' => 'Decimal odds',
                'stake' => 'Amount wagered',
                'operator' => 'Betting operator/bookmaker',
            ],
            'optional' => [
                'status' => 'Bet status (pending, won, lost, void, push)',
                'description' => 'Additional notes',
                'placed_at' => 'When the bet was placed',
                'league' => 'League/competition name',
                'referrer' => 'Referral source',
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