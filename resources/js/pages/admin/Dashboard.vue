<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    UsersIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    DocumentTextIcon,
    TicketIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
    ServerIcon,
    CircleStackIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
    UserPlusIcon,
    ClockIcon
} from '@heroicons/vue/24/outline';
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
function getStatusColor(status: string): string {
    switch (status) {
        case 'healthy':
            return 'text-green-600 bg-green-100';
        case 'warning':
            return 'text-yellow-600 bg-yellow-100';
        case 'error':
            return 'text-red-600 bg-red-100';
        default:
            return 'text-gray-600 bg-gray-100';
    }
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'healthy':
            return CheckCircleIcon;
        case 'warning':
        case 'error':
            return ExclamationTriangleIcon;
        default:
            return CheckCircleIcon;
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
</script>

<template>
    <AdminLayout>
        <Head title="Admin Dashboard" />
        
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Dashboard Overview</h1>
                <p class="text-gray-600 mt-2">Welcome back! Here's what's happening with your platform today.</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Users Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <UsersIcon class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="flex items-center text-sm">
                            <component 
                                :is="stats.users.growth_rate >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                :class="stats.users.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'"
                                class="h-4 w-4 mr-1"
                            />
                            <span :class="stats.users.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ Math.abs(stats.users.growth_rate) }}%
                            </span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.users.total) }}</h3>
                    <p class="text-sm text-gray-600">Total Users</p>
                    <div class="mt-3 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">{{ stats.users.active_subscribers }}</span> active subscribers
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-green-100 rounded-lg">
                            <CurrencyDollarIcon class="h-6 w-6 text-green-600" />
                        </div>
                        <div class="flex items-center text-sm">
                            <component 
                                :is="stats.revenue.growth_rate >= 0 ? ArrowTrendingUpIcon : ArrowTrendingDownIcon"
                                :class="stats.revenue.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'"
                                class="h-4 w-4 mr-1"
                            />
                            <span :class="stats.revenue.growth_rate >= 0 ? 'text-green-600' : 'text-red-600'">
                                {{ Math.abs(stats.revenue.growth_rate) }}%
                            </span>
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatCurrency(stats.revenue.mrr) }}</h3>
                    <p class="text-sm text-gray-600">Monthly Recurring Revenue</p>
                    <div class="mt-3 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">{{ formatCurrency(stats.revenue.this_month) }}</span> this month
                    </div>
                </div>

                <!-- Bets Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-100 rounded-lg">
                            <ChartBarIcon class="h-6 w-6 text-purple-600" />
                        </div>
                        <div class="text-sm font-medium text-purple-600">
                            {{ stats.bets.win_rate }}% Win
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ formatNumber(stats.bets.total) }}</h3>
                    <p class="text-sm text-gray-600">Total Bets</p>
                    <div class="mt-3 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">{{ stats.bets.today }}</span> today • 
                        <span class="font-medium text-gray-700">{{ stats.bets.active }}</span> active
                    </div>
                </div>

                <!-- Content Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-orange-100 rounded-lg">
                            <DocumentTextIcon class="h-6 w-6 text-orange-600" />
                        </div>
                        <div class="text-sm font-medium text-orange-600">
                            {{ formatNumber(stats.content.page_views) }} views
                        </div>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900">{{ stats.content.published }}</h3>
                    <p class="text-sm text-gray-600">Published Posts</p>
                    <div class="mt-3 text-xs text-gray-500">
                        <span class="font-medium text-gray-700">{{ stats.content.this_month }}</span> this month
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Activity Chart -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Platform Activity</h2>
                    <div class="h-64">
                        <Line :data="lineChartData" :options="lineChartOptions" />
                    </div>
                </div>

                <!-- Tier Breakdown -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Tiers</h2>
                    <div class="h-64">
                        <Doughnut :data="doughnutChartData" :options="doughnutChartOptions" />
                    </div>
                </div>
            </div>

            <!-- Bottom Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Activity -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <div v-for="(activity, index) in recentActivity" :key="index" class="flex items-start space-x-3">
                            <div :class="`p-2 rounded-lg bg-${activity.color}-100`">
                                <component :is="activity.icon" :class="`h-4 w-4 text-${activity.color}-600`" />
                            </div>
                            <div class="flex-1">
                                <p class="text-sm text-gray-900">{{ activity.message }}</p>
                                <p class="text-xs text-gray-500 flex items-center mt-1">
                                    <ClockIcon class="h-3 w-3 mr-1" />
                                    {{ formatTime(activity.time) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">System Health</h2>
                    <div class="space-y-4">
                        <!-- Database -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <CircleStackIcon class="h-5 w-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Database</p>
                                    <p class="text-xs text-gray-500">{{ systemHealth.database.size }}</p>
                                </div>
                            </div>
                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(systemHealth.database.status)}`">
                                <component :is="getStatusIcon(systemHealth.database.status)" class="h-3 w-3 mr-1" />
                                {{ systemHealth.database.status }}
                            </span>
                        </div>

                        <!-- Storage -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <ServerIcon class="h-5 w-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Storage</p>
                                    <p class="text-xs text-gray-500">{{ systemHealth.storage.used }} / {{ systemHealth.storage.total }}</p>
                                </div>
                            </div>
                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(systemHealth.storage.status)}`">
                                <component :is="getStatusIcon(systemHealth.storage.status)" class="h-3 w-3 mr-1" />
                                {{ systemHealth.storage.percentage }}%
                            </span>
                        </div>

                        <!-- Queue -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="h-5 w-5 text-gray-400 mr-3">
                                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Queue</p>
                                    <p class="text-xs text-gray-500">{{ systemHealth.queue.pending }} pending, {{ systemHealth.queue.failed }} failed</p>
                                </div>
                            </div>
                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(systemHealth.queue.status)}`">
                                <component :is="getStatusIcon(systemHealth.queue.status)" class="h-3 w-3 mr-1" />
                                {{ systemHealth.queue.status }}
                            </span>
                        </div>

                        <!-- Errors -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <ExclamationTriangleIcon class="h-5 w-5 text-gray-400 mr-3" />
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Error Logs</p>
                                    <p class="text-xs text-gray-500">{{ systemHealth.errors.today }} errors today</p>
                                </div>
                            </div>
                            <span :class="`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getStatusColor(systemHealth.errors.status)}`">
                                <component :is="getStatusIcon(systemHealth.errors.status)" class="h-3 w-3 mr-1" />
                                {{ systemHealth.errors.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>