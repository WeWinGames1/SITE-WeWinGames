<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import PricingCards from '@/components/PricingCards.vue';
import { computed, onMounted } from 'vue';

interface PaymentMethod {
    id: string;
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
    is_default: boolean;
}

interface Invoice {
    id: string;
    number: string;
    date: string;
    total: string;
    status: string;
    pdf: string;
}

interface CurrentPlan {
    tier: string;
    period: string;
    price_id: string;
    status: string;
    ends_at?: string;
    trial_ends_at?: string;
    current_period_end?: string;
}

interface Props {
    subscriptions: any[];
    currentPlan?: CurrentPlan;
    paymentMethods: PaymentMethod[];
    invoices: Invoice[];
    hasStripeId: boolean;
}

const props = defineProps<Props>();

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

// Check if a plan matches the current subscription
const isPlanActive = (planName: string, planPeriod: string) => {
    if (!props.currentPlan) return false;
    return props.currentPlan.tier.toLowerCase() === planName.toLowerCase() && 
           props.currentPlan.period === planPeriod;
};

const plans = computed(() => [
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
    isCurrentPlan: isPlanActive('Silver', 'monthly'),
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
    isCurrentPlan: isPlanActive('Gold', 'monthly'),
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
    isCurrentPlan: isPlanActive('Platinum', 'monthly'),
  },
]);

const handleBillingPortal = () => {
    window.location.href = route('billing.portal');
};

// Check if we're returning from Stripe and refresh the page
onMounted(() => {
    // Check if there's a session success parameter or if we're returning from Stripe
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('from') && urlParams.get('from') === 'stripe') {
        // Remove the parameter from URL
        const newUrl = window.location.pathname;
        window.history.replaceState({}, '', newUrl);
        
        // Force a page reload to get fresh data from server
        setTimeout(() => {
            router.reload({ only: ['paymentMethods', 'currentPlan', 'invoices'] });
        }, 100);
    }
});
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
                    <!-- Current Subscription Status -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Current Subscription</h5>
                            <p class="mb-0 small">Your active plan and billing details</p>
                        </div>
                        <div class="card-body">
                            <div v-if="!currentPlan" class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                You don't have an active subscription. Choose a plan below to get started.
                            </div>
                            <div v-else>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Plan Details</h6>
                                        <div class="mb-3">
                                            <span class="badge bg-primary fs-6 mb-2">{{ currentPlan.tier }} {{ currentPlan.period }}</span>
                                            <p class="mb-1"><strong>Status:</strong> 
                                                <span :class="['badge', currentPlan.status === 'active' ? 'bg-success' : 'bg-secondary']">
                                                    {{ currentPlan.status }}
                                                </span>
                                            </p>
                                            <p class="mb-1" v-if="currentPlan.current_period_end">
                                                <strong>Next billing date:</strong> 
                                                {{ new Date(currentPlan.current_period_end).toLocaleDateString() }}
                                            </p>
                                            <p class="mb-1" v-if="currentPlan.trial_ends_at">
                                                <strong>Trial ends:</strong> 
                                                {{ new Date(currentPlan.trial_ends_at).toLocaleDateString() }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Quick Actions</h6>
                                        <button @click="handleBillingPortal" class="btn btn-outline-primary mb-2 w-100">
                                            <i class="bi bi-gear me-2"></i>
                                            Manage Subscription
                                        </button>
                                        <button @click="handleBillingPortal" class="btn btn-outline-secondary w-100">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Cancel Subscription
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Methods -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-0">Payment Methods</h5>
                                <p class="mb-0 small">Cards on file for automatic billing</p>
                            </div>
                            <button @click="router.reload()" class="btn btn-sm btn-outline-secondary" title="Refresh payment methods">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <div v-if="paymentMethods.length === 0" class="text-center py-3">
                                <i class="bi bi-credit-card" style="font-size: 3rem;"></i>
                                <p class="mt-2">No payment methods on file</p>
                                <button @click="handleBillingPortal" class="btn btn-primary">
                                    Add Payment Method
                                </button>
                            </div>
                            <div v-else class="row">
                                <div v-for="method in paymentMethods" :key="method.id" class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="list-group list-group-flush">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center">
                                                    <i :class="[method.brand === 'Stripe Link' ? 'bi-link-45deg' : 'bi-credit-card-fill', 'bi text-primary me-3']" style="font-size: 1.5rem;"></i>
                                                    <div>
                                                        <p class="mb-0">
                                                            <strong>{{ method.brand }}</strong> 
                                                            <span v-if="method.brand === 'Stripe Link'">{{ method.last4 }}</span>
                                                            <span v-else>ending in {{ method.last4 }}</span>
                                                        </p>
                                                        <small v-if="method.exp_month && method.exp_year">Expires {{ method.exp_month }}/{{ method.exp_year }}</small>
                                                        <small v-else-if="method.brand === 'Stripe Link'">Quick checkout</small>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span v-if="method.is_default" class="badge bg-success me-2">Default</span>
                                                    <button @click="handleBillingPortal" class="btn btn-sm btn-outline-secondary">
                                                        Manage
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Add Payment Method Card -->
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100 border-dashed bg-light">
                                        <div class="card-body d-flex align-items-center justify-content-center">
                                            <button @click="handleBillingPortal" class="btn btn-link text-decoration-none">
                                                <i class="bi bi-plus-circle" style="font-size: 2rem;"></i>
                                                <p class="mb-0 mt-2">Add Payment Method</p>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Payment History</h5>
                            <p class="mb-0 small">Recent invoices and receipts</p>
                        </div>
                        <div class="card-body">
                            <div v-if="invoices.length === 0" class="text-center py-3">
                                <i class="bi bi-receipt" style="font-size: 3rem;"></i>
                                <p class="mt-2">No payment history yet</p>
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Invoice #</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="invoice in invoices" :key="invoice.id">
                                            <td>{{ invoice.date }}</td>
                                            <td>{{ invoice.number || 'N/A' }}</td>
                                            <td>${{ invoice.total }}</td>
                                            <td>
                                                <span :class="['badge', invoice.status === 'paid' ? 'bg-success' : 'bg-warning']">
                                                    {{ invoice.status }}
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <a :href="invoice.pdf" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-download"></i> PDF
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-if="invoices.length > 0" class="text-center mt-3">
                                <button @click="handleBillingPortal" class="btn btn-outline-primary">
                                    View All Invoices
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Available Plans -->
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <h5 class="mb-0">Available Plans</h5>
                            <p class="mb-0 small">
                                {{ currentPlan ? 'Upgrade or switch your plan' : 'Choose the plan that fits your betting style' }}
                            </p>
                        </div>
                        <div class="card-body">
                            <PricingCards :plans="plans" :current-plan="currentPlan" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>