<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Bet;
use App\Events\NewBatchUpload;
use Illuminate\Support\Facades\Log;

class BetImportController extends Controller
{
    public function importCsv(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);
        $bets = [];
        $file = $request->file('csv');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle); // skip header

        while (($row = fgetcsv($handle)) !== false) {
            //dd($row);
            $teams = explode('@', $row[4]);
            $team_one = trim($teams[0] ?? '');
            $team_two = trim($teams[1] ?? '');
             // Clean wager_amount: remove $ and commas, convert to float
            $wagerRaw = $row[13] ?? null;
            $wager_amount = $wagerRaw !== null ? floatval(str_replace(['$', ','], '', $wagerRaw)) : null;

            $winningRaw = $row[14] ?? null;
            $winning_amount = $winningRaw !== null ? floatval(str_replace(['$', ','], '', $winningRaw)) : null;

            $profitRaw = $row[15] ?? null;
            $profit_amount = $profitRaw !== null ? floatval(str_replace(['$', ','], '', $profitRaw)) : null;

            // Capitalize status
            $statusRaw = $row[11] ?? null;
            switch ($statusRaw) {
                case 'win':
                    $statusRaw = 'Won';
                    break;
                case 'loss':
                    $statusRaw = 'Lost';
                    break;
                case 'lose':
                    $statusRaw = 'Lost';
                    break;
                case 'push':
                    $statusRaw = 'Push';
                    break;
                case 'pending':
                    $statusRaw = 'Pending';
                    break;
                default:
                    $status = 'Pending';
            }
            $status = $statusRaw ? ucfirst(strtolower($statusRaw)) : null;

            // Clean ROI: remove % and convert to decimal
            $roiRaw = $row[12] ?? null;
            $roi = null;
            if ($roiRaw !== null) {
                $roi = floatval(str_replace('%', '', $roiRaw));
            }
            $roi = $roi !== null ? $roi / 100 : null;
            $data = [
                'sports'         => $row[0],
                'league'         => $row[1],
                'betting_date'   => \Carbon\Carbon::parse($row[3]),
                'matches'        => $row[4],
                'markets'        => $row[6],
                'team_one'       => $team_one,
                'team_two'       => $team_two,
                'tips'           => $row[7],
                'wager_odds'     => $row[8],
                'membership'     => $row[9],
                'referrer'       => $row[10] ?? null,
                'status'         => $status,
                'roi'            => $roi,
                'wager_amount'   => $wager_amount,
                'winning_amount' => $winning_amount,
                'profit_amount'  => $profit_amount
            ];
           try {
            //code...
             $bets[] = Bet::create($data);
           } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error creating bet: ' . $th->getMessage(), [
                'data' => $data,
                'error' => $th->getMessage(),
            ]);
           }
           
        }
        fclose($handle);
        NewBatchUpload::dispatch(collect($bets));
        return response()->json(['success' => true]);
    }
}