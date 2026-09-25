<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordService
{
    private ?string $botToken;

    private ?string $guildId;

    private array $roles;

    /** @var array<int, string> */
    private array $exemptRoles;

    private string $apiBase = 'https://discord.com/api/v10';

    public function __construct()
    {
        $this->botToken = config('services.discord.bot_token');
        $this->guildId = config('services.discord.guild_id');
        $this->roles = config('services.discord.roles') ?? [];
        $this->exemptRoles = config('services.discord.exempt_roles') ?? [];
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
     * Higher tiers include all lower tier roles (hierarchical)
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

        // Tier hierarchy: gold < platinum
        // Each tier includes all lower tier roles
        $tier = strtolower($tier ?? '');

        if ($tier === 'gold' || $tier === 'platinum') {
            if (! empty($this->roles['gold'])) {
                $roles[] = $this->roles['gold'];
            }
        }

        if ($tier === 'platinum') {
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

        $tier = $user->hasActiveSubscription() ? $user->getCurrentTier() : null;
        $targetRoles = $this->targetRolesForUser($user);
        $allManagedRoles = $this->managedRoleIds();

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
     * Roles the user should hold right now, based on the site database
     *
     * @return array<int, string>
     */
    public function targetRolesForUser(User $user): array
    {
        $hasActiveSubscription = $user->hasActiveSubscription();

        return array_values($this->getRolesForTier(
            $hasActiveSubscription ? $user->getCurrentTier() : null,
            $hasActiveSubscription
        ));
    }

    /**
     * Role IDs this integration owns (and may add or remove)
     *
     * @return array<int, string>
     */
    public function managedRoleIds(): array
    {
        return array_values(array_filter([
            $this->roles['free'] ?? null,
            $this->roles['gold'] ?? null,
            $this->roles['platinum'] ?? null,
        ]));
    }

    /**
     * Human-readable tier name for a managed role ID
     */
    public function roleLabel(string $roleId): string
    {
        return array_search($roleId, $this->roles, true) ?: $roleId;
    }

    /**
     * List every member of the guild (paginated, 1000 per call)
     *
     * Requires the "Server Members Intent" to be enabled for the bot
     * in the Discord developer portal. Returns null when the list cannot be read.
     *
     * @return Collection<int, array{id: string, username: ?string, global_name: ?string, bot: bool, roles: array<int, string>}>|null
     */
    public function listGuildMembers(): ?Collection
    {
        $members = collect();
        $after = '0';

        do {
            $response = $this->bot()->get("{$this->apiBase}/guilds/{$this->guildId}/members", [
                'limit' => 1000,
                'after' => $after,
            ]);

            if (! $response->successful()) {
                Log::error('Failed to list Discord guild members', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            $page = $response->json() ?? [];

            foreach ($page as $member) {
                $members->push([
                    'id' => (string) $member['user']['id'],
                    'username' => $member['user']['username'] ?? null,
                    'global_name' => $member['user']['global_name'] ?? null,
                    'bot' => (bool) ($member['user']['bot'] ?? false),
                    'roles' => $member['roles'] ?? [],
                ]);
                $after = (string) $member['user']['id'];
            }
        } while (count($page) === 1000);

        return $members;
    }

    /**
     * Compare every Discord member holding a managed role against the site database
     *
     * "linked" rows are members whose Discord account is connected to a site user and
     * whose roles differ from what the database says. "unlinked" rows are members who
     * hold a managed role but are not connected to any site account; for those the
     * roles are only compared against a site user whose stored Discord username matches.
     *
     * @return array{
     *     members_scanned: int,
     *     linked: array<int, array{user_id: int, name: string, email: string, discord_id: string, discord_username: ?string, tier: ?string, add: array<int, string>, remove: array<int, string>}>,
     *     unlinked: array<int, array{discord_id: string, discord_username: ?string, display_name: ?string, roles: array<int, string>, matched_user_id: ?int, matched_user_email: ?string, remove: array<int, string>}>,
     *     linked_not_in_guild: int
     * }|null
     */
    public function audit(): ?array
    {
        $members = $this->listGuildMembers();

        if ($members === null) {
            return null;
        }

        $managed = $this->managedRoleIds();
        $membersById = $members->keyBy('id');

        $linkedUsers = User::query()->with('subscriptions')->whereNotNull('discord_id')->get()->keyBy('discord_id');

        $linked = [];
        $linkedNotInGuild = 0;

        foreach ($linkedUsers as $discordId => $user) {
            $member = $membersById->get((string) $discordId);

            if (! $member) {
                $linkedNotInGuild++;

                continue;
            }

            $current = array_values(array_intersect($member['roles'], $managed));
            $target = $this->targetRolesForUser($user);
            $add = array_values(array_diff($target, $current));
            $remove = array_values(array_diff($current, $target));

            if ($add === [] && $remove === []) {
                continue;
            }

            $linked[] = [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'discord_id' => (string) $discordId,
                'discord_username' => $member['username'],
                'tier' => $user->hasActiveSubscription() ? $user->getCurrentTier() : null,
                'add' => $add,
                'remove' => $remove,
            ];
        }

        $unlinkedMembers = $members->filter(fn (array $member): bool => ! $member['bot']
            && ! $linkedUsers->has($member['id'])
            && array_intersect($member['roles'], $managed) !== []
            && array_intersect($member['roles'], $this->exemptRoles) === []);

        $usernames = $unlinkedMembers->pluck('username')->filter()->map(fn (string $name): string => strtolower($name));
        $usersByUsername = User::query()
            ->with('subscriptions')
            ->whereNull('discord_id')
            ->whereIn(DB::raw('LOWER(discord_username)'), $usernames->all())
            ->get()
            ->keyBy(fn (User $user): string => strtolower($user->discord_username));

        $unlinked = [];

        foreach ($unlinkedMembers as $member) {
            $matchedUser = $member['username'] ? $usersByUsername->get(strtolower($member['username'])) : null;
            $current = array_values(array_intersect($member['roles'], $managed));
            $remove = array_values(array_diff($current, $matchedUser ? $this->targetRolesForUser($matchedUser) : []));

            if ($remove === []) {
                continue;
            }

            $unlinked[] = [
                'discord_id' => $member['id'],
                'discord_username' => $member['username'],
                'display_name' => $member['global_name'],
                'roles' => $current,
                'matched_user_id' => $matchedUser?->id,
                'matched_user_email' => $matchedUser?->email,
                'remove' => $remove,
            ];
        }

        return [
            'members_scanned' => $members->count(),
            'linked' => $linked,
            'unlinked' => $unlinked,
            'linked_not_in_guild' => $linkedNotInGuild,
        ];
    }

    /**
     * Apply role changes produced by audit()
     *
     * @param  array<int, array{discord_id: string, add?: array<int, string>, remove: array<int, string>}>  $rows
     * @return array{fixed: int, failed: int}
     */
    public function applyAuditRows(array $rows): array
    {
        $fixed = 0;
        $failed = 0;

        foreach ($rows as $row) {
            $ok = true;

            foreach ($row['add'] ?? [] as $roleId) {
                $ok = $this->addRole($row['discord_id'], $roleId) && $ok;
            }

            foreach ($row['remove'] as $roleId) {
                $ok = $this->removeRole($row['discord_id'], $roleId) && $ok;
            }

            $ok ? $fixed++ : $failed++;

            Log::info('Discord audit applied', [
                'discord_id' => $row['discord_id'],
                'user_id' => $row['user_id'] ?? null,
                'roles_added' => $row['add'] ?? [],
                'roles_removed' => $row['remove'],
                'success' => $ok,
            ]);
        }

        return ['fixed' => $fixed, 'failed' => $failed];
    }

    /**
     * Bot-authenticated request that waits out Discord rate limits
     */
    private function bot(): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => "Bot {$this->botToken}",
            'X-Audit-Log-Reason' => 'WeWinGames subscription sync',
        ])->retry(
            3,
            fn (int $attempt, \Throwable $exception): int => $exception instanceof RequestException
                ? (int) ceil(((float) ($exception->response->json('retry_after') ?? 1)) * 1000)
                : 1000,
            fn (\Throwable $exception): bool => $exception instanceof RequestException
                && $exception->response->status() === 429,
            throw: false
        );
    }

    /**
     * Get a guild member's current roles
     */
    public function getMemberRoles(string $discordId): ?array
    {
        $response = $this->bot()->get("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}");

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
        $response = $this->bot()->put("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");

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
        $response = $this->bot()->delete("{$this->apiBase}/guilds/{$this->guildId}/members/{$discordId}/roles/{$roleId}");

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

        $success = true;
        foreach ($this->managedRoleIds() as $roleId) {
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
