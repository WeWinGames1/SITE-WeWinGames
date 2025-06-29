<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Cashier\Billable;
use NotificationChannels\WebPush\HasPushSubscriptions;
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use Billable, HasApiTokens, HasPushSubscriptions, HasRoles, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }

    /**
     * Check if user has active subscription or special privileges
     */
    public function hasActiveSubscription(): bool
    {
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
                // Get the price to tier mapping from config
                $priceToTier = config('stripe.price_to_tier');
                if (isset($priceToTier[$subscription->stripe_price])) {
                    return $priceToTier[$subscription->stripe_price]['tier'];
                }
            }
        }
        
        return null;
    }
}
