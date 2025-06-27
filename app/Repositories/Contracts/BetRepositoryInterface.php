<?php

namespace App\Repositories\Contracts;

use App\Models\Bet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BetRepositoryInterface extends BaseRepositoryInterface
{
    public function getRecentBets(int $limit = 10): Collection;
    
    public function getBetsByStatus(string $status): Collection;
    
    public function getBetsByUser(int $userId): Collection;
    
    public function getBetsBySport(int $sportId): Collection;
    
    public function getBetsWithRelations(array $relations = ['user', 'sport', 'game']): LengthAwarePaginator;
    
    public function getProfitableBets(): Collection;
    
    public function getBetsByDateRange(\DateTime $start, \DateTime $end): Collection;
    
    public function calculateTotalProfit(): float;
    
    public function calculateProfitByUser(int $userId): float;
    
    public function getBetStatistics(): array;
}