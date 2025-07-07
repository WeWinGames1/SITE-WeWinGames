<?php

// Read the original CSV file
$inputFile = 'WWG picks record - 2025.csv';
$outputFile = 'WWG_picks_2025_cleaned.csv';

// Expected column headers for the import
$expectedHeaders = [
    'Sports',
    'League',
    'Month',
    'Date/Time',
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

// Mapping from old columns to new columns
$columnMapping = [
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

// Read the CSV
$rows = [];
$headers = [];
$handle = fopen($inputFile, 'r');
if ($handle !== false) {
    // Read header row
    $headerRow = fgetcsv($handle);

    // Find column indices for the data we need
    $columnIndices = [];
    foreach ($headerRow as $index => $header) {
        $header = trim($header);

        // Special handling for duplicate "Wager" columns
        if ($header === 'Wager' && ! isset($columnIndices['Wager Amount'])) {
            // Use the first occurrence (index 7)
            if ($index === 7) {
                $columnIndices['Wager Amount'] = $index;
            }
        } elseif (isset($columnMapping[$header])) {
            $columnIndices[$columnMapping[$header]] = $index;
        }
    }

    // Process data rows
    while (($data = fgetcsv($handle)) !== false) {
        // Skip empty rows or rows that don't have enough columns
        if (count($data) < 10 || empty(trim($data[0]))) {
            continue;
        }

        $newRow = [];

        // Map data to new format
        $newRow['Sports'] = ucfirst(strtolower($data[$columnIndices['Sports']] ?? ''));
        $newRow['League'] = strtoupper($data[$columnIndices['League']] ?? '');

        // Format date and time
        $dateStr = $data[$columnIndices['Betting Date']] ?? '';
        if (! empty($dateStr)) {
            $date = DateTime::createFromFormat('n/j/Y', $dateStr);
            if ($date) {
                $newRow['Month'] = $date->format('M'); // Get 3-letter month abbreviation
                $newRow['Date/Time'] = $date->format('Y-m-d H:i');
                $newRow['Betting Date'] = $date->format('Y-m-d');
            } else {
                continue; // Skip rows with invalid dates
            }
        } else {
            continue;
        }

        $newRow['Matches'] = $data[$columnIndices['Matches']] ?? '';
        $newRow['Markets'] = $data[$columnIndices['Markets']] ?? '';
        $newRow['Wager Type'] = ''; // Leave empty when we don't have the value
        $newRow['Wager Name'] = $data[$columnIndices['Wager Name']] ?? '';

        // Convert odds (remove minus sign if present for display)
        $odds = $data[$columnIndices['Wager Odds']] ?? '0';
        $newRow['Wager Odds'] = $odds;

        // Convert level to membership tier
        $level = strtolower($data[$columnIndices['Membership']] ?? '');
        $membershipMap = [
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum',
            'bronze' => 'Bronze',
        ];
        $newRow['Membership'] = $membershipMap[$level] ?? 'Silver';

        // Referrer from Code column, or default
        $newRow['Referrer'] = $data[$columnIndices['Referrer']] ?? 'WWG2025';

        // Convert status
        $status = strtolower($data[$columnIndices['Status']] ?? '');
        $newRow['Status'] = ($status === 'win') ? 'Won' : 'Lost';

        // Format ROI
        $roi = $data[$columnIndices['ROI %']] ?? '-100%';
        $newRow['ROI %'] = $roi;

        // Format amounts (remove $ and spaces)
        $wager = preg_replace('/[^\d.-]/', '', isset($columnIndices['Wager Amount']) && isset($data[$columnIndices['Wager Amount']]) ? $data[$columnIndices['Wager Amount']] : '0');
        $winning = preg_replace('/[^\d.-]/', '', isset($columnIndices['Winning Amount']) && isset($data[$columnIndices['Winning Amount']]) ? $data[$columnIndices['Winning Amount']] : '0');
        $profit = preg_replace('/[^\d.-]/', '', isset($columnIndices['Profit Amount']) && isset($data[$columnIndices['Profit Amount']]) ? $data[$columnIndices['Profit Amount']] : '0');

        $newRow['Wager Amount'] = '$'.number_format((float) $wager, 2);
        $newRow['Winning Amount'] = '$'.number_format((float) $winning, 2);
        $newRow['Profit Amount'] = '$'.number_format((float) $profit, 2);

        $rows[] = $newRow;
    }
    fclose($handle);
}

// Write the cleaned CSV
$handle = fopen($outputFile, 'w');
if ($handle !== false) {
    // Write headers
    fputcsv($handle, $expectedHeaders);

    // Write data rows
    foreach ($rows as $row) {
        $outputRow = [];
        foreach ($expectedHeaders as $header) {
            $outputRow[] = $row[$header] ?? '';
        }
        fputcsv($handle, $outputRow);
    }

    fclose($handle);

    echo "Successfully cleaned CSV file!\n";
    echo "Input: $inputFile\n";
    echo "Output: $outputFile\n";
    echo 'Processed '.count($rows)." rows\n";
} else {
    echo "Error: Could not create output file\n";
}
