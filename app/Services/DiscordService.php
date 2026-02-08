<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private ?string $botToken;

    private ?string $guildId;

    private array $roles;

    private string $apiBase = 'https://discord.com/api/v10';

    public function __construct()
    {
        $this->botToken = config('services.discord.bot_token');
        $this->guildId = config('services.discord.guild_id');
        $this->roles = config('services.discord.roles') ?? [];
    }

    /**
     * Check if Discord integration is properly configured
     */
    public function isConfigured(): bool
    {
        return ! empty($this->botToken)
            && ! empty($this->guildId)
            && ! empty($this->roles['free']);
    }

    /**
     * Get the Discord invite URL
     */
    public function getInviteUrl(): ?string
    {
        return config('services.discord.invite_url');
    }

    /**
     * Get roles that should be assigned based on subscription tier
     * Returns empty array if user has no active subscription (all roles will be removed)
     */
    public function getRolesForTier(?string $tier, bool $hasActiveSubscription = true): array
    {
        // No active subscription = no roles at all
        if (! $hasActiveSubscription) {
            return [];
        }

        $roles = [];

        // Active subscribers get the free role as a base
        if (! empty($this->roles['free'])) {
            $roles[] = $this->roles['free'];
        }

        // Add tier-specific roles
        if ($tier === 'gold' && ! empty($this->roles['gold'])) {
            $roles[] = $this->roles['gold'];
        }

        if ($tier === 'platinum') {
            if (! empty($this->roles['gold'])) {
                $roles[] = $this->roles['gold'];
            }
            if (! empty($this->roles['platinum'])) {
                $roles[] = $this->roles['platinum'];
            }
        }

        return array_unique($roles);
    }

    /**
     * Sync Discord roles for a user based on their subscription status and tier
     * Users without active subscriptions will have ALL roles removed
     */
    public function syncRoles(User $user): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('Discord integration not configured');

            return false;
        }

        if (! $user->discord_id) {
            Log::debug('User has no Discord ID', ['user_id' => $user->id]);

            return false;
        }

        // Check if user has an active subscription
        $hasActiveSubscription = $user->hasActiveSubscription();
        $tier = $hasActiveSubscription ? $user->getCurrentTier() : null;
        $targetRoles = $this->getRolesForTier($tier, $hasActiveSubscription);

        Log::info('Calculating Discord roles for user', [
            'user_id' => $user->id,
            'has_active_subscription' => $hasActiveSubscription,
            'tier' => $tier,
            'target_roles' => $targetRoles,
        ]);
        $allManagedRoles = array_filter([
            $this->roles['free'],
            $this->roles['gold'],
            $this->roles['platinum'],
        ]);

        // Get current member roles
        $currentRoles = $this->getMemberRoles($user->discord_id);
        if ($currentRoles === null) {
            // User might not be in the server
            Log::info('Could not get member roles - user may not be in server', [
                'user_id' => $user->id,
                'discord_id' => $user->discord_id,
            ]);

            return false;
        }

        // Calculate roles to add and remove
        $rolesToAdd = array_diff($targetRoles, $currentRoles);
        $rolesToRemove = array_intersect(
            array_diff($allManagedRoles, $targetRoles),
            $currentRoles
        );

        $success = true;

        // Add new roles
        foreach ($rolesToAdd as $roleId) {
            if (! $this->addRole($user->discord_id, $roleId)) {
                $success = false;
            }
        }

        // Remove old roles
        foreach ($rolesToRemove as $roleId) {
            if (! $this->removeRole($user->discord_id, $roleId)) {
                $success = false;
            }
        }

        // Update user's synced roles
        if ($success) {
            $user->update([
                'discord_roles_synced' => $targetRoles,
            ]);
        }

        Log::info('Discord roles synced', [
            'user_id' => $user->id,
            'discord_id' => $user->discord_id,
            'tier' => $tier,
            'roles_added' => $rolesToAdd,
            'roles_removed' => $rolesToRemove,
            'success' => $success,
        ]);

        return $success;
    }

    /**
     * Get a guild member's current roles
     */
    public function getMemberRoles(string $discordId): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->botToken}",
        ])->get("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}");

        if ($response->successful()) {
            return $response->json('roles', []);
        }

        if ($response->status() === 404) {
            // Member not found in guild
            return null;
        }

        Log::error('Failed to get Discord member roles', [
            'discord_id' => $discordId,
            'status' => $response->status(),
            'response' => $response->json(),
        ]);

        return null;
    }

    /**
     * Add a role to a guild member
     */
    public function addRole(string $discordId, string $roleId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->botToken}",
            'X-Audit-Log-Reason' => 'WeWinGames subscription sync',
        ])->put("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");

        if (! $response->successful()) {
            Log::error('Failed to add Discord role', [
                'discord_id' => $discordId,
                'role_id' => $roleId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Remove a role from a guild member
     */
    public function removeRole(string $discordId, string $roleId): bool
    {
        $response = Http::withHeaders([
            'Authorization' => "Bot {$this->botToken}",
            'X-Audit-Log-Reason' => 'WeWinGames subscription sync',
        ])->delete("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");

        if (! $response->successful()) {
            Log::error('Failed to remove Discord role', [
                'discord_id' => $discordId,
                'role_id' => $roleId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Remove all managed roles from a user (for disconnect)
     */
    public function removeAllRoles(User $user): bool
    {
        if (! $user->discord_id) {
            return true;
        }

        $allManagedRoles = array_filter([
            $this->roles['free'],
            $this->roles['gold'],
            $this->roles['platinum'],
        ]);

        $success = true;
        foreach ($allManagedRoles as $roleId) {
            if (! $this->removeRole($user->discord_id, $roleId)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Check if a user is a member of the Discord server
     */
    public function isMemberOfGuild(string $discordId): bool
    {
        return $this->getMemberRoles($discordId) !== null;
    }

    /**
     * Get user info from Discord API using their access token
     */
    public function getUserInfo(string $accessToken): ?array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$accessToken}",
        ])->get("{$this->apiBase}/users/@me");

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * Get the avatar URL for a Discord user
     */
    public function getAvatarUrl(string $discordId, ?string $avatarHash): ?string
    {
        if (! $avatarHash) {
            return null;
        }

        $extension = str_starts_with($avatarHash, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/avatars/{$discordId}/{$avatarHash}.{$extension}";
    }
}
