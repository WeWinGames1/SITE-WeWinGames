<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { debounce } from 'lodash';
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
    CalendarIcon,
    ArrowsUpDownIcon,
    ChevronUpIcon,
    ChevronDownIcon
} from '@heroicons/vue/24/outline';

interface Bet {
    id: number;
    user?: {
        id: number;
        name: string;
        email: string;
    };
    sport?: {
        id: number;
        name: string;
    };
    game?: {
        id: number;
        home_team: string;
        away_team: string;
    };
    operator?: {
        id: number;
        name: string;
    };
    selection: string;
    description?: string;
    bet_type: string;
    odds: number | null;
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

interface Operator {
    id: number;
    name: string;
}

interface Stats {
    total_bets: number;
    pending_bets: number;
    total_stake: number;
    total_profit: number;
    win_rate: number;
    roi: number;
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
        operator_id?: number;
        user_id?: number;
        date_from?: string;
        date_to?: string;
        search?: string;
        bet_type?: string;
        is_featured?: boolean;
        min_confidence?: number;
        profit_status?: string;
        sort?: string;
        direction?: string;
        per_page?: number;
    };
    sports?: Sport[] | null;
    operators?: Operator[] | null;
    statuses: string[];
    betTypes: Record<string, string>;
    stats: Stats;
}

const props = defineProps<Props>();

// Form for filters
const filterForm = useForm({
    status: props.filters.status || '',
    sport_id: props.filters.sport_id || '',
    operator_id: props.filters.operator_id || '',
    user_id: props.filters.user_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
    bet_type: props.filters.bet_type || '',
    is_featured: props.filters.is_featured || '',
    min_confidence: props.filters.min_confidence || '',
    profit_status: props.filters.profit_status || '',
    sort: props.filters.sort || 'game_at',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 25,
});

const showFilters = ref(false);
const selectedBets = ref<number[]>([]);
const bulkStatusForm = useForm({
    bet_ids: [] as number[],
    status: '',
});

// Debounced search
const debouncedSearch = debounce((value: string) => {
    filterForm.search = value;
    applyFilters();
}, 300);

// Apply filters with page reset
const applyFilters = () => {
    router.get(route('admin.bets.index'), {
        ...filterForm.data(),
        page: 1,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Sort functionality
const sortBy = (field: string) => {
    if (filterForm.sort === field) {
        filterForm.direction = filterForm.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filterForm.sort = field;
        filterForm.direction = 'desc';
    }
    applyFilters();
};

const getSortIcon = (field: string) => {
    if (filterForm.sort !== field) return ArrowsUpDownIcon;
    return filterForm.direction === 'asc' ? ChevronUpIcon : ChevronDownIcon;
};

// Watch for non-search filter changes
watch(() => ({
    status: filterForm.status,
    sport_id: filterForm.sport_id,
    operator_id: filterForm.operator_id,
    date_from: filterForm.date_from,
    date_to: filterForm.date_to,
    bet_type: filterForm.bet_type,
    is_featured: filterForm.is_featured,
    min_confidence: filterForm.min_confidence,
    profit_status: filterForm.profit_status,
    per_page: filterForm.per_page,
}), () => {
    applyFilters();
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
    applyFilters();
}

// Format currency
function formatMoney(amount: number | null | undefined): string {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '$0';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
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
            return 'badge bg-success';
        case 'lost':
            return 'badge bg-danger';
        case 'push':
            return 'badge bg-warning';
        case 'pending':
            return 'badge bg-primary';
        case 'cancelled':
            return 'badge bg-secondary';
        default:
            return 'badge bg-secondary';
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

function formatOdds(odds: number | null | undefined): string {
    if (odds === null || odds === undefined) {
        return 'N/A';
    }
    if (odds > 0) {
        return `+${odds}`;
    }
    return odds.toString();
}

function formatCurrency(amount: number | null | undefined): string {
    if (amount === null || amount === undefined || isNaN(amount)) {
        return '$0.00';
    }
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
}
</script>

<template>
    <AdminLayout>
        <Head title="Bet Management" />
        
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Bet Management</h1>
                            <p class="text-muted mb-0">
                                Manage all betting picks and predictions
                            </p>
                        </div>
                        <div class="d-flex gap-2">
                            <button
                                @click="exportBets"
                                type="button"
                                class="btn btn-outline-secondary"
                                style="padding: 0.375rem 0.75rem;"
                            >
                                <i class="bi bi-download me-2"></i>Export
                            </button>
                            <Link
                                :href="route('admin.bets.create')"
                                class="btn btn-primary"
                                style="padding: 0.375rem 0.75rem;"
                            >
                                <i class="bi bi-plus-circle me-2"></i>Add Bet
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Total Bets</p>
                                    <h4 class="mb-0">{{ stats.total_bets || 0 }}</h4>
                                </div>
                                <ChartBarIcon class="text-muted" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Pending</p>
                                    <h4 class="mb-0 text-primary">{{ stats.pending_bets || 0 }}</h4>
                                </div>
                                <CalendarIcon class="text-primary" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Total Stake</p>
                                    <h4 class="mb-0">{{ formatMoney(stats.total_stake) }}</h4>
                                </div>
                                <BanknotesIcon class="text-muted" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Total Profit</p>
                                    <h4 class="mb-0" :class="(stats.total_profit || 0) >= 0 ? 'text-success' : 'text-danger'">
                                        {{ formatMoney(stats.total_profit) }}
                                    </h4>
                                </div>
                                <TrophyIcon :class="(stats.total_profit || 0) >= 0 ? 'text-success' : 'text-danger'" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Win Rate</p>
                                    <h4 class="mb-0">{{ stats.win_rate || 0 }}%</h4>
                                </div>
                                <CheckIcon class="text-success" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">ROI</p>
                                    <h4 class="mb-0" :class="(stats.roi || 0) >= 0 ? 'text-success' : 'text-danger'">
                                        {{ stats.roi || 0 }}%
                                    </h4>
                                </div>
                                <TrophyIcon :class="(stats.roi || 0) >= 0 ? 'text-success' : 'text-danger'" style="width: 2rem; height: 2rem;" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="row mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <!-- Search Bar -->
                            <div class="row mb-3">
                                <div class="col">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <MagnifyingGlassIcon style="width: 1rem; height: 1rem;" />
                                        </span>
                                        <input
                                            :value="filterForm.search"
                                            @input="debouncedSearch($event.target.value)"
                                            type="search"
                                            placeholder="Search bets, users, sports, operators..."
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button
                                        @click="showFilters = !showFilters"
                                        type="button"
                                        class="btn btn-outline-secondary"
                                    >
                                        <FunnelIcon style="width: 1rem; height: 1rem;" class="me-1" />
                                        Filters
                                        <span v-if="Object.values(filterForm.data()).some(v => v)" class="badge bg-primary ms-1">
                                            Active
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Panel -->
                            <div v-if="showFilters" class="border-top pt-3">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Status</label>
                                        <select
                                            v-model="filterForm.status"
                                            class="form-select"
                                        >
                                            <option value="">All Statuses</option>
                                            <option v-for="status in statuses" :key="status" :value="status">
                                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Sport</label>
                                        <select
                                            v-model="filterForm.sport_id"
                                            class="form-select"
                                        >
                                            <option value="">All Sports</option>
                                            <option v-for="sport in (sports || [])" :key="sport?.id || Math.random()" :value="sport?.id">
                                                {{ sport?.name || 'Loading...' }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Operator</label>
                                        <select
                                            v-model="filterForm.operator_id"
                                            class="form-select"
                                        >
                                            <option value="">All Operators</option>
                                            <option v-for="operator in (operators || [])" :key="operator?.id || Math.random()" :value="operator?.id">
                                                {{ operator?.name || 'Loading...' }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Bet Type</label>
                                        <select
                                            v-model="filterForm.bet_type"
                                            class="form-select"
                                        >
                                            <option value="">All Types</option>
                                            <option v-for="(label, value) in betTypes" :key="value" :value="value">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Date From</label>
                                        <input
                                            v-model="filterForm.date_from"
                                            type="date"
                                            class="form-control"
                                        />
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Date To</label>
                                        <input
                                            v-model="filterForm.date_to"
                                            type="date"
                                            class="form-control"
                                        />
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Featured</label>
                                        <select
                                            v-model="filterForm.is_featured"
                                            class="form-select"
                                        >
                                            <option value="">All Bets</option>
                                            <option value="true">Featured Only</option>
                                            <option value="false">Non-Featured</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Profit Status</label>
                                        <select
                                            v-model="filterForm.profit_status"
                                            class="form-select"
                                        >
                                            <option value="">All</option>
                                            <option value="profit">Profitable</option>
                                            <option value="loss">Loss</option>
                                            <option value="breakeven">Breakeven</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Min Confidence</label>
                                        <input
                                            v-model="filterForm.min_confidence"
                                            type="number"
                                            min="1"
                                            max="10"
                                            placeholder="1-10"
                                            class="form-control"
                                        />
                                    </div>
                                    
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Per Page</label>
                                        <select
                                            v-model="filterForm.per_page"
                                            class="form-select"
                                        >
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-end">
                                    <button
                                        @click="clearFilters"
                                        type="button"
                                        class="btn btn-link text-muted p-0"
                                    >
                                        Clear all filters
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div v-if="selectedBets.length > 0" class="row mb-4">
                <div class="col">
                    <div class="alert alert-primary d-flex align-items-center justify-content-between">
                        <span>
                            {{ selectedBets.length }} bet{{ selectedBets.length === 1 ? '' : 's' }} selected
                        </span>
                        <div class="d-flex align-items-center gap-2">
                            <select
                                v-model="bulkStatusForm.status"
                                class="form-select form-select-sm"
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
                                class="btn btn-primary btn-sm"
                            >
                                Update Status
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bets Table -->
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 50px;">
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    :checked="allSelected"
                                                    :indeterminate="someSelected"
                                                    @change="toggleAll"
                                                    class="form-check-input"
                                                />
                                            </div>
                                        </th>
                                        <th>User / Selection</th>
                                        <th>Sport / Game</th>
                                        <th>
                                            Type / 
                                            <button @click="sortBy('odds')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Odds
                                                <component :is="getSortIcon('odds')" style="width: 0.75rem; height: 0.75rem;" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('stake')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Stake
                                                <component :is="getSortIcon('stake')" style="width: 0.75rem; height: 0.75rem;" class="ms-1" />
                                            </button>
                                            / 
                                            <button @click="sortBy('potential_win')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Win
                                                <component :is="getSortIcon('potential_win')" style="width: 0.75rem; height: 0.75rem;" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('status')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Status
                                                <component :is="getSortIcon('status')" style="width: 0.75rem; height: 0.75rem;" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('game_at')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Game Date
                                                <component :is="getSortIcon('game_at')" style="width: 0.75rem; height: 0.75rem;" class="ms-1" />
                                            </button>
                                        </th>
                                        <th class="text-center" style="width: 100px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="bet in bets.data" :key="bet.id">
                                        <td class="text-center">
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    :checked="selectedBets.includes(bet.id)"
                                                    @change="toggleBet(bet.id)"
                                                    class="form-check-input"
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ bet.user?.name || 'Unknown User' }}</div>
                                                <div class="text-muted small">{{ bet.user?.email || '' }}</div>
                                                <div class="mt-1">{{ bet.selection }}</div>
                                                <div v-if="bet.is_featured" class="mt-1">
                                                    <span class="badge bg-warning text-dark">
                                                        <TrophyIcon style="width: 0.75rem; height: 0.75rem;" class="me-1" />
                                                        Featured
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ bet.sport?.name || 'Unknown Sport' }}</div>
                                                <div v-if="bet.game" class="text-muted small">
                                                    {{ bet.game.away_team }} @ {{ bet.game.home_team }}
                                                </div>
                                                <div class="text-muted small">{{ bet.operator?.name || 'Unknown Operator' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div>{{ bet.bet_type }}</div>
                                                <div class="fw-medium">{{ formatOdds(bet.odds) }}</div>
                                                <div v-if="bet.confidence" class="mt-1 d-flex align-items-center">
                                                    <ChartBarIcon style="width: 0.75rem; height: 0.75rem;" class="text-muted me-1" />
                                                    <span class="text-muted small">{{ bet.confidence }}/10</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div>{{ formatCurrency(bet.stake) }}</div>
                                                <div class="text-muted small">Win: {{ formatCurrency(bet.potential_win) }}</div>
                                                <div v-if="bet.profit !== null" class="fw-medium" :class="(bet.profit || 0) >= 0 ? 'text-success' : 'text-danger'">
                                                    {{ (bet.profit || 0) >= 0 ? '+' : '' }}{{ formatCurrency(bet.profit) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span :class="getStatusColor(bet.status)">
                                                <component v-if="getStatusIcon(bet.status)" :is="getStatusIcon(bet.status)" style="width: 0.75rem; height: 0.75rem;" class="me-1" />
                                                {{ bet.status.charAt(0).toUpperCase() + bet.status.slice(1) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <CalendarIcon style="width: 1rem; height: 1rem;" class="text-muted me-1" />
                                                <span class="small">{{ formatDate(bet.game_at) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <Link
                                                    :href="route('admin.bets.edit', bet.id)"
                                                    class="btn btn-outline-primary btn-sm"
                                                >
                                                    <PencilIcon style="width: 1rem; height: 1rem;" />
                                                </Link>
                                                <button
                                                    @click="deleteBet(bet)"
                                                    class="btn btn-outline-danger btn-sm"
                                                >
                                                    <TrashIcon style="width: 1rem; height: 1rem;" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="bets.data.length === 0" class="text-center py-5">
                            <ChartBarIcon class="mx-auto text-muted" style="width: 3rem; height: 3rem;" />
                            <h5 class="mt-3">No bets found</h5>
                            <p class="text-muted">
                                Get started by creating a new bet or adjusting your filters.
                            </p>
                            <div class="mt-4">
                                <Link
                                    :href="route('admin.bets.create')"
                                    class="btn btn-primary"
                                >
                                    <PlusIcon style="width: 1rem; height: 1rem;" class="me-1" />
                                    New Bet
                                </Link>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="bets.last_page > 1" class="card-footer">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex d-sm-none">
                                    <Link
                                        v-if="bets.current_page > 1"
                                        :href="`?page=${bets.current_page - 1}`"
                                        preserve-scroll
                                        class="btn btn-outline-secondary"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="bets.current_page < bets.last_page"
                                        :href="`?page=${bets.current_page + 1}`"
                                        preserve-scroll
                                        class="btn btn-outline-secondary ms-2"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="d-none d-sm-flex justify-content-between align-items-center w-100">
                                    <div>
                                        <span class="text-muted small">
                                            Showing
                                            <span class="fw-medium">{{ bets.from || 0 }}</span>
                                            to
                                            <span class="fw-medium">{{ bets.to || 0 }}</span>
                                            of
                                            <span class="fw-medium">{{ bets.total || 0 }}</span>
                                            results
                                        </span>
                                    </div>
                                    <div>
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0">
                                                <li v-for="page in bets.last_page" :key="page" class="page-item" :class="{ active: page === bets.current_page }">
                                                    <Link
                                                        :href="`?page=${page}`"
                                                        preserve-scroll
                                                        class="page-link"
                                                    >
                                                        {{ page }}
                                                    </Link>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>