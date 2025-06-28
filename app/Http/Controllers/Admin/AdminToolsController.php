<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\GenericAdminNotification;
use App\Services\BetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Response;

class AdminToolsController extends Controller
{
    public function __construct(
        private BetService $betService
    ) {}

    public function notifyAll(Request $request)
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
        ]);

        $users = User::all();
        Notification::send($users, new GenericAdminNotification($request->title, $request->body));

        return response()->json(['success' => true]);
    }

    public function exportBets()
    {
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $bets = $this->betService->getAllBetsForExport();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bets_export.csv"',
        ];

        $callback = function() use ($bets) {
            $handle = fopen('php://output', 'w');
            // CSV header (adjust to match your import format)
            fputcsv($handle, [
                'id', 'sports', 'league', 'matches', 'markets', 'team_one', 'team_two',
                'team_one_logo', 'team_two_logo', 'wager_amount', 'winning_amount', 'profit_amount', 'roi', 'betting_date',
                'tips', 'wager_odds', 'status', 'membership', 'referrer'
            ]);
            
            foreach ($bets as $bet) {
                fputcsv($handle, [
                    $bet->id,
                    $bet->sports,
                    $bet->league,
                    $bet->matches,
                    $bet->markets,
                    $bet->team_one,
                    $bet->team_two,
                    $bet->team_one_logo,
                    $bet->team_two_logo,
                    $bet->wager_amount,
                    $bet->winning_amount,
                    $bet->profit_amount,
                    $bet->roi,
                    $bet->betting_date,
                    $bet->tips,
                    $bet->wager_odds,
                    $bet->status,
                    $bet->membership,
                    $bet->referrer,
                ]);
            }
            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}