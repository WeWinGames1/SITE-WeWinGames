<?php

namespace App\Services;

use DateTime;

class CsvCleanerService
{
    /**
     * Expected column headers for the cleaned import format
     */
    private array $expectedHeaders = [
        'Sport',
        'League',
        'Month',
        'Game Date',
        'Game',
        'Bet Type',
        'Wager Type',
        'Wager Name',
        'Odds',
        'Level',
        'Code',
        'Status',
        'ROI(net)',
        'Wager',
        'Profits',
        'Winning Amount',
    ];

    /**
     * Mapping from old column names to new column names
     */
    private array $columnMapping = [
        'Section' => 'Sport',
        'Subsection' => 'League',
        'Month' => 'Month',
        'Date' => 'Game Date',
        'Game' => 'Game',
        'Wager name' => 'Wager Name',
        'Bet Type' => 'Bet Type',
        // Note: Column 7 "Wager" maps to "Wager Type"
        // Note: Column 13 "Wager" maps to "Wager" (amount)
        'Odds' => 'Odds',
        'Level' => 'Level',
        'Code' => 'Code',
        'Status' => 'Status',
        'ROI(net)' => 'ROI(net)',
        'Profits' => 'Profits',
        'Winning Amount' => 'Winning Amount',
    ];

    /**
     * Clean a CSV file with duplicate columns and format it for import
     *
     * @param  string  $inputPath  Path to input CSV file
     * @param  string  $outputPath  Path to output cleaned CSV file
     * @return array Result with success status and details
     */
    public function cleanCsvFile(string $inputPath, string $outputPath): array
    {
        try {
            // Read the CSV
            $rows = [];
            $handle = fopen($inputPath, 'r');
            if ($handle === false) {
                return [
                    'success' => false,
                    'message' => 'Could not open input file',
                ];
            }

            // Read header row
            $headerRow = fgetcsv($handle);
            if (! $headerRow) {
                fclose($handle);

                return [
                    'success' => false,
                    'message' => 'Could not read header row',
                ];
            }

            // Find column indices for the data we need
            $columnIndices = $this->mapColumnIndices($headerRow);

            // Process data rows
            while (($data = fgetcsv($handle)) !== false) {
                // Skip empty rows or rows that don't have enough columns
                if (count($data) < 10 || empty(trim($data[0] ?? ''))) {
                    continue;
                }

                $newRow = $this->processRow($data, $columnIndices, $inputPath);
                if ($newRow !== null) {
                    $rows[] = $newRow;
                }
            }
            fclose($handle);

            // Write the cleaned CSV
            $result = $this->writeCsvFile($outputPath, $rows);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Successfully cleaned CSV file',
                    'input' => $inputPath,
                    'output' => $outputPath,
                    'rows_processed' => count($rows),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to write output file',
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error cleaning CSV: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Map column indices from header row
     */
    private function mapColumnIndices(array $headerRow): array
    {
        $columnIndices = [];
        $wagerCount = 0;

        foreach ($headerRow as $index => $header) {
            $header = trim($header);

            // Special handling for duplicate "Wager" columns
            if ($header === 'Wager') {
                $wagerCount++;
                if ($wagerCount === 1) {
                    // First "Wager" column (index 7) is Wager Type
                    $columnIndices['Wager Type'] = $index;
                } elseif ($wagerCount === 2) {
                    // Second "Wager" column (index 13) is Wager amount
                    $columnIndices['Wager'] = $index;
                }
            } elseif (isset($this->columnMapping[$header])) {
                $columnIndices[$this->columnMapping[$header]] = $index;
            } else {
                // Direct mapping if no transformation needed
                $columnIndices[$header] = $index;
            }
        }

        return $columnIndices;
    }

    /**
     * Process a single row of data
     */
    private function processRow(array $data, array $columnIndices, string $inputPath): ?array
    {
        $newRow = [];

        // Direct mapping based on expected headers
        foreach ($this->expectedHeaders as $header) {
            $index = $columnIndices[$header] ?? null;
            
            if ($index !== null && isset($data[$index])) {
                $value = trim($data[$index]);
                
                // Special handling for specific fields
                switch ($header) {
                    case 'Sport':
                        // Keep original casing for sports
                        $newRow[$header] = $value;
                        break;
                    
                    case 'Game Date':
                        // Format date from m/d/Y to Y-m-d
                        if (! empty($value)) {
                            $date = DateTime::createFromFormat('n/j/Y', $value);
                            if ($date) {
                                $newRow[$header] = $date->format('Y-m-d');
                            } else {
                                // Try alternative formats
                                $date = DateTime::createFromFormat('m/d/Y', $value);
                                if ($date) {
                                    $newRow[$header] = $date->format('Y-m-d');
                                } else {
                                    return null; // Skip rows with invalid dates
                                }
                            }
                        } else {
                            return null;
                        }
                        break;
                    
                    case 'Wager':
                    case 'Profits':
                    case 'Winning Amount':
                        // Clean monetary values - remove $ and spaces
                        $newRow[$header] = str_replace(['$', ' ', ','], '', $value);
                        break;
                    
                    case 'ROI(net)':
                        // Keep ROI as is (including % sign)
                        $newRow[$header] = $value;
                        break;
                    
                    default:
                        $newRow[$header] = $value;
                        break;
                }
            } else {
                $newRow[$header] = '';
            }
        }
        
        // Skip rows without essential data
        if (empty($newRow['Sport']) || empty($newRow['Game Date'])) {
            return null;
        }

        return $newRow;
    }

    /**
     * Write cleaned data to CSV file
     */
    private function writeCsvFile(string $outputPath, array $rows): bool
    {
        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            return false;
        }

        // Write headers
        fputcsv($handle, $this->expectedHeaders);

        // Write data rows
        foreach ($rows as $row) {
            $outputRow = [];
            foreach ($this->expectedHeaders as $header) {
                $outputRow[] = $row[$header] ?? '';
            }
            fputcsv($handle, $outputRow);
        }

        fclose($handle);

        return true;
    }

    /**
     * Clean multiple CSV files at once
     */
    public function cleanMultipleCsvFiles(array $files): array
    {
        $results = [];

        foreach ($files as $input => $output) {
            $results[] = $this->cleanCsvFile($input, $output);
        }

        return $results;
    }

    /**
     * Get expected headers for the cleaned CSV format
     */
    public function getExpectedHeaders(): array
    {
        return $this->expectedHeaders;
    }

    /**
     * Get column mapping configuration
     */
    public function getColumnMapping(): array
    {
        return $this->columnMapping;
    }
}
