<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    
    public function getAdmins(): Collection;
    
    public function getNonAdminUsers(): Collection;
    
    public function getUsersWithActiveSubscriptions(): Collection;
    
    public function assignRole(User $user, string $role): void;
    
    public function removeRole(User $user, string $role): void;
    
    public function getUsersByRole(string $role): Collection;
}