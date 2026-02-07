<?php

namespace App\Http\Controllers;

use App\Services\DiscordService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    public function __construct(
        private DiscordService $discordService
    ) {}

    /**
     * Redirect to Discord OAuth
     */
    public function redirect()
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'guilds.join'])
            ->redirect();
    }

    /**
     * Handle Discord OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            if ($request->has('error')) {
                Log::warning('Discord OAuth error', [
                    'error' => $request->get('error'),
                    'description' => $request->get('error_description'),
                ]);

                return redirect()->route('dashboard')
                    ->with('error', 'Discord connection was cancelled or denied.');
            }

            $discordUser = Socialite::driver('discord')->user();
            $user = $request->user();

            // Check if this Discord account is already linked to another user
            $existingUser = \App\Models\User::where('discord_id', $discordUser->getId())
                ->where('id', '!=', $user->id)
                ->first();

            if ($existingUser) {
                return redirect()->route('dashboard')
                    ->with('error', 'This Discord account is already linked to another user.');
            }

            // Update user with Discord info
            $user->update([
                'discord_id' => $discordUser->getId(),
                'discord_username' => $discordUser->getNickname() ?? $discordUser->getName(),
                'discord_discriminator' => $discordUser->user['discriminator'] ?? null,
                'discord_avatar' => $discordUser->getAvatar(),
                'discord_access_token' => $discordUser->token,
                'discord_refresh_token' => $discordUser->refreshToken,
                'discord_connected_at' => now(),
                'discord_token_expires_at' => $discordUser->expiresIn
                    ? now()->addSeconds($discordUser->expiresIn)
                    : null,
            ]);

            // Sync Discord roles based on subscription
            if ($this->discordService->isConfigured()) {
                $this->discordService->syncRoles($user);
            }

            Log::info('Discord account connected', [
                'user_id' => $user->id,
                'discord_id' => $discordUser->getId(),
                'discord_username' => $discordUser->getNickname(),
            ]);

            return redirect()->route('dashboard')
                ->with('success', 'Discord account connected successfully! Join our server to get your roles.');

        } catch (\Exception $e) {
            Log::error('Discord OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Failed to connect Discord account. Please try again.');
        }
    }

    /**
     * Disconnect Discord account
     */
    public function disconnect(Request $request)
    {
        $user = $request->user();

        if (! $user->discord_id) {
            return back()->with('error', 'No Discord account is connected.');
        }

        // Remove roles from Discord server
        if ($this->discordService->isConfigured()) {
            $this->discordService->removeAllRoles($user);
        }

        // Clear Discord data from user
        $user->update([
            'discord_id' => null,
            'discord_discriminator' => null,
            'discord_avatar' => null,
            'discord_access_token' => null,
            'discord_refresh_token' => null,
            'discord_connected_at' => null,
            'discord_token_expires_at' => null,
            'discord_roles_synced' => null,
            // Keep discord_username as it might have been manually entered
        ]);

        Log::info('Discord account disconnected', [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Discord account disconnected.');
    }

    /**
     * Manually trigger role sync
     */
    public function syncRoles(Request $request)
    {
        $user = $request->user();

        if (! $user->discord_id) {
            return back()->with('error', 'Please connect your Discord account first.');
        }

        if (! $this->discordService->isConfigured()) {
            return back()->with('error', 'Discord integration is not fully configured.');
        }

        $success = $this->discordService->syncRoles($user);

        if ($success) {
            return back()->with('success', 'Discord roles synchronized successfully!');
        }

        // Check if user is in the server
        if (! $this->discordService->isMemberOfGuild($user->discord_id)) {
            return back()->with('error', 'Please join our Discord server first, then sync your roles.');
        }

        return back()->with('error', 'Failed to sync Discord roles. Please try again later.');
    }

    /**
     * Get Discord connection status (for API)
     */
    public function status(Request $request)
    {
        $user = $request->user();

        $isConnected = ! empty($user->discord_id);
        $isMember = false;
        $currentRoles = [];

        if ($isConnected && $this->discordService->isConfigured()) {
            $memberRoles = $this->discordService->getMemberRoles($user->discord_id);
            $isMember = $memberRoles !== null;
            $currentRoles = $memberRoles ?? [];
        }

        return response()->json([
            'connected' => $isConnected,
            'discord_username' => $user->discord_username,
            'discord_avatar' => $user->discord_avatar
                ? $this->discordService->getAvatarUrl($user->discord_id, $user->discord_avatar)
                : null,
            'is_member' => $isMember,
            'invite_url' => $this->discordService->getInviteUrl(),
            'roles_synced' => $user->discord_roles_synced ?? [],
            'subscription_tier' => $user->getCurrentTier(),
        ]);
    }
}
