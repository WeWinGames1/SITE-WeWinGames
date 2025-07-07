<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\BetRequest;
use App\Http\Resources\V1\BetResource;
use App\Repositories\Contracts\BetRepositoryInterface;
use App\Services\BetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Bet Management
 *
 * APIs for managing sports bets
 */
class BetApiController extends BaseApiController
{
    public function __construct(
        private BetRepositoryInterface $betRepository,
        private BetService $betService
    ) {}

    /**
     * Get all bets
     *
     * Retrieve a paginated list of bets. Regular users can only see their own bets, while admins can see all bets.
     *
     * @authenticated
     *
     * @queryParam per_page integer Number of items per page. Min: 1, Max: 100. Example: 20
     * @queryParam page integer Page number. Example: 1
     * @queryParam sort_by string Field to sort by. Available: created_at, placed_at, odds, stake, profit. Example: created_at
     * @queryParam sort_order string Sort direction. Available: asc, desc. Example: desc
     * @queryParam status string Filter by bet status. Available: pending, won, lost, void, cashout, push. Example: won
     * @queryParam sport_id integer Filter by sport ID. Example: 1
     * @queryParam date_from string Filter by start date (ISO 8601 format). Example: 2024-01-01
     * @queryParam date_to string Filter by end date (ISO 8601 format). Example: 2024-12-31
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": [
     *     {
     *       "id": 1,
     *       "user": {"id": 1, "name": "John Doe"},
     *       "sport": {"id": 1, "name": "NFL"},
     *       "game": {
     *         "id": 1,
     *         "teams": {"home": "Chiefs", "away": "Bills"},
     *         "date": "2024-01-15T18:00:00Z"
     *       },
     *       "operator": {"id": 1, "name": "DraftKings"},
     *       "bet_type": "Spread",
     *       "selection": "Chiefs -3.5",
     *       "odds": 1.91,
     *       "stake": 100,
     *       "potential_return": 191,
     *       "status": "pending",
     *       "profit": null,
     *       "winning_amount": null,
     *       "description": "AFC Championship game",
     *       "placed_at": "2024-01-14T15:30:00Z",
     *       "settled_at": null,
     *       "created_at": "2024-01-14T15:30:00Z",
     *       "updated_at": "2024-01-14T15:30:00Z"
     *     }
     *   ],
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z",
     *     "pagination": {
     *       "total": 50,
     *       "per_page": 20,
     *       "current_page": 1,
     *       "last_page": 3,
     *       "from": 1,
     *       "to": 20
     *     }
     *   },
     *   "links": {
     *     "first": "https://example.com/api/v1/bets?page=1",
     *     "last": "https://example.com/api/v1/bets?page=3",
     *     "prev": null,
     *     "next": "https://example.com/api/v1/bets?page=2"
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $this->getPerPage($request);
        $sortParams = $this->getSortParams($request);

        $query = $this->betRepository
            ->with(['user', 'sport', 'game'])
            ->orderBy($sortParams['sort_by'], $sortParams['sort_order']);

        // Apply filters
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($sportId = $request->input('sport_id')) {
            $query->where('sport_id', $sportId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo);
        }

        // Only show user's own bets unless admin
        if (! $request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }

        $bets = $query->paginate($perPage);

        return $this->paginatedResponse(
            $bets->through(fn ($bet) => new BetResource($bet))
        );
    }

    /**
     * Create a bet
     *
     * Place a new bet. The potential return is automatically calculated based on stake and odds.
     *
     * @authenticated
     *
     * @bodyParam sport_id integer required The ID of the sport. Example: 1
     * @bodyParam game_id integer required The ID of the game. Example: 1
     * @bodyParam operator_id integer required The ID of the betting operator. Example: 1
     * @bodyParam bet_type string required Type of bet (e.g., Spread, Moneyline, Over/Under). Max: 50 characters. Example: Spread
     * @bodyParam selection string required The bet selection. Max: 255 characters. Example: Chiefs -3.5
     * @bodyParam odds numeric required Decimal odds. Min: 1.01, Max: 1000. Example: 1.91
     * @bodyParam stake numeric required Amount wagered. Min: 0.01, Max: 100000. Example: 100
     * @bodyParam description string optional Additional notes about the bet. Max: 500 characters. Example: AFC Championship game
     *
     * @response 201 scenario="Created" {
     *   "success": true,
     *   "message": "Bet created successfully",
     *   "data": {
     *     "id": 1,
     *     "user": {"id": 1, "name": "John Doe"},
     *     "sport": {"id": 1, "name": "NFL"},
     *     "game": {
     *       "id": 1,
     *       "teams": {"home": "Chiefs", "away": "Bills"},
     *       "date": "2024-01-15T18:00:00Z"
     *     },
     *     "operator": {"id": 1, "name": "DraftKings"},
     *     "bet_type": "Spread",
     *     "selection": "Chiefs -3.5",
     *     "odds": 1.91,
     *     "stake": 100,
     *     "potential_return": 191,
     *     "status": "pending",
     *     "profit": null,
     *     "winning_amount": null,
     *     "description": "AFC Championship game",
     *     "placed_at": "2024-01-14T15:30:00Z",
     *     "settled_at": null,
     *     "created_at": "2024-01-14T15:30:00Z",
     *     "updated_at": "2024-01-14T15:30:00Z"
     *   },
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T15:30:00Z"
     *   }
     * }
     * @response 422 scenario="Validation Error" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "sport_id": ["The selected sport does not exist."],
     *     "odds": ["Odds must be at least 1.01."]
     *   },
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T15:30:00Z"
     *   }
     * }
     */
    public function store(BetRequest $request): JsonResponse
    {
        $result = $this->betService->createBet(
            $request->user(),
            $request->validated()
        );

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse(
            new BetResource($result['bet']),
            'Bet created successfully',
            201
        );
    }

    /**
     * Get a bet
     *
     * Retrieve details of a specific bet. Users can only view their own bets unless they are admins.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the bet. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": {
     *     "id": 1,
     *     "user": {"id": 1, "name": "John Doe"},
     *     "sport": {"id": 1, "name": "NFL"},
     *     "game": {
     *       "id": 1,
     *       "teams": {"home": "Chiefs", "away": "Bills"},
     *       "date": "2024-01-15T18:00:00Z"
     *     },
     *     "operator": {"id": 1, "name": "DraftKings"},
     *     "bet_type": "Spread",
     *     "selection": "Chiefs -3.5",
     *     "odds": 1.91,
     *     "stake": 100,
     *     "potential_return": 191,
     *     "status": "won",
     *     "profit": 91,
     *     "winning_amount": 191,
     *     "description": "AFC Championship game",
     *     "placed_at": "2024-01-14T15:30:00Z",
     *     "settled_at": "2024-01-15T21:00:00Z",
     *     "created_at": "2024-01-14T15:30:00Z",
     *     "updated_at": "2024-01-15T21:00:00Z"
     *   },
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Bet not found",
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     * @response 403 scenario="Forbidden" {
     *   "success": false,
     *   "message": "Forbidden",
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $bet = $this->betRepository->find($id);

        if (! $bet) {
            return $this->notFoundResponse('Bet not found');
        }

        // Check ownership unless admin
        if (! $request->user()->hasRole('admin') && $bet->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        return $this->successResponse(new BetResource($bet));
    }

    /**
     * Update a bet
     *
     * Update an existing bet. Only pending bets can be updated. Settled bets (won, lost, void) cannot be modified.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the bet. Example: 1
     *
     * @bodyParam sport_id integer The ID of the sport. Example: 1
     * @bodyParam game_id integer The ID of the game. Example: 1
     * @bodyParam operator_id integer The ID of the betting operator. Example: 1
     * @bodyParam bet_type string Type of bet. Max: 50 characters. Example: Spread
     * @bodyParam selection string The bet selection. Max: 255 characters. Example: Chiefs -3.5
     * @bodyParam odds numeric Decimal odds. Min: 1.01, Max: 1000. Example: 1.91
     * @bodyParam stake numeric Amount wagered. Min: 0.01, Max: 100000. Example: 100
     * @bodyParam description string Additional notes about the bet. Max: 500 characters. Example: Updated description
     * @bodyParam status string Bet status. Available: pending, won, lost, void, cashout, push. Example: pending
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Bet updated successfully",
     *   "data": {
     *     "id": 1,
     *     "user": {"id": 1, "name": "John Doe"},
     *     "sport": {"id": 1, "name": "NFL"},
     *     "game": {
     *       "id": 1,
     *       "teams": {"home": "Chiefs", "away": "Bills"},
     *       "date": "2024-01-15T18:00:00Z"
     *     },
     *     "operator": {"id": 1, "name": "DraftKings"},
     *     "bet_type": "Spread",
     *     "selection": "Chiefs -3.5",
     *     "odds": 1.95,
     *     "stake": 150,
     *     "potential_return": 292.5,
     *     "status": "pending",
     *     "profit": null,
     *     "winning_amount": null,
     *     "description": "Updated description",
     *     "placed_at": "2024-01-14T15:30:00Z",
     *     "settled_at": null,
     *     "created_at": "2024-01-14T15:30:00Z",
     *     "updated_at": "2024-01-14T16:00:00Z"
     *   },
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     * @response 400 scenario="Cannot Update Settled Bet" {
     *   "success": false,
     *   "message": "Cannot update a settled bet",
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     */
    public function update(BetRequest $request, int $id): JsonResponse
    {
        $bet = $this->betRepository->find($id);

        if (! $bet) {
            return $this->notFoundResponse('Bet not found');
        }

        // Check ownership unless admin
        if (! $request->user()->hasRole('admin') && $bet->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $result = $this->betService->updateBet($bet, $request->validated());

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse(
            new BetResource($result['bet']),
            'Bet updated successfully'
        );
    }

    /**
     * Delete a bet
     *
     * Delete a bet. Only pending bets can be deleted. Settled bets (won, lost) cannot be deleted for audit purposes.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the bet. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Bet deleted successfully",
     *   "data": null,
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     * @response 400 scenario="Cannot Delete Settled Bet" {
     *   "success": false,
     *   "message": "Cannot delete a settled bet",
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     * @response 404 scenario="Not Found" {
     *   "success": false,
     *   "message": "Bet not found",
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $bet = $this->betRepository->find($id);

        if (! $bet) {
            return $this->notFoundResponse('Bet not found');
        }

        // Check ownership unless admin
        if (! $request->user()->hasRole('admin') && $bet->user_id !== $request->user()->id) {
            return $this->forbiddenResponse();
        }

        $result = $this->betService->deleteBet($bet);

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse(null, 'Bet deleted successfully');
    }

    /**
     * Get betting statistics
     *
     * Retrieve comprehensive betting statistics. Regular users see their own stats, admins see global stats.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Success",
     *   "data": {
     *     "total_bets": 150,
     *     "winning_bets": 75,
     *     "losing_bets": 60,
     *     "pending_bets": 15,
     *     "total_profit": 2500.50,
     *     "total_stake": 15000,
     *     "average_stake": 100,
     *     "average_odds": 1.95,
     *     "win_rate": 55.56,
     *     "roi": 16.67
     *   },
     *   "meta": {
     *     "version": "v1",
     *     "timestamp": "2024-01-14T16:00:00Z"
     *   }
     * }
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()->hasRole('admin') ? null : $request->user()->id;
        $stats = $this->betService->getStatistics($userId);

        return $this->successResponse($stats);
    }
}
