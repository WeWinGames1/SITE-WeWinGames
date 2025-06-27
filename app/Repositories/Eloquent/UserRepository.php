<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected function getModelClass(): Model
    {
        return new User();
    }

    public function findByEmail(string $email): ?User
    {
        $cacheKey = $this->getCacheKey('findByEmail', $email);
        
        return $this->remember($cacheKey, function () use ($email) {
            return $this->model->where('email', $email)->first();
        });
    }

    public function getAdmins(): Collection
    {
        $cacheKey = $this->getCacheKey('getAdmins');
        
        return $this->remember($cacheKey, function () {
            return $this->model->role('admin')->get();
        });
    }

    public function getNonAdminUsers(): Collection
    {
        $cacheKey = $this->getCacheKey('getNonAdminUsers');
        
        return $this->remember($cacheKey, function () {
            return $this->model->whereDoesntHave('roles', function($q) {
                $q->where('name', 'admin');
            })->get();
        });
    }

    public function getUsersWithActiveSubscriptions(): Collection
    {
        $cacheKey = $this->getCacheKey('getUsersWithActiveSubscriptions');
        
        return $this->remember($cacheKey, function () {
            return $this->model->with('subscriptions')
                ->whereHas('subscriptions', function($q) {
                    $q->active();
                })
                ->get();
        }, 1800); // Cache for 30 minutes
    }

    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
        $this->clearCache();
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
        $this->clearCache();
    }

    public function getUsersByRole(string $role): Collection
    {
        $cacheKey = $this->getCacheKey('getUsersByRole', $role);
        
        return $this->remember($cacheKey, function () use ($role) {
            return $this->model->role($role)->get();
        });
    }
}