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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

class BetManagementController extends Controller
{
    /**
     * Display a listing of bets.
     */
    public function index(Request $request)
    {
        // Log admin accessing bets
        activity()
            ->causedBy(Auth::user())
            ->log('Viewed bets list');

        $perPage = $request->input('per_page', 25);
        $sortField = $request->input('sort', 'game_at');
        $sortDirection = $request->input('direction', 'desc');
        
        $query = Bet::with(['user:id,name,email', 'sport:id,name', 'game', 'operator:id,name']);
        
        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
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
        
        // Enhanced bet type filter
        if ($request->filled('bet_type')) {
            $query->where('bet_type', $request->bet_type);
        }
        
        // Featured filter
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        
        // Confidence filter
        if ($request->filled('min_confidence')) {
            $query->where('confidence', '>=', $request->min_confidence);
        }
        
        // Profit filter
        if ($request->filled('profit_status')) {
            if ($request->profit_status === 'profit') {
                $query->where('profit', '>', 0);
            } elseif ($request->profit_status === 'loss') {
                $query->where('profit', '<', 0);
            } elseif ($request->profit_status === 'breakeven') {
                $query->where('profit', 0);
            }
        }
        
        // Enhanced search across multiple fields
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('selection', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhere('actual_result', 'like', '%' . $searchTerm . '%')
                    ->orWhere('bet_type', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('sport', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('operator', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    });
            });
        }
        
        // Apply sorting
        $validSortFields = ['game_at', 'created_at', 'stake', 'potential_win', 'profit', 'odds', 'confidence', 'status'];
        if (in_array($sortField, $validSortFields)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            // Default sorting
            $query->orderBy('game_at', 'desc')->orderBy('created_at', 'desc');
        }
        
        $bets = $query->paginate($perPage)->withQueryString();
        
        // Get statistics for dashboard
        $stats = $this->getQuickStats($request);
        
        return Inertia::render('admin/Bets/Index', [
            'bets' => $bets,
            'filters' => $request->only([
                'status', 'sport_id', 'operator_id', 'user_id', 
                'date_from', 'date_to', 'search', 'bet_type', 
                'is_featured', 'min_confidence', 'profit_status',
                'sort', 'direction', 'per_page'
            ]),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'operators' => Operator::orderBy('name')->get(['id', 'name']),
            'statuses' => ['pending', 'won', 'lost', 'push', 'cancelled'],
            'betTypes' => [
                'moneyline' => 'Moneyline',
                'spread' => 'Point Spread',
                'over_under' => 'Over/Under',
                'prop' => 'Prop Bet',
                'parlay' => 'Parlay',
                'teaser' => 'Teaser',
                'futures' => 'Futures',
            ],
            'stats' => $stats,
        ]);
    }
    
    /**
     * Show the form for creating a new bet.
     */
    public function create()
    {
        return Inertia::render('admin/Bets/Create', [
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'games' => Game::with(['sport', 'operator'])
                ->where('game_date', '>=', now())
                ->orderBy('game_date')
                ->limit(100)
                ->get()
                ->map(function ($game) {
                    return [
                        'id' => $game->id,
                        'title' => $game->title,
                        'game_date' => $game->game_date,
                        'sport' => $game->sport,
                        'operator' => $game->operator,
                        'team1' => $game->team1,
                        'team2' => $game->team2,
                        'team1_img' => $game->team1_img,
                        'team2_img' => $game->team2_img,
                        'homeTeam' => $game->homeTeam(),
                        'awayTeam' => $game->awayTeam(),
                    ];
                }),
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
        
        // Log the action
        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->withProperties([
                'selection' => $bet->selection,
                'stake' => $bet->stake,
                'odds' => $bet->odds,
                'status' => $bet->status,
            ])
            ->log('Created new bet');
        
        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet created successfully.');
    }
    
    /**
     * Show the form for editing a bet.
     */
    public function edit(Bet $bet)
    {
        $bet->load(['user:id,name,email', 'sport:id,name', 'game', 'operator:id,name']);
        
        return Inertia::render('admin/Bets/Edit', [
            'bet' => $bet,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'sports' => Sport::orderBy('name')->get(['id', 'name']),
            'games' => Game::with(['sport', 'operator'])
                ->where('sport_id', $bet->sport_id)
                ->orderBy('game_date', 'desc')
                ->limit(100)
                ->get()
                ->map(function ($game) {
                    return [
                        'id' => $game->id,
                        'title' => $game->title,
                        'game_date' => $game->game_date,
                        'sport' => $game->sport,
                        'operator' => $game->operator,
                        'team1' => $game->team1,
                        'team2' => $game->team2,
                        'team1_img' => $game->team1_img,
                        'team2_img' => $game->team2_img,
                        'homeTeam' => $game->homeTeam(),
                        'awayTeam' => $game->awayTeam(),
                    ];
                }),
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
        
        // Store original values for logging
        $original = $bet->only(['status', 'profit', 'selection', 'stake']);
        
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
        
        // Log the action with changes
        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->withProperties([
                'old' => $original,
                'new' => $bet->only(['status', 'profit', 'selection', 'stake']),
            ])
            ->log('Updated bet');
        
        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet updated successfully.');
    }
    
    /**
     * Remove the specified bet.
     */
    public function destroy(Bet $bet)
    {
        // Log the deletion
        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->withProperties([
                'selection' => $bet->selection,
                'user' => $bet->user->name,
                'stake' => $bet->stake,
                'status' => $bet->status,
            ])
            ->log('Deleted bet');
        
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
        $updatedCount = 0;
        
        DB::beginTransaction();
        try {
            foreach ($bets as $bet) {
                $originalStatus = $bet->status;
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
                
                $updatedCount++;
            }
            
            DB::commit();
            
            // Log bulk action
            activity()
                ->causedBy(Auth::user())
                ->withProperties([
                    'count' => $updatedCount,
                    'status' => $validated['status'],
                    'bet_ids' => $validated['bet_ids'],
                ])
                ->log("Bulk updated {$updatedCount} bets to status: {$validated['status']}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update bets. Please try again.');
        }
        
        return back()->with('success', $updatedCount . ' bets updated successfully.');
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
    
    /**
     * Get quick statistics for the dashboard.
     */
    private function getQuickStats($request)
    {
        $query = Bet::query();
        
        // Apply the same filters as the main query
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('game_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('game_at', '<=', $request->date_to);
        }
        
        $totalBets = $query->count();
        $totalStake = $query->sum('stake');
        $totalProfit = $query->sum('profit');
        
        $wonBets = (clone $query)->where('status', 'won')->count();
        $lostBets = (clone $query)->where('status', 'lost')->count();
        $pendingBets = (clone $query)->where('status', 'pending')->count();
        
        $winRate = ($wonBets + $lostBets) > 0 
            ? round(($wonBets / ($wonBets + $lostBets)) * 100, 2) 
            : 0;
        
        $roi = $totalStake > 0 
            ? round(($totalProfit / $totalStake) * 100, 2) 
            : 0;
        
        return [
            'total_bets' => $totalBets,
            'pending_bets' => $pendingBets,
            'total_stake' => $totalStake,
            'total_profit' => $totalProfit,
            'win_rate' => $winRate,
            'roi' => $roi,
        ];
    }
    
    /**
     * Export bets to CSV.
     */
    public function export(Request $request)
    {
        // Log export action
        activity()
            ->causedBy(Auth::user())
            ->withProperties([
                'filters' => $request->all(),
            ])
            ->log('Exported bets data');
        
        $query = Bet::with(['user:id,name,email', 'sport:id,name', 'game', 'operator:id,name']);
        
        // Apply all the same filters as index
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('sport_id')) {
            $query->where('sport_id', $request->sport_id);
        }
        if ($request->filled('operator_id')) {
            $query->where('operator_id', $request->operator_id);
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
        if ($request->filled('bet_type')) {
            $query->where('bet_type', $request->bet_type);
        }
        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('selection', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    });
            });
        }
        
        $bets = $query->orderBy('game_at', 'desc')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bets-export-' . now()->format('Y-m-d-His') . '.csv"',
        ];
        
        $callback = function() use ($bets) {
            $file = fopen('php://output', 'w');
            
            // CSV headers
            fputcsv($file, [
                'ID',
                'User',
                'Email',
                'Sport',
                'Game',
                'Operator',
                'Selection',
                'Description',
                'Type',
                'Odds',
                'Stake',
                'Potential Win',
                'Status',
                'Profit',
                'Game Date',
                'Created At',
                'Featured',
                'Confidence',
            ]);
            
            foreach ($bets as $bet) {
                fputcsv($file, [
                    $bet->id,
                    $bet->user->name,
                    $bet->user->email,
                    $bet->sport->name,
                    $bet->game ? "{$bet->game->away_team} @ {$bet->game->home_team}" : '',
                    $bet->operator->name,
                    $bet->selection,
                    $bet->description,
                    $bet->bet_type,
                    $bet->odds,
                    $bet->stake,
                    $bet->potential_win,
                    $bet->status,
                    $bet->profit,
                    $bet->game_at,
                    $bet->created_at,
                    $bet->is_featured ? 'Yes' : 'No',
                    $bet->confidence,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}