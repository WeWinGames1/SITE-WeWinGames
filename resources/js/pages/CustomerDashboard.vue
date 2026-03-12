<script setup lang="ts">
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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
    discord?: {
        connected: boolean;
        username: string | null;
        avatarUrl: string | null;
        connectedAt: string | null;
        rolesSynced: string[];
        inviteUrl: string | null;
        isConfigured: boolean;
    };
}>();

const user = page.props.auth.user.data;
const isSyncingRoles = ref(false);
const isDisconnecting = ref(false);

// Get subscription badge info
const subscriptionBadge = computed(() => {
    switch (props.subscriptionTier) {
        case 'platinum':
            return { text: 'Platinum', class: 'bg-purple' };
        case 'gold':
            return { text: 'Gold', class: 'bg-warning text-dark' };
        default:
            return { text: 'Free', class: 'bg-success' };
    }
});

// Get Discord role display based on subscription tier
const discordRoleDisplay = computed(() => {
    const roles = [];
    roles.push({ name: 'Free', class: 'bg-success' });

    if (props.subscriptionTier === 'gold' || props.subscriptionTier === 'platinum') {
        roles.push({ name: 'Gold', class: 'bg-warning text-dark' });
    }

    if (props.subscriptionTier === 'platinum') {
        roles.push({ name: 'Platinum', class: 'bg-purple' });
    }

    return roles;
});

// Connect Discord
const connectDiscord = () => {
    window.location.href = route('discord.redirect');
};

// Disconnect Discord
const disconnectDiscord = () => {
    if (!confirm('Are you sure you want to disconnect your Discord account? Your roles will be removed from the server.')) {
        return;
    }

    isDisconnecting.value = true;
    router.post(
        route('discord.disconnect'),
        {},
        {
            onFinish: () => {
                isDisconnecting.value = false;
            },
        },
    );
};

// Sync Discord roles
const syncDiscordRoles = () => {
    isSyncingRoles.value = true;
    router.post(
        route('discord.sync-roles'),
        {},
        {
            onFinish: () => {
                isSyncingRoles.value = false;
            },
        },
    );
};
</script>

<template>
    <CustomerLayout>
        <Head title="Dashboard" />

        <div class="min-vh-100" style="background-color: var(--bs-body-bg)">
            <!-- Welcome Section -->
            <section class="py-5" style="background: linear-gradient(135deg, #7c3aed 0%, #111827 100%)">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 fw-bold text-white mb-3">Welcome back, {{ user.name }}!</h1>
                            <p class="fs-5 text-gray-light mb-4">Track your betting performance and access today's expert picks</p>
                            <div class="d-flex align-items-center gap-3">
                                <span :class="subscriptionBadge.class" class="badge fs-6 px-3 py-2"> {{ subscriptionBadge.text }} Member </span>
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

            <!-- Dashboard Content -->
            <section class="py-5">
                <div class="container">
                    <!-- Discord Community Section -->
                    <div class="row g-4 mb-5">
                        <div class="col-12 d-none d-md-block">
                            <h2 class="h4 fw-bold text-white mb-4">
                                <i class="bi bi-discord me-2" style="color: #5865f2"></i>
                                Discord Community
                            </h2>
                        </div>
                        <div class="col-12">
                            <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                                <div class="card-body p-4">
                                    <div v-if="!props.discord?.connected" class="text-center py-4">
                                        <!-- Not Connected State -->
                                        <div class="mb-4">
                                            <i class="bi bi-discord display-1" style="color: #5865f2"></i>
                                        </div>
                                        <h5 class="text-white mb-3">Connect Your Discord Account</h5>
                                        <p class="text-gray-light mb-4" style="max-width: 500px; margin: 0 auto">
                                            Join our exclusive Discord community! Connect your account to get roles based on your subscription tier
                                            and access member-only channels.
                                        </p>
                                        <button @click="connectDiscord" class="btn btn-lg px-5" style="background-color: #5865f2; color: white">
                                            <i class="bi bi-discord me-2"></i>
                                            Connect Discord
                                        </button>
                                    </div>

                                    <div v-else>
                                        <!-- No Subscription Warning -->
                                        <div v-if="!props.hasActiveSubscription" class="alert alert-warning mb-4">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                                                <div>
                                                    <strong>No Active Subscription</strong>
                                                    <p class="mb-0 small">
                                                        Your Discord roles have been removed because you don't have an active subscription.
                                                        <Link href="/buy-our-picks" class="alert-link">Subscribe now</Link> to regain access to
                                                        exclusive channels.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row align-items-center">
                                            <!-- Connected State -->
                                            <div class="col-md-6">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="position-relative">
                                                        <img
                                                            v-if="props.discord.avatarUrl"
                                                            :src="props.discord.avatarUrl"
                                                            alt="Discord Avatar"
                                                            class="rounded-circle"
                                                            style="width: 64px; height: 64px"
                                                        />
                                                        <div
                                                            v-else
                                                            class="rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 64px; height: 64px; background-color: #5865f2"
                                                        >
                                                            <i class="bi bi-discord text-white fs-3"></i>
                                                        </div>
                                                        <span
                                                            class="position-absolute bottom-0 end-0 rounded-circle"
                                                            :class="props.hasActiveSubscription ? 'bg-success' : 'bg-warning'"
                                                            style="width: 16px; height: 16px; border: 2px solid var(--bs-card-bg)"
                                                        ></span>
                                                    </div>
                                                    <div>
                                                        <h5 class="text-white mb-1">{{ props.discord.username }}</h5>
                                                        <span class="badge bg-success me-2">
                                                            <i class="bi bi-check-circle me-1"></i>
                                                            Connected
                                                        </span>
                                                        <small class="text-gray-light">
                                                            since
                                                            {{
                                                                props.discord.connectedAt
                                                                    ? new Date(props.discord.connectedAt).toLocaleDateString()
                                                                    : 'recently'
                                                            }}
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <span class="text-gray-light small me-2">Your Roles:</span>
                                                    <template v-if="props.hasActiveSubscription">
                                                        <span
                                                            v-for="role in discordRoleDisplay"
                                                            :key="role.name"
                                                            :class="role.class"
                                                            class="badge me-1"
                                                        >
                                                            {{ role.name }}
                                                        </span>
                                                    </template>
                                                    <span v-else class="badge bg-secondary">
                                                        <i class="bi bi-x-circle me-1"></i>
                                                        No roles (inactive subscription)
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Next Steps Info - Only show if not yet synced roles -->
                                            <div v-if="!props.discord.rolesSynced || props.discord.rolesSynced.length === 0" class="col-12 mt-3">
                                                <div
                                                    class="alert mb-0"
                                                    style="background-color: rgba(88, 101, 242, 0.15); border-color: #5865f2; color: #fff"
                                                >
                                                    <i class="bi bi-arrow-right-circle me-2" style="color: #5865f2"></i>
                                                    <strong>Next:</strong> Click <strong>"Join Discord Server"</strong> to join our community, then
                                                    <strong>"Sync Roles"</strong> to get your subscription-based roles.
                                                </div>
                                            </div>

                                            <div class="col-md-6 text-md-end mt-4 mt-md-0">
                                                <div class="d-flex flex-column flex-md-row gap-2 justify-content-md-end">
                                                    <a
                                                        v-if="props.discord.inviteUrl"
                                                        :href="props.discord.inviteUrl"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="btn btn-lg"
                                                        style="background-color: #5865f2; color: white"
                                                    >
                                                        <i class="bi bi-box-arrow-up-right me-2"></i>
                                                        Join Discord Server
                                                    </a>
                                                    <button @click="syncDiscordRoles" :disabled="isSyncingRoles" class="btn btn-outline-light">
                                                        <i v-if="isSyncingRoles" class="bi bi-arrow-repeat spin me-2"></i>
                                                        <i v-else class="bi bi-arrow-repeat me-2"></i>
                                                        {{ isSyncingRoles ? 'Syncing...' : 'Sync Roles' }}
                                                    </button>
                                                    <button @click="disconnectDiscord" :disabled="isDisconnecting" class="btn btn-outline-danger">
                                                        <i class="bi bi-x-circle me-2"></i>
                                                        Disconnect
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="row g-4 mb-5">
                        <!-- Win Rate Card -->
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
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
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
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
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
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
                            <div class="card h-100" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
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
                            <Link
                                href="/todays-bets"
                                class="card h-100 text-decoration-none"
                                style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)"
                            >
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-lightning-charge text-purple fs-1 mb-3"></i>
                                    <h5 class="text-white">Today's Picks</h5>
                                    <p class="text-gray-light small mb-0">View all available picks</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link
                                href="/betting-results"
                                class="card h-100 text-decoration-none"
                                style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)"
                            >
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-trophy text-warning fs-1 mb-3"></i>
                                    <h5 class="text-white">Results</h5>
                                    <p class="text-gray-light small mb-0">Check past performance</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link
                                href="/settings/billing"
                                class="card h-100 text-decoration-none"
                                style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)"
                            >
                                <div class="card-body p-4 text-center">
                                    <i class="bi bi-credit-card text-info fs-1 mb-3"></i>
                                    <h5 class="text-white">Billing</h5>
                                    <p class="text-gray-light small mb-0">Manage subscription</p>
                                </div>
                            </Link>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <Link
                                href="/support/tickets"
                                class="card h-100 text-decoration-none"
                                style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)"
                            >
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
                            <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
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
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
}

a.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3) !important;
}

/* Discord button hover */
.btn[style*='background-color: #5865f2']:hover {
    background-color: #4752c4 !important;
}

/* Spin animation for sync button */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.spin {
    animation: spin 1s linear infinite;
}
</style>
