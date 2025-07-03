<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { computed } from 'vue';

const page = usePage();
const props = defineProps<{
    subscriptionTier: string;
    todaysBetsCount: number;
    recentWins: any[];
    monthlyStats: {
        winRate: number;
        totalPicks: number;
        profitPercentage: number;
    };
    hasActiveSubscription: boolean;
}>();

const user = page.props.auth.user.data;

// Get subscription badge info
const subscriptionBadge = computed(() => {
    switch (props.subscriptionTier) {
        case 'platinum':
            return { text: 'Platinum', class: 'bg-purple' };
        case 'gold':
            return { text: 'Gold', class: 'bg-warning text-dark' };
        case 'silver':
            return { text: 'Silver', class: 'bg-secondary' };
        default:
            return { text: 'Free', class: 'bg-dark border' };
    }
});
</script>

<template>
    <CustomerLayout>
        <Head title="Dashboard" />

        <div class="min-vh-100" style="background-color: var(--bs-body-bg);">
            <!-- Welcome Section -->
            <section class="py-5" style="background: linear-gradient(135deg, #7C3AED 0%, #111827 100%);">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 fw-bold text-white mb-3">
                                Welcome back, {{ user.name }}!
                            </h1>
                            <p class="fs-5 text-gray-light mb-4">
                                Track your betting performance and access today's expert picks
                            </p>
                            <div class="d-flex align-items-center gap-3">
                                <span :class="subscriptionBadge.class" class="badge fs-6 px-3 py-2">
                                    {{ subscriptionBadge.text }} Member
                                </span>
                                <span class="text-gray-light">
                                    Member since {{ new Date(user.created_at).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                            <Link href="/todays-bets" class="btn btn-light btn-lg px-5">
                                <i class="bi bi-lightning-charge me-2"></i>
                                View Today's Picks
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Cards -->
            <section class="py-5">
                <div class="container">
                    <div class="row g-4 mb-5">
                        <!-- Win Rate Card -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                            <i class="bi bi-graph-up-arrow text-success fs-4"></i>
                                        </div>
                                        <span class="badge bg-success">This Month</span>
                                    </div>
                                    <h3 class="h2 fw-bold text-white mb-1">{{ props.monthlyStats.winRate }}%</h3>
                                    <p class="text-gray-light mb-0">Win Rate</p>
                                </div>
                            </div>
                        </div>

                        <!-- Total Picks Card -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                            <i class="bi bi-list-check text-primary fs-4"></i>
                                        </div>
                                        <span class="badge bg-primary">Total</span>
                                    </div>
                                    <h3 class="h2 fw-bold text-white mb-1">{{ props.monthlyStats.totalPicks }}</h3>
                                    <p class="text-gray-light mb-0">Picks This Month</p>
                                </div>
                            </div>
                        </div>

                        <!-- Profit Card -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                            <i class="bi bi-currency-dollar text-warning fs-4"></i>
                                        </div>
                                        <span class="badge bg-warning text-dark">ROI</span>
                                    </div>
                                    <h3 class="h2 fw-bold text-white mb-1">+{{ props.monthlyStats.profitPercentage }}%</h3>
                                    <p class="text-gray-light mb-0">Monthly Profit</p>
                                </div>
                            </div>
                        </div>

                        <!-- Today's Picks Card -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="rounded-circle bg-purple bg-opacity-10 p-3">
                                            <i class="bi bi-lightning-charge text-purple fs-4"></i>
                                        </div>
                                        <span class="badge bg-purple">New</span>
                                    </div>
                                    <h3 class="h2 fw-bold text-white mb-1">{{ props.todaysBetsCount }}</h3>
                                    <p class="text-gray-light mb-0">Picks Available Today</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row g-4 mb-5">
                        <div class="col-12">
                            <h2 class="h4 fw-bold text-white mb-4">Quick Actions</h2>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link href="/todays-bets" class="card h-100 text-decoration-none" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-lightning-charge text-purple fs-1 mb-3"></i>
                                    <h5 class="text-white">Today's Picks</h5>
                                    <p class="text-gray-light small mb-0">View all available picks</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link href="/betting-results" class="card h-100 text-decoration-none" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-trophy text-warning fs-1 mb-3"></i>
                                    <h5 class="text-white">Results</h5>
                                    <p class="text-gray-light small mb-0">Check past performance</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link href="/settings/billing" class="card h-100 text-decoration-none" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-credit-card text-info fs-1 mb-3"></i>
                                    <h5 class="text-white">Billing</h5>
                                    <p class="text-gray-light small mb-0">Manage subscription</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link href="/support/tickets" class="card h-100 text-decoration-none" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-headset text-success fs-1 mb-3"></i>
                                    <h5 class="text-white">Support</h5>
                                    <p class="text-gray-light small mb-0">Get help</p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <!-- Recent Wins -->
                    <div v-if="props.recentWins.length > 0" class="row g-4 mb-5">
                        <div class="col-12">
                            <h2 class="h4 fw-bold text-white mb-4">Recent Winning Picks</h2>
                        </div>
                        <div class="col-12">
                            <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-dark table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="text-gray-light">Date</th>
                                                    <th class="text-gray-light">Sport</th>
                                                    <th class="text-gray-light">Teams</th>
                                                    <th class="text-gray-light">Pick</th>
                                                    <th class="text-gray-light">Result</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="win in props.recentWins" :key="win.id">
                                                    <td class="text-white">{{ new Date(win.betting_date).toLocaleDateString() }}</td>
                                                    <td class="text-white">{{ win.sports }}</td>
                                                    <td class="text-white">{{ win.team_one }} vs {{ win.team_two }}</td>
                                                    <td class="text-white">{{ win.tips }}</td>
                                                    <td>
                                                        <span class="badge bg-success">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Won
                                                        </span>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upgrade CTA -->
                    <div v-if="!props.hasActiveSubscription || props.subscriptionTier === 'free'" class="row">
                        <div class="col-12">
                            <div class="card bg-gradient-primary text-white">
                                <div class="card-body p-5 text-center">
                                    <h3 class="h2 fw-bold mb-3">Unlock Premium Picks</h3>
                                    <p class="fs-5 mb-4">Get access to all our expert picks and start winning today</p>
                                    <Link href="/buy-our-picks" class="btn btn-light btn-lg px-5">
                                        <span class="text-dark fw-semibold">View Plans</span>
                                        <i class="bi bi-arrow-right ms-2 text-dark"></i>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </CustomerLayout>
</template>

<style scoped>
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

a.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3) !important;
}
</style>