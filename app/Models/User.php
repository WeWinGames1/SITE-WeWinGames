<?php

namespace App\Models;

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
        'affiliate_bound_at',
        'affiliate_bound_plan',
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
        ];
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

        // Check for admin override first
        if ($this->is_ambassador || $this->is_gifted || $this->admin_override) {
            // If there's an expiry date, check if it's still valid
            if ($this->override_expiry && $this->override_expiry->isPast()) {
                return false;
            }

            return true;
        }

        // Then check Stripe subscription
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
    public function bindToAffiliate(Affiliate $affiliate, string $plan = null): void
    {
        $this->affiliate_id = $affiliate->id;
        $this->affiliate_bound_at = now();
        $this->affiliate_bound_plan = $plan;
        $this->save();
    }
}
