<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Stripe\StripeClient;

/**
 * Keeps local Cashier subscription rows in line with Stripe (and with their own
 * end dates, for manual admin subscriptions that never receive a webhook).
 */
class SubscriptionSyncService
{
    /**
     * Statuses Cashier may treat as granting access
     *
     * @var array<int, string>
     */
    public const LIVE_STATUSES = ['active', 'trialing', 'past_due'];

    /**
     * Cancel manual (admin-created) subscriptions whose period has ended
     *
     * Manual subscriptions are stored as stripe_status=active with no ends_at, so
     * without this they grant access forever once the admin override expires.
     *
     * @return Collection<int, int> IDs of users whose access changed
     */
    public function expireManualSubscriptions(bool $dryRun = false): Collection
    {
        $expired = Subscription::query()
            ->where('stripe_id', 'like', 'manual_%')
            ->whereIn('stripe_status', self::LIVE_STATUSES)
            ->whereNull('ends_at')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '<=', now())
            ->get();

        if (! $dryRun) {
            foreach ($expired as $subscription) {
                $subscription->forceFill([
                    'stripe_status' => 'canceled',
                    'ends_at' => $subscription->current_period_end,
                ])->save();

                Log::info('Expired manual subscription', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'period_end' => (string) $subscription->current_period_end,
                ]);
            }
        }

        return $expired->pluck('user_id')->unique()->values();
    }

    /**
     * Pull the customer's subscriptions from Stripe and overwrite the local rows
     *
     * Covers webhooks that were missed or failed: status, price, cancellation date
     * and period dates are taken from Stripe as the source of truth.
     *
     * @return array<int, string> Human-readable list of changes made
     */
    public function syncFromStripe(User $user): array
    {
        if (! $user->stripe_id) {
            return [];
        }

        $changes = [];
        $remote = collect($this->stripe()->subscriptions->all([
            'customer' => $user->stripe_id,
            'status' => 'all',
            'limit' => 100,
        ])->data)->keyBy('id');

        foreach ($remote as $stripeId => $stripeSubscription) {
            $subscription = $user->subscriptions()->where('stripe_id', $stripeId)->first();

            if (! $subscription) {
                continue;
            }

            $item = $stripeSubscription->items->data[0] ?? null;
            $endsAt = $this->endsAt($stripeSubscription);

            $subscription->fill([
                'stripe_status' => $stripeSubscription->status,
                'stripe_price' => count($stripeSubscription->items->data) === 1 ? $item?->price?->id : null,
                'ends_at' => $endsAt,
                'current_period_start' => $this->timestamp($stripeSubscription->current_period_start ?? $item?->current_period_start ?? null),
                'current_period_end' => $this->timestamp($stripeSubscription->current_period_end ?? $item?->current_period_end ?? null),
            ]);

            if ($subscription->isDirty(['stripe_status', 'stripe_price', 'ends_at'])) {
                $changes[] = sprintf(
                    '%s: %s → %s%s',
                    $stripeId,
                    $subscription->getOriginal('stripe_status'),
                    $subscription->stripe_status,
                    $endsAt ? ' (ends '.$endsAt->toDateString().')' : ''
                );
            }

            $subscription->save();
        }

        $orphaned = $user->subscriptions()
            ->where('stripe_id', 'not like', 'manual_%')
            ->whereIn('stripe_status', self::LIVE_STATUSES)
            ->whereNotIn('stripe_id', $remote->keys()->all())
            ->get();

        foreach ($orphaned as $subscription) {
            $subscription->forceFill(['stripe_status' => 'canceled', 'ends_at' => now()])->save();
            $changes[] = "{$subscription->stripe_id}: not found in Stripe → canceled";
        }

        if ($changes !== []) {
            Log::info('Subscriptions resynced from Stripe', [
                'user_id' => $user->id,
                'changes' => $changes,
            ]);
        }

        return $changes;
    }

    protected function stripe(): StripeClient
    {
        return Cashier::stripe();
    }

    /**
     * Mirror of Cashier's webhook handling of cancellation dates
     */
    private function endsAt(object $stripeSubscription): ?Carbon
    {
        if ($stripeSubscription->status === 'canceled') {
            return $this->timestamp($stripeSubscription->ended_at ?? $stripeSubscription->canceled_at ?? null) ?? now();
        }

        if ($stripeSubscription->cancel_at_period_end ?? false) {
            return $this->timestamp($stripeSubscription->current_period_end ?? null);
        }

        return $this->timestamp($stripeSubscription->cancel_at ?? null);
    }

    private function timestamp(?int $timestamp): ?Carbon
    {
        return $timestamp ? Carbon::createFromTimestamp($timestamp, config('app.timezone')) : null;
    }
}
