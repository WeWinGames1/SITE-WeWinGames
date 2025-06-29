<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Line, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    Filler
} from 'chart.js';

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    Filler
);

interface Stats {
    users: {
        total: number;
        active_subscribers: number;
        new_today: number;
        new_this_month: number;
        growth_rate: number;
    };
    revenue: {
        mrr: number;
        this_month: number;
        growth_rate: number;
    };
    bets: {
        total: number;
        today: number;
        active: number;
        win_rate: number;
    };
    content: {
        total_posts: number;
        published: number;
        this_month: number;
        page_views: number;
    };
    discounts: {
        active: number;
        used_this_month: number;
    };
}

interface Activity {
    type: string;
    message: string;
    time: string;
    icon: string;
    color: string;
}

interface Props {
    stats: Stats;
    charts: {
        daily_metrics: Array<{
            date: string;
            users: number;
            revenue: number;
            bets: number;
        }>;
        tier_breakdown: Array<{
            tier: string;
            count: number;
        }>;
    };
    recentActivity: Activity[];
    systemHealth: {
        database: { size: string; status: string };
        storage: { used: string; total: string; percentage: number; status: string };
        queue: { failed: number; pending: number; status: string };
        errors: { today: number; status: string };
    };
}

const props = defineProps<Props>();

// Chart configurations
const lineChartData = computed(() => ({
    labels: props.charts.daily_metrics.map(d => new Date(d.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
    datasets: [
        {
            label: 'New Users',
            data: props.charts.daily_metrics.map(d => d.users),
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            tension: 0.4,
            fill: true,
        },
        {
            label: 'Revenue ($)',
            data: props.charts.daily_metrics.map(d => d.revenue),
            borderColor: 'rgb(16, 185, 129)',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            tension: 0.4,
            fill: true,
            yAxisID: 'y1',
        },
        {
            label: 'Bets',
            data: props.charts.daily_metrics.map(d => d.bets),
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            tension: 0.4,
            fill: true,
        },
    ],
}));

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
        mode: 'index' as const,
        intersect: false,
    },
    plugins: {
        legend: {
            position: 'bottom' as const,
        },
        tooltip: {
            mode: 'index' as const,
            intersect: false,
        },
    },
    scales: {
        y: {
            type: 'linear' as const,
            display: true,
            position: 'left' as const,
            grid: {
                color: 'rgba(156, 163, 175, 0.1)',
            },
        },
        y1: {
            type: 'linear' as const,
            display: true,
            position: 'right' as const,
            grid: {
                drawOnChartArea: false,
            },
        },
    },
};

const doughnutChartData = computed(() => ({
    labels: props.charts.tier_breakdown.map(t => t.tier),
    datasets: [{
        data: props.charts.tier_breakdown.map(t => t.count),
        backgroundColor: [
            'rgb(251, 191, 36)',
            'rgb(156, 163, 175)',
            'rgb(252, 211, 77)',
            'rgb(168, 85, 247)',
        ],
    }],
}));

const doughnutChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'right' as const,
        },
    },
};

// Helper functions
function getStatusBadgeClass(status: string): string {
    switch (status) {
        case 'healthy':
            return 'badge bg-success';
        case 'warning':
            return 'badge bg-warning';
        case 'error':
            return 'badge bg-danger';
        default:
            return 'badge bg-secondary';
    }
}

function getStatusIcon(status: string): string {
    switch (status) {
        case 'healthy':
            return 'bi-check-circle';
        case 'warning':
        case 'error':
            return 'bi-exclamation-triangle';
        default:
            return 'bi-check-circle';
    }
}

function formatNumber(num: number): string {
    return new Intl.NumberFormat('en-US').format(num);
}

function formatCurrency(num: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(num);
}

function formatTime(time: string): string {
    const date = new Date(time);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return 'Just now';
    if (minutes < 60) return `${minutes}m ago`;
    if (hours < 24) return `${hours}h ago`;
    return `${days}d ago`;
}

function getActivityIconColor(color: string): string {
    const colorMap: Record<string, string> = {
        'blue': 'text-primary',
        'green': 'text-success',
        'yellow': 'text-warning',
        'red': 'text-danger',
        'purple': 'text-purple',
        'orange': 'text-orange'
    };
    return colorMap[color] || 'text-secondary';
}
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />
        
        <div class="p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-1">Dashboard Overview</h1>
                <p class="text-muted">Welcome back! Here's what's happening with your platform today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <!-- Users Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="p-3 bg-primary bg-opacity-10 rounded">
                                    <i class="bi bi-people fs-4 text-primary"></i>
                                </div>
                                <div class="d-flex align-items-center small">
                                    <i :class="[stats.users.growth_rate >= 0 ? 'bi-arrow-up-right text-success' : 'bi-arrow-down-right text-danger', 'me-1']"></i>
                                    <span :class="stats.users.growth_rate >= 0 ? 'text-success' : 'text-danger'">
                                        {{ Math.abs(stats.users.growth_rate) }}%
                                    </span>
                                </div>
                            </div>
                            <h3 class="h4 mb-1">{{ formatNumber(stats.users.total) }}</h3>
                            <p class="text-muted small mb-2">Total Users</p>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <span class="fw-medium text-dark">{{ stats.users.active_subscribers }}</span> active subscribers
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="p-3 bg-success bg-opacity-10 rounded">
                                    <i class="bi bi-cash-stack fs-4 text-success"></i>
                                </div>
                                <div class="d-flex align-items-center small">
                                    <i :class="[stats.revenue.growth_rate >= 0 ? 'bi-arrow-up-right text-success' : 'bi-arrow-down-right text-danger', 'me-1']"></i>
                                    <span :class="stats.revenue.growth_rate >= 0 ? 'text-success' : 'text-danger'">
                                        {{ Math.abs(stats.revenue.growth_rate) }}%
                                    </span>
                                </div>
                            </div>
                            <h3 class="h4 mb-1">{{ formatCurrency(stats.revenue.mrr) }}</h3>
                            <p class="text-muted small mb-2">Monthly Recurring Revenue</p>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <span class="fw-medium text-dark">{{ formatCurrency(stats.revenue.this_month) }}</span> this month
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bets Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="p-3 bg-purple bg-opacity-10 rounded" style="background-color: rgba(168, 85, 247, 0.1);">
                                    <i class="bi bi-bar-chart fs-4" style="color: rgb(168, 85, 247);"></i>
                                </div>
                                <div class="small fw-medium" style="color: rgb(168, 85, 247);">
                                    {{ stats.bets.win_rate }}% Win
                                </div>
                            </div>
                            <h3 class="h4 mb-1">{{ formatNumber(stats.bets.total) }}</h3>
                            <p class="text-muted small mb-2">Total Bets</p>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <span class="fw-medium text-dark">{{ stats.bets.today }}</span> today • 
                                <span class="fw-medium text-dark">{{ stats.bets.active }}</span> active
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Content Card -->
                <div class="col-lg-3 col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="p-3 bg-orange bg-opacity-10 rounded" style="background-color: rgba(251, 146, 60, 0.1);">
                                    <i class="bi bi-file-text fs-4" style="color: rgb(251, 146, 60);"></i>
                                </div>
                                <div class="small fw-medium" style="color: rgb(251, 146, 60);">
                                    {{ formatNumber(stats.content.page_views) }} views
                                </div>
                            </div>
                            <h3 class="h4 mb-1">{{ stats.content.published }}</h3>
                            <p class="text-muted small mb-2">Published Posts</p>
                            <div class="text-muted" style="font-size: 0.75rem;">
                                <span class="fw-medium text-dark">{{ stats.content.this_month }}</span> this month
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="row g-4 mb-4">
                <!-- Activity Chart -->
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Platform Activity</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <Line :data="lineChartData" :options="lineChartOptions" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tier Breakdown -->
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Subscription Tiers</h5>
                        </div>
                        <div class="card-body">
                            <div style="height: 300px;">
                                <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="row g-4">
                <!-- Recent Activity -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Recent Activity</h5>
                        </div>
                        <div class="card-body">
                            <div class="overflow-auto" style="max-height: 400px;">
                                <div v-for="(activity, index) in recentActivity" :key="index" class="d-flex align-items-start mb-3">
                                    <div :class="`p-2 rounded bg-${activity.color} bg-opacity-10 me-3`">
                                        <i :class="[`bi bi-${activity.icon}`, getActivityIconColor(activity.color), 'fs-6']"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <p class="mb-1 small">{{ activity.message }}</p>
                                        <div class="d-flex align-items-center text-muted" style="font-size: 0.75rem;">
                                            <i class="bi bi-clock me-1"></i>
                                            {{ formatTime(activity.time) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">System Health</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <!-- Database -->
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-database me-3 text-muted fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-medium">Database</p>
                                            <small class="text-muted">{{ systemHealth.database.size }}</small>
                                        </div>
                                    </div>
                                    <span :class="getStatusBadgeClass(systemHealth.database.status)">
                                        <i :class="[getStatusIcon(systemHealth.database.status), 'me-1']"></i>
                                        {{ systemHealth.database.status }}
                                    </span>
                                </div>

                                <!-- Storage -->
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-hdd me-3 text-muted fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-medium">Storage</p>
                                            <small class="text-muted">{{ systemHealth.storage.used }} / {{ systemHealth.storage.total }}</small>
                                        </div>
                                    </div>
                                    <span :class="getStatusBadgeClass(systemHealth.storage.status)">
                                        <i :class="[getStatusIcon(systemHealth.storage.status), 'me-1']"></i>
                                        {{ systemHealth.storage.percentage }}%
                                    </span>
                                </div>

                                <!-- Queue -->
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-list-task me-3 text-muted fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-medium">Queue</p>
                                            <small class="text-muted">{{ systemHealth.queue.pending }} pending, {{ systemHealth.queue.failed }} failed</small>
                                        </div>
                                    </div>
                                    <span :class="getStatusBadgeClass(systemHealth.queue.status)">
                                        <i :class="[getStatusIcon(systemHealth.queue.status), 'me-1']"></i>
                                        {{ systemHealth.queue.status }}
                                    </span>
                                </div>

                                <!-- Errors -->
                                <div class="list-group-item px-0 d-flex justify-content-between align-items-center border-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-exclamation-triangle me-3 text-muted fs-5"></i>
                                        <div>
                                            <p class="mb-0 fw-medium">Error Logs</p>
                                            <small class="text-muted">{{ systemHealth.errors.today }} errors today</small>
                                        </div>
                                    </div>
                                    <span :class="getStatusBadgeClass(systemHealth.errors.status)">
                                        <i :class="[getStatusIcon(systemHealth.errors.status), 'me-1']"></i>
                                        {{ systemHealth.errors.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.text-purple {
    color: rgb(168, 85, 247);
}

.text-orange {
    color: rgb(251, 146, 60);
}

.bg-purple {
    background-color: rgb(168, 85, 247);
}

.bg-orange {
    background-color: rgb(251, 146, 60);
}
</style>