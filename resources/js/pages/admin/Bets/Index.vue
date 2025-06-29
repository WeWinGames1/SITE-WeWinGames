<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    PlusIcon,
    PencilIcon,
    TrashIcon,
    CheckIcon,
    XMarkIcon,
    BanknotesIcon,
    TrophyIcon,
    ChartBarIcon,
    CalendarIcon
} from '@heroicons/vue/24/outline';

interface Bet {
    id: number;
    user: {
        id: number;
        name: string;
        email: string;
    };
    sport: {
        id: number;
        name: string;
    };
    game?: {
        id: number;
        home_team: string;
        away_team: string;
    };
    operator: {
        id: number;
        name: string;
    };
    selection: string;
    description?: string;
    bet_type: string;
    odds: number;
    stake: number;
    potential_win: number;
    game_at: string;
    status: 'pending' | 'won' | 'lost' | 'push' | 'cancelled';
    actual_result?: string;
    profit?: number;
    is_featured: boolean;
    confidence?: number;
    created_at: string;
    updated_at: string;
}

interface Sport {
    id: number;
    name: string;
}

interface Props {
    bets: {
        data: Bet[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        status?: string;
        sport_id?: number;
        user_id?: number;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
    sports: Sport[];
    statuses: string[];
}

const props = defineProps<Props>();

// Form for filters
const filterForm = useForm({
    status: props.filters.status || '',
    sport_id: props.filters.sport_id || '',
    user_id: props.filters.user_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
});

const showFilters = ref(false);
const selectedBets = ref<number[]>([]);
const bulkStatusForm = useForm({
    bet_ids: [] as number[],
    status: '',
});

// Watch for filter changes and submit
watch(() => filterForm.data(), () => {
    filterForm.get(route('admin.bets.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}, { deep: true });

// Computed properties
const allSelected = computed(() => 
    props.bets.data.length > 0 && selectedBets.value.length === props.bets.data.length
);

const someSelected = computed(() => 
    selectedBets.value.length > 0 && selectedBets.value.length < props.bets.data.length
);

// Methods
function toggleAll() {
    if (allSelected.value) {
        selectedBets.value = [];
    } else {
        selectedBets.value = props.bets.data.map(bet => bet.id);
    }
}

function toggleBet(betId: number) {
    const index = selectedBets.value.indexOf(betId);
    if (index > -1) {
        selectedBets.value.splice(index, 1);
    } else {
        selectedBets.value.push(betId);
    }
}

function clearFilters() {
    filterForm.reset();
}

function deleteBet(bet: Bet) {
    if (confirm(`Are you sure you want to delete this bet?`)) {
        router.delete(route('admin.bets.destroy', bet.id), {
            preserveScroll: true,
        });
    }
}

function bulkUpdateStatus() {
    if (!bulkStatusForm.status) {
        alert('Please select a status');
        return;
    }
    
    bulkStatusForm.bet_ids = selectedBets.value;
    bulkStatusForm.post(route('admin.bets.bulk-update-status'), {
        onSuccess: () => {
            selectedBets.value = [];
            bulkStatusForm.reset();
        },
    });
}

function exportBets() {
    window.location.href = route('admin.bets.export') + '?' + new URLSearchParams(filterForm.data() as any);
}

function getStatusColor(status: string): string {
    switch (status) {
        case 'won':
            return 'bg-green-100 text-green-800';
        case 'lost':
            return 'bg-red-100 text-red-800';
        case 'push':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-blue-100 text-blue-800';
        case 'cancelled':
            return 'bg-gray-100 text-gray-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}

function getStatusIcon(status: string) {
    switch (status) {
        case 'won':
            return CheckIcon;
        case 'lost':
            return XMarkIcon;
        default:
            return null;
    }
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
}

function formatOdds(odds: number): string {
    if (odds > 0) {
        return `+${odds}`;
    }
    return odds.toString();
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}
</script>

<template>
    <AdminLayout>
        <Head title="Bet Management" />
        
        <div class="p-6">
            <!-- Header -->
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Bet Management</h1>
                    <p class="mt-2 text-sm text-gray-700">
                        Manage all betting picks and predictions
                    </p>
                </div>
                <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none space-x-3">
                    <button
                        @click="exportBets"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <ArrowDownTrayIcon class="h-4 w-4 mr-2" />
                        Export
                    </button>
                    <Link
                        :href="route('admin.bets.create')"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                    >
                        <PlusIcon class="h-4 w-4 mr-2" />
                        Add Bet
                    </Link>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 space-y-4">
                <!-- Search Bar -->
                <div class="flex gap-4">
                    <div class="flex-1 relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            v-model="filterForm.search"
                            type="search"
                            placeholder="Search bets, users, or descriptions..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        />
                    </div>
                    <button
                        @click="showFilters = !showFilters"
                        type="button"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        <FunnelIcon class="h-4 w-4 mr-2" />
                        Filters
                        <span v-if="Object.values(filterForm.data()).some(v => v)" class="ml-2 bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full text-xs">
                            Active
                        </span>
                    </button>
                </div>

                <!-- Filter Panel -->
                <div v-if="showFilters" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select
                                v-model="filterForm.status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Statuses</option>
                                <option v-for="status in statuses" :key="status" :value="status">
                                    {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
                            <select
                                v-model="filterForm.sport_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">All Sports</option>
                                <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                    {{ sport.name }}
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                            <input
                                v-model="filterForm.date_from"
                                type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                            <input
                                v-model="filterForm.date_to"
                                type="date"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button
                            @click="clearFilters"
                            type="button"
                            class="text-sm text-gray-600 hover:text-gray-900"
                        >
                            Clear all filters
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedBets.length > 0" class="mb-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-blue-800">
                        {{ selectedBets.length }} bet{{ selectedBets.length === 1 ? '' : 's' }} selected
                    </p>
                    <div class="flex items-center gap-3">
                        <select
                            v-model="bulkStatusForm.status"
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="">Select status...</option>
                            <option value="won">Mark as Won</option>
                            <option value="lost">Mark as Lost</option>
                            <option value="push">Mark as Push</option>
                            <option value="cancelled">Mark as Cancelled</option>
                        </select>
                        <button
                            @click="bulkUpdateStatus"
                            :disabled="!bulkStatusForm.status || bulkStatusForm.processing"
                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Update Status
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bets Table -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="relative px-6 py-3">
                                <input
                                    type="checkbox"
                                    :checked="allSelected"
                                    :indeterminate="someSelected"
                                    @change="toggleAll"
                                    class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User / Selection
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sport / Game
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Type / Odds
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Stake / Win
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Game Date
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="bet in bets.data" :key="bet.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input
                                    type="checkbox"
                                    :checked="selectedBets.includes(bet.id)"
                                    @change="toggleBet(bet.id)"
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ bet.user.name }}</div>
                                    <div class="text-sm text-gray-500">{{ bet.user.email }}</div>
                                    <div class="mt-1 text-sm text-gray-900">{{ bet.selection }}</div>
                                    <div v-if="bet.is_featured" class="mt-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <TrophyIcon class="h-3 w-3 mr-1" />
                                            Featured
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ bet.sport.name }}</div>
                                    <div v-if="bet.game" class="text-sm text-gray-500">
                                        {{ bet.game.away_team }} @ {{ bet.game.home_team }}
                                    </div>
                                    <div class="text-xs text-gray-400">{{ bet.operator.name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="text-sm text-gray-900">{{ bet.bet_type }}</div>
                                    <div class="text-sm font-medium">{{ formatOdds(bet.odds) }}</div>
                                    <div v-if="bet.confidence" class="mt-1 flex items-center">
                                        <ChartBarIcon class="h-3 w-3 text-gray-400 mr-1" />
                                        <span class="text-xs text-gray-500">{{ bet.confidence }}/10</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm text-gray-900">{{ formatCurrency(bet.stake) }}</div>
                                    <div class="text-sm text-gray-500">Win: {{ formatCurrency(bet.potential_win) }}</div>
                                    <div v-if="bet.profit !== null" class="text-sm font-medium" :class="bet.profit >= 0 ? 'text-green-600' : 'text-red-600'">
                                        {{ bet.profit >= 0 ? '+' : '' }}{{ formatCurrency(bet.profit) }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="getStatusColor(bet.status)">
                                    <component v-if="getStatusIcon(bet.status)" :is="getStatusIcon(bet.status)" class="h-3 w-3 mr-1" />
                                    {{ bet.status.charAt(0).toUpperCase() + bet.status.slice(1) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center text-sm text-gray-900">
                                    <CalendarIcon class="h-4 w-4 text-gray-400 mr-1" />
                                    {{ formatDate(bet.game_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <Link
                                        :href="route('admin.bets.edit', bet.id)"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>
                                    <button
                                        @click="deleteBet(bet)"
                                        class="text-red-600 hover:text-red-900"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Empty State -->
                <div v-if="bets.data.length === 0" class="text-center py-12">
                    <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No bets found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Get started by creating a new bet or adjusting your filters.
                    </p>
                    <div class="mt-6">
                        <Link
                            :href="route('admin.bets.create')"
                            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700"
                        >
                            <PlusIcon class="h-4 w-4 mr-2" />
                            New Bet
                        </Link>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="bets.last_page > 1" class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="bets.current_page > 1"
                                :href="`?page=${bets.current_page - 1}`"
                                preserve-scroll
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="bets.current_page < bets.last_page"
                                :href="`?page=${bets.current_page + 1}`"
                                preserve-scroll
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ bets.from }}</span>
                                    to
                                    <span class="font-medium">{{ bets.to }}</span>
                                    of
                                    <span class="font-medium">{{ bets.total }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <Link
                                        v-for="page in bets.last_page"
                                        :key="page"
                                        :href="`?page=${page}`"
                                        preserve-scroll
                                        :class="[
                                            page === bets.current_page
                                                ? 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600'
                                                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                        ]"
                                    >
                                        {{ page }}
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>