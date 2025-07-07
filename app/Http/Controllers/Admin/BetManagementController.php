<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\User;
use App\Services\SimpleCacheService;
use App\Traits\HasFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BetManagementController extends Controller
{
    use HasFilters;

    /**
     * Display a listing of bets with optimized queries and caching.
     */
    public function index(Request $request)
    {
        // Log admin accessing bets
        activity()
            ->causedBy(Auth::user())
            ->log('Viewed bets list');

        $perPage = $this->getPerPage($request, 25, 100);

        // Build optimized query with eager loading
        $query = Bet::query()
            ->select(['bets.*']);

        // Apply filters using trait
        $this->applyFilters($query, $request);

        // Apply sorting using trait
        $validSortFields = ['betting_date', 'created_at', 'wager_amount', 'winning_amount', 'profit_amount', 'wager_odds', 'status'];
        $this->applySorting($query, $request, $validSortFields, 'betting_date', 'desc');

        // Execute query with optimized pagination
        $bets = $query->paginate($perPage)->withQueryString();

        // Get cached statistics
        $stats = SimpleCacheService::rememberQuery(
            SimpleCacheService::KEY_BET_STATS.':filtered:'.md5(serialize($request->all())),
            SimpleCacheService::TTL_SHORT,
            fn () => $this->getOptimizedStats($request)
        );

        // Get cached filter options
        $filterOptions = $this->getCachedFilterOptions();

        return Inertia::render('admin/Bets/Index', [
            'bets' => $bets,
            'filters' => $request->only([
                'status', 'sport_id', 'operator_id', 'user_id',
                'date_from', 'date_to', 'search', 'bet_type',
                'is_featured', 'profit_status',
                'sort_by', 'sort_direction', 'per_page',
            ]),
            'stats' => $stats,
            'sports' => $filterOptions['sports'],
            'operators' => $filterOptions['operators'],
            'statuses' => ['pending', 'won', 'lost', 'void', 'push'],
            'betTypes' => ['single', 'parlay', 'prop'],
        ]);
    }

    /**
     * Apply all filters to the query
     */
    private function applyFilters($query, Request $request): void
    {
        // Basic filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sport filter by name
        if ($request->filled('sport_id')) {
            $query->where('sports', $request->sport_id);
        }

        // League filter
        if ($request->filled('league')) {
            $query->where('league', $request->league);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('bet_type')) {
            $query->where('bet_type', $request->bet_type);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', $request->boolean('is_featured'));
        }

        // Date filters using trait
        $this->applyDateFilters($query, $request, 'betting_date');

        // Profit status filter
        if ($request->filled('profit_status')) {
            match ($request->profit_status) {
                'profit' => $query->where('profit_amount', '>', 0),
                'loss' => $query->where('profit_amount', '<', 0),
                'breakeven' => $query->where('profit_amount', 0),
                default => null
            };
        }

        // Search filter using trait with SQL injection protection
        if ($request->filled('search')) {
            $searchFields = [
                'sports',
                'league',
                'matches',
                'markets',
                'team_one',
                'team_two',
                'tips',
                'wager_type',
                'membership',
                'status',
            ];
            $this->applySearchFilter($query, $request, $searchFields);
        }
    }

    /**
     * Get optimized statistics using single query
     */
    private function getOptimizedStats(Request $request): array
    {
        // Build base query for stats
        $baseQuery = Bet::query();
        $this->applyFilters($baseQuery, $request);

        // Get all stats in a single query using conditional aggregations
        $stats = $baseQuery->selectRaw('
            COUNT(*) as total_bets,
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = "won" THEN 1 ELSE 0 END) as won_count,
            SUM(CASE WHEN status = "lost" THEN 1 ELSE 0 END) as lost_count,
            SUM(CASE WHEN status = "push" THEN 1 ELSE 0 END) as push_count,
            SUM(CASE WHEN status = "void" THEN 1 ELSE 0 END) as void_count,
            COALESCE(SUM(wager_amount), 0) as total_stake,
            COALESCE(SUM(CASE WHEN status = "won" THEN profit_amount ELSE 0 END), 0) as total_profit,
            COALESCE(SUM(CASE WHEN status = "lost" THEN ABS(profit_amount) ELSE 0 END), 0) as total_loss,
            COALESCE(AVG(wager_odds), 0) as avg_odds
        ')->first();

        // Calculate derived stats
        $totalSettled = $stats->won_count + $stats->lost_count;
        $winRate = $totalSettled > 0 ? ($stats->won_count / $totalSettled) * 100 : 0;
        $roi = $stats->total_stake > 0 ? (($stats->total_profit - $stats->total_loss) / $stats->total_stake) * 100 : 0;

        return [
            'total_bets' => $stats->total_bets,
            'pending_count' => $stats->pending_count,
            'won_count' => $stats->won_count,
            'lost_count' => $stats->lost_count,
            'total_stake' => round($stats->total_stake, 2),
            'total_profit' => round($stats->total_profit, 2),
            'total_loss' => round($stats->total_loss, 2),
            'net_profit' => round($stats->total_profit - $stats->total_loss, 2),
            'win_rate' => round($winRate, 2),
            'roi' => round($roi, 2),
            'avg_odds' => round($stats->avg_odds, 2),
        ];
    }

    /**
     * Get cached filter options
     */
    private function getCachedFilterOptions(): array
    {
        $sports = SimpleCacheService::rememberQuery(
            SimpleCacheService::KEY_SPORTS_LIST,
            SimpleCacheService::TTL_LONG,
            fn () => Sport::orderBy('name')->pluck('name', 'id')
        );

        $operators = SimpleCacheService::rememberQuery(
            SimpleCacheService::KEY_OPERATORS_LIST,
            SimpleCacheService::TTL_LONG,
            fn () => Operator::orderBy('name')->pluck('name', 'id')
        );

        return compact('sports', 'operators');
    }

    /**
     * Show the form for creating a new bet.
     */
    public function create()
    {
        // Get filter options for dropdowns
        $sports = Sport::orderBy('name')->get(['id', 'name']);
        $operators = Operator::orderBy('name')->get(['id', 'name']);
        $games = []; // Empty array since we'll use team text fields instead

        // Define bet types
        $betTypes = [
            'moneyline' => 'Moneyline',
            'spread' => 'Point Spread',
            'total' => 'Over/Under (Total)',
            'prop' => 'Prop Bet',
            'parlay' => 'Parlay',
            'futures' => 'Futures',
        ];

        return Inertia::render('admin/Bets/Create', [
            'sports' => $sports,
            'operators' => $operators,
            'games' => $games,
            'betTypes' => $betTypes,
        ]);
    }

    /**
     * Store a newly created bet.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sport_id' => 'required|exists:sports,id',
            'operator_id' => 'required|exists:operators,id',
            'team_one' => 'required|string|max:255',
            'team_two' => 'required|string|max:255',
            'selection' => 'required|string|max:255',
            'description' => 'nullable|string',
            'bet_type' => 'required|in:moneyline,spread,total,prop,parlay,futures',
            'stake' => 'required|numeric|min:0',
            'odds' => 'required|numeric',
            'game_at' => 'required|date',
            'status' => 'required|in:pending,won,lost,void,push',
            'is_featured' => 'boolean',
            'membership' => 'required|in:bronze,silver,gold,platinum',
            'referrer' => 'nullable|string|max:255',
        ]);

        // Set the user_id to the authenticated admin
        $validated['user_id'] = Auth::id();

        // Calculate potential win and profit
        $validated['potential_win'] = $this->calculatePotentialWin($validated['stake'], $validated['odds']);
        $validated['profit'] = $this->calculateProfit($validated);
        $validated['betting_date'] = now();

        // Map sport_id to sports field
        $sport = Sport::find($validated['sport_id']);
        $validated['sports'] = $sport ? $sport->name : '';

        // Map other fields
        $validated['wager_amount'] = $validated['stake'];
        $validated['wager_odds'] = $validated['odds'];
        $validated['tips'] = $validated['selection'];

        // Remove fields that don't exist in bets table
        unset($validated['sport_id']);
        unset($validated['operator_id']);
        unset($validated['stake']);
        unset($validated['odds']);
        unset($validated['selection']);
        unset($validated['game_at']);

        $bet = Bet::create($validated);

        // Clear related caches
        SimpleCacheService::invalidateRelated('bet');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->log('Created new bet');

        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet created successfully.');
    }

    /**
     * Show the form for editing the specified bet.
     */
    public function edit(Bet $bet)
    {
        // Get filter options for dropdowns
        $sports = Sport::orderBy('name')->get(['id', 'name']);
        $operators = Operator::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Load the bet with only existing relationships
        $bet->load(['user']);

        return Inertia::render('admin/Bets/Edit', [
            'bet' => $bet->toArray(), // Convert to array to ensure all fields are included
            'sports' => $sports,
            'operators' => $operators,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified bet.
     */
    public function update(Request $request, Bet $bet)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'sports' => 'required|string|max:255',
            'league' => 'nullable|string|max:255',
            'month' => 'nullable|string|max:50',
            'matches' => 'nullable|string|max:500',
            'markets' => 'nullable|string|max:255',
            'wager_type' => 'nullable|string|max:50',
            'team_one' => 'nullable|string|max:255',
            'team_two' => 'nullable|string|max:255',
            'tips' => 'nullable|string|max:500',
            'betting_date' => 'required|date',
            'wager_odds' => 'required|numeric',
            'wager_amount' => 'required|numeric|min:0',
            'winning_amount' => 'nullable|numeric|min:0',
            'profit_amount' => 'nullable|numeric',
            'roi' => 'nullable|numeric',
            'status' => 'required|in:pending,won,lost,void,push',
            'membership' => 'required|in:bronze,silver,gold,platinum',
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'referrer' => 'nullable|string|max:255',
            'place_fraction' => 'nullable|numeric|between:0,1',
        ]);

        $bet->update($validated);

        // Clear related caches
        SimpleCacheService::invalidateRelated('bet');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->log('Updated bet');

        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet updated successfully.');
    }

    /**
     * Remove the specified bet from storage.
     */
    public function destroy(Bet $bet)
    {
        $bet->delete();

        // Clear related caches
        SimpleCacheService::invalidateRelated('bet');

        activity()
            ->causedBy(Auth::user())
            ->performedOn($bet)
            ->log('Deleted bet');

        return redirect()->route('admin.bets.index')
            ->with('success', 'Bet deleted successfully.');
    }

    /**
     * Bulk update bet statuses with transaction
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validated = $request->validate([
            'bet_ids' => 'required|array',
            'bet_ids.*' => 'exists:bets,id',
            'status' => 'required|in:won,lost,void,push',
            'actual_result' => 'nullable|string',
        ]);

        $updatedCount = 0;

        DB::transaction(function () use ($validated, &$updatedCount) {
            $bets = Bet::whereIn('id', $validated['bet_ids'])->get();

            foreach ($bets as $bet) {
                $updateData = [
                    'status' => $validated['status'],
                    'actual_result' => $validated['actual_result'] ?? $bet->actual_result,
                    'profit' => $this->calculateProfit([
                        'status' => $validated['status'],
                        'stake' => $bet->stake,
                        'potential_win' => $bet->potential_win,
                    ]),
                ];

                $bet->update($updateData);
                $updatedCount++;
            }
        });

        // Clear related caches
        SimpleCacheService::invalidateRelated('bet');

        activity()
            ->causedBy(Auth::user())
            ->log("Bulk updated {$updatedCount} bets to status: {$validated['status']}");

        return back()->with('success', "{$updatedCount} bets updated successfully.");
    }

    /**
     * Calculate potential win based on stake and odds
     */
    private function calculatePotentialWin($stake, $odds): float
    {
        if ($odds > 0) {
            return $stake * ($odds / 100);
        } else {
            return $stake * (100 / abs($odds));
        }
    }

    /**
     * Calculate profit based on bet status
     */
    private function calculateProfit(array $data): float
    {
        return match ($data['status']) {
            'won' => $data['potential_win'] ?? 0,
            'lost' => -($data['stake'] ?? 0),
            'void', 'push' => 0,
            default => 0
        };
    }
}
