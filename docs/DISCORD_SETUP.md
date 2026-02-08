# Discord Integration Setup Guide

This guide walks you through setting up Discord OAuth and bot integration for WeWinGames subscription-based role management.

## Overview

The Discord integration allows:
- Users to connect their Discord accounts via OAuth
- Automatic role assignment based on subscription tier
- Role syncing when subscriptions change
- Manual role sync from the customer dashboard

### Role Mapping

**Important:** Users must have an **active subscription** to receive any Discord roles. If a subscription is cancelled, paused, or payment fails, ALL roles are removed.

| Subscription Status | Discord Roles Assigned |
|---------------------|------------------------|
| No subscription / Cancelled / Paused / Payment Failed | **None** (all roles removed) |
| Active - Free tier | @Free |
| Active - Gold | @Free, @Gold |
| Active - Platinum | @Free, @Gold, @Platinum |

---

## Step 1: Create a Discord Application

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications)
2. Click **"New Application"**
3. Name it **"WeWinGames"** (or your preferred name)
4. Click **"Create"**

### Get OAuth2 Credentials

1. In your application, go to **"OAuth2"** → **"General"**
2. Copy the **Client ID**
3. Click **"Reset Secret"** and copy the **Client Secret**
4. Under **Redirects**, add:
   ```
   https://wewingames.com/auth/discord/callback
   ```
   (For local development: `http://wewingames.test/auth/discord/callback`)

---

## Step 2: Create a Bot

1. In your application, go to the **"Bot"** tab
2. Click **"Add Bot"** → **"Yes, do it!"**
3. Under the bot's username, click **"Reset Token"** and copy the **Bot Token**

### Configure Bot Settings

1. Scroll down to **"Privileged Gateway Intents"**
2. Enable **"Server Members Intent"** (required to manage member roles)
3. Click **"Save Changes"**

---

## Step 3: Invite the Bot to Your Server

1. Go to **"OAuth2"** → **"URL Generator"**
2. Under **Scopes**, select:
   - `bot`
   - `applications.commands` (optional, for future slash commands)
3. Under **Bot Permissions**, select:
   - `Manage Roles`
4. Copy the generated URL at the bottom
5. Open the URL in your browser and select your Discord server
6. Click **"Authorize"**

**Important:** The bot's role must be positioned ABOVE the roles it will manage in your server's role hierarchy.

---

## Step 4: Get Server and Role IDs

### Enable Developer Mode

1. Open Discord (desktop app recommended)
2. Go to **User Settings** (gear icon)
3. Navigate to **"App Settings"** → **"Advanced"**
4. Enable **"Developer Mode"**

### Get Server (Guild) ID

1. Right-click on your server name in the sidebar
2. Click **"Copy Server ID"**

### Get Role IDs

1. Go to **Server Settings** → **"Roles"**
2. Right-click on each role you want to use:
   - Right-click **@Free** → **"Copy Role ID"**
   - Right-click **@Gold** → **"Copy Role ID"**
   - Right-click **@Platinum** → **"Copy Role ID"**

### Create an Invite Link

1. Go to **Server Settings** → **"Invites"**
2. Create a new invite or copy an existing one
3. Recommended: Create a permanent invite link that never expires

---

## Step 5: Configure Environment Variables

Add the following to your `.env` file:

```env
# Discord OAuth (from Discord Developer Portal → OAuth2)
DISCORD_CLIENT_ID=your_client_id_here
DISCORD_CLIENT_SECRET=your_client_secret_here
DISCORD_PUBLIC_KEY=your_public_key_here
DISCORD_REDIRECT_URI=https://wewingames.com/auth/discord/callback

# Discord Bot
DISCORD_BOT_TOKEN=your_bot_token_here
DISCORD_GUILD_ID=your_server_id_here

# Discord Role IDs
DISCORD_ROLE_FREE=your_free_role_id
DISCORD_ROLE_GOLD=your_gold_role_id
DISCORD_ROLE_PLATINUM=your_platinum_role_id

# Discord Invite Link
DISCORD_INVITE_URL=https://discord.gg/your-invite-code
```

**Where to find these values:**
- **CLIENT_ID** (Application ID): Discord Developer Portal → Your App → General Information
- **CLIENT_SECRET**: Discord Developer Portal → Your App → OAuth2 → Client Secret
- **PUBLIC_KEY**: Discord Developer Portal → Your App → General Information → Public Key

---

## Step 5b: Configure Discord Interactions Endpoint (Optional)

If you want to use slash commands, buttons, or other Discord interactions:

1. Go to Discord Developer Portal → Your App → **General Information**
2. Set **Interactions Endpoint URL** to:
   ```
   https://wewingames.com/api/discord/interactions
   ```
3. Discord will verify the endpoint by sending a PING request
4. If verification succeeds, save your changes

**Note:** The `DISCORD_PUBLIC_KEY` must be configured in your `.env` for signature verification to work.

---

## Step 6: Run Migration (If Not Already Done)

```bash
php artisan migrate
```

This adds the following fields to the `users` table:
- `discord_id` - Discord user ID
- `discord_discriminator` - The #1234 part (legacy)
- `discord_avatar` - Avatar hash
- `discord_access_token` - OAuth access token
- `discord_refresh_token` - OAuth refresh token
- `discord_connected_at` - Connection timestamp
- `discord_token_expires_at` - Token expiration
- `discord_roles_synced` - Currently synced roles (JSON)

---

## How It Works

### User Connection Flow

1. User clicks "Connect Discord" on their dashboard
2. Redirected to Discord OAuth consent screen
3. User authorizes the application
4. Callback saves Discord user info to database
5. Roles are automatically synced based on subscription tier

### Automatic Role Syncing

Roles are automatically synced when:
- User connects their Discord account
- Subscription is created (Stripe webhook)
- Subscription is updated/changed (Stripe webhook)
- Subscription is cancelled (Stripe webhook)
- Subscription is paused (Stripe webhook)
- Subscription is resumed (Stripe webhook)
- Payment fails (Stripe webhook)
- Payment succeeds (Stripe webhook)
- User manually clicks "Sync Roles" button

### Subscription Status & Role Removal

Discord access requires an **active subscription**. Roles are **automatically removed** when:

| Event | Action Taken |
|-------|--------------|
| Subscription cancelled | All roles removed immediately |
| Subscription paused (admin action) | All roles removed |
| Payment fails | All roles removed |
| Subscription expires (end of billing period) | All roles removed |
| Subscription downgrade (Platinum → Gold) | Higher tier roles removed, appropriate roles remain |
| Subscription upgrade (Gold → Platinum) | Additional roles added |

The system handles these scenarios automatically via Stripe webhooks. Users will see a warning on their dashboard if they have Discord connected but no active subscription.

### Manual Role Sync

Users can manually sync their roles from the dashboard:
1. Go to Dashboard
2. Find the "Discord Community" section
3. Click "Sync Roles"

---

## Routes

| Route | Method | Description |
|-------|--------|-------------|
| `/auth/discord` | GET | Initiate OAuth flow |
| `/auth/discord/callback` | GET | OAuth callback handler |
| `/auth/discord/disconnect` | POST | Disconnect Discord account |
| `/auth/discord/sync-roles` | POST | Manually sync roles |
| `/api/discord/status` | GET | Get connection status (API) |
| `/api/discord/interactions` | POST | Discord interactions webhook (slash commands, buttons) |

---

## Files Reference

### Backend

| File | Description |
|------|-------------|
| `app/Services/DiscordService.php` | Discord API interactions |
| `app/Http/Controllers/DiscordController.php` | OAuth and role endpoints |
| `app/Jobs/SyncDiscordRolesJob.php` | Queued role sync job |
| `app/Listeners/SyncDiscordRolesOnSubscriptionChange.php` | Stripe webhook listener |
| `config/services.php` | Discord configuration |

### Frontend

| File | Description |
|------|-------------|
| `resources/js/pages/CustomerDashboard.vue` | Dashboard with Discord section |

---

## Troubleshooting

### "Missing Access" Error

- Ensure the bot has the "Manage Roles" permission
- Ensure the bot's role is ABOVE the roles it's trying to assign
- Check that the bot is actually in the server

### User Not Getting Roles

1. Check if user has joined the Discord server
2. Click "Sync Roles" to manually trigger sync
3. Check Laravel logs for API errors:
   ```bash
   tail -f storage/logs/laravel.log
   ```

### OAuth Callback Error

- Verify `DISCORD_REDIRECT_URI` matches exactly what's in Discord Developer Portal
- Check that client ID and secret are correct
- Ensure the callback URL uses HTTPS in production

### Rate Limiting

Discord API has rate limits. The integration handles this gracefully, but if you see 429 errors:
- Reduce frequency of role sync operations
- Implement exponential backoff (already built into the job retry logic)

---

## Security Notes

- Bot token is stored securely in `.env` (never commit this)
- User access tokens are encrypted in the database
- OAuth scopes are minimal (`identify`, `guilds.join`)
- Tokens can be revoked by users disconnecting their account

---

## Testing Locally

1. Use ngrok or similar to expose your local server:
   ```bash
   ngrok http 80
   ```

2. Update Discord OAuth redirect to ngrok URL:
   ```
   https://abc123.ngrok.io/auth/discord/callback
   ```

3. Update `.env`:
   ```env
   DISCORD_REDIRECT_URI=https://abc123.ngrok.io/auth/discord/callback
   ```

4. Test the flow end-to-end

---

## Future Enhancements

Potential improvements to consider:
- Welcome DM when user connects
- Notification channel for new subscribers
- Slash commands for checking subscription status
- Auto-kick when subscription expires (optional)
- Discord-exclusive promotions/announcements
