<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use League\Csv\Reader;

class BetMassEditController extends Controller
{
    /**
     * Show mass edit page for Golf Each-Way bets
     */
    public function index(Request $request)
    {
        $query = Bet::query()
            ->whereIn('status', ['won', 'placed'])
            ->orderBy('betting_date', 'desc')
            ->orderBy('matches', 'asc');

        // Add sport filter
        if ($request->filled('sport')) {
            $query->where('sports', $request->sport);
        }

        // Add status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Add each way filter
        if ($request->filled('each_way') && $request->each_way === '1') {
            $query->where('is_each_way', true);
        }

        // Add date filters
        if ($request->filled('date_from')) {
            $query->whereDate('betting_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('betting_date', '<=', $request->date_to);
        }

        $bets = $query->paginate(100)->withQueryString();

        // Get list of available sports
        $sports = Bet::distinct()->pluck('sports')->filter()->values()->toArray();

        // Debug: Log the query and results
        \Log::info('Sport filter value:', ['sport' => $request->sport]);
        \Log::info('Available sports in database:', $sports);
        \Log::info('Query SQL:', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        \Log::info('Result count:', ['count' => $bets->total()]);

        return Inertia::render('admin/Bets/MassEdit', [
            'bets' => $bets,
            'filters' => $request->only(['sport', 'status', 'each_way', 'date_from', 'date_to']),
            'sports' => $sports,
        ]);
    }

    /**
     * Update multiple bets via inline editing
     */
    public function updateBatch(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.id' => 'required|exists:bets,id',
            'updates.*.winning_amount' => 'required|numeric',
            'updates.*.profit_amount' => 'nullable|numeric',
        ]);

        $updatedCount = 0;

        DB::transaction(function () use ($validated, &$updatedCount) {
            foreach ($validated['updates'] as $update) {
                $bet = Bet::find($update['id']);

                // Only update if it's a Golf bet
                if ($bet->sports === 'Golf') {
                    $bet->winning_amount = $update['winning_amount'];

                    // Calculate profit if not provided
                    if (isset($update['profit_amount'])) {
                        $bet->profit_amount = $update['profit_amount'];
                    } else {
                        $bet->profit_amount = $update['winning_amount'] - $bet->wager_amount;
                    }

                    $bet->save();
                    $updatedCount++;
                }
            }
        });

        return back()->with('success', "Updated {$updatedCount} bets successfully.");
    }

    /**
     * Import corrections via CSV
     */
    public function importCorrections(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        try {
            $path = $request->file('file')->getRealPath();
            $csv = Reader::createFromPath($path, 'r');
            $csv->setHeaderOffset(0);

            $records = iterator_to_array($csv->getRecords());
            $updatedCount = 0;
            $notFoundCount = 0;
            $errors = [];

            DB::transaction(function () use ($records, &$updatedCount, &$notFoundCount, &$errors) {
                foreach ($records as $rowIndex => $record) {
                    $rowNumber = $rowIndex + 2; // Account for header row

                    // Required fields
                    if (empty($record['date']) || empty($record['player']) || empty($record['winning_amount'])) {
                        $errors[] = "Row {$rowNumber}: Missing required fields (date, player, winning_amount)";

                        continue;
                    }

                    // Parse date
                    try {
                        $date = \Carbon\Carbon::parse($record['date'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $errors[] = "Row {$rowNumber}: Invalid date format '{$record['date']}'";

                        continue;
                    }

                    // Find matching bet
                    $query = Bet::where('sports', 'Golf')
                        ->whereDate('betting_date', $date)
                        ->whereIn('status', ['won', 'placed']);

                    // Match by player name in matches or wager_name
                    $playerName = trim($record['player']);
                    $query->where(function ($q) use ($playerName) {
                        $q->where('matches', 'LIKE', "%{$playerName}%")
                            ->orWhere('wager_name', 'LIKE', "%{$playerName}%")
                            ->orWhere('tips', 'LIKE', "%{$playerName}%");
                    });

                    $bet = $query->first();

                    if (! $bet) {
                        $notFoundCount++;
                        $errors[] = "Row {$rowNumber}: No matching bet found for {$playerName} on {$date}";

                        continue;
                    }

                    // Update the bet
                    $bet->winning_amount = (float) $record['winning_amount'];

                    // Calculate profit or use provided value
                    if (! empty($record['profit_amount'])) {
                        $bet->profit_amount = (float) $record['profit_amount'];
                    } else {
                        $bet->profit_amount = $bet->winning_amount - $bet->wager_amount;
                    }

                    $bet->save();
                    $updatedCount++;
                }
            });

            $message = "Import complete: {$updatedCount} bets updated";
            if ($notFoundCount > 0) {
                $message .= ", {$notFoundCount} not found";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated' => $updatedCount,
                'not_found' => $notFoundCount,
                'errors' => array_slice($errors, 0, 10), // First 10 errors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download sample CSV for corrections
     */
    public function downloadSample()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="golf_bet_corrections_sample.csv"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, ['date', 'player', 'winning_amount', 'profit_amount']);

            // Sample rows
            fputcsv($handle, ['2024-02-11', 'Tom McKibbin', '99.00', '69.00']);
            fputcsv($handle, ['2024-03-24', 'Sam Bairstow', '67.50', '57.50']);
            fputcsv($handle, ['2024-03-31', 'Mac Meissner', '105.00', '75.00']);

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
