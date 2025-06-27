<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Services\BetService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BetController extends Controller
{

    /**
     * The BetService instance.
     *
     * @var \App\Services\BetService
     */
    protected $betService;  
    /**
     * Create a new controller instance.
     *
     * @param  \App\Services\BetService  $betService
     * @return void
     */
    public function __construct(BetService $betService)
    {
        $this->betService = $betService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        if (request()->user()->cannot('viewAny', Bet::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $bets = $this->betService->getAllBets();
        return response()->json($bets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->cannot('create', Bet::class)) {
            session()->flash('error', 'You are not authorized to create a bet.');
            return redirect()->back();
        }

        $bet = $this->betService->createBetFromRequest($request);

        session()->flash('success', 'Bet created successfully!');
        return redirect()->route('dashboard');
    }

    /**
     * Display the specified resource.
     */
    public function show(Bet $bet)
    {
        //
        if (request()->user()->cannot('view', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return response()->json($bet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Bet $bet)
    {
        if ($request->user()->cannot('update', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $updatedBet = $this->betService->updateBetAndCalculateROI($bet, $request);

        return response()->json($updatedBet);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Bet $bet)
    {
        if (request()->user()->cannot('delete', $bet)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->betService->deleteBet($bet);

        return response()->json(null, 204);
    }

    /**
     * Get the bets for a specific sport.
     *
     * @param  string  $sport
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBetsBySport($sport)
    {
        if (request()->user()->cannot('viewAny', Bet::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $bets = $this->betService->getBetsBySport($sport);
        return response()->json($bets);
    }

    /**
     * Get all bets.
     *
     * @return \Inertia\Response
     */
    public function getAllBets()
    {
        // Ensure the user is an admin
        if (request()->user()->cannot('viewAny', Bet::class)) {
            abort(403, 'Unauthorized');
        }

        // Fetch all bets using the BetService
        $bets = $this->betService->getAllBets();

        // Return an Inertia response
        return Inertia::render('Dashboard', [
            'bets' => $bets,
        ]);
    }
}
