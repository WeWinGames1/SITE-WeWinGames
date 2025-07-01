<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HasFilters
{
    /**
     * Apply date range filters to a query
     */
    protected function applyDateFilters(Builder $query, Request $request, string $dateField = 'created_at'): Builder
    {
        if ($request->filled('date_from')) {
            $query->whereDate($dateField, '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate($dateField, '<=', $request->date_to);
        }
        
        return $query;
    }
    
    /**
     * Apply search filter to multiple fields
     */
    protected function applySearchFilter(Builder $query, Request $request, array $searchFields): Builder
    {
        if ($searchTerm = $request->input('search')) {
            // Escape SQL wildcards to prevent injection
            $searchTerm = str_replace(['%', '_'], ['\%', '\_'], $searchTerm);
            
            $query->where(function ($q) use ($searchTerm, $searchFields) {
                foreach ($searchFields as $field) {
                    if (str_contains($field, '.')) {
                        // Handle relationship searches
                        [$relation, $relationField] = explode('.', $field, 2);
                        $q->orWhereHas($relation, function ($query) use ($relationField, $searchTerm) {
                            $query->where($relationField, 'like', "%{$searchTerm}%");
                        });
                    } else {
                        $q->orWhere($field, 'like', "%{$searchTerm}%");
                    }
                }
            });
        }
        
        return $query;
    }
    
    /**
     * Apply sorting to query
     */
    protected function applySorting(Builder $query, Request $request, array $allowedSorts, string $defaultSort = 'created_at', string $defaultDirection = 'desc'): Builder
    {
        $sortBy = $request->input('sort_by', $defaultSort);
        $sortDirection = $request->input('sort_direction', $defaultDirection);
        
        // Validate sort field
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = $defaultSort;
        }
        
        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = $defaultDirection;
        }
        
        return $query->orderBy($sortBy, $sortDirection);
    }
    
    /**
     * Apply status filter
     */
    protected function applyStatusFilter(Builder $query, Request $request, string $field = 'status'): Builder
    {
        if ($status = $request->input('status')) {
            $query->where($field, $status);
        }
        
        return $query;
    }
    
    /**
     * Get pagination per page with validation
     */
    protected function getPerPage(Request $request, int $default = 25, int $max = 100): int
    {
        $perPage = (int) $request->input('per_page', $default);
        
        return min(max($perPage, 10), $max);
    }
}