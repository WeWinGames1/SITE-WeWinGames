<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Game;
use App\Models\Sport;
use App\Models\Operator;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class BetManagementController extends Controller
{
    /**
     * Display a listing of bets.
     */
    public function index(Request $request)
    {
        $query = Bet::with(['user:id,name,email', 'sport:id,name', 'game', 'operator:id,name']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->filled('date_from')) {
            $query->whereDate('game_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('game_at', '<=', $request->date_to);
        }
        
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('selection', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhereHas('user', function ($q) use ($request) {
                        $q->where('name', 'like', '%' . $request->search . '%')
                            ->orWhere('email', 'like', '%' . $request->search . '%');
                    });
            });
        }
        
        $bets = $query->orderBy('game_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50)
            ->withQueryString();
        
        return Inertia::render('Admin/Bets/Index', [
            'bets' => $bets,
            'filters' => $request->only(['status', 'sport_id', 'user_id', 'date_from', 'date_to', 'search']),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'won', 'lost', 'push', 'cancelled'],
        ]);
    }
    
    /**
     * Show the form for creating a new bet.
     */
    public function create()
    {
        return Inertia::render('Admin/Bets/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'games' => Game::with(['sport', 'homeTeam', 'awayTeam'])
                ->where('game_at', '>=', now())
                ->orderBy('game_at')
                ->limit(100)
                ->get(),
            'operators' => Operator::orderBy('name')->get(['id', 'name']),
            'betTypes' => [
                'moneyline' => 'Moneyline',
                'spread' => 'Point Spread',
                'over_under' => 'Over/Under',
                'prop' => 'Prop Bet',
                'parlay' => 'Parlay',
                'teaser' => 'Teaser',
                'futures' => 'Futures',
            ],
        ]);
    }
    
    /**
     * Store a newly created bet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sport_id' => 'required|exists:sports,id',
            'game_id' => 'nullable|exists:games,id',
            'operator_id' => 'required|exists:operators,id',
            'selection' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bet_type' => 'required|string',
            'odds' => 'required|numeric',
            'stake' => 'required|numeric|min:0',
            'potential_win' => 'required|numeric|min:0',
            'game_at' => 'required|date',
            'status' => 'required|in:pending,won,lost,push,cancelled',
            'actual_result' => 'nullable|string',
            'profit' => 'nullable|numeric',
            'is_featured' => 'boolean',
            'confidence' => 'nullable|integer|min:1|max:10',
        ]);
        
        // Calculate profit if status is settled
        if (in_array($validated['status'], ['won', 'lost', 'push'])) {
            if ($validated['status'] === 'won') {
                $validated['profit'] = $validated['potential_win'];
            } elseif ($validated['status'] === 'lost') {
                $validated['profit'] = -$validated['stake'];
            } else {
                $validated['profit'] = 0;
            }
        }
        
        $bet = Bet::create($validated);
        
        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet created successfully.');
    }
    
    /**
     * Show the form for editing a bet.
     */
    public function edit(Bet $bet)
    {
        $bet->load(['user:id,name,email', 'sport:id,name', 'game', 'operator:id,name']);
        
        return Inertia::render('Admin/Bets/Edit', [
            'bet' => $bet,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'games' => Game::with(['sport', 'homeTeam', 'awayTeam'])
                ->where('sport_id', $bet->sport_id)
                ->orderBy('game_at', 'desc')
                ->limit(100)
                ->get(),
            'operators' => Operator::orderBy('name')->get(['id', 'name']),
            'betTypes' => [
                'moneyline' => 'Moneyline',
                'spread' => 'Point Spread',
                'over_under' => 'Over/Under',
                'prop' => 'Prop Bet',
                'parlay' => 'Parlay',
                'teaser' => 'Teaser',
                'futures' => 'Futures',
            ],
        ]);
    }
    
    /**
     * Update the specified bet.
     */
    public function update(Request $request, Bet $bet)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'sport_id' => 'required|exists:sports,id',
            'game_id' => 'nullable|exists:games,id',
            'operator_id' => 'required|exists:operators,id',
            'selection' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bet_type' => 'required|string',
            'odds' => 'required|numeric',
            'stake' => 'required|numeric|min:0',
            'potential_win' => 'required|numeric|min:0',
            'game_at' => 'required|date',
            'status' => 'required|in:pending,won,lost,push,cancelled',
            'actual_result' => 'nullable|string',
            'profit' => 'nullable|numeric',
            'is_featured' => 'boolean',
            'confidence' => 'nullable|integer|min:1|max:10',
        ]);
        
        // Calculate profit if status is settled
        if (in_array($validated['status'], ['won', 'lost', 'push'])) {
            if ($validated['status'] === 'won') {
                $validated['profit'] = $validated['potential_win'];
            } elseif ($validated['status'] === 'lost') {
                $validated['profit'] = -$validated['stake'];
            } else {
                $validated['profit'] = 0;
            }
        }
        
        $bet->update($validated);
        
        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet updated successfully.');
    }
    
    /**
     * Remove the specified bet.
     */
    public function destroy(Bet $bet)
    {
        $bet->delete();
        
        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet deleted successfully.');
    }
    
    /**
     * Bulk update bet statuses.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'bet_ids' => 'required|array',
            'bet_ids.*' => 'exists:bets,id',
            'status' => 'required|in:won,lost,push,cancelled',
        ]);
        
        $bets = Bet::whereIn('id', $validated['bet_ids'])->get();
        
        foreach ($bets as $bet) {
            $profit = 0;
            
            if ($validated['status'] === 'won') {
                $profit = $bet->potential_win;
            } elseif ($validated['status'] === 'lost') {
                $profit = -$bet->stake;
            }
            
            $bet->update([
                'status' => $validated['status'],
                'profit' => $profit,
            ]);
        }
        
        return back()->with('success', count($bets) . ' bets updated successfully.');
    }
    
    /**
     * Get bet statistics.
     */
    public function statistics()
    {
        $stats = [
            'total_bets' => Bet::count(),
            'pending_bets' => Bet::where('status', 'pending')->count(),
            'won_bets' => Bet::where('status', 'won')->count(),
            'lost_bets' => Bet::where('status', 'lost')->count(),
            'total_staked' => Bet::sum('stake'),
            'total_profit' => Bet::sum('profit'),
            'win_rate' => Bet::whereIn('status', ['won', 'lost'])->count() > 0 
                ? (Bet::where('status', 'won')->count() / Bet::whereIn('status', ['won', 'lost'])->count()) * 100 
                : 0,
            'roi' => Bet::sum('stake') > 0 
                ? (Bet::sum('profit') / Bet::sum('stake')) * 100 
                : 0,
            'by_sport' => Sport::withCount('bets')
                ->with(['bets' => function ($query) {
                    $query->selectRaw('sport_id, SUM(stake) as total_stake, SUM(profit) as total_profit')
                        ->groupBy('sport_id');
                }])
                ->get()
                ->map(function ($sport) {
                    return [
                        'name' => $sport->name,
                        'count' => $sport->bets_count,
                        'stake' => $sport->bets->first()->total_stake ?? 0,
                        'profit' => $sport->bets->first()->total_profit ?? 0,
                    ];
                }),
        ];
        
        return response()->json($stats);
    }
}