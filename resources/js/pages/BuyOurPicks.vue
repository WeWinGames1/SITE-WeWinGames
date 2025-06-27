<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import PricingCards from '../components/PricingCards.vue';
const page = usePage<SharedData>();
const user = page.props.auth.user as User;
const subscriptions = page.props.subscriptions;
const silver_monthly = page.props.env.SILVER_MONTHLY;
const silver_weekly = page.props.env.SILVER_WEEKLY;
const gold_weekly = page.props.env.GOLD_WEEKLY;
const platinum_weekly = page.props.env.PLATINUM_WEEKLY;
const silver_daily = page.props.env.SILVER_DAILY;
const gold_daily = page.props.env.GOLD_DAILY;
const platinum_daily = page.props.env.PLATINUM_DAILY;
const gold_monthly = page.props.env.GOLD_MONTHLY;
const platinum_monthly = page.props.env.PLATINUM_MONTHLY;
const plans = [
  {
    name: 'Silver',
    price: '$60',
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
    weeklyPrice: '20',
    dailyPrice: '5',
    highlight: false,
  },
  {
    name: 'Gold',
    price: '$110',
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
    weeklyPrice: '39',
    dailyPrice: '10',
    highlight: true,
  },
  {
    name: 'Platinum',
    price: '$149',
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
    weeklyPrice: '55',
    dailyPrice: '15',
    highlight: false,
  },
];
</script>

<template>
  <WelcomeLayout>
    <Head title="Buy Our Picks" />
    <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200">
      <div class="container mx-auto px-4 py-16 max-w-5xl">
        <h1 class="text-4xl font-bold mb-4 text-center text-white">Buy Our Picks</h1>
        <p class="text-lg text-center text-gray-300 mb-10">
          We help people enjoy their experience betting on their mobiles. The sports betting app world is taking off and we want you to enjoy your experience. What better way than with our profitable free betting tips. We have set up WeWinGames to help you avail of this opportunity.
        </p>

        <!-- Pricing Cards -->
       <PricingCards :plans="plans" />
      
      </div>
    </div>
  </WelcomeLayout>
</template>