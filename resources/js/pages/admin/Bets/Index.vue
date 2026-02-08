<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import {
    ArrowsUpDownIcon,
    BanknotesIcon,
    CalendarIcon,
    ChartBarIcon,
    CheckIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    FunnelIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    PlusIcon,
    TrashIcon,
    TrophyIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';

interface Team {
    id: number;
    name: string;
}

interface Bet {
    id: number;
    sports: string;
    league?: string;
    month?: string;
    matches?: string;
    markets?: string;
    wager_type?: string;
    team_one?: string;
    team_one_logo?: string;
    team_two?: string;
    team_two_logo?: string;
    teamOne?: Team;
    teamTwo?: Team;
    tips?: string;
    betting_date: string;
    wager_odds: number | string;
    membership: string;
    level?: string;
    code?: string;
    roi?: number;
    wager_amount: number;
    winning_amount?: number;
    profit_amount?: number;
    status: string;
    referrer?: string;
    place_fraction?: number;
    is_each_way?: boolean;
    each_way_stake?: number;
    place_payout?: number;
    user_id?: number;
    created_at: string;
    updated_at: string;
}

interface Sport {
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
        referrer?: string;
        user_id?: number;
        date_from?: string;
        date_to?: string;
        search?: string;
        wager_type?: string;
        is_featured?: boolean;
        min_confidence?: number;
        profit_status?: string;
        sort_by?: string;
        sort_direction?: string;
        per_page?: number;
    };
    sports?: Sport[] | null;
    referrers?: string[] | null;
    statuses: string[];
    betTypes: Record<string, string>;
    stats: Stats;
}

const props = defineProps<Props>();

// Form for filters
const filterForm = useForm({
    status: props.filters.status || '',
    sport_id: props.filters.sport_id || '',
    referrer: props.filters.referrer || '',
    user_id: props.filters.user_id || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
    search: props.filters.search || '',
    wager_type: props.filters.wager_type || '',
    is_featured: props.filters.is_featured || '',
    min_confidence: props.filters.min_confidence || '',
    profit_status: props.filters.profit_status || '',
    sort_by: props.filters.sort_by || 'betting_date',
    sort_direction: props.filters.sort_direction || 'desc',
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
    router.get(
        route('admin.bets.index'),
        {
            ...filterForm.data(),
            page: 1,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

// Sort functionality
const sortBy = (field: string) => {
    if (filterForm.sort_by === field) {
        filterForm.sort_direction = filterForm.sort_direction === 'asc' ? 'desc' : 'asc';
    } else {
        filterForm.sort_by = field;
        filterForm.sort_direction = 'desc';
    }
    applyFilters();
};

// Generate pagination URL with current filters
const getPaginationUrl = (page: number) => {
    const params = new URLSearchParams();

    // Add all current filters
    Object.entries(filterForm.data()).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) {
            params.append(key, String(value));
        }
    });

    // Update page number
    params.set('page', String(page));

    return `?${params.toString()}`;
};

const getSortIcon = (field: string) => {
    if (filterForm.sort_by !== field) return ArrowsUpDownIcon;
    return filterForm.sort_direction === 'asc' ? ChevronUpIcon : ChevronDownIcon;
};

// Watch for non-search filter changes
watch(
    () => ({
        status: filterForm.status,
        sport_id: filterForm.sport_id,
        referrer: filterForm.referrer,
        date_from: filterForm.date_from,
        date_to: filterForm.date_to,
        wager_type: filterForm.wager_type,
        is_featured: filterForm.is_featured,
        min_confidence: filterForm.min_confidence,
        profit_status: filterForm.profit_status,
        per_page: filterForm.per_page,
    }),
    () => {
        applyFilters();
    },
    { deep: true },
);

// Computed properties
const allSelected = computed(() => props.bets.data.length > 0 && selectedBets.value.length === props.bets.data.length);

const someSelected = computed(() => selectedBets.value.length > 0 && selectedBets.value.length < props.bets.data.length);

// Generate pagination pages with ellipsis
const paginationPages = computed(() => {
    const current = props.bets.current_page;
    const last = props.bets.last_page;
    const delta = 2;
    const pages: (number | string)[] = [];

    // Always show first page
    pages.push(1);

    // Calculate range around current page
    const rangeStart = Math.max(2, current - delta);
    const rangeEnd = Math.min(last - 1, current + delta);

    // Add ellipsis if needed before range
    if (rangeStart > 2) {
        pages.push('...');
    }

    // Add pages in range
    for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
    }

    // Add ellipsis if needed after range
    if (rangeEnd < last - 1) {
        pages.push('...');
    }

    // Always show last page if there's more than one page
    if (last > 1) {
        pages.push(last);
    }

    return pages;
});

// Methods
function getTeamName(bet: Bet, position: 'one' | 'two'): string {
    // Simply return the text field value
    const textField = position === 'one' ? bet.team_one : bet.team_two;

    // If the field contains a JSON string, parse it
    if (textField && typeof textField === 'string' && textField.startsWith('{')) {
        try {
            const parsed = JSON.parse(textField);
            return parsed.name || textField;
        } catch {
            return textField;
        }
    }

    return textField || '';
}

function toggleAll() {
    if (allSelected.value) {
        selectedBets.value = [];
    } else {
        selectedBets.value = props.bets.data.map((bet) => bet.id);
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

function exportAllBets() {
    window.location.href = route('admin.bets.export');
}

function getStatusColor(status: string): string {
    switch (status) {
        case 'won':
            return 'badge bg-success';
        case 'loss':
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
        case 'loss':
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

function formatOdds(odds: number | string | null | undefined): string {
    if (odds === null || odds === undefined) {
        return 'N/A';
    }
    // If it's already a string with + or -, return as-is
    if (typeof odds === 'string' && (odds.startsWith('+') || odds.startsWith('-'))) {
        return odds;
    }
    // Convert to number for comparison
    const numOdds = typeof odds === 'string' ? parseFloat(odds) : odds;
    if (numOdds > 0) {
        return `+${numOdds}`;
    }
    return numOdds.toString();
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

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Bet Management</h1>
                    <p class="text-muted mb-0">Manage all betting picks and predictions</p>
                </div>
                <div class="d-flex gap-2">
                    <Link :href="route('admin.bets.mass-edit.index')" class="btn btn-warning">
                        <i class="bi bi-pencil-square me-2"></i>Mass Edit
                    </Link>
                    <button @click="exportBets" type="button" class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Export Current
                    </button>
                    <button @click="exportAllBets" type="button" class="btn btn-outline-secondary">
                        <i class="bi bi-download me-2"></i>Export All
                    </button>
                    <Link :href="route('admin.bets.create')" class="btn btn-primary"> <i class="bi bi-plus-circle me-2"></i>Add Bet </Link>
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
                                <ChartBarIcon class="text-muted" style="width: 2rem; height: 2rem" />
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
                                <CalendarIcon class="text-primary" style="width: 2rem; height: 2rem" />
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
                                <BanknotesIcon class="text-muted" style="width: 2rem; height: 2rem" />
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
                                <TrophyIcon
                                    :class="(stats.total_profit || 0) >= 0 ? 'text-success' : 'text-danger'"
                                    style="width: 2rem; height: 2rem"
                                />
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
                                <CheckIcon class="text-success" style="width: 2rem; height: 2rem" />
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
                                    <h4 class="mb-0" :class="(stats.roi || 0) >= 0 ? 'text-success' : 'text-danger'">{{ stats.roi || 0 }}%</h4>
                                </div>
                                <TrophyIcon :class="(stats.roi || 0) >= 0 ? 'text-success' : 'text-danger'" style="width: 2rem; height: 2rem" />
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
                                            <MagnifyingGlassIcon style="width: 1rem; height: 1rem" />
                                        </span>
                                        <input
                                            :value="filterForm.search"
                                            @input="debouncedSearch($event.target.value)"
                                            type="search"
                                            placeholder="Search bets, users, sports, referrers..."
                                            class="form-control"
                                        />
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <button @click="showFilters = !showFilters" type="button" class="btn btn-outline-secondary">
                                        <FunnelIcon style="width: 1rem; height: 1rem" class="me-1" />
                                        Filters
                                        <span v-if="Object.values(filterForm.data()).some((v) => v)" class="badge bg-primary ms-1"> Active </span>
                                    </button>
                                </div>
                            </div>

                            <!-- Filter Panel -->
                            <div v-if="showFilters" class="border-top pt-3">
                                <div class="row g-3">
                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Status</label>
                                        <select v-model="filterForm.status" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option v-for="status in statuses" :key="status" :value="status">
                                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Sport</label>
                                        <select v-model="filterForm.sport_id" class="form-select">
                                            <option value="">All Sports</option>
                                            <option v-for="sport in sports || []" :key="sport?.id || Math.random()" :value="sport?.id">
                                                {{ sport?.name || 'Loading...' }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Referrer</label>
                                        <select v-model="filterForm.referrer" class="form-select">
                                            <option value="">All Referrers</option>
                                            <option v-for="referrer in referrers || []" :key="referrer" :value="referrer">
                                                {{ referrer }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Wager Type</label>
                                        <select v-model="filterForm.wager_type" class="form-select">
                                            <option value="">All Types</option>
                                            <option v-for="(label, value) in betTypes" :key="value" :value="value">
                                                {{ label }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Date From</label>
                                        <input v-model="filterForm.date_from" type="date" class="form-control" />
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Date To</label>
                                        <input v-model="filterForm.date_to" type="date" class="form-control" />
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Featured</label>
                                        <select v-model="filterForm.is_featured" class="form-select">
                                            <option value="">All Bets</option>
                                            <option value="true">Featured Only</option>
                                            <option value="false">Non-Featured</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-4 col-sm-6">
                                        <label class="form-label">Profit Status</label>
                                        <select v-model="filterForm.profit_status" class="form-select">
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
                                        <select v-model="filterForm.per_page" class="form-select">
                                            <option value="10">10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-3 text-end">
                                    <button @click="clearFilters" type="button" class="btn btn-link text-muted p-0">Clear all filters</button>
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
                        <span> {{ selectedBets.length }} bet{{ selectedBets.length === 1 ? '' : 's' }} selected </span>
                        <div class="d-flex align-items-center gap-2">
                            <select v-model="bulkStatusForm.status" class="form-select form-select-sm">
                                <option value="">Select status...</option>
                                <option value="won">Mark as Won</option>
                                <option value="loss">Mark as Loss</option>
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
                                        <th class="text-center" style="width: 50px">
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
                                        <th>Teams / Membership</th>
                                        <th>Sport / League</th>
                                        <th>
                                            Type /
                                            <button @click="sortBy('wager_odds')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Odds
                                                <component :is="getSortIcon('wager_odds')" style="width: 0.75rem; height: 0.75rem" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('wager_amount')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Stake
                                                <component :is="getSortIcon('wager_amount')" style="width: 0.75rem; height: 0.75rem" class="ms-1" />
                                            </button>
                                            /
                                            <button @click="sortBy('winning_amount')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Win
                                                <component :is="getSortIcon('winning_amount')" style="width: 0.75rem; height: 0.75rem" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('status')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Status
                                                <component :is="getSortIcon('status')" style="width: 0.75rem; height: 0.75rem" class="ms-1" />
                                            </button>
                                        </th>
                                        <th>
                                            <button @click="sortBy('game_date')" class="btn btn-link p-0 text-muted text-decoration-none">
                                                Game Date
                                                <component :is="getSortIcon('game_date')" style="width: 0.75rem; height: 0.75rem" class="ms-1" />
                                            </button>
                                        </th>
                                        <th class="text-center" style="width: 100px">Actions</th>
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
                                                <div class="fw-medium">
                                                    {{ getTeamName(bet, 'one') || bet.tips || 'N/A' }}
                                                </div>
                                                <div v-if="getTeamName(bet, 'two')" class="text-muted small">vs {{ getTeamName(bet, 'two') }}</div>
                                                <div class="mt-1 text-muted small">{{ bet.membership || 'All' }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ bet.sports || 'N/A' }}</div>
                                                <div v-if="bet.league" class="text-muted small">{{ bet.league }}</div>
                                                <div v-if="bet.markets" class="text-muted small">{{ bet.markets }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div>{{ bet.wager_type || bet.markets || 'N/A' }}</div>
                                                <div class="fw-medium">{{ formatOdds(bet.wager_odds) }}</div>
                                                <div v-if="bet.level" class="text-muted small">Level: {{ bet.level }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">Stake: {{ formatCurrency(bet.wager_amount) }}</div>
                                                <div
                                                    class="small"
                                                    :class="{
                                                        'text-success': bet.status === 'won' || bet.status === 'placed',
                                                        'text-danger': bet.status === 'loss',
                                                        'text-muted': bet.status === 'pending' || bet.status === 'push' || bet.status === 'void',
                                                    }"
                                                >
                                                    <template v-if="bet.status === 'won' || bet.status === 'placed'">
                                                        Win: {{ formatCurrency(bet.winning_amount) }}
                                                    </template>
                                                    <template v-else-if="bet.status === 'loss'">
                                                        Lost: {{ formatCurrency(bet.wager_amount) }}
                                                    </template>
                                                    <template v-else-if="bet.status === 'push' || bet.status === 'void'">
                                                        Refund: {{ formatCurrency(bet.wager_amount) }}
                                                    </template>
                                                    <template v-else> Potential: {{ formatCurrency(bet.winning_amount || 0) }} </template>
                                                </div>
                                                <div
                                                    v-if="bet.status !== 'pending'"
                                                    class="fw-medium small"
                                                    :class="{
                                                        'text-success': (bet.profit_amount || 0) > 0,
                                                        'text-danger': (bet.profit_amount || 0) < 0,
                                                        'text-muted': (bet.profit_amount || 0) === 0,
                                                    }"
                                                >
                                                    Profit: {{ (bet.profit_amount || 0) >= 0 ? '+' : '' }}{{ formatCurrency(bet.profit_amount) }}
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span :class="getStatusColor(bet.status)">
                                                <component
                                                    v-if="getStatusIcon(bet.status)"
                                                    :is="getStatusIcon(bet.status)"
                                                    style="width: 0.75rem; height: 0.75rem"
                                                    class="me-1"
                                                />
                                                {{ bet.status.charAt(0).toUpperCase() + bet.status.slice(1) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <CalendarIcon style="width: 1rem; height: 1rem" class="text-muted me-1" />
                                                <span class="small">{{ formatDate(bet.game_date || bet.betting_date) }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <Link :href="route('admin.bets.edit', bet.id)" class="btn btn-outline-primary" title="Edit">
                                                    <PencilIcon style="width: 1rem; height: 1rem" />
                                                </Link>
                                                <button @click="deleteBet(bet)" class="btn btn-outline-danger" title="Delete">
                                                    <TrashIcon style="width: 1rem; height: 1rem" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div v-if="bets.data.length === 0" class="text-center py-5">
                            <ChartBarIcon class="mx-auto text-muted" style="width: 3rem; height: 3rem" />
                            <h5 class="mt-3">No bets found</h5>
                            <p class="text-muted">Get started by creating a new bet or adjusting your filters.</p>
                            <div class="mt-4">
                                <Link :href="route('admin.bets.create')" class="btn btn-primary">
                                    <PlusIcon style="width: 1rem; height: 1rem" class="me-1" />
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
                                                <li v-if="bets.current_page > 1" class="page-item">
                                                    <Link
                                                        :href="getPaginationUrl(bets.current_page - 1)"
                                                        preserve-scroll
                                                        preserve-state
                                                        class="page-link"
                                                    >
                                                        Previous
                                                    </Link>
                                                </li>
                                                <template v-for="(page, index) in paginationPages" :key="index">
                                                    <li v-if="page === '...'" class="page-item disabled">
                                                        <span class="page-link">...</span>
                                                    </li>
                                                    <li v-else class="page-item" :class="{ active: page === bets.current_page }">
                                                        <Link :href="getPaginationUrl(page)" preserve-scroll preserve-state class="page-link">
                                                            {{ page }}
                                                        </Link>
                                                    </li>
                                                </template>
                                                <li v-if="bets.current_page < bets.last_page" class="page-item">
                                                    <Link
                                                        :href="getPaginationUrl(bets.current_page + 1)"
                                                        preserve-scroll
                                                        preserve-state
                                                        class="page-link"
                                                    >
                                                        Next
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
