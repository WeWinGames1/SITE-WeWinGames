<?php

namespace App\Services;

use DateTime;
use Illuminate\Support\Facades\Storage;

class CsvCleanerService
{
    /**
     * Expected column headers for the cleaned import format
     */
    private array $expectedHeaders = [
        'Sports',
        'League',
        'Month',
        'Betting Date',
        'Matches',
        'Markets',
        'Wager Type',
        'Wager Name',
        'Wager Odds',
        'Membership',
        'Referrer',
        'Status',
        'ROI %',
        'Wager Amount',
        'Winning Amount',
        'Profit Amount',
    ];

    /**
     * Mapping from old column names to new column names
     */
    private array $columnMapping = [
        'Section' => 'Sports',
        'Subsection' => 'League',
        'Date' => 'Betting Date',
        'Game' => 'Matches',
        'Bet Type' => 'Markets',
        'Wager name' => 'Wager Name',
        'Odds' => 'Wager Odds',
        'Level' => 'Membership',
        'Code' => 'Referrer',
        'Status' => 'Status',
        'ROI(net)' => 'ROI %',
        'Profits' => 'Profit Amount',
        'Winnings' => 'Winning Amount',
        'Winning Amount' => 'Winning Amount', // Handle both with and without space
    ];

    /**
     * Clean a CSV file with duplicate columns and format it for import
     *
     * @param string $inputPath Path to input CSV file
     * @param string $outputPath Path to output cleaned CSV file
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
            if (!$headerRow) {
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
                'message' => 'Error cleaning CSV: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Map column indices from header row
     */
    private function mapColumnIndices(array $headerRow): array
    {
        $columnIndices = [];
        
        foreach ($headerRow as $index => $header) {
            $header = trim($header);
            
            // Special handling for duplicate "Wager" columns
            if ($header === 'Wager' && !isset($columnIndices['Wager Amount'])) {
                // Use the first occurrence (index 7)
                if ($index === 7) {
                    $columnIndices['Wager Amount'] = $index;
                }
            } elseif (isset($this->columnMapping[$header])) {
                $columnIndices[$this->columnMapping[$header]] = $index;
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
        
        // Map data to new format
        $newRow['Sports'] = ucfirst(strtolower($data[$columnIndices['Sports'] ?? 0] ?? ''));
        $newRow['League'] = strtoupper($data[$columnIndices['League'] ?? 0] ?? '');
        
        // Format date
        $dateStr = $data[$columnIndices['Betting Date'] ?? 0] ?? '';
        if (!empty($dateStr)) {
            $date = DateTime::createFromFormat('n/j/Y', $dateStr);
            if ($date) {
                $newRow['Month'] = $date->format('M'); // Get 3-letter month abbreviation
                $newRow['Betting Date'] = $date->format('Y-m-d');
            } else {
                return null; // Skip rows with invalid dates
            }
        } else {
            return null;
        }
        
        $newRow['Matches'] = $data[$columnIndices['Matches'] ?? 0] ?? '';
        $newRow['Markets'] = $data[$columnIndices['Markets'] ?? 0] ?? '';
        $newRow['Wager Type'] = ''; // Leave empty when we don't have the value
        $newRow['Wager Name'] = $data[$columnIndices['Wager Name'] ?? 0] ?? '';
        
        // Convert odds
        $odds = $data[$columnIndices['Wager Odds'] ?? 0] ?? '0';
        $newRow['Wager Odds'] = $odds;
        
        // Convert level to membership tier
        $level = strtolower($data[$columnIndices['Membership'] ?? 0] ?? '');
        $membershipMap = [
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum',
            'bronze' => 'Bronze'
        ];
        $newRow['Membership'] = $membershipMap[$level] ?? 'Silver';
        
        // Referrer from Code column, or default based on year
        $defaultReferrer = strpos($inputPath, '2024') !== false ? 'WWG2024' : 'WWG2025';
        $newRow['Referrer'] = $data[$columnIndices['Referrer'] ?? 0] ?? $defaultReferrer;
        
        // Convert status
        $status = strtolower($data[$columnIndices['Status'] ?? 0] ?? '');
        $newRow['Status'] = ($status === 'win') ? 'Won' : 'Lost';
        
        // Format ROI
        $roi = $data[$columnIndices['ROI %'] ?? 0] ?? '-100%';
        $newRow['ROI %'] = $roi;
        
        // Format amounts (remove $ and spaces)
        $wager = preg_replace('/[^\d.-]/', '', $data[$columnIndices['Wager Amount'] ?? 0] ?? '0');
        $winning = preg_replace('/[^\d.-]/', '', $data[$columnIndices['Winning Amount'] ?? 0] ?? '0');
        $profit = preg_replace('/[^\d.-]/', '', $data[$columnIndices['Profit Amount'] ?? 0] ?? '0');
        
        $newRow['Wager Amount'] = '$' . number_format((float)$wager, 2);
        $newRow['Winning Amount'] = '$' . number_format((float)$winning, 2);
        $newRow['Profit Amount'] = '$' . number_format((float)$profit, 2);
        
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