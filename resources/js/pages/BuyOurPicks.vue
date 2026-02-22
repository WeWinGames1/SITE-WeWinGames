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
    const defaults: Record<string, Record<string, string>> = {
        gold: { monthly: '$45', weekly: '15', daily: '5' },
        platinum: { monthly: '$90', weekly: '30', daily: '10' },
    };
    return defaults[tier.toLowerCase()]?.[billingPeriod] || '$0';
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
        monthlyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_monthly }),
        weeklyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_weekly }),
        dailyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: stripePrices.gold_daily }),
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
        monthlyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_monthly }),
        weeklyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_weekly }),
        dailyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: stripePrices.platinum_daily }),
        weeklyPrice: getPrice('platinum', 'weekly'),
        dailyPrice: getPrice('platinum', 'daily'),
        highlight: true,
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

                <!-- Free Tier Section (De-emphasized) -->
                <div class="row justify-content-center mb-5">
                    <div class="col-lg-8">
                        <div class="card bg-dark border-secondary">
                            <div class="card-body p-4">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <span class="badge bg-secondary me-2 px-3 py-2">Free Access</span>
                                            <h3 class="h5 fw-bold mb-0 text-gray-light">Start With Free Picks</h3>
                                        </div>
                                        <p class="text-muted mb-3 small">
                                            Create a free account and get instant access to our Free picks. No credit card required.
                                        </p>
                                        <ul class="list-unstyled mb-0">
                                            <li v-for="feature in getDefaultFeatures('free')" :key="feature" class="mb-1 d-flex align-items-start">
                                                <i class="bi bi-check-circle text-muted me-2 flex-shrink-0 small" style="margin-top: 2px"></i>
                                                <span class="small text-muted">{{ feature }}</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="col-md-4 text-center text-md-end mt-3 mt-md-0">
                                        <div class="h3 fw-bold text-muted mb-2">$0</div>
                                        <p class="text-muted small mb-3">Forever free</p>
                                        <a v-if="!user" :href="route('register')" class="btn btn-outline-secondary btn-sm px-4">
                                            <i class="bi bi-person-plus me-2"></i>
                                            Sign Up Free
                                        </a>
                                        <a v-else :href="route('todays-bets')" class="btn btn-outline-secondary btn-sm px-4">
                                            <i class="bi bi-eye me-2"></i>
                                            View Free Picks
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Plans Header -->
                <div class="text-center mb-4">
                    <h2 class="h3 fw-bold text-white mb-2">Upgrade to Premium</h2>
                    <p class="text-gray-light">Get access to more picks and exclusive features</p>
                </div>

                <!-- Pricing Cards -->
                <PricingCards :plans="plans" />
            </div>
        </div>
    </WelcomeLayout>
</template>
