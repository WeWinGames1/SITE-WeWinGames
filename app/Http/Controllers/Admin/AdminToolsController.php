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
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'target_type' => 'required|in:all,tiers,users',
            'tiers' => 'required_if:target_type,tiers|array',
            'tiers.*' => 'in:Bronze,Silver,Gold,Platinum',
            'user_ids' => 'required_if:target_type,users|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        $users = collect();
        $sentCount = 0;
        $failedCount = 0;

        switch ($request->target_type) {
            case 'all':
                $users = User::with('roles')->get();
                break;

            case 'tiers':
                // Get users who have the specified tiers
                $users = User::where(function ($query) use ($request) {
                    $query->whereIn('override_tier', $request->tiers)
                        ->where('admin_override', true);
                })->orWhere(function ($query) use ($request) {
                    // Get users with active subscriptions in these tiers
                    $query->whereHas('subscriptions', function ($subQuery) use ($request) {
                        $subQuery->where('stripe_status', 'active');
                        // Map tiers to Stripe price IDs
                        $priceIds = [];
                        foreach ($request->tiers as $tier) {
                            $tierLower = strtolower($tier);
                            $priceIds[] = config("stripe.products.{$tierLower}.monthly");
                            $priceIds[] = config("stripe.products.{$tierLower}.weekly");
                            $priceIds[] = config("stripe.products.{$tierLower}.daily");
                        }
                        $subQuery->whereIn('stripe_price', array_filter($priceIds));
                    });
                })->with('roles')->get();
                break;

            case 'users':
                $users = User::with('roles')->whereIn('id', $request->user_ids)->get();
                break;
        }

        // Send notifications
        if ($users->isNotEmpty()) {
            try {
                Notification::send($users, new GenericAdminNotification($request->title, $request->body));
                $sentCount = $users->count();
            } catch (\Exception $e) {
                $failedCount = $users->count();
                \Log::error('Notification sending failed: '.$e->getMessage());
            }
        }

        // Store notification metadata for tracking
        $notificationData = [
            'title' => $request->title,
            'body' => $request->body,
            'target_type' => $request->target_type,
            'target_tiers' => $request->target_type === 'tiers' ? $request->tiers : null,
            'target_users' => $request->target_type === 'users' ? $request->user_ids : null,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ];

        return response()->json([
            'success' => true,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'message' => "Notification sent to {$sentCount} users",
        ]);
    }

    public function exportBets()
    {
        if (! auth()->check() || ! auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized');
        }

        $bets = $this->betService->getAllBetsForExport();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bets_export.csv"',
        ];

        $callback = function () use ($bets) {
            $handle = fopen('php://output', 'w');
            // CSV header matching the import format (17 columns - now with separate Home/Away)
            fputcsv($handle, [
                'Sport', 'League', 'Month', 'Date', 'Home Team', 'Away Team', 'Bet Type',
                'Wager Type', 'Wager Name', 'odds', 'level', 'code', 'Status',
                'ROI(net)', 'Wager', 'Profits', 'Winning Amount',
            ]);

            foreach ($bets as $bet) {
                // Format date
                $bettingDate = $bet->betting_date ? \Carbon\Carbon::parse($bet->betting_date) : null;
                $month = $bettingDate ? $bettingDate->format('F') : '';
                $date = $bettingDate ? $bettingDate->format('Y-m-d') : '';

                // Format ROI as percentage
                $roi = $bet->roi ? number_format($bet->roi, 2).'%' : '0.00%';

                // Format monetary values
                $wager = '$'.number_format($bet->wager_amount ?? 0, 2);
                $profits = '$'.number_format($bet->profit_amount ?? 0, 2);
                $winningAmount = '$'.number_format($bet->winning_amount ?? 0, 2);

                fputcsv($handle, [
                    $bet->sports ?? '',                    // Sport
                    $bet->league ?? '',                    // League
                    $bet->month ?? $month,                 // Month (use stored month or calculated)
                    $date,                                  // Date
                    $bet->team_one ?? '',                  // Home Team
                    $bet->team_two ?? '',                  // Away Team
                    $bet->markets ?? '',                   // Bet Type
                    $bet->wager_type ?? '',                // Wager Type
                    $bet->tips ?? '',                      // Wager Name
                    $bet->wager_odds ?? '',                // odds (American format)
                    $bet->level ?? $bet->membership ?? 'Bronze',  // level
                    $bet->code ?? $bet->referrer ?? '',   // code
                    ucfirst($bet->status ?? 'Pending'),   // Status
                    $roi,                                   // ROI(net)
                    $wager,                                 // Wager
                    $profits,                               // Profits
                    $winningAmount,                          // Winning Amount
                ]);
            }
            fclose($handle);
        };

        return Response::stream($callback, 200, $headers);
    }
}
