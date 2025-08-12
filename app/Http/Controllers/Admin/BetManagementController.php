<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\League;
use App\Models\Operator;
use App\Models\Sport;
use App\Models\Team;
use App\Models\User;
use App\Services\SimpleCacheService;
use App\Traits\HasFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
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

        // Build optimized query without eager loading team relationships
        // The team_one and team_two fields should remain as strings
        $query = Bet::query()
            ->select(['bets.*']);

        // Apply filters using trait
        $this->applyFilters($query, $request);

        // Apply sorting using trait
        $validSortFields = ['betting_date', 'created_at', 'wager_amount', 'winning_amount', 'profit_amount', 'wager_odds', 'status'];
        $this->applySorting($query, $request, $validSortFields, 'betting_date', 'desc');

        // Execute query with optimized pagination
        $bets = $query->paginate($perPage)->withQueryString();
        
        // Transform bet data to ensure team fields are strings
        $bets->getCollection()->transform(function ($bet) {
            // Make sure team_one and team_two remain as strings
            $bet->team_one = (string) $bet->team_one;
            $bet->team_two = (string) $bet->team_two;
            return $bet;
        });

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
                'status', 'sport_id', 'referrer', 'user_id',
                'date_from', 'date_to', 'search', 'wager_type',
                'is_featured', 'profit_status',
                'sort_by', 'sort_direction', 'per_page',
            ]),
            'stats' => $stats,
            'sports' => $filterOptions['sports'],
            'referrers' => $filterOptions['referrers'],
            'statuses' => ['pending', 'won', 'loss', 'placed', 'push'],
            'betTypes' => [
                'Single Wager' => 'Single Wager',
                'Parlay' => 'Parlay',
                'Each Way' => 'Each Way',
                'moneyline' => 'Moneyline',
                'spread' => 'Point Spread',
                'total' => 'Over/Under (Total)',
                'prop' => 'Prop Bet',
                'futures' => 'Futures',
            ],
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

        // Sport filter - convert ID to name
        if ($request->filled('sport_id')) {
            $sport = Sport::find($request->sport_id);
            if ($sport) {
                $query->where('sports', $sport->name);
            }
        }

        // League filter
        if ($request->filled('league')) {
            $query->where('league', $request->league);
        }

        // User filter
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Referrer filter (code field)
        if ($request->filled('referrer')) {
            $query->where('code', $request->referrer);
        }

        if ($request->filled('wager_type')) {
            $query->where('wager_type', $request->wager_type);
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
            fn () => Sport::orderBy('name')->get(['id', 'name'])->toArray()
        );

        // Get unique referrers (codes) from bets
        $referrers = SimpleCacheService::rememberQuery(
            'bets:referrers:list',
            SimpleCacheService::TTL_SHORT,
            fn () => Bet::whereNotNull('code')
                ->where('code', '!=', '')
                ->distinct()
                ->pluck('code')
                ->sort()
                ->values()
                ->toArray()
        );

        return compact('sports', 'referrers');
    }

    /**
     * Show the form for creating a new bet.
     */
    public function create()
    {
        // Get filter options for dropdowns
        $sports = Sport::orderBy('name')->get(['id', 'name']);
        $leagues = League::with('sport')->orderBy('name')->get(['id', 'name', 'sport_id']);
        $operators = Operator::orderBy('name')->get(['id', 'name']);
        $games = []; // Empty array since we'll use team text fields instead

        // Define bet types
        $betTypes = [
            'single_wager' => 'Single Wager',
            'moneyline' => 'Moneyline',
            'spread' => 'Point Spread',
            'total' => 'Over/Under (Total)',
            'prop' => 'Prop Bet',
            'parlay' => 'Parlay',
            'futures' => 'Futures',
            'each_way' => 'Each Way',
        ];

        return Inertia::render('admin/Bets/Create', [
            'sports' => $sports,
            'leagues' => $leagues,
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
        // Define individual sports that don't require team_two
        $individualSports = ['golf', 'tennis', 'boxing', 'mma', 'ufc', 'racing', 'nascar'];
        $isIndividualSport = in_array(strtolower($request->input('sports', '')), $individualSports);
        
        $validated = $request->validate([
            'sports' => 'required|string|max:255',
            'sport_id' => 'nullable|exists:sports,id',
            'league' => 'nullable|string|max:255',
            'league_id' => 'nullable|exists:leagues,id',
            'month' => 'nullable|string|max:50',
            'team_one' => 'required|string|max:255',
            'team_one_id' => 'nullable|exists:teams,id',
            'team_one_is_new' => 'nullable|boolean',
            'team_two' => $isIndividualSport ? 'nullable|string|max:255' : 'required|string|max:255',
            'team_two_id' => 'nullable|exists:teams,id',
            'team_two_is_new' => 'nullable|boolean',
            'parlay_teams' => 'nullable|array',
            'parlay_teams.*.id' => 'nullable|exists:teams,id',
            'parlay_teams.*.name' => 'nullable|string|max:255',
            'tips' => 'nullable|string|max:500',
            'premium_notes' => 'nullable|string',
            'premium_notes_enabled' => 'nullable|boolean',
            'premium_notes_heading' => 'nullable|string|max:255',
            'markets' => 'required|string|max:255',
            'wager_type' => 'required|string|in:single_wager,moneyline,spread,total,prop,parlay,futures,each_way',
            'betting_date' => 'required|date',
            'game_date' => 'required|date',
            'wager_odds' => 'required|numeric',
            'wager_amount' => 'required|numeric|min:0',
            'status' => 'required|in:pending,won,loss,placed,push',
            'membership' => 'required|in:bronze,silver,gold,platinum',
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'referrer' => 'nullable|string|max:255',
            'place_fraction' => 'nullable|numeric|between:0,1',
            'is_each_way' => 'nullable|boolean',
            // New fields for position and dead heat
            'finishing_position' => 'nullable|string|max:20',
            'places_paid' => 'nullable|integer|min:1|max:50',
            'is_dead_heat' => 'nullable|boolean',
            'dead_heat_players' => 'nullable|integer|min:2|required_if:is_dead_heat,true',
            'dead_heat_spots' => 'nullable|numeric|min:0|required_if:is_dead_heat,true',
            'golf_place' => 'nullable|boolean',
            'golf_place_fraction' => 'nullable|numeric|between:0,1',
        ]);

        // Set the user_id to the authenticated admin
        $validated['user_id'] = Auth::id();
        
        // Normalize sports name to lowercase
        if (isset($validated['sports'])) {
            $validated['sports'] = strtolower($validated['sports']);
        }
        
        // For individual sports, set team_two to empty string if not provided
        if ($isIndividualSport && empty($validated['team_two'])) {
            $validated['team_two'] = '';
        }
        
        // Handle new team creation
        DB::transaction(function () use (&$validated, $request) {
            // Create team one if it's new
            if ($request->boolean('team_one_is_new') && !empty($validated['team_one']) && empty($validated['team_one_id'])) {
                $teamOne = \App\Models\Team::create([
                    'name' => $validated['team_one'],
                    'sport_id' => $validated['sport_id'],
                    'league_id' => $validated['league_id'],
                    'is_active' => true,
                ]);
                $validated['team_one_id'] = $teamOne->id;
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($teamOne)
                    ->log('Created new team/player via bet creation');
            }
            
            // Create team two if it's new
            if ($request->boolean('team_two_is_new') && !empty($validated['team_two']) && empty($validated['team_two_id'])) {
                $teamTwo = \App\Models\Team::create([
                    'name' => $validated['team_two'],
                    'sport_id' => $validated['sport_id'],
                    'league_id' => $validated['league_id'],
                    'is_active' => true,
                ]);
                $validated['team_two_id'] = $teamTwo->id;
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($teamTwo)
                    ->log('Created new team/player via bet creation');
            }
        });
        
        // Remove the is_new flags from validated data
        unset($validated['team_one_is_new'], $validated['team_two_is_new']);

        // Handle Each Way bets
        if ($request->boolean('is_each_way')) {
            $validated['is_each_way'] = true;
            $validated['each_way_stake'] = $validated['wager_amount'] / 2;
            // Default place fraction to 1/5 if not provided
            if (!isset($validated['place_fraction'])) {
                $validated['place_fraction'] = 0.2;
            }
            
            // If position data is provided, calculate status automatically
            if (!empty($validated['finishing_position']) && !empty($validated['places_paid'])) {
                $betService = app(\App\Services\BetService::class);
                
                // Create temporary bet object for calculation
                $tempBet = new Bet($validated);
                
                // Prepare dead heat info if applicable
                $deadHeatInfo = null;
                if (!empty($validated['is_dead_heat']) && !empty($validated['dead_heat_players']) && isset($validated['dead_heat_spots'])) {
                    $deadHeatInfo = [
                        'players_tied' => $validated['dead_heat_players'],
                        'spots_available' => $validated['dead_heat_spots'],
                    ];
                }
                
                // Calculate using automatic determination
                $calculation = $betService->calculateEachWayPayoutWithDeadHeat(
                    $tempBet,
                    $validated['finishing_position'],
                    $validated['places_paid'],
                    $deadHeatInfo
                );
                
                // Apply calculated values
                $validated['status'] = $calculation['status'];
                $validated['bet_result_type'] = $calculation['bet_result_type'];
                $validated['winning_amount'] = $calculation['winning_amount'];
                $validated['place_payout'] = $calculation['place_payout'];
                $validated['profit_amount'] = $calculation['profit_amount'];
                
                // Store position numeric
                $validated['position_numeric'] = $betService->parsePosition($validated['finishing_position']);
            } else {
                // Manual calculation for backward compatibility
                $validated['winning_amount'] = $this->calculatePotentialWin($validated['wager_amount'], $validated['wager_odds']);
                $validated['profit_amount'] = $this->calculateProfit([
                    'status' => $validated['status'],
                    'stake' => $validated['wager_amount'],
                    'potential_win' => $validated['winning_amount']
                ]);
            }
        } else {
            // Standard bet calculation
            $validated['winning_amount'] = $this->calculatePotentialWin($validated['wager_amount'], $validated['wager_odds']);
            $validated['profit_amount'] = $this->calculateProfit([
                'status' => $validated['status'],
                'stake' => $validated['wager_amount'],
                'potential_win' => $validated['winning_amount']
            ]);
        }

        // Check if this is a parlay
        $isParlay = $validated['wager_type'] === 'parlay';
        $validated['is_parlay'] = $isParlay;

        // Handle parlay teams
        if ($isParlay && isset($validated['parlay_teams'])) {
            $parlayTeams = $validated['parlay_teams'];
            unset($validated['parlay_teams']);
            
            DB::transaction(function () use ($validated, $parlayTeams) {
                // Create the bet
                $bet = Bet::create($validated);
                
                // Create parlay team associations
                foreach ($parlayTeams as $teamData) {
                    if (!empty($teamData['id'])) {
                        \App\Models\BetTeam::create([
                            'bet_id' => $bet->id,
                            'team_id' => $teamData['id'],
                        ]);
                    }
                }
                
                // Clear related caches
                SimpleCacheService::invalidateRelated('bet');

                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($bet)
                    ->log('Created new parlay bet');
            });
        } else {
            // Remove parlay_teams from validated data if not a parlay
            unset($validated['parlay_teams']);
            
            $bet = Bet::create($validated);

            // Clear related caches
            SimpleCacheService::invalidateRelated('bet');

            activity()
                ->causedBy(Auth::user())
                ->performedOn($bet)
                ->log('Created new bet');
        }

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
        $leagues = League::with('sport')->orderBy('name')->get(['id', 'name', 'sport_id']);
        $operators = Operator::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        // Load the bet with relationships
        $bet->load(['user', 'teamOne', 'teamTwo', 'parlayTeams']);

        // Define bet types
        $betTypes = [
            'single_wager' => 'Single Wager',
            'moneyline' => 'Moneyline',
            'spread' => 'Point Spread',
            'total' => 'Over/Under (Total)',
            'prop' => 'Prop Bet',
            'parlay' => 'Parlay',
            'futures' => 'Futures',
            'each_way' => 'Each Way',
        ];

        // Convert bet to array and ensure team relationships are properly formatted
        $betArray = $bet->toArray();
        
        // Ensure wager_type and league are included
        $betArray['wager_type'] = $bet->wager_type;
        $betArray['league'] = $bet->league;
        
        // Ensure team relationships are properly formatted
        if ($bet->teamOne) {
            $betArray['teamOne'] = [
                'id' => $bet->teamOne->id,
                'name' => $bet->teamOne->name,
                'sport_id' => $bet->teamOne->sport_id,
                'league_id' => $bet->teamOne->league_id,
                'logo_url' => $bet->teamOne->logo_url,
            ];
        }
        
        if ($bet->teamTwo) {
            $betArray['teamTwo'] = [
                'id' => $bet->teamTwo->id,
                'name' => $bet->teamTwo->name,
                'sport_id' => $bet->teamTwo->sport_id,
                'league_id' => $bet->teamTwo->league_id,
                'logo_url' => $bet->teamTwo->logo_url,
            ];
        }
        
        // Get all teams for the sport to populate Select2
        $sportId = null;
        if ($bet->sports) {
            $sport = Sport::whereRaw('LOWER(name) = ?', [strtolower($bet->sports)])->first();
            if ($sport) {
                $sportId = $sport->id;
            }
        }
        
        $teams = [];
        if ($sportId) {
            $teams = Team::where('sport_id', $sportId)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function($team) {
                    return [
                        'id' => $team->id,
                        'text' => $team->name,
                    ];
                });
        }
        
        return Inertia::render('admin/Bets/Edit', [
            'bet' => $betArray,
            'sports' => $sports,
            'leagues' => $leagues,
            'operators' => $operators,
            'users' => $users,
            'betTypes' => $betTypes,
            'teams' => $teams,
        ]);
    }

    /**
     * Update the specified bet.
     */
    public function update(Request $request, Bet $bet)
    {
        // Define individual sports that don't require team_two
        $individualSports = ['golf', 'tennis', 'boxing', 'mma', 'ufc', 'racing', 'nascar'];
        $isIndividualSport = in_array(strtolower($request->input('sports', '')), $individualSports);
        
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'sports' => 'required|string|max:255',
            'sport_id' => 'nullable|exists:sports,id',
            'league' => 'nullable|string|max:255',
            'league_id' => 'nullable|exists:leagues,id',
            'month' => 'nullable|string|max:50',
            'matches' => 'nullable|string|max:500',
            'markets' => 'required|string|max:255',
            'team_one' => 'required|string|max:255',
            'team_one_id' => 'nullable|exists:teams,id',
            'team_one_is_new' => 'nullable|boolean',
            'team_two' => $isIndividualSport ? 'nullable|string|max:255' : 'required|string|max:255',
            'team_two_id' => 'nullable|exists:teams,id',
            'team_two_is_new' => 'nullable|boolean',
            'tips' => 'nullable|string|max:500',
            'wager_type' => 'required|string|in:single_wager,moneyline,spread,total,prop,parlay,futures,each_way',
            'betting_date' => 'required|date',
            'game_date' => 'required|date',
            'wager_odds' => 'required|numeric',
            'wager_amount' => 'required|numeric|min:0',
            'winning_amount' => 'nullable|numeric',
            'profit_amount' => 'nullable|numeric',
            'roi' => 'nullable|numeric',
            'status' => 'required|in:pending,won,loss,placed,push',
            'membership' => 'required|in:bronze,silver,gold,platinum',
            'level' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:255',
            'referrer' => 'nullable|string|max:255',
            'place_fraction' => 'nullable|numeric|between:0,1',
            'is_each_way' => 'nullable|boolean',
            'is_parlay' => 'nullable|boolean',
            'parlay_teams' => 'nullable|array',
            'parlay_teams.*.id' => 'nullable|exists:teams,id',
            'parlay_teams.*.name' => 'nullable|string|max:255',
            // New fields for position and dead heat
            'finishing_position' => 'nullable|string|max:20',
            'places_paid' => 'nullable|integer|min:1|max:50',
            'is_dead_heat' => 'nullable|boolean',
            'dead_heat_players' => 'nullable|integer|min:2',
            'dead_heat_spots' => 'nullable|numeric|min:0',
            'golf_place' => 'nullable|boolean',
            'golf_place_fraction' => 'nullable|numeric|between:0,1',
        ]);
        
        // Normalize sports name to lowercase
        if (isset($validated['sports'])) {
            $validated['sports'] = strtolower($validated['sports']);
        }
        
        // For individual sports, set team_two to empty string if not provided
        if ($isIndividualSport && empty($validated['team_two'])) {
            $validated['team_two'] = '';
        }
        
        // Handle new team creation
        DB::transaction(function () use (&$validated, $request) {
            // Create team one if it's new
            if ($request->boolean('team_one_is_new') && !empty($validated['team_one']) && empty($validated['team_one_id'])) {
                $teamOne = \App\Models\Team::create([
                    'name' => $validated['team_one'],
                    'sport_id' => $validated['sport_id'] ?? null,
                    'league_id' => $validated['league_id'] ?? null,
                    'is_active' => true,
                ]);
                $validated['team_one_id'] = $teamOne->id;
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($teamOne)
                    ->log('Created new team/player via bet update');
            }
            
            // Create team two if it's new
            if ($request->boolean('team_two_is_new') && !empty($validated['team_two']) && empty($validated['team_two_id'])) {
                $teamTwo = \App\Models\Team::create([
                    'name' => $validated['team_two'],
                    'sport_id' => $validated['sport_id'] ?? null,
                    'league_id' => $validated['league_id'] ?? null,
                    'is_active' => true,
                ]);
                $validated['team_two_id'] = $teamTwo->id;
                
                activity()
                    ->causedBy(Auth::user())
                    ->performedOn($teamTwo)
                    ->log('Created new team/player via bet update');
            }
        });
        
        // Remove the is_new flags from validated data
        unset($validated['team_one_is_new'], $validated['team_two_is_new']);

        // Handle Each Way bets
        if ($request->boolean('is_each_way')) {
            $validated['is_each_way'] = true;
            $validated['each_way_stake'] = $validated['wager_amount'] / 2;
            // Default place fraction to 1/5 if not provided
            if (!isset($validated['place_fraction'])) {
                $validated['place_fraction'] = 0.2;
            }
            
            // If position data is provided, calculate status automatically
            if (!empty($validated['finishing_position']) && !empty($validated['places_paid'])) {
                $betService = app(\App\Services\BetService::class);
                
                // Update bet with current data first
                $bet->fill($validated);
                
                // Prepare dead heat info if applicable
                $deadHeatInfo = null;
                if (!empty($validated['is_dead_heat']) && !empty($validated['dead_heat_players']) && isset($validated['dead_heat_spots'])) {
                    $deadHeatInfo = [
                        'players_tied' => $validated['dead_heat_players'],
                        'spots_available' => $validated['dead_heat_spots'],
                    ];
                }
                
                // Calculate using automatic determination
                $calculation = $betService->calculateEachWayPayoutWithDeadHeat(
                    $bet,
                    $validated['finishing_position'],
                    $validated['places_paid'],
                    $deadHeatInfo
                );
                
                // Apply calculated values
                $validated['status'] = $calculation['status'];
                $validated['bet_result_type'] = $calculation['bet_result_type'];
                $validated['winning_amount'] = $calculation['winning_amount'];
                $validated['place_payout'] = $calculation['place_payout'];
                $validated['profit_amount'] = $calculation['profit_amount'];
                
                // Store position numeric
                $validated['position_numeric'] = $betService->parsePosition($validated['finishing_position']);
            }
        }

        // Handle parlay teams
        if ($request->has('is_parlay') && $request->is_parlay && isset($validated['parlay_teams'])) {
            DB::transaction(function () use ($bet, $validated) {
                // Update bet
                $bet->update(array_diff_key($validated, ['parlay_teams' => '']));
                
                // Update parlay teams
                $bet->parlayTeams()->delete();
                
                foreach ($validated['parlay_teams'] as $teamData) {
                    if (!empty($teamData['id'])) {
                        \App\Models\BetTeam::create([
                            'bet_id' => $bet->id,
                            'team_id' => $teamData['id'],
                        ]);
                    }
                }
            });
        } else {
            $bet->update($validated);
        }

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
            'status' => 'required|in:won,loss,placed,push',
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

    /**
     * Export bets to CSV
     */
    public function export(Request $request)
    {
        // Build the same query as index with filters
        $query = Bet::query()->select(['bets.*']);
        
        // Apply filters using the same method as index
        $this->applyFilters($query, $request);
        
        // Apply sorting
        $validSortFields = ['betting_date', 'created_at', 'wager_amount', 'winning_amount', 'profit_amount', 'wager_odds', 'status'];
        $this->applySorting($query, $request, $validSortFields, 'betting_date', 'desc');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bets_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            
            // CSV header matching the import format (17 columns)
            fputcsv($handle, [
                'Sport', 'League', 'Month', 'Date', 'Home Team', 'Away Team', 'Bet Type',
                'Wager Type', 'Wager Name', 'odds', 'level', 'code', 'Status',
                'ROI(net)', 'Wager', 'Profits', 'Winning Amount',
            ]);

            // Stream results using cursor for memory efficiency
            foreach ($query->cursor() as $bet) {
                // Format date
                $bettingDate = $bet->betting_date ? \Carbon\Carbon::parse($bet->betting_date) : null;
                $month = $bettingDate ? $bettingDate->format('F') : '';
                $date = $bettingDate ? $bettingDate->format('Y-m-d') : '';

                // Format ROI as percentage
                $roi = $bet->roi ? number_format($bet->roi, 2) . '%' : '0.00%';

                // Format monetary values
                $wager = '$' . number_format($bet->wager_amount ?? 0, 2);
                $profits = '$' . number_format($bet->profit_amount ?? 0, 2);
                $winningAmount = '$' . number_format($bet->winning_amount ?? 0, 2);

                fputcsv($handle, [
                    $bet->sports ?? '',                    // Sport
                    $bet->league ?? '',                    // League
                    $bet->month ?? $month,                 // Month
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
                    $winningAmount,                         // Winning Amount
                ]);
            }
            fclose($handle);
        };

        activity()
            ->causedBy(auth()->user())
            ->log('Exported bets with filters');

        return response()->stream($callback, 200, $headers);
    }
}
