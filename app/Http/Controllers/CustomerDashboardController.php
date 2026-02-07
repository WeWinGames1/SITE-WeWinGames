<?php

namespace App\Http\Controllers;

use App\Services\BetService;
use App\Services\DiscordService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private BetService $betService,
        private DiscordService $discordService
    ) {}

    public function index()
    {
        $user = Auth::user();

        // Redirect admin users to admin dashboard
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Get today's bets for the customer based on their subscription
        $todaysBets = $this->betService->getTodaysBets();

        // Get recent winning bets
        $recentWins = $this->betService->getRecentWinningBets(5);

        // Get user's subscription tier
        $subscriptionTier = $user->getCurrentTier() ?? 'free';
        $hasActiveSubscription = $user->hasActiveSubscription();

        // Discord integration data
        $discordData = [
            'connected' => $user->hasDiscordConnected(),
            'username' => $user->discord_username,
            'avatarUrl' => $user->discord_avatar_url,
            'connectedAt' => $user->discord_connected_at?->toISOString(),
            'rolesSynced' => $user->discord_roles_synced ?? [],
            'inviteUrl' => $this->discordService->getInviteUrl(),
            'isConfigured' => $this->discordService->isConfigured(),
        ];

        return Inertia::render('CustomerDashboard', [
            'subscriptionTier' => strtolower($subscriptionTier),
            'todaysBetsCount' => count($todaysBets),
            'recentWins' => $recentWins,
            'monthlyStats' => [
                'winRate' => 68, // This should come from actual calculations
                'totalPicks' => 124,
                'profitPercentage' => 18.5,
            ],
            'hasActiveSubscription' => $hasActiveSubscription,
            'discord' => $discordData,
        ]);
    }
}
