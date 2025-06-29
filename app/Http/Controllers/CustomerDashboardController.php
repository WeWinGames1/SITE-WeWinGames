<?php

namespace App\Http\Controllers;

use App\Services\BetService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private BetService $betService
    ) {}

    public function index()
    {
        $user = Auth::user();
        
        // Get today's bets for the customer based on their subscription
        $todaysBets = $this->betService->getTodaysBets();
        
        // Get recent winning bets
        $recentWins = $this->betService->getRecentWinningBets(5);
        
        // Get user's subscription tier
        $subscription = $user->subscriptions()->active()->first();
        $subscriptionTier = $subscription ? $subscription->type : 'free';
        
        return Inertia::render('CustomerDashboard', [
            'subscriptionTier' => $subscriptionTier,
            'todaysBetsCount' => count($todaysBets),
            'recentWins' => $recentWins,
            'monthlyStats' => [
                'winRate' => 68, // This should come from actual calculations
                'totalPicks' => 124,
                'profitPercentage' => 18.5,
            ],
            'hasActiveSubscription' => $subscription !== null,
        ]);
    }
}