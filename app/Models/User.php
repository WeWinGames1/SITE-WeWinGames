<?php

namespace App\Models;

use App\Notifications\CustomResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasPushSubscriptions, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'notification_preferences',
        'registration_ip',
        'registration_user_agent',
        'last_login_at',
        'last_login_ip',
        'is_ambassador',
        'is_gifted',
        'admin_override',
        'override_expiry',
        'override_tier',
        'affiliate_id',
        'discord_username',
        'discord_id',
        'discord_discriminator',
        'discord_avatar',
        'discord_access_token',
        'discord_refresh_token',
        'discord_connected_at',
        'discord_token_expires_at',
        'discord_roles_synced',
        'affiliate_bound_at',
        'affiliate_bound_plan',
        'favorite_team',
        'favorite_sport',
        'primary_betting_app',
        'completion_token',
        'completion_token_expires_at',
        'registration_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'registration_ip',
        'registration_user_agent',
        'last_login_ip',
        'discord_access_token',
        'discord_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notification_preferences' => 'array',
            'last_login_at' => 'datetime',
            'is_ambassador' => 'boolean',
            'is_gifted' => 'boolean',
            'admin_override' => 'boolean',
            'override_expiry' => 'date',
            'affiliate_bound_at' => 'datetime',
            'discord_connected_at' => 'datetime',
            'discord_token_expires_at' => 'datetime',
            'discord_roles_synced' => 'array',
            'completion_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Check if Discord account is connected
     */
    public function hasDiscordConnected(): bool
    {
        return ! empty($this->discord_id);
    }

    /**
     * Get the Discord avatar URL
     */
    public function getDiscordAvatarUrlAttribute(): ?string
    {
        if (! $this->discord_id || ! $this->discord_avatar) {
            return null;
        }

        $extension = str_starts_with($this->discord_avatar, 'a_') ? 'gif' : 'png';

        return "https://cdn.discordapp.com/avatars/{$this->discord_id}/{$this->discord_avatar}.{$extension}";
    }

    /**
     * Check if user has active subscription or special privileges
     */
    public function hasActiveSubscription(): bool
    {
        // Check if user is disabled
        if ($this->status === 'disabled') {
            return false;
        }

        // Check for admin override first (ambassador, gifted, or manual override)
        if ($this->is_ambassador || $this->is_gifted || $this->admin_override) {
            // If there's no expiry date, or the expiry is in the future, override is valid
            if (! $this->override_expiry || ! $this->override_expiry->isPast()) {
                return true;
            }
            // Override has expired - fall through to check Stripe subscription
        }

        // Check Stripe subscription as fallback
        return $this->subscribed();
    }

    /**
     * Get the user's current subscription tier
     */
    public function getCurrentTier(): ?string
    {
        // If user has an override tier, return that
        if ($this->override_tier && $this->hasActiveSubscription()) {
            return $this->override_tier;
        }

        // Otherwise check their actual subscription
        if ($this->subscribed()) {
            $subscription = $this->subscription();
            if ($subscription && $subscription->stripe_price) {
                // First try to get from StripeProduct table (more reliable)
                $stripeProduct = \App\Models\StripeProduct::where('stripe_price_id', $subscription->stripe_price)
                    ->where('is_active', true)
                    ->first();

                if ($stripeProduct) {
                    return $stripeProduct->tier;
                }

                // Fallback to config
                $priceToTier = config('stripe.price_to_tier');
                if (isset($priceToTier[$subscription->stripe_price])) {
                    return $priceToTier[$subscription->stripe_price]['tier'];
                }
            }
        }

        return null;
    }

    /**
     * Alias for getCurrentTier() for backward compatibility
     */
    public function currentTier(): ?string
    {
        return $this->getCurrentTier();
    }

    /**
     * Get the user's support tickets
     */
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Get the affiliate that referred this user
     */
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Bind user to affiliate when they upgrade to paid plan
     */
    public function bindToAffiliate(Affiliate $affiliate, ?string $plan = null): void
    {
        $this->affiliate_id = $affiliate->id;
        $this->affiliate_bound_at = now();
        $this->affiliate_bound_plan = $plan;
        $this->save();
    }

    /**
     * Get user's discount redemptions
     */
    public function discountRedemptions()
    {
        return $this->hasMany(\App\Models\DiscountRedemption::class);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

    /**
     * Check if user needs to complete registration (quick checkout flow)
     */
    public function needsRegistrationCompletion(): bool
    {
        return $this->status === 'pending_setup'
            && in_array($this->registration_type, ['quick_checkout', 'affiliate_trial'], true);
    }

    /**
     * Check if completion token is valid
     */
    public function hasValidCompletionToken(?string $token = null): bool
    {
        if (! $this->completion_token) {
            return false;
        }

        if ($token !== null && ! hash_equals($this->completion_token, $token)) {
            return false;
        }

        return $this->completion_token_expires_at && $this->completion_token_expires_at->isFuture();
    }

    /**
     * Generate a new completion token
     */
    public function generateCompletionToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->completion_token = $token;
        $this->completion_token_expires_at = now()->addHours(24);
        $this->save();

        return $token;
    }

    /**
     * Clear the completion token
     */
    public function clearCompletionToken(): void
    {
        $this->completion_token = null;
        $this->completion_token_expires_at = null;
        $this->save();
    }
}
