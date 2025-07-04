<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import PricingCards from '@/components/PricingCards.vue';

interface Props {
    subscriptions: any[];
}

defineProps<Props>();

const page = usePage();
const subscriptions = page.props.subscriptions || [];
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
    weeklyPrice: '49',
    dailyPrice: '12',
    highlight: false,
  },
];
</script>

<template>
    <CustomerLayout>
        <Head title="Billing Settings" />

        <div class="container py-4">
            <!-- Settings Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Settings</h2>
                    <nav class="nav nav-pills">
                        <Link :href="route('profile.edit')" class="nav-link">
                            <i class="bi bi-person me-2"></i>Profile
                        </Link>
                        <Link :href="route('billing.edit')" class="nav-link active">
                            <i class="bi bi-credit-card me-2"></i>Billing
                        </Link>
                        <Link :href="route('password.edit')" class="nav-link">
                            <i class="bi bi-shield-lock me-2"></i>Security
                        </Link>
                        <Link :href="route('appearance')" class="nav-link">
                            <i class="bi bi-palette me-2"></i>Appearance
                        </Link>
                    </nav>
                </div>
            </div>

            <div class="row">
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Current Subscriptions</h5>
                        <p class="text-muted mb-0 small">Manage your subscription and billing information</p>
                    </div>
                    <div class="card-body">
                        <!-- Show message if no subscriptions -->
                        <div v-if="subscriptions.length == 0" class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            You aren't subscribed to any plans. Choose a plan below to get started.
                        </div>
                        <div v-else>
                            <p class="text-muted mb-3">You are subscribed to the following plans:</p>
                            <div class="list-group">
                                <div v-for="subscription in subscriptions" :key="subscription.id" class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">{{ subscription.name }}</h6>
                                        <small class="text-muted">Status: {{ subscription.status }}</small>
                                    </div>
                                    <span :class="['badge', subscription.status === 'active' ? 'bg-success' : 'bg-secondary']">
                                        {{ subscription.status }}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Billing Portal Button -->
                            <div class="mt-4">
                                <a href="/billing-portal" class="btn btn-secondary">
                                    <i class="bi bi-credit-card me-2"></i>
                                    Manage Billing & Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Plans -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Available Plans</h5>
                        <p class="text-muted mb-0 small">Choose the plan that fits your betting style</p>
                    </div>
                    <div class="card-body">
                        <PricingCards :plans="plans" />
                    </div>
                </div>
            </div>
        </div>
        </div>
    </CustomerLayout>
</template>