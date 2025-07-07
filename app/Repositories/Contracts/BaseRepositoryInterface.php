<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    public function all(array $columns = ['*']): Collection;

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator;

    public function create(array $data): Model;

    public function update(array $data, int $id): bool;

    public function delete(int $id): bool;

    public function find(int $id, array $columns = ['*']): ?Model;

    public function findOrFail(int $id, array $columns = ['*']): Model;

    public function findBy(string $field, $value, array $columns = ['*']): ?Model;

    public function with(array $relations): self;

    public function withCount(array $relations): self;

    public function orderBy(string $column, string $direction = 'asc'): self;

    public function where(string $column, $operator = null, $value = null): self;

    public function whereIn(string $column, array $values): self;

    public function whereHas(string $relation, ?\Closure $callback = null): self;

    public function firstOrCreate(array $attributes, array $values = []): Model;

    public function updateOrCreate(array $attributes, array $values = []): Model;
}
