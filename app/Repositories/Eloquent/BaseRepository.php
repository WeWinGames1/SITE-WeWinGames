<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected Builder $query;
    protected int $cacheTime = 3600; // 1 hour default
    protected bool $cacheEnabled = true;

    public function __construct()
    {
        $this->model = $this->getModelClass();
        $this->resetQuery();
    }

    abstract protected function getModelClass(): Model;

    protected function resetQuery(): void
    {
        $this->query = $this->model->newQuery();
    }

    protected function getCacheKey(string $method, ...$params): string
    {
        return sprintf(
            '%s:%s:%s',
            $this->model->getTable(),
            $method,
            md5(serialize($params))
        );
    }

    protected function remember(string $key, \Closure $callback, ?int $seconds = null)
    {
        if (!$this->cacheEnabled) {
            return $callback();
        }

        return Cache::remember($key, $seconds ?? $this->cacheTime, $callback);
    }

    public function all(array $columns = ['*']): Collection
    {
        $result = $this->query->get($columns);
        $this->resetQuery();
        return $result;
    }

    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        $result = $this->query->paginate($perPage, $columns);
        $this->resetQuery();
        return $result;
    }

    public function create(array $data): Model
    {
        $model = $this->model->create($data);
        $this->clearCache();
        return $model;
    }

    public function update(array $data, int $id): bool
    {
        $result = $this->model->where('id', $id)->update($data);
        $this->clearCache();
        return $result;
    }

    public function delete(int $id): bool
    {
        $result = $this->model->destroy($id);
        $this->clearCache();
        return $result;
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        $cacheKey = $this->getCacheKey('find', $id, $columns);
        
        return $this->remember($cacheKey, function () use ($id, $columns) {
            return $this->model->find($id, $columns);
        });
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    public function findBy(string $field, $value, array $columns = ['*']): ?Model
    {
        $cacheKey = $this->getCacheKey('findBy', $field, $value, $columns);
        
        return $this->remember($cacheKey, function () use ($field, $value, $columns) {
            return $this->model->where($field, $value)->first($columns);
        });
    }

    public function with(array $relations): self
    {
        $this->query->with($relations);
        return $this;
    }

    public function withCount(array $relations): self
    {
        $this->query->withCount($relations);
        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    public function where(string $column, $operator = null, $value = null): self
    {
        $this->query->where($column, $operator, $value);
        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->query->whereIn($column, $values);
        return $this;
    }

    public function whereHas(string $relation, \Closure $callback = null): self
    {
        $this->query->whereHas($relation, $callback);
        return $this;
    }

    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->model->firstOrCreate($attributes, $values);
        $this->clearCache();
        return $model;
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->model->updateOrCreate($attributes, $values);
        $this->clearCache();
        return $model;
    }

    protected function clearCache(): void
    {
        Cache::tags([$this->model->getTable()])->flush();
    }

    public function disableCache(): self
    {
        $this->cacheEnabled = false;
        return $this;
    }

    public function enableCache(): self
    {
        $this->cacheEnabled = true;
        return $this;
    }
}