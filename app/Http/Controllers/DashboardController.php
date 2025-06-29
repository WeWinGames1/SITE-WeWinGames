<?php

namespace App\Http\Controllers;

use App\Services\BetService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        private BetService $betService
    ) {}

    public function index()
    {
        return Inertia::render('Dashboard', [
            'subscriptions' => Auth::user()->subscriptions,
            'bets' => $this->betService->getAllBets(),
            'roiData' => $this->betService->getTotalROIBySubscriptionLevel(),
            'sportProfitRoiData' => $this->betService->getProfitAndROIBySport(),
        ]);
    }
}