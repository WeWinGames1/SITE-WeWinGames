<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Affiliate {
    id: number;
    name: string;
    code: string;
}

interface AffiliatePerformance {
    id: number;
    name: string;
    code: string;
    total_customers: number;
    active_subscribers: number;
    cancelled_customers: number;
    cancellation_rate: number;
    conversion_rate: number;
}

interface DiscountCodePerformance {
    id: number;
    code: string;
    description: string;
    discount_type: string;
    discount_amount: number;
    total_redemptions: number;
    unique_users: number;
    active_users: number;
    cancelled_users: number;
    retention_rate: number;
    cancellation_rate: number;
}

interface Metrics {
    total_customers: number;
    active_subscribers: number;
    cancelled_count: number;
    cancellation_rate: number;
    resubscribe_count: number;
    resubscribe_rate: number;
    upgrade_count: number;
    upgrade_rate: number;
    downgrade_count: number;
    downgrade_rate: number;
    avg_subscription_duration_days: number;
    total_revenue: number;
    avg_ltv: number;
}

interface TierTrends {
    distribution: Record<string, number>;
    total: number;
}

interface Filters {
    affiliate_id: number | null;
    discount_code: string | null;
    date_from: string | null;
    date_to: string | null;
}

interface Props {
    metrics: Metrics;
    affiliatePerformance: AffiliatePerformance[];
    discountCodePerformance: DiscountCodePerformance[];
    tierTrends: TierTrends;
    affiliates: Affiliate[];
    discountCodes: string[];
    filters: Filters;
}

const props = defineProps<Props>();

// Local filter state
const localFilters = ref({
    affiliate_id: props.filters.affiliate_id ?? '',
    discount_code: props.filters.discount_code ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const isLoading = ref(false);

// Apply filters
const applyFilters = () => {
    isLoading.value = true;
    router.get(
        '/admin/reports/customer-value',
        {
            affiliate_id: localFilters.value.affiliate_id || undefined,
            discount_code: localFilters.value.discount_code || undefined,
            date_from: localFilters.value.date_from || undefined,
            date_to: localFilters.value.date_to || undefined,
        },
        {
            preserveState: true,
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

// Reset filters
const resetFilters = () => {
    localFilters.value = {
        affiliate_id: '',
        discount_code: '',
        date_from: '',
        date_to: '',
    };
    applyFilters();
};

// Format currency
const formatCurrency = (value: number): string => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(value);
};

// Format percentage
const formatPercent = (value: number): string => {
    return `${value}%`;
};

// Get rate class based on value
const getRateClass = (rate: number, isGood: boolean = false): string => {
    if (isGood) {
        if (rate >= 50) return 'text-success';
        if (rate >= 25) return 'text-warning';
        return 'text-danger';
    } else {
        if (rate <= 10) return 'text-success';
        if (rate <= 25) return 'text-warning';
        return 'text-danger';
    }
};
</script>

<template>
    <Head title="Customer Value Reports" />

    <AdminLayout>
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="bi bi-graph-up-arrow text-primary me-2"></i>
                        Customer Value Reports
                    </h1>
                    <p class="text-muted mb-0">Analyze customer retention, upgrades, and cancellations by source</p>
                </div>
            </div>

            <!-- Filters Card -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2"></i>
                        Filters
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Affiliate</label>
                            <select v-model="localFilters.affiliate_id" class="form-select">
                                <option value="">All Affiliates</option>
                                <option v-for="affiliate in affiliates" :key="affiliate.id" :value="affiliate.id">
                                    {{ affiliate.name }} ({{ affiliate.code }})
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Discount Code</label>
                            <select v-model="localFilters.discount_code" class="form-select">
                                <option value="">All Discount Codes</option>
                                <option v-for="code in discountCodes" :key="code" :value="code">
                                    {{ code }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date From</label>
                            <input v-model="localFilters.date_from" type="date" class="form-control" />
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Date To</label>
                            <input v-model="localFilters.date_to" type="date" class="form-control" />
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button @click="applyFilters" class="btn btn-primary" :disabled="isLoading">
                                <span v-if="isLoading" class="spinner-border spinner-border-sm me-1"></span>
                                <i v-else class="bi bi-search me-1"></i>
                                Apply
                            </button>
                            <button @click="resetFilters" class="btn btn-outline-secondary" :disabled="isLoading">
                                <i class="bi bi-x-circle me-1"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics Cards -->
            <div class="row g-4 mb-4">
                <!-- Total Customers -->
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-people-fill text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Total Customers</h6>
                                    <h3 class="mb-0">{{ metrics.total_customers.toLocaleString() }}</h3>
                                    <small class="text-muted">{{ metrics.active_subscribers }} active subscribers</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cancellation Rate -->
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-x-circle-fill text-danger fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Cancellation Rate</h6>
                                    <h3 class="mb-0" :class="getRateClass(metrics.cancellation_rate)">
                                        {{ formatPercent(metrics.cancellation_rate) }}
                                    </h3>
                                    <small class="text-muted">{{ metrics.cancelled_count }} cancelled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resubscribe Rate -->
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-arrow-repeat text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Resubscribe Rate</h6>
                                    <h3 class="mb-0" :class="getRateClass(metrics.resubscribe_rate, true)">
                                        {{ formatPercent(metrics.resubscribe_rate) }}
                                    </h3>
                                    <small class="text-muted">{{ metrics.resubscribe_count }} resubscribed</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upgrade Rate -->
                <div class="col-xl-3 col-md-6">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                        <i class="bi bi-arrow-up-circle-fill text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="text-muted mb-1">Upgrade Rate</h6>
                                    <h3 class="mb-0" :class="getRateClass(metrics.upgrade_rate, true)">
                                        {{ formatPercent(metrics.upgrade_rate) }}
                                    </h3>
                                    <small class="text-muted">{{ metrics.upgrade_count }} upgraded</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Metrics -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check text-info fs-1 mb-2"></i>
                            <h4>{{ metrics.avg_subscription_duration_days }} days</h4>
                            <p class="text-muted mb-0">Avg. Subscription Duration</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-currency-dollar text-success fs-1 mb-2"></i>
                            <h4>{{ formatCurrency(metrics.total_revenue) }}</h4>
                            <p class="text-muted mb-0">Monthly Revenue (MRR)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-person-heart text-primary fs-1 mb-2"></i>
                            <h4>{{ formatCurrency(metrics.avg_ltv) }}</h4>
                            <p class="text-muted mb-0">Avg. Customer LTV</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tier Distribution & Downgrade Info -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-pie-chart me-2"></i>
                                Tier Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div v-for="(count, tier) in tierTrends.distribution" :key="tier" class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold">{{ tier }}</span>
                                    <span>{{ count }} ({{ tierTrends.total > 0 ? Math.round((count / tierTrends.total) * 100) : 0 }}%)</span>
                                </div>
                                <div class="progress" style="height: 20px">
                                    <div
                                        class="progress-bar"
                                        :class="{
                                            'bg-success': tier === 'Free',
                                            'bg-warning': tier === 'Gold',
                                            'bg-purple': tier === 'Platinum',
                                        }"
                                        :style="{ width: tierTrends.total > 0 ? `${(count / tierTrends.total) * 100}%` : '0%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-arrow-down-up me-2"></i>
                                Tier Movement Summary
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-around text-center">
                                <div>
                                    <div class="display-6 text-success">
                                        <i class="bi bi-arrow-up-circle-fill"></i>
                                    </div>
                                    <h3 class="mb-1">{{ metrics.upgrade_count }}</h3>
                                    <p class="text-muted mb-0">Upgrades</p>
                                    <small class="text-success">{{ formatPercent(metrics.upgrade_rate) }}</small>
                                </div>
                                <div>
                                    <div class="display-6 text-danger">
                                        <i class="bi bi-arrow-down-circle-fill"></i>
                                    </div>
                                    <h3 class="mb-1">{{ metrics.downgrade_count }}</h3>
                                    <p class="text-muted mb-0">Downgrades</p>
                                    <small class="text-danger">{{ formatPercent(metrics.downgrade_rate) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Tables -->
            <div class="row g-4">
                <!-- Affiliate Performance -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-link-45deg me-2"></i>
                                Top Affiliates Performance
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Affiliate</th>
                                            <th class="text-center">Customers</th>
                                            <th class="text-center">Active</th>
                                            <th class="text-center">Cancel Rate</th>
                                            <th class="text-center">Conversion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="affiliate in affiliatePerformance" :key="affiliate.id">
                                            <td>
                                                <strong>{{ affiliate.name }}</strong>
                                                <br />
                                                <small class="text-muted">{{ affiliate.code }}</small>
                                            </td>
                                            <td class="text-center">{{ affiliate.total_customers }}</td>
                                            <td class="text-center">{{ affiliate.active_subscribers }}</td>
                                            <td class="text-center" :class="getRateClass(affiliate.cancellation_rate)">
                                                {{ formatPercent(affiliate.cancellation_rate) }}
                                            </td>
                                            <td class="text-center" :class="getRateClass(affiliate.conversion_rate, true)">
                                                {{ formatPercent(affiliate.conversion_rate) }}
                                            </td>
                                        </tr>
                                        <tr v-if="affiliatePerformance.length === 0">
                                            <td colspan="5" class="text-center text-muted py-4">No affiliate data available</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Discount Code Performance -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-tag me-2"></i>
                                Top Discount Codes Performance
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th class="text-center">Redemptions</th>
                                            <th class="text-center">Active</th>
                                            <th class="text-center">Retention</th>
                                            <th class="text-center">Cancel Rate</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="code in discountCodePerformance" :key="code.id">
                                            <td>
                                                <strong>{{ code.code }}</strong>
                                                <br />
                                                <small class="text-muted">
                                                    {{
                                                        code.discount_type === 'percentage' ? `${code.discount_amount}%` : `$${code.discount_amount}`
                                                    }}
                                                    off
                                                </small>
                                            </td>
                                            <td class="text-center">{{ code.total_redemptions }}</td>
                                            <td class="text-center">{{ code.active_users }}</td>
                                            <td class="text-center" :class="getRateClass(code.retention_rate, true)">
                                                {{ formatPercent(code.retention_rate) }}
                                            </td>
                                            <td class="text-center" :class="getRateClass(code.cancellation_rate)">
                                                {{ formatPercent(code.cancellation_rate) }}
                                            </td>
                                        </tr>
                                        <tr v-if="discountCodePerformance.length === 0">
                                            <td colspan="5" class="text-center text-muted py-4">No discount code data available</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.bg-purple {
    background-color: #6f42c1 !important;
}

.progress-bar.bg-purple {
    background-color: #6f42c1;
}
</style>
