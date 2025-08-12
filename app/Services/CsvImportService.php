<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Writer;
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
            
            // Log the raw headers for debugging
            \Log::info('CSV Headers (raw)', [
                'headers' => $headers,
                'headers_with_length' => array_map(function($h) { 
                    return $h . ' (length: ' . strlen($h) . ')'; 
                }, $headers)
            ]);
            $stmt = Statement::create()->limit(5);
            $records = $stmt->process($csv);

            $sampleData = [];
            foreach ($records as $record) {
                // Convert associative array to indexed array based on header order
                $row = [];
                foreach ($headers as $header) {
                    $row[] = $record[$header] ?? '';
                }
                $sampleData[] = $row;
            }

            // Count rows properly
            $rowCount = iterator_count($csv->getRecords());

            return [
                'success' => true,
                'headers' => $headers,
                'sample_data' => $sampleData,
                'total_rows' => $rowCount,
                'detected_mappings' => $this->detectColumnMappings($headers),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to analyze CSV: '.$e->getMessage(),
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
            'sport' => ['sports', 'sport', 'sport_name', 'category', 'sport_type'],
            'league' => ['league', 'competition', 'tournament', 'division', 'conference', 'comp', 'championship', 'event'],
            'month' => ['month', 'month_name'],
            'game_date' => ['game date', 'date', 'game_date', 'match_date', 'event_date', 'gamedate', 'game date ', 'game date :', 'kickoff'],
            'betting_date' => ['betting date', 'betting_date', 'bet_date', 'placed_date', 'placed_at'],
            'game' => ['game', 'games', 'match', 'matchup', 'fixture', 'contest', 'game/player'],
            'wager_type' => ['wager type', 'bet type', 'wager_type', 'bettype', 'type', 'wagertype', 'wagering_type', 'bet style'],
            'betting_market' => ['betting market', 'betting_market', 'market', 'markets', 'bet_market', 'bet market'],
            'wager_name' => ['wager name/wager description', 'wager name', 'wager_name', 'wagername', 'wager description', 'selection', 'pick', 'bet', 'tip', 'tips', 'bet description'],
            'odds' => ['wager odds', 'odds', 'american odds', 'price', 'wager_odds', 'line', 'betting_odds'],
            'membership' => ['membership', 'tier', 'subscription_level', 'confidence level', 'plan'],
            'code' => ['referrer', 'code', 'tracking code', 'source code', 'capper', 'referrer_code', 'affiliate_code'],
            'status' => ['status', 'result', 'outcome', 'bet_status', 'bet outcome'],
            'roi' => ['roi %', 'roi(net)', 'roi', 'roi_net', 'return on investment', 'net_roi'],
            'wager' => ['wager amount', 'wager', 'stake', 'wager_amount', 'amount staked', 'bet_amount'],
            'profits' => ['profit amount', 'profits', 'profit', 'net gain', 'pnl', 'p&l', 'net gain/loss'],
            'winning_amount' => ['winning amount', 'winning_amount', 'winningamount', 'total returned', 'payout'],
            'golf_place_fraction' => ['golf only: place fraction', 'golf place fraction', 'place fraction', 'golf_place_fraction', 'place_fraction'],
        ];

        // First pass: exact matches only (to avoid incorrect fuzzy matches)
        foreach ($headers as $header) {
            // Clean the header: remove trailing colons and normalize
            $cleaned = trim($header);
            $cleaned = rtrim($cleaned, ':'); // Remove trailing colon
            $cleaned = trim($cleaned); // Trim again after removing colon
            $normalized = strtolower($cleaned);
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
            // Section/Subsection headers from actual CSV
            'Section' => 'sport',
            'Subsection' => 'league',
            // Standard headers from actual CSV
            'Sport' => 'sport',
            'Sports' => 'sport',
            'League' => 'league',
            'Month' => 'month',
            'Date' => 'game_date',
            'Game Date' => 'game_date',
            'Game Date ' => 'game_date',  // With trailing space
            'Game Date :' => 'game_date',
            'Betting Date' => 'betting_date',
            'Betting Date:' => 'betting_date',
            'Home Team' => 'home_team',
            'Away Team' => 'away_team',
            'Game' => 'game',
            'Bet Type' => 'wager_type',
            'Wager name' => 'wager_name', // exact match from CSV
            'Wager Type' => 'wager_type',
            'Betting Market' => 'betting_market',
            'Wager Name' => 'wager_name',
            'Wager Name/Wager Description' => 'wager_name',
            'Odds' => 'odds',
            'Wager Odds' => 'odds',
            'Level' => 'membership',
            'Membership' => 'membership',
            'Code' => 'code',
            'Referrer' => 'code',
            'Status' => 'status',
            'ROI(net)' => 'roi',
            'ROI %' => 'roi',
            'Wager' => 'wager',
            'Wager Amount' => 'wager',
            'Profits' => 'profits',
            'Profit Amount' => 'profits',
            'Winning Amount' => 'winning_amount',
            'Golf Only: Place Fraction' => 'golf_place_fraction',
        ];

        // Also add lowercase versions (excluding date to avoid conflict)
        $exactMappingsLower = [
            'section' => 'sport',
            'subsection' => 'league',
            'sport' => 'sport',
            'league' => 'league',
            'month' => 'month',
            'game date ' => 'game_date',  // With trailing space
            'betting date' => 'betting_date',
            'home team' => 'home_team',
            'away team' => 'away_team',
            'game' => 'game',
            'bet type' => 'wager_type',
            'wager type' => 'wager_type',
            'betting market' => 'betting_market',
            'wager name' => 'wager_name',
            'odds' => 'odds',
            'level' => 'level',
            'code' => 'code',
            'referrer' => 'code',
            'status' => 'status',
            'roi(net)' => 'roi',
            'roi %' => 'roi',
            'wager' => 'wager',
            'wager amount' => 'wager',
            'wager odds' => 'odds',
            'membership' => 'level',
            'profits' => 'profits',
            'profit amount' => 'profits',
            'winning amount' => 'winning_amount',
            'golf only: place fraction' => 'golf_place_fraction',
        ];

        foreach ($headers as $header) {
            $trimmedHeader = trim($header);
            $lowerHeader = strtolower($trimmedHeader);

            // Try exact case match first
            if (isset($exactMappings[$trimmedHeader]) && ! isset($mappings[$exactMappings[$trimmedHeader]])) {
                $mappings[$exactMappings[$trimmedHeader]] = $header;
            }
            // Then try lowercase match
            elseif (isset($exactMappingsLower[$lowerHeader]) && ! isset($mappings[$exactMappingsLower[$lowerHeader]])) {
                $mappings[$exactMappingsLower[$lowerHeader]] = $header;
            }
            
            // Special handling for headers with spaces before colons (e.g., "Game Date :")
            $headerWithoutColon = rtrim($trimmedHeader, ' :');
            $lowerWithoutColon = strtolower($headerWithoutColon);
            if (isset($exactMappings[$headerWithoutColon]) && ! isset($mappings[$exactMappings[$headerWithoutColon]])) {
                $mappings[$exactMappings[$headerWithoutColon]] = $header;
            } elseif (isset($exactMappingsLower[$lowerWithoutColon]) && ! isset($mappings[$exactMappingsLower[$lowerWithoutColon]])) {
                $mappings[$exactMappingsLower[$lowerWithoutColon]] = $header;
            }
        }

        // Second pass: fuzzy matches for unmapped fields
        foreach ($headers as $header) {
            // Clean the header: remove trailing colons
            $cleaned = trim($header);
            $cleaned = rtrim($cleaned, ':');
            $cleaned = trim($cleaned);
            $normalized = strtolower($cleaned);
            $normalized = str_replace(['_', '-'], ' ', $normalized);

            // Only do fuzzy matching for fields that aren't already mapped
            foreach ($commonMappings as $field => $variations) {
                if (! isset($mappings[$field]) && $this->fuzzyMatch($normalized, $variations)) {
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
        
        // Debug logging
        \Log::info('CsvImportService: Column mappings received', [
            'mappings' => $columnMappings,
            'static_values' => $staticValues
        ]);

        try {
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            
            // Get headers for debugging
            $csvHeaders = $csv->getHeader();
            \Log::info('CsvImportService: validateImport - CSV Headers', [
                'headers' => $csvHeaders,
                'first_record_keys' => array_keys(iterator_to_array($csv->getRecords())[0] ?? [])
            ]);

            $validRows = [];
            $invalidRows = [];
            $allInvalidRows = [];
            $rowNumber = 1; // Start after header
            $totalRows = 0;
            $validCount = 0;
            $invalidCount = 0;

            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
                $totalRows++;
                $mappedData = $this->mapRowData($record);
                
                // Debug row 5 specifically (the one with the error)
                if ($rowNumber == 5) {
                    \Log::error('Debug Row 5 - CSV Import Issue', [
                        'row_number' => $rowNumber,
                        'raw_record' => $record,
                        'record_keys' => array_keys($record),
                        'column_mappings' => $this->columnMappings,
                        'mapped_data_before_transform' => $mappedData,
                        'has_game_date' => isset($mappedData['game_date']),
                        'game_date_value' => $mappedData['game_date'] ?? 'NOT SET',
                    ]);
                }
                
                $validation = $this->validateRow($mappedData, $rowNumber);

                if ($validation['valid']) {
                    $validCount++;
                    // Only store first 100 valid rows for preview
                    if (count($validRows) < 100) {
                        $validRows[] = [
                            'row' => $rowNumber,
                            'data' => $mappedData,
                            'original' => $record,
                            'warnings' => $validation['warnings'] ?? [],
                        ];
                    }
                } else {
                    $invalidCount++;
                    $invalidRowData = [
                        'row' => $rowNumber,
                        'data' => $mappedData,
                        'original' => $record,
                        'errors' => $validation['errors'],
                    ];
                    
                    // Store ALL invalid rows
                    $allInvalidRows[] = $invalidRowData;
                    
                    // Only store first 100 invalid rows for preview
                    if (count($invalidRows) < 100) {
                        $invalidRows[] = $invalidRowData;
                    }
                }
            }

            return [
                'success' => true,
                'total_rows' => $totalRows,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'all_invalid_rows' => $allInvalidRows,
                'summary' => [
                    'total' => $totalRows,
                    'valid' => $validCount,
                    'invalid' => $invalidCount,
                    'preview_limit' => 100,
                    'valid_preview_count' => count($validRows),
                    'invalid_preview_count' => count($invalidRows),
                    'is_preview' => $totalRows > 100,
                    'has_more_invalid' => $invalidCount > 100,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Validation failed: '.$e->getMessage(),
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
            // Try exact match first
            if (isset($record[$csvColumn])) {
                $value = $this->cleanValue($record[$csvColumn]);
                $mapped[$field] = $value;
            } 
            // If not found, try with trimmed column name
            else {
                $trimmedColumn = trim($csvColumn);
                foreach ($record as $recordKey => $recordValue) {
                    if (trim($recordKey) === $trimmedColumn) {
                        $value = $this->cleanValue($recordValue);
                        $mapped[$field] = $value;
                        break;
                    }
                }
            }
        }
        
        // Debug first row mapping
        static $debugged = false;
        if (!$debugged) {
            \Log::info('CsvImportService: First row mapping debug', [
                'raw_record' => $record,
                'column_mappings' => $this->columnMappings,
                'mapped_data' => $mapped
            ]);
            $debugged = true;
        }

        // Then apply static values (these override any mapped values)
        foreach ($this->staticValues as $field => $value) {
            $mapped[$field] = $value;
        }

        // Apply data transformations
        $transformed = $this->transformData($mapped);
        
        // Debug first row after transformation
        static $debuggedTransform = false;
        if (!$debuggedTransform) {
            \Log::info('CsvImportService: First row after transformation', [
                'transformed_data' => $transformed
            ]);
            $debuggedTransform = true;
        }
        
        return $transformed;
    }

    /**
     * Export invalid rows to CSV
     */
    public function exportInvalidRows(array $invalidRows): string
    {
        if (empty($invalidRows)) {
            return '';
        }

        $csv = Writer::createFromString();
        
        // Add headers
        $headers = ['Row', 'Error'];
        $firstRow = reset($invalidRows);
        if (isset($firstRow['data']) && is_array($firstRow['data'])) {
            $headers = array_merge($headers, array_keys($firstRow['data']));
        }
        $csv->insertOne($headers);
        
        // Add invalid rows
        foreach ($invalidRows as $row) {
            $errorMessages = [];
            foreach ($row['errors'] as $field => $errors) {
                $errorMessages[] = $field . ': ' . implode(', ', $errors);
            }
            
            $csvRow = [
                $row['row'],
                implode(' | ', $errorMessages)
            ];
            
            if (isset($row['data']) && is_array($row['data'])) {
                foreach (array_keys($firstRow['data']) as $key) {
                    $csvRow[] = $row['data'][$key] ?? '';
                }
            }
            
            $csv->insertOne($csvRow);
        }
        
        return $csv->toString();
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
        if (isset($data['game']) && ! empty($data['game'])) {
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

        if ($dateField && ! empty($data[$dateField])) {
            // Use the parseDate method which handles more formats including 2-digit years
            $parsedDate = $this->parseDate($data[$dateField]);
            if ($parsedDate !== null) {
                $data['game_date'] = $parsedDate;
            }
        }

        // Parse betting_date
        if (isset($data['betting_date']) && ! empty($data['betting_date'])) {
            $parsedDate = $this->parseDate($data['betting_date']);
            if ($parsedDate !== null) {
                $data['betting_date'] = $parsedDate;
            }
        }
        
        // Parse placed_at date
        if (isset($data['placed_at']) && ! empty($data['placed_at'])) {
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

        // Handle wager field
        if (isset($data['wager'])) {
            $data['wager'] = $this->parseMonetary($data['wager']);
        }

        // Only handle stake if it's explicitly provided in the CSV
        if (isset($data['stake']) && ! isset($data['wager'])) {
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

        // Parse date fields
        if (isset($data['game_date'])) {
            $data['game_date'] = $this->parseDate($data['game_date']);
        }
        
        // Normalize status
        if (isset($data['status'])) {
            $data['status'] = $this->normalizeStatus($data['status']);
        }

        // Parse teams from combined field if needed
        if (! isset($data['home_team']) && ! isset($data['away_team']) && isset($data['teams'])) {
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
     * Parse date value to ensure proper formatting
     */
    private function parseDate($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            // Replace non-breaking hyphens (U+2011) with regular hyphens
            $value = str_replace('‑', '-', $value);
            
            // Handle various date formats
            $date = null;
            
            // Log the input value for debugging
            static $loggedDates = 0;
            if ($loggedDates < 5) {
                \Log::info('Parsing date value', ['input' => $value, 'type' => gettype($value)]);
                $loggedDates++;
            }
            
            // Try common date formats
            $formats = [
                'm-d-Y',      // 07-16-2025 (with leading zeros)
                'n-j-Y',      // 7-16-2025 (without leading zeros)
                'm/d/Y',      // 07/16/2025
                'n/j/Y',      // 7/16/2025
                'm/d/y',      // 07/16/25
                'n/j/y',      // 7/16/25
                'Y-m-d',      // 2025-07-16
                'd/m/Y',      // 16/07/2025
                'd/m/y',      // 16/07/25
                'm-d-y',      // 07-16-25
                'n-j-y',      // 7-16-25
            ];
            
            foreach ($formats as $format) {
                $parsed = \DateTime::createFromFormat($format, $value);
                if ($parsed !== false) {
                    // Get the year as an integer
                    $year = (int) $parsed->format('Y');
                    
                    // If year is less than 100, it's a 2-digit year
                    if ($year < 100) {
                        // If less than 50, assume 2000s; otherwise 1900s
                        if ($year < 50) {
                            $parsed->setDate($year + 2000, (int) $parsed->format('m'), (int) $parsed->format('d'));
                        } else {
                            $parsed->setDate($year + 1900, (int) $parsed->format('m'), (int) $parsed->format('d'));
                        }
                    }
                    $date = $parsed;
                    break;
                }
            }
            
            // If no format matched, try Carbon's parse as fallback
            if (!$date) {
                $date = \Carbon\Carbon::parse($value);
                // Check for 2-digit year issue
                $year = $date->year;
                if ($year < 100) {
                    // If less than 50, assume 2000s; otherwise 1900s
                    if ($year < 50) {
                        $date->year = $year + 2000;
                    } else {
                        $date->year = $year + 1900;
                    }
                }
            }
            
            // Return in MySQL format
            return $date->format('Y-m-d H:i:s');
            
        } catch (\Exception $e) {
            // If parsing fails, return the original value
            // This will let MySQL handle it (which might cause the 2-digit year issue)
            return $value;
        }
    }

    /**
     * Normalize status values
     */
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
            'void' => 'pending',  // Changed from 'void' to 'pending'
            'v' => 'pending',     // Changed from 'void' to 'pending'
            'pending' => 'pending',
            'open' => 'pending',
            'active' => 'pending',
            'placed' => 'placed',  // For E/W bets that placed but didn't win
            'cashout' => 'pending',  // Changed from 'cashout' to 'pending'
            'cash out' => 'pending', // Changed from 'cashout' to 'pending'
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
            'betting_date.required' => 'Betting Date is required',
            'game_date.required' => 'Game Date is required',
            'game.required' => 'Game is required',
            'wager_type.required' => 'Wager Type is required',
            'wager_name.required' => 'Wager Name is required',
            'odds.required' => 'odds are required',
            'odds.numeric' => 'odds must be a number',
            'membership.required' => 'membership is required',
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
        
        // Debug validation for problematic rows
        static $debuggedValidation = 0;
        if ($validator->fails() && $debuggedValidation < 3) {
            \Log::info('CsvImportService: Validation failed for row ' . $rowNumber, [
                'data' => $data,
                'errors' => $validator->errors()->toArray(),
                'rules' => $rules
            ]);
            $debuggedValidation++;
        }

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
        
        // Custom validation: ensure at least one date is provided
        if (empty($data['game_date']) && empty($data['betting_date'])) {
            return [
                'valid' => false,
                'errors' => ['game_date' => 'Either Game Date or Betting Date is required'],
            ];
        }

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
            'game_date' => 'nullable|string', // Made optional - can use betting_date as fallback
            'game' => 'required|string|max:250',
            'wager_type' => 'required|string|max:50',
            'wager_name' => 'required|string|max:250',
            'odds' => ['required', 'numeric', 'between:-100000,100000'], // Allow negative odds
            'membership' => 'required|string|max:50',
            'code' => 'required|string|max:255',
            'status' => 'required|string|max:50',
            'roi' => 'nullable|numeric',
            'wager' => 'required|numeric|min:0.01|max:100000',
            'profits' => 'nullable|numeric',
            'winning_amount' => 'nullable|numeric',
            // Home/Away teams are parsed from game column
            'home_team' => 'required|string|max:255',
            'away_team' => 'nullable|string|max:255', // Optional for individual sports
            // Optional fields
            'betting_date' => 'nullable|string', // Optional as CSV might only have game_date
            'stake' => 'nullable|numeric|min:0|max:100000',
            'operator' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'placed_at' => 'nullable|string',
            'golf_place_fraction' => 'nullable|string|max:50',
            'level' => 'nullable|string|max:50', // Optional field
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
                'sport' => 'The sport being bet on (e.g., Soccer, Basketball, Golf)',
                'league' => 'The league or competition name (e.g., Champions League, NBA, PGA)',
                'month' => 'Calendar month (e.g., Feb, March, June)',
                'game' => 'The matchup or player (e.g., "Team A @ Team B" or player name for individual sports)',
                'wager_type' => 'Type of bet (e.g., Single Wager, Parlay, Each Way)',
                'betting_market' => 'Market type (e.g., Moneyline, Spread, Total, Prop)',
                'wager_name' => 'Specific bet description (e.g., "Atalanta to win", "Over 2.5 goals")',
                'odds' => 'American odds (e.g., -120, +150, 111)',
                'membership' => 'Membership tier (Bronze, Silver, Gold, Platinum)',
                'code' => 'Referrer code or source (e.g., Footy, BB, Golf Brad)',
                'status' => 'Bet outcome (win, loss, push, pending)',
                'wager' => 'Amount wagered (e.g., $30, $50)',
            ],
            'optional' => [
                'betting_date' => 'Date bet was placed (MM/DD/YYYY format)',
                'game_date' => 'Date of the game/event (MM/DD/YYYY format)',
                'roi' => 'Return on Investment percentage',
                'profits' => 'Profit or loss amount',
                'winning_amount' => 'Total payout if won',
                'golf_place_fraction' => 'Golf Only: Place fraction for Each Way bets (e.g., 1/5, 1/4)',
                'level' => 'Optional confidence level or tier',
            ],
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
            'Month',
            'Betting Date',
            'Game Date',
            'Game',
            'Wager Type',
            'Betting Market',
            'Wager Name/Wager Description',
            'Wager Odds',
            'Membership',
            'Referrer',
            'Status',
            'ROI %',
            'Wager Amount',
            'Winning Amount',
            'Profit Amount',
            'Golf Only: Place Fraction',
        ];

        $sampleData = [
            [
                'soccer',  // Sports
                'Champions League',  // League
                'Feb',  // Month
                '02-12-2025',  // Betting Date
                '02-09-2025',  // Game Date
                'Club Brugge @ Atalanta Bergamasca Calcio',  // Game
                'Single Wager',  // Wager Type
                'Moneyline',  // Betting Market
                'Atalanta to win',  // Wager Name/Description
                '111',  // Wager Odds
                'gold',  // Membership
                'Footy',  // Referrer
                'loss',  // Status
                '-100%',  // ROI %
                '$30',  // Wager Amount
                '$0',  // Winning Amount
                '-$30',  // Profit Amount
                '',  // Golf Only: Place Fraction
            ],
            [
                'basketball',  // Sports
                'NBA',  // League
                'Jan',  // Month
                '01-15-2025',  // Betting Date
                '01-16-2025',  // Game Date
                'Boston Celtics @ Los Angeles Lakers',  // Game
                'Single Wager',  // Wager Type
                'Spread',  // Betting Market
                'Lakers +3.5',  // Wager Name/Description
                '-110',  // Wager Odds
                'platinum',  // Membership
                'BB',  // Referrer
                'win',  // Status
                '90.91%',  // ROI %
                '$50',  // Wager Amount
                '$95.45',  // Winning Amount
                '$45.45',  // Profit Amount
                '',  // Golf Only: Place Fraction
            ],
            [
                'golf',  // Sports
                'PGA Tour',  // League
                'May',  // Month
                '05-01-2025',  // Betting Date
                '05-02-2025',  // Game Date
                'Scottie Scheffler',  // Game (player name for individual sport)
                'Each Way',  // Wager Type
                'Tournament Winner',  // Betting Market
                'Scottie Scheffler to Win',  // Wager Name/Description
                '800',  // Wager Odds
                'silver',  // Membership
                'Golf Brad',  // Referrer
                'placed',  // Status
                '40%',  // ROI %
                '$20',  // Wager Amount
                '$28',  // Winning Amount
                '$8',  // Profit Amount
                '1/5',  // Golf Only: Place Fraction
            ],
        ];

        $filename = 'bet_import_template_'.now()->format('Y-m-d').'.csv';
        $path = 'temp/'.$filename;

        Storage::disk('local')->put($path, implode(',', $headers)."\n");

        foreach ($sampleData as $row) {
            Storage::disk('local')->append($path, implode(',', $row)."\n");
        }

        return Storage::disk('local')->path($path);
    }
}
