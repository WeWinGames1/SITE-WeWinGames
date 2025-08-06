<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PricingCards from '../components/PricingCards.vue';

const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const subscriptions = page.props.subscriptions;

// Use the dynamic Stripe prices and products from shared props
const stripePrices = page.props.stripePrices || {};
const stripeProducts = page.props.stripeProducts || {};

// Helper function to get features for a specific tier and billing period
const getFeatures = (tier: string, billingPeriod: string = 'monthly') => {
    const products = stripeProducts[billingPeriod];
    if (!products || !products[tier.toLowerCase()]) {
        // Return default features if not found
        return getDefaultFeatures(tier);
    }
    return products[tier.toLowerCase()].features || getDefaultFeatures(tier);
};

// Default features as fallback
const getDefaultFeatures = (tier: string) => {
    switch (tier.toLowerCase()) {
        case 'silver':
            return ['Over 5 picks a day', 'Straight bets', 'Favorite picks', 'Avg odds -120', '24/7 support'];
        case 'gold':
            return ['All Silver features +', '> 5 gold picks daily', 'Best Value Bets', 'Avg odds > +100', 'Cancel anytime', '24/7 support'];
        case 'platinum':
            return [
                'All Silver & Gold features +',
                '5 platinum picks daily',
                'Parlay & prop bets',
                'Best tipsters & ROI',
                'Cancel anytime',
                '24/7 support',
            ];
        default:
            return [];
    }
};

// Get prices from stripeProducts
const getPrice = (tier: string, billingPeriod: string = 'monthly') => {
    const products = stripeProducts[billingPeriod];
    if (!products || !products[tier.toLowerCase()]) {
        // Return default prices if not found
        return getDefaultPrice(tier, billingPeriod);
    }
    const amount = products[tier.toLowerCase()].amount;
    return billingPeriod === 'monthly' ? `$${amount}` : amount.toString();
};

// Default prices as fallback
const getDefaultPrice = (tier: string, billingPeriod: string) => {
    const defaults = {
        silver: { monthly: '$45', weekly: '17', daily: '5' },
        gold: { monthly: '$65', weekly: '29', daily: '8' },
        platinum: { monthly: '$80', weekly: '49', daily: '12' },
    };
    return defaults[tier.toLowerCase()]?.[billingPeriod] || '$0';
};

const plans = computed(() => [
    {
        name: 'Silver',
        price: getPrice('silver', 'monthly'),
        monthlyPrice: getPrice('silver', 'monthly'),
        duration: '30 days',
        features: getFeatures('silver', 'monthly'), // Using monthly features as default display
        monthlyFeatures: getFeatures('silver', 'monthly'),
        weeklyFeatures: getFeatures('silver', 'weekly'),
        dailyFeatures: getFeatures('silver', 'daily'),
        monthlyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: stripePrices.silver_monthly }),
        weeklyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: stripePrices.silver_weekly }),
        dailyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: stripePrices.silver_daily }),
        weeklyPrice: getPrice('silver', 'weekly'),
        dailyPrice: getPrice('silver', 'daily'),
        highlight: false,
    },
    {
        name: 'Gold',
        price: getPrice('gold', 'monthly'),
        monthlyPrice: getPrice('gold', 'monthly'),
        duration: '30 days',
        features: getFeatures('gold', 'monthly'), // Using monthly features as default display
        monthlyFeatures: getFeatures('gold', 'monthly'),
        weeklyFeatures: getFeatures('gold', 'weekly'),
        dailyFeatures: getFeatures('gold', 'daily'),
        monthlyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_monthly }),
        weeklyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_weekly }),
        dailyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_daily }),
        weeklyPrice: getPrice('gold', 'weekly'),
        dailyPrice: getPrice('gold', 'daily'),
        highlight: true,
    },
    {
        name: 'Platinum',
        price: getPrice('platinum', 'monthly'),
        monthlyPrice: getPrice('platinum', 'monthly'),
        duration: '30 days',
        features: getFeatures('platinum', 'monthly'), // Using monthly features as default display
        monthlyFeatures: getFeatures('platinum', 'monthly'),
        weeklyFeatures: getFeatures('platinum', 'weekly'),
        dailyFeatures: getFeatures('platinum', 'daily'),
        monthlyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_monthly }),
        weeklyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_weekly }),
        dailyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_daily }),
        weeklyPrice: getPrice('platinum', 'weekly'),
        dailyPrice: getPrice('platinum', 'daily'),
        highlight: false,
    },
]);
</script>

<template>
    <WelcomeLayout>
        <Head title="Buy Our Picks" />
        <div class="min-vh-100" style="background: linear-gradient(180deg, #4f46e5 0%, #1a2332 50%, #0a1628 100%)">
            <div class="container-fluid px-4 px-lg-5 py-5" style="max-width: 1200px">
                <div class="pt-5 mt-5">
                    <h1 class="display-4 fw-bold mb-4 text-center text-white">Buy Our Picks</h1>
                    <p class="fs-5 text-center text-gray-light mb-5 mx-auto" style="max-width: 800px">
                        We help people enjoy their experience betting on their mobiles. The sports betting app world is taking off and we want you to
                        enjoy your experience. What better way than with our profitable free betting tips. We have set up WeWinGames to help you avail
                        of this opportunity.
                    </p>
                </div>

                <!-- Pricing Cards -->
                <PricingCards :plans="plans" />
            </div>
        </div>
    </WelcomeLayout>
</template>
