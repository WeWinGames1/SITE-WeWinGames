<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function getAllAdmins(): Collection
    {
        return $this->userRepository->getAdmins();
    }

    public function getNonAdminUsers(): Collection
    {
        return $this->userRepository->getNonAdminUsers();
    }

    public function promoteToAdmin(int $userId): array
    {
        try {
            DB::beginTransaction();
            
            $user = $this->userRepository->findOrFail($userId);
            
            if ($user->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'User is already an admin',
                ];
            }
            
            $this->userRepository->assignRole($user, 'admin');
            
            Log::info('User promoted to admin', [
                'user_id' => $userId,
                'promoted_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'User successfully promoted to admin',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to promote user to admin', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to promote user. Please try again.',
            ];
        }
    }

    public function demoteFromAdmin(int $userId): array
    {
        try {
            DB::beginTransaction();
            
            $user = $this->userRepository->findOrFail($userId);
            
            if (!$user->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'User is not an admin',
                ];
            }
            
            // Prevent removing the last admin
            $adminCount = $this->userRepository->getAdmins()->count();
            if ($adminCount <= 1) {
                return [
                    'success' => false,
                    'message' => 'Cannot remove the last admin',
                ];
            }
            
            $this->userRepository->removeRole($user, 'admin');
            
            Log::info('Admin demoted to regular user', [
                'user_id' => $userId,
                'demoted_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Admin successfully demoted',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to demote admin', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to demote admin. Please try again.',
            ];
        }
    }

    public function getCustomersWithSubscriptions(): Collection
    {
        return $this->userRepository->getUsersWithActiveSubscriptions();
    }

    public function updateUserProfile(User $user, array $data): array
    {
        try {
            $updateData = [
                'name' => $data['name'] ?? $user->name,
                'email' => $data['email'] ?? $user->email,
            ];
            
            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }
            
            $this->userRepository->update($updateData, $user->id);
            
            Log::info('User profile updated', [
                'user_id' => $user->id,
                'updated_fields' => array_keys($updateData),
            ]);
            
            return [
                'success' => true,
                'message' => 'Profile updated successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to update user profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to update profile. Please try again.',
            ];
        }
    }

    public function deleteUser(int $userId): array
    {
        try {
            DB::beginTransaction();
            
            $user = $this->userRepository->findOrFail($userId);
            
            // Prevent deleting admins
            if ($user->hasRole('admin')) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete admin users',
                ];
            }
            
            // Cancel active subscriptions
            if ($user->subscriptions()->active()->exists()) {
                $user->subscription('default')->cancel();
            }
            
            $this->userRepository->delete($userId);
            
            Log::info('User deleted', [
                'user_id' => $userId,
                'deleted_by' => auth()->id(),
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'User deleted successfully',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete user', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to delete user. Please try again.',
            ];
        }
    }
}