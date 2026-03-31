<?php

namespace App\Repositories\Eloquent;

use App\Models\Bet;
use App\Repositories\Contracts\BetRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BetRepository extends BaseRepository implements BetRepositoryInterface
{
    protected function getModelClass(): Model
    {
        return new Bet;
    }

    public function getRecentBets(int $limit = 10): Collection
    {
        $cacheKey = $this->getCacheKey('getRecentBets', $limit);

        return $this->remember($cacheKey, function () use ($limit) {
            return $this->model->with(['user', 'sport', 'game'])
                ->latest()
                ->limit($limit)
                ->get();
        }, 300); // Cache for 5 minutes
    }

    public function getBetsByStatus(string $status): Collection
    {
        $cacheKey = $this->getCacheKey('getBetsByStatus', $status);

        return $this->remember($cacheKey, function () use ($status) {
            return $this->model->where('status', $status)->get();
        }, 600); // Cache for 10 minutes
    }

    public function getBetsByUser(int $userId): Collection
    {
        $cacheKey = $this->getCacheKey('getBetsByUser', $userId);

        return $this->remember($cacheKey, function () use ($userId) {
            return $this->model->where('user_id', $userId)
                ->with(['sport', 'game'])
                ->orderBy('created_at', 'desc')
                ->get();
        }, 600);
    }

    public function getBetsBySport(int $sportId): Collection
    {
        $cacheKey = $this->getCacheKey('getBetsBySport', $sportId);

        return $this->remember($cacheKey, function () use ($sportId) {
            return $this->model->where('sport_id', $sportId)
                ->with(['user', 'game'])
                ->get();
        }, 600);
    }

    public function getBetsWithRelations(array $relations = ['user', 'sport', 'game']): LengthAwarePaginator
    {
        return $this->with($relations)
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function getProfitableBets(): Collection
    {
        $cacheKey = $this->getCacheKey('getProfitableBets');

        return $this->remember($cacheKey, function () {
            return $this->model->where('profit', '>', 0)
                ->with(['user', 'sport', 'game'])
                ->orderBy('profit', 'desc')
                ->get();
        }, 1800); // Cache for 30 minutes
    }

    public function getBetsByDateRange(\DateTime $start, \DateTime $end): Collection
    {
        $cacheKey = $this->getCacheKey('getBetsByDateRange', $start->format('Y-m-d'), $end->format('Y-m-d'));

        return $this->remember($cacheKey, function () use ($start, $end) {
            return $this->model->whereBetween('created_at', [$start, $end])
                ->with(['user', 'sport', 'game'])
                ->get();
        }, 3600);
    }

    public function calculateTotalProfit(): float
    {
        $cacheKey = $this->getCacheKey('calculateTotalProfit');

        return $this->remember($cacheKey, function () {
            return $this->model->sum('profit') ?? 0.0;
        }, 3600);
    }

    public function calculateProfitByUser(int $userId): float
    {
        $cacheKey = $this->getCacheKey('calculateProfitByUser', $userId);

        return $this->remember($cacheKey, function () use ($userId) {
            return $this->model->where('user_id', $userId)->sum('profit') ?? 0.0;
        }, 1800);
    }

    public function getBetStatistics(): array
    {
        $cacheKey = $this->getCacheKey('getBetStatistics');

        return $this->remember($cacheKey, function () {
            return [
                'total_bets' => $this->model->count(),
                'winning_bets' => $this->model->where('status', 'won')->count(),
                'losing_bets' => $this->model->where('status', 'loss')->count(),
                'pending_bets' => $this->model->where('status', 'pending')->count(),
                'total_profit' => $this->calculateTotalProfit(),
                'average_stake' => $this->model->avg('stake') ?? 0,
                'average_odds' => $this->model->avg('odds') ?? 0,
                'win_rate' => $this->calculateWinRate(),
            ];
        }, 3600);
    }

    private function calculateWinRate(): float
    {
        $totalBets = $this->model->whereIn('status', ['won', 'loss'])->count();
        if ($totalBets === 0) {
            return 0.0;
        }

        $winningBets = $this->model->where('status', 'won')->count();

        return round(($winningBets / $totalBets) * 100, 2);
    }
}
