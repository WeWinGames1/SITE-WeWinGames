<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PricingCards from '../components/PricingCards.vue';
const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const subscriptions = page.props.subscriptions;

// Use the dynamic Stripe prices from shared props
const stripePrices = page.props.stripePrices || {};
const silver_monthly = stripePrices.silver_monthly;
const silver_weekly = stripePrices.silver_weekly;
const silver_daily = stripePrices.silver_daily;
const gold_monthly = stripePrices.gold_monthly;
const gold_weekly = stripePrices.gold_weekly;
const gold_daily = stripePrices.gold_daily;
const platinum_monthly = stripePrices.platinum_monthly;
const platinum_weekly = stripePrices.platinum_weekly;
const platinum_daily = stripePrices.platinum_daily;
const plans = [
  {
    name: 'Silver',
    price: '$45',
    monthlyPrice: '$45',
    duration: '30 days',
    features: [
      'Over 5 picks a day',
      'Straight bets',
      'Favorite picks',
      'Avg odds -120',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_daily }),
    weeklyPrice: '17',
    dailyPrice: '5',
    highlight: false,
  },
  {
    name: 'Gold',
    price: '$65',
    monthlyPrice: '$65',
    duration: '30 days',
    features: [
      'All Silver features +',
      '> 5 gold picks daily',
      'Best Value Bets',
      'Avg odds > +100',
      'Cancel anytime',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_daily }),
    weeklyPrice: '29',
    dailyPrice: '8',
    highlight: true,
  },
  {
    name: 'Platinum',
    price: '$80',
    monthlyPrice: '$80',
    duration: '30 days',
    features: [
      'All Silver & Gold features +',
      '5 platinum picks daily',
      'Parlay & prop bets',
      'Best tipsters & ROI',
      'Cancel anytime',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_daily }),
    weeklyPrice: '49',
    dailyPrice: '12',
    highlight: false,
  },
];
</script>

<template>
  <WelcomeLayout>
    <Head title="Buy Our Picks" />
    <div class="min-vh-100" style="background: linear-gradient(180deg, #4f46e5 0%, #1a2332 50%, #0a1628 100%);">
      <div class="container-fluid px-4 px-lg-5 py-5" style="max-width: 1200px;">
        <div class="pt-5 mt-5">
          <h1 class="display-4 fw-bold mb-4 text-center text-white">Buy Our Picks</h1>
          <p class="fs-5 text-center text-gray-light mb-5 mx-auto" style="max-width: 800px;">
            We help people enjoy their experience betting on their mobiles. The sports betting app world is taking off and we want you to enjoy your experience. What better way than with our profitable free betting tips. We have set up WeWinGames to help you avail of this opportunity.
          </p>
        </div>

        <!-- Pricing Cards -->
        <PricingCards :plans="plans" />
      
      </div>
    </div>
  </WelcomeLayout>
</template>