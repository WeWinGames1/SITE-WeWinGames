import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { route } from 'ziggy-js';

/**
 * Builds the pricing "plans" array used by the homepage PricingCards widget,
 * from the dynamic Stripe products/prices shared via Inertia props. Extracted
 * so both the hardcoded homepage and the editable homepage can reuse it
 * without duplicating the pricing logic.
 */
export function useHomePlans() {
    const page = usePage();
    const auth = page.props.auth as any;
    const stripePrices = (page.props.stripePrices || {}) as Record<string, string>;
    const stripeProducts = (page.props.stripeProducts || {}) as Record<string, any>;

    const buildCheckoutLink = (tier: string, period: string, priceId: string): string => {
        if (auth?.user) {
            return route('subscription.checkout', {
                subscription_name: tier,
                subscription_price_id: priceId,
            });
        }

        return route('quick-checkout', { plan: tier, period });
    };

    const getDefaultFeatures = (tier: string): string[] => {
        switch (tier.toLowerCase()) {
            case 'free':
                return ['Access to Free picks daily', 'Basic straight bets', 'Email notifications', 'Community access', '24/7 support'];
            case 'gold':
                return [
                    'All Free picks included',
                    'Access to Gold-tier premium picks',
                    'Priority email notifications',
                    'Advanced analytics',
                    'Early access to picks',
                    'Cancel anytime',
                ];
            case 'platinum':
                return [
                    'All Free & Gold picks included',
                    'Access to ALL premium picks',
                    'Instant notifications',
                    'Premium analytics dashboard',
                    'VIP support',
                    'Personal betting consultant',
                ];
            default:
                return [];
        }
    };

    const getFeatures = (tier: string, billingPeriod: string = 'monthly'): string[] => {
        const products = stripeProducts[billingPeriod];
        if (!products || !products[tier.toLowerCase()]) {
            return getDefaultFeatures(tier);
        }
        return products[tier.toLowerCase()].features || getDefaultFeatures(tier);
    };

    const getDefaultPrice = (tier: string, billingPeriod: string): string => {
        const defaults: Record<string, Record<string, string>> = {
            gold: { monthly: '$45', weekly: '15', daily: '5' },
            platinum: { monthly: '$90', weekly: '30', daily: '10' },
        };
        return defaults[tier.toLowerCase()]?.[billingPeriod] || '$0';
    };

    const getPrice = (tier: string, billingPeriod: string = 'monthly'): string => {
        const products = stripeProducts[billingPeriod];
        if (!products || !products[tier.toLowerCase()]) {
            return getDefaultPrice(tier, billingPeriod);
        }
        const amount = products[tier.toLowerCase()].amount;
        return billingPeriod === 'monthly' ? `$${amount}` : amount.toString();
    };

    const plans = computed(() => [
        {
            name: 'Gold',
            price: getPrice('gold', 'monthly'),
            monthlyPrice: getPrice('gold', 'monthly'),
            duration: '30 days',
            features: getFeatures('gold', 'monthly'),
            monthlyFeatures: getFeatures('gold', 'monthly'),
            weeklyFeatures: getFeatures('gold', 'weekly'),
            dailyFeatures: getFeatures('gold', 'daily'),
            monthlyLink: buildCheckoutLink('gold', 'monthly', stripePrices.gold_monthly),
            weeklyLink: buildCheckoutLink('gold', 'weekly', stripePrices.gold_weekly),
            dailyLink: buildCheckoutLink('gold', 'daily', stripePrices.gold_daily),
            weeklyPrice: getPrice('gold', 'weekly'),
            dailyPrice: getPrice('gold', 'daily'),
            highlight: false,
        },
        {
            name: 'Platinum',
            price: getPrice('platinum', 'monthly'),
            monthlyPrice: getPrice('platinum', 'monthly'),
            duration: '30 days',
            features: getFeatures('platinum', 'monthly'),
            monthlyFeatures: getFeatures('platinum', 'monthly'),
            weeklyFeatures: getFeatures('platinum', 'weekly'),
            dailyFeatures: getFeatures('platinum', 'daily'),
            monthlyLink: buildCheckoutLink('platinum', 'monthly', stripePrices.platinum_monthly),
            weeklyLink: buildCheckoutLink('platinum', 'weekly', stripePrices.platinum_weekly),
            dailyLink: buildCheckoutLink('platinum', 'daily', stripePrices.platinum_daily),
            weeklyPrice: getPrice('platinum', 'weekly'),
            dailyPrice: getPrice('platinum', 'daily'),
            highlight: true,
        },
    ]);

    return { plans, getPrice, getFeatures, buildCheckoutLink };
}
