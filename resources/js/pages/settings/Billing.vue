<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem } from '@/types';
import PricingCards from '@/components/PricingCards.vue';
const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Billing settings',
        href: '/settings/billing',
    },
];

interface Props {
    subscriptions: array;
}

defineProps<Props>();

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
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Billing settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall title="Manage Billing" description="Ensure your account is using the correct subscription" />

                <!-- Show message if no subscriptions -->
                <div v-if="subscriptions.length == 0" class="text-gray-600">
                    You aren't subscribed to any plans.
                </div>
                <div v-else class="text-gray-600">
                    You are subscribed to the following plans:
                    <ul class="list-disc pl-5">
                        <li v-for="subscription in subscriptions" :key="subscription.id">
                            {{ subscription.name }} - {{ subscription.status }}
                        </li>
                    </ul>
                </div>

                <!-- Pricing Cards -->
                <div class="w-full my-8">
                  <PricingCards :plans="plans" />
                </div>

                <!-- Billing Portal -->
                <a
                    href="billing-portal"
                    class="mt-6 inline-block bg-gray-600 text-white px-4 py-2 rounded-md shadow hover:bg-gray-700"
                >
                    Billing Portal
                </a>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
