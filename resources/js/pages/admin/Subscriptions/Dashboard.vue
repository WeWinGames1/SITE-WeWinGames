<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    ChartBarIcon, 
    UsersIcon, 
    CurrencyDollarIcon,
    CalendarIcon,
    MagnifyingGlassIcon,
    FunnelIcon,
    ArrowDownTrayIcon,
    PlusIcon,
    XMarkIcon
} from '@heroicons/vue/24/outline';

interface Customer {
    id: number;
    name: string;
    email: string;
    subscription_id: string;
    status: string;
    tier: string | null;
    current_period_start: string;
    current_period_end: string;
    created_at: string;
    is_ambassador: boolean;
    is_gifted: boolean;
    has_override: boolean;
    days_until_renewal: number;
}

interface Stats {
    total_active: number;
    total_trialing: number;
    total_cancelled: number;
    tier_breakdown: Record<string, number>;
    mrr: number;
    upcoming_renewals: number;
}

interface Props {
    customers: {
        data: Customer[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
        tier?: string;
        renewal_period?: string;
        search?: string;
    };
    stats: Stats;
    tiers: string[];
}

const props = defineProps<Props>();

// State
const selectedCustomers = ref<number[]>([]);
const showFilters = ref(false);
const showGrantModal = ref(false);
const selectedUser = ref<Customer | null>(null);

// Forms
const filterForm = useForm({
    status: props.filters.status || '',
    tier: props.filters.tier || '',
    renewal_period: props.filters.renewal_period || '',
    search: props.filters.search || '',
});

const grantForm = useForm({
    user_id: null as number | null,
    tier: 'Silver',
    duration_days: 30,
    reason: '',
});

// Computed
const allSelected = computed(() => {
    return selectedCustomers.value.length === props.customers.data.length;
});

const selectedCount = computed(() => {
    return selectedCustomers.value.length;
});

// Methods
function toggleAll() {
    if (allSelected.value) {
        selectedCustomers.value = [];
    } else {
        selectedCustomers.value = props.customers.data.map(c => c.id);
    }
}

function applyFilters() {
    filterForm.transform(data => {
        const filtered: any = {};
        Object.keys(data).forEach(key => {
            if (data[key]) filtered[key] = data[key];
        });
        return filtered;
    }).get(route('admin.subscriptions.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    filterForm.reset();
    router.get(route('admin.subscriptions.index'));
}

function exportSelected() {
    const form = useForm({
        ids: selectedCustomers.value,
    });
    
    form.post(route('admin.subscriptions.export'), {
        onSuccess: () => {
            selectedCustomers.value = [];
        },
    });
}

function openGrantModal(customer?: Customer) {
    if (customer) {
        selectedUser.value = customer;
        grantForm.user_id = customer.id;
    }
    showGrantModal.value = true;
}

function grantSubscription() {
    grantForm.post(route('admin.subscriptions.grant'), {
        onSuccess: () => {
            showGrantModal.value = false;
            grantForm.reset();
            selectedUser.value = null;
            router.reload();
        },
    });
}

function cancelSubscription(customer: Customer, immediately = false) {
    const message = immediately 
        ? 'Cancel this subscription immediately? The user will lose access right away.'
        : 'Cancel this subscription at the end of the billing period?';
        
    if (confirm(message)) {
        router.post(route('admin.subscriptions.cancel', customer.id), {
            immediately,
        });
    }
}

function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
        active: 'badge bg-success',
        trialing: 'badge bg-primary',
        canceled: 'badge bg-danger',
        past_due: 'badge bg-warning',
    };
    return colors[status] || 'badge bg-secondary';
}

function getTierColor(tier: string | null): string {
    if (!tier) return 'badge bg-secondary';
    
    const colors: Record<string, string> = {
        Bronze: 'badge bg-warning',
        Silver: 'badge bg-secondary',
        Gold: 'badge bg-warning',
        Platinum: 'badge bg-purple',
    };
    return colors[tier] || 'badge bg-secondary';
}

// Auto-submit search after delay
let searchTimeout: NodeJS.Timeout;
watch(() => filterForm.search, (value) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});
</script>

<template>
    <AdminLayout>
        <Head title="Subscription Dashboard" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">Subscription Dashboard</h1>
            </div>
                
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Active Subscriptions</p>
                                    <h4 class="mb-0 text-success">{{ stats.total_active }}</h4>
                                </div>
                                <UsersIcon class="text-success" style="width: 2.5rem; height: 2.5rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Monthly Recurring Revenue</p>
                                    <h4 class="mb-0 text-primary">${{ stats.mrr.toLocaleString() }}</h4>
                                </div>
                                <CurrencyDollarIcon class="text-primary" style="width: 2.5rem; height: 2.5rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Renewals (30 days)</p>
                                    <h4 class="mb-0 text-warning">{{ stats.upcoming_renewals }}</h4>
                                </div>
                                <CalendarIcon class="text-warning" style="width: 2.5rem; height: 2.5rem;" />
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1 small">Tier Breakdown</p>
                                    <div class="small">
                                        <div v-for="(count, tier) in stats.tier_breakdown" :key="tier" class="d-flex justify-content-between">
                                            <span>{{ tier }}:</span>
                                            <span class="fw-medium">{{ count }}</span>
                                        </div>
                                    </div>
                                </div>
                                <ChartBarIcon class="text-purple" style="width: 2.5rem; height: 2.5rem;" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Actions Bar -->
            <div class="row mb-4">
                <div class="col">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Search -->
                                        <div class="input-group" style="max-width: 300px;">
                                            <span class="input-group-text">
                                                <MagnifyingGlassIcon style="width: 1rem; height: 1rem;" />
                                            </span>
                                            <input 
                                                v-model="filterForm.search" 
                                                class="form-control" 
                                                placeholder="Search by name or email..."
                                            />
                                        </div>
                                        
                                        <!-- Filters -->
                                        <button @click="showFilters = !showFilters" class="btn btn-outline-secondary">
                                            <FunnelIcon style="width: 1rem; height: 1rem;" class="me-1" />
                                            Filters
                                            <span v-if="Object.values(props.filters).filter(v => v).length > 0" 
                                                  class="badge bg-primary ms-1">
                                                {{ Object.values(props.filters).filter(v => v).length }}
                                            </span>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-4 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button 
                                            v-if="selectedCount > 0"
                                            @click="exportSelected" 
                                            class="btn btn-outline-info"
                                        >
                                            <ArrowDownTrayIcon style="width: 1rem; height: 1rem;" class="me-1" />
                                            Export ({{ selectedCount }})
                                        </button>
                                        
                                        <button @click="openGrantModal()" class="btn btn-primary">
                                            <PlusIcon style="width: 1rem; height: 1rem;" class="me-1" />
                                            Grant Subscription
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
            <!-- Filters Panel -->
            <div v-if="showFilters" class="row mb-4">
                <div class="col">
                    <div class="card bg-light">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label text-dark fw-medium">Status</label>
                                    <select v-model="filterForm.status" class="form-select">
                                        <option value="">All Statuses</option>
                                        <option value="active">Active</option>
                                        <option value="trialing">Trialing</option>
                                        <option value="canceled">Cancelled</option>
                                        <option value="past_due">Past Due</option>
                                    </select>
                                </div>
                                
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label text-dark fw-medium">Tier</label>
                                    <select v-model="filterForm.tier" class="form-select">
                                        <option value="">All Tiers</option>
                                        <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                                    </select>
                                </div>
                                
                                <div class="col-lg-3 col-md-6">
                                    <label class="form-label text-dark fw-medium">Renewal Period</label>
                                    <select v-model="filterForm.renewal_period" class="form-select">
                                        <option value="">Any Time</option>
                                        <option value="7">Next 7 days</option>
                                        <option value="30">Next 30 days</option>
                                        <option value="60">Next 60 days</option>
                                    </select>
                                </div>
                                
                                <div class="col-lg-3 col-md-6 d-flex align-items-end gap-2">
                                    <button @click="applyFilters" class="btn btn-primary flex-grow-1">Apply</button>
                                    <button @click="clearFilters" class="btn btn-link text-muted p-1">Clear</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Customers Table -->
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
                                                    @change="toggleAll"
                                                    class="form-check-input"
                                                />
                                            </div>
                                        </th>
                                        <th>Customer</th>
                                        <th>Tier</th>
                                        <th>Status</th>
                                        <th>Started</th>
                                        <th>Renews</th>
                                        <th class="text-center" style="width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="customer in customers.data" :key="customer.id">
                                        <td class="text-center">
                                            <div class="form-check">
                                                <input 
                                                    type="checkbox" 
                                                    :value="customer.id"
                                                    v-model="selectedCustomers"
                                                    class="form-check-input"
                                                />
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ customer.name }}</div>
                                                <div class="text-muted small">{{ customer.email }}</div>
                                                <div v-if="customer.is_ambassador || customer.is_gifted || customer.has_override" class="mt-1">
                                                    <span v-if="customer.is_ambassador" class="badge bg-primary me-1">
                                                        Ambassador
                                                    </span>
                                                    <span v-if="customer.is_gifted" class="badge bg-success me-1">
                                                        Gifted
                                                    </span>
                                                    <span v-if="customer.has_override" class="badge bg-purple me-1">
                                                        Override
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span :class="getTierColor(customer.tier)">
                                                {{ customer.tier || 'Unknown' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span :class="getStatusColor(customer.status)">
                                                {{ customer.status }}
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            {{ new Date(customer.created_at).toLocaleDateString() }}
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div v-if="customer.current_period_end">
                                                    {{ new Date(customer.current_period_end).toLocaleDateString() }}
                                                </div>
                                                <div v-else class="text-muted">No end date</div>
                                                <div class="text-muted">
                                                    <span v-if="customer.days_until_renewal === null">No renewal date</span>
                                                    <span v-else-if="customer.days_until_renewal < 0" class="text-danger">Expired</span>
                                                    <span v-else-if="customer.days_until_renewal === 0" class="text-warning fw-bold">Today</span>
                                                    <span v-else>{{ Math.round(customer.days_until_renewal) }} days</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <button @click="openGrantModal(customer)" class="btn btn-sm btn-outline-primary">
                                                    Grant
                                                </button>
                                                <button @click="cancelSubscription(customer)" class="btn btn-sm btn-outline-warning">
                                                    Cancel
                                                </button>
                                                <button @click="cancelSubscription(customer, true)" class="btn btn-sm btn-outline-danger">
                                                    Cancel Now
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div v-if="customers.links.length > 3" class="card-footer bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ customers.meta.from }} to {{ customers.meta.to }} of {{ customers.meta.total }} results
                                </div>
                                <nav>
                                    <ul class="pagination pagination-sm mb-0">
                                        <li v-for="link in customers.links" :key="link.label" class="page-item" :class="{ active: link.active, disabled: !link.url }">
                                            <button
                                                v-if="link.url"
                                                @click="router.get(link.url)"
                                                class="page-link"
                                                v-html="link.label"
                                            />
                                            <span v-else class="page-link" v-html="link.label" />
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grant Subscription Modal -->
        <div class="modal fade" :class="{ show: showGrantModal, 'd-block': showGrantModal }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Grant Manual Subscription</h5>
                        <button type="button" class="btn-close" @click="showGrantModal = false"></button>
                    </div>
                    <form @submit.prevent="grantSubscription">
                        <div class="modal-body">
                            <div v-if="selectedUser" class="alert alert-info">
                                <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.email }})
                            </div>
                            
                            <div class="mb-3">
                                <label for="tier" class="form-label">Subscription Tier</label>
                                <select v-model="grantForm.tier" id="tier" class="form-select" required>
                                    <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                                </select>
                                <div v-if="grantForm.errors.tier" class="text-danger small mt-1">{{ grantForm.errors.tier }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="duration" class="form-label">Duration (days)</label>
                                <input v-model.number="grantForm.duration_days" id="duration" type="number" min="1" class="form-control" required />
                                <div v-if="grantForm.errors.duration_days" class="text-danger small mt-1">{{ grantForm.errors.duration_days }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason (optional)</label>
                                <textarea 
                                    v-model="grantForm.reason" 
                                    id="reason" 
                                    class="form-control"
                                    rows="3"
                                ></textarea>
                                <div v-if="grantForm.errors.reason" class="text-danger small mt-1">{{ grantForm.errors.reason }}</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showGrantModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="grantForm.processing">
                                <span v-if="grantForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Grant Subscription
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showGrantModal" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>