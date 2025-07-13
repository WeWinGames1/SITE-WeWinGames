<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Affiliate extends Model
{
    protected $fillable = [
        'name',
        'code',
        'email',
        'phone',
        'commission_rate',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($affiliate) {
            if (empty($affiliate->code)) {
                $affiliate->code = static::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function activeUsers(): HasMany
    {
        return $this->hasMany(User::class)->whereHas('subscriptions', function ($query) {
            $query->where('stripe_status', 'active');
        });
    }

    public function getShareUrl(): string
    {
        return url('/?affiliate=' . $this->code);
    }

    public function getTotalCustomers(): int
    {
        return $this->users()->count();
    }

    public function getActiveCustomers(): int
    {
        return $this->activeUsers()->count();
    }

    public function getTotalRevenue(): float
    {
        // Calculate based on Stripe subscriptions
        return $this->users()
            ->join('subscriptions', 'users.id', '=', 'subscriptions.user_id')
            ->where('subscriptions.stripe_status', 'active')
            ->sum('subscriptions.quantity') * 30; // Assuming $30/month average
    }
}
