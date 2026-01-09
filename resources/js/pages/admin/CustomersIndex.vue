<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import { route } from 'ziggy-js';

interface Customer {
    id: number;
    name: string;
    email: string;
    created_at: string;
    status: 'active' | 'disabled' | 'pending';
    stripe_id?: string;
    pm_type?: string;
    pm_last_four?: string;
    subscriptions: Array<{
        stripe_status: string;
        price: string;
        stripe_price: string;
        trial_ends_at: string | null;
        current_period_end?: string;
        ends_at?: string | null;
    }>;
    tier?: string;
    interval?: string;
    is_ambassador?: boolean;
    is_gifted?: boolean;
    admin_override?: boolean;
}

interface Props {
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters: {
        search?: string;
        status?: string;
        tier?: string;
        start_date?: string;
        end_date?: string;
        sort?: string;
        direction?: string;
        per_page?: number;
    };
    stats: {
        total: number;
        active: number;
        trialing: number;
        no_subscription: number;
    };
}

const props = defineProps<Props>();
const page = usePage();
const stripePrices = page.props.stripePrices || {};

// Determine Stripe mode from configuration
const stripeMode = computed(() => {
    const stripeKey = page.props.stripeKey || '';
    return stripeKey.includes('pk_test_') ? 'test' : 'live';
});

// Filter state
const filters = ref({
    search: props.filters.search || '',
    status: props.filters.status || '',
    subscription_status: props.filters.subscription_status || '',
    tier: props.filters.tier || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    sort: props.filters.sort || 'created_at',
    direction: props.filters.direction || 'desc',
    per_page: props.filters.per_page || 25,
});

// Modal state
const showGrantModal = ref(false);
const selectedCustomer = ref<Customer | null>(null);
const planPreview = ref<{
    name: string;
    price: string;
    interval: string;
    tier: string;
    nextBilling: string;
} | null>(null);
const grantForm = useForm({
    subscription_price: '',
    subscription_status: 'active',
    trial_days: '',
    action_type: 'create',
});

// Apply filters with debounce for search
const applyFilters = debounce(() => {
    router.get(
        route('admin.customers.index'),
        {
            ...filters.value,
            page: 1, // Reset to first page when filtering
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

// Watch for filter changes
watch(() => filters.value.search, applyFilters);
watch(
    () => filters.value.status,
    () => {
        router.get(route('admin.customers.index'), filters.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
);
watch(
    () => filters.value.subscription_status,
    () => {
        router.get(route('admin.customers.index'), filters.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
);
watch(
    () => filters.value.tier,
    () => {
        router.get(route('admin.customers.index'), filters.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
);
watch(
    () => filters.value.per_page,
    () => {
        router.get(route('admin.customers.index'), filters.value, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
);

function toggleSort(field: string) {
    if (filters.value.sort === field) {
        filters.value.direction = filters.value.direction === 'asc' ? 'desc' : 'asc';
    } else {
        filters.value.sort = field;
        filters.value.direction = 'asc';
    }

    router.get(route('admin.customers.index'), filters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function getSortIcon(field: string) {
    if (filters.value.sort !== field) return 'bi-chevron-expand';
    return filters.value.direction === 'asc' ? 'bi-chevron-up' : 'bi-chevron-down';
}

function getSubscriptionBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'bg-success';
        case 'canceled':
            return 'bg-secondary';
        case 'past_due':
            return 'bg-warning';
        case 'unpaid':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

function openGrantModal(customer: Customer) {
    selectedCustomer.value = customer;
    showGrantModal.value = true;
    planPreview.value = null;

    // Reset form to defaults
    grantForm.subscription_price = '';
    grantForm.subscription_status = 'active';
    grantForm.trial_days = '';
    grantForm.action_type = 'create';

    // Pre-fill form if customer has active subscription
    const activeSubscription = customer.subscriptions?.find((sub) => !sub.ends_at && ['active', 'trialing'].includes(sub.stripe_status));

    if (activeSubscription) {
        // Customer has an active subscription
        const matchingPriceKey = Object.keys(stripePrices).find((key) => stripePrices[key] === activeSubscription.stripe_price);
        if (matchingPriceKey) {
            grantForm.subscription_price = activeSubscription.stripe_price;
            grantForm.action_type = 'update';
        } else {
            // Custom subscription - don't pre-fill price
            grantForm.subscription_price = '';
            grantForm.action_type = 'manual';
        }
        grantForm.subscription_status = activeSubscription.stripe_status || 'active';
    } else {
        // No active subscription - create new
        grantForm.subscription_status = 'active';
        grantForm.action_type = 'create';
        grantForm.subscription_price = '';
    }

    // Update preview if we have a price
    if (grantForm.subscription_price) {
        updatePlanPreview();
    }
}

function grantSubscription() {
    if (!selectedCustomer.value) return;

    grantForm.put(route('admin.customers.update', selectedCustomer.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showGrantModal.value = false;
            grantForm.reset();
            grantForm.clearErrors();
        },
        onError: (errors) => {
            console.error('Subscription update errors:', errors);
        },
    });
}

function impersonateUser(user: Customer) {
    if (confirm(`Impersonate ${user.name}?`)) {
        // Use Inertia to handle the request properly with CSRF
        router.post(
            route('admin.customers.impersonate', user.id),
            {},
            {
                preserveScroll: true,
                preserveState: false,
                onStart: () => {
                    // Optionally show loading state
                },
                onSuccess: () => {
                    // The controller will redirect to dashboard
                },
                onError: (errors) => {
                    console.error('Impersonation error:', errors);
                    // If it's a 419 error, retry once
                    if (errors.response?.status === 419) {
                        setTimeout(() => {
                            router.post(
                                route('admin.customers.impersonate', user.id),
                                {},
                                {
                                    preserveScroll: true,
                                    preserveState: false,
                                },
                            );
                        }, 100);
                    } else {
                        alert('Failed to impersonate user. Please try again.');
                    }
                },
            },
        );
    }
}

function sendPasswordReset(user: Customer) {
    if (confirm(`Send password reset link to ${user.email}?`)) {
        router.post(
            route('admin.customers.password-reset', user.id),
            {},
            {
                preserveScroll: true,
            },
        );
    }
}

function exportCustomers() {
    const params = new URLSearchParams(filters.value as any);
    window.location.href = route('admin.customers.export') + '?' + params.toString();
}

function clearFilters() {
    filters.value = {
        search: '',
        status: '',
        subscription_status: '',
        tier: '',
        start_date: '',
        end_date: '',
        sort: 'created_at',
        direction: 'desc',
        per_page: 25,
    };

    router.get(
        route('admin.customers.index'),
        {},
        {
            preserveState: false,
            preserveScroll: true,
        },
    );
}

// Navigate to page
function goToPage(page: number) {
    router.get(
        route('admin.customers.index'),
        {
            ...filters.value,
            page: page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
}

function getCurrentPlanName(customer: Customer): string {
    if (!customer.subscriptions || customer.subscriptions.length === 0) {
        return 'No subscription';
    }

    const priceId = customer.subscriptions[0].price;
    const planKey = Object.keys(stripePrices).find((key) => stripePrices[key] === priceId);

    if (!planKey) return 'Custom';

    return planKey.replace('_', ' ').toUpperCase();
}

function updatePlanPreview() {
    if (!grantForm.subscription_price) {
        planPreview.value = null;
        return;
    }

    const planKey = Object.keys(stripePrices).find((key) => stripePrices[key] === grantForm.subscription_price);
    if (!planKey) return;

    const parts = planKey.split('_');
    const tier = parts[0].toUpperCase();
    const interval = parts[1].toLowerCase();

    // Calculate next billing date
    const nextBilling = new Date();
    switch (interval) {
        case 'daily':
            nextBilling.setDate(nextBilling.getDate() + 1);
            break;
        case 'weekly':
            nextBilling.setDate(nextBilling.getDate() + 7);
            break;
        case 'monthly':
            nextBilling.setMonth(nextBilling.getMonth() + 1);
            break;
    }

    // Price calculation (you may need to adjust these based on your actual pricing)
    const prices = {
        silver_daily: '5.00',
        silver_weekly: '17.00',
        silver_monthly: '45.00',
        gold_daily: '8.00',
        gold_weekly: '29.00',
        gold_monthly: '65.00',
        platinum_daily: '12.00',
        platinum_weekly: '49.00',
        platinum_monthly: '80.00',
    };

    planPreview.value = {
        name: `${tier} ${interval.charAt(0).toUpperCase() + interval.slice(1)}`,
        price: prices[planKey] || '0.00',
        interval: interval,
        tier: tier,
        nextBilling: nextBilling.toLocaleDateString(),
    };
}

function toggleUserStatus(user: Customer) {
    const newStatus = user.status === 'active' ? 'disabled' : 'active';
    const action = newStatus === 'disabled' ? 'disable' : 'enable';

    if (confirm(`Are you sure you want to ${action} ${user.name}?`)) {
        router.put(
            route('admin.customers.update', user.id),
            {
                status: newStatus,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['customers'] });
                },
            },
        );
    }
}

function cancelSubscription(user: Customer, immediately = false) {
    const message = immediately
        ? 'Cancel this subscription immediately? The user will lose access right away.'
        : 'Cancel this subscription at the end of the billing period?';

    if (confirm(message)) {
        router.post(
            route('admin.customers.cancel-subscription', user.id),
            {
                immediately,
            },
            {
                preserveScroll: true,
            },
        );
    }
}

function getCustomerBadgeClass(status: string) {
    switch (status) {
        case 'active':
            return 'bg-success';
        case 'disabled':
            return 'bg-danger';
        case 'pending':
            return 'bg-warning';
        default:
            return 'bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Customers" />
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">Customer Management</h1>
                <div class="d-flex gap-2">
                    <a :href="route('admin.customers.create')" class="btn btn-success"> <i class="bi bi-person-plus me-2"></i>Create Customer </a>
                    <button class="btn btn-primary" @click="exportCustomers"><i class="bi bi-download me-2"></i>Export CSV</button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Total Customers</div>
                                    <div class="h4 mb-0">{{ stats.total }}</div>
                                </div>
                                <i class="bi bi-people fs-2 text-primary opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Active Subscriptions</div>
                                    <div class="h4 mb-0">{{ stats.active }}</div>
                                </div>
                                <i class="bi bi-check-circle fs-2 text-success opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">Trial Users</div>
                                    <div class="h4 mb-0">{{ stats.trialing }}</div>
                                </div>
                                <i class="bi bi-clock fs-2 text-warning opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="text-muted small">No Subscription</div>
                                    <div class="h4 mb-0">{{ stats.no_subscription }}</div>
                                </div>
                                <i class="bi bi-x-circle fs-2 text-secondary opacity-25"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input v-model="filters.search" type="text" class="form-control" placeholder="Search by name, email, or ID..." />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">User Status</label>
                            <select v-model="filters.status" class="form-select">
                                <option value="">All User Status</option>
                                <option value="active">Active</option>
                                <option value="disabled">Disabled</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Subscription</label>
                            <select v-model="filters.subscription_status" class="form-select">
                                <option value="">All Subscriptions</option>
                                <option value="active">Active</option>
                                <option value="canceled">Canceled</option>
                                <option value="past_due">Past Due</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="no_subscription">No Subscription</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Tier</label>
                            <select v-model="filters.tier" class="form-select">
                                <option value="">All Tiers</option>
                                <option value="free">Free</option>
                                <option value="gold">Gold</option>
                                <option value="platinum">Platinum</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Per Page</label>
                            <select v-model.number="filters.per_page" class="form-select">
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                                <option :value="100">100</option>
                                <option :value="250">250</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn btn-secondary" @click="clearFilters" v-if="filters.search || filters.status || filters.tier">
                                    <i class="bi bi-x-circle me-1"></i>Clear
                                </button>
                                <div class="text-muted small">
                                    Showing {{ customers.from || 0 }} to {{ customers.to || 0 }} of {{ customers.total || 0 }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th @click="toggleSort('name')" role="button" class="user-select-none">
                                        Name
                                        <i :class="getSortIcon('name')" class="bi ms-1 small"></i>
                                    </th>
                                    <th @click="toggleSort('email')" role="button" class="user-select-none">
                                        Email
                                        <i :class="getSortIcon('email')" class="bi ms-1 small"></i>
                                    </th>
                                    <th>Status</th>
                                    <th>Subscription</th>
                                    <th>Plan</th>
                                    <th>Interval</th>
                                    <th>Renewal</th>
                                    <th @click="toggleSort('created_at')" role="button" class="user-select-none">
                                        Joined
                                        <i :class="getSortIcon('created_at')" class="bi ms-1 small"></i>
                                    </th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="customer in customers.data" :key="customer.id">
                                    <td>
                                        <div class="fw-medium">{{ customer.name }}</div>
                                        <div class="text-muted small">ID: {{ customer.id }}</div>
                                    </td>
                                    <td>
                                        <div>{{ customer.email }}</div>
                                        <div v-if="customer.is_ambassador || customer.is_gifted || customer.admin_override" class="mt-1">
                                            <span v-if="customer.is_ambassador" class="badge bg-primary me-1" style="font-size: 0.7rem">
                                                Ambassador
                                            </span>
                                            <span v-if="customer.is_gifted" class="badge bg-success me-1" style="font-size: 0.7rem"> Gifted </span>
                                            <span v-if="customer.admin_override" class="badge bg-purple me-1" style="font-size: 0.7rem">
                                                Override
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <span :class="['badge', getCustomerBadgeClass(customer.status)]">
                                            {{ customer.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <div v-if="customer.subscriptions && customer.subscriptions.length">
                                            <span :class="['badge', getSubscriptionBadgeClass(customer.subscriptions[0].stripe_status)]">
                                                {{ customer.subscriptions[0].stripe_status }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted">No subscription</span>
                                    </td>
                                    <td>
                                        <div class="dropdown dropup">
                                            <button
                                                v-if="customer.tier"
                                                class="btn btn-sm btn-link text-decoration-none p-0"
                                                type="button"
                                                :id="`planDropdown${customer.id}`"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                <span class="badge bg-info">
                                                    {{ customer.tier }}
                                                    <i class="bi bi-chevron-down ms-1"></i>
                                                </span>
                                            </button>
                                            <button
                                                v-else
                                                class="btn btn-sm btn-link text-decoration-none text-muted p-0"
                                                type="button"
                                                :id="`planDropdown${customer.id}`"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                FREE
                                                <i class="bi bi-chevron-down ms-1"></i>
                                            </button>
                                            <ul class="dropdown-menu" :aria-labelledby="`planDropdown${customer.id}`">
                                                <li><h6 class="dropdown-header">Subscription Actions</h6></li>
                                                <li>
                                                    <button class="dropdown-item" @click="openGrantModal(customer)">
                                                        <i class="bi bi-gift me-2"></i>Grant/Update Subscription
                                                    </button>
                                                </li>
                                                <li v-if="customer.subscriptions && customer.subscriptions.length">
                                                    <button class="dropdown-item text-warning" @click="cancelSubscription(customer)">
                                                        <i class="bi bi-x-circle me-2"></i>Cancel at Period End
                                                    </button>
                                                </li>
                                                <li v-if="customer.subscriptions && customer.subscriptions.length">
                                                    <button class="dropdown-item text-danger" @click="cancelSubscription(customer, true)">
                                                        <i class="bi bi-x-circle-fill me-2"></i>Cancel Immediately
                                                    </button>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td>
                                        <div v-if="customer.interval">
                                            <span class="badge bg-secondary">
                                                {{ customer.interval }}
                                            </span>
                                        </div>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <div
                                            v-if="
                                                customer.subscriptions &&
                                                customer.subscriptions.length &&
                                                customer.subscriptions[0].current_period_end
                                            "
                                        >
                                            <small>{{ new Date(customer.subscriptions[0].current_period_end).toLocaleDateString() }}</small>
                                        </div>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ new Date(customer.created_at).toLocaleDateString() }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="dropdown dropup">
                                            <button
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                                type="button"
                                                :id="`actionDropdown${customer.id}`"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                            >
                                                <i class="bi bi-three-dots-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" :aria-labelledby="`actionDropdown${customer.id}`">
                                                <li><h6 class="dropdown-header">User Actions</h6></li>
                                                <li>
                                                    <a :href="route('admin.customers.show', customer.id)" class="dropdown-item">
                                                        <i class="bi bi-eye me-2 text-primary"></i>
                                                        View Customer Details
                                                    </a>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" @click="toggleUserStatus(customer)">
                                                        <i
                                                            :class="[
                                                                'bi me-2',
                                                                customer.status === 'active'
                                                                    ? 'bi-x-circle text-danger'
                                                                    : 'bi-check-circle text-success',
                                                            ]"
                                                        ></i>
                                                        {{ customer.status === 'active' ? 'Disable User' : 'Enable User' }}
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" @click="impersonateUser(customer)">
                                                        <i class="bi bi-person-badge me-2 text-warning"></i>
                                                        Impersonate User
                                                    </button>
                                                </li>
                                                <li>
                                                    <button class="dropdown-item" @click="sendPasswordReset(customer)">
                                                        <i class="bi bi-key me-2"></i>
                                                        Send Password Reset
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider" /></li>
                                                <li><h6 class="dropdown-header">Billing</h6></li>
                                                <li v-if="customer.stripe_id">
                                                    <a
                                                        :href="`https://dashboard.stripe.com/${stripeMode}/customers/${customer.stripe_id}`"
                                                        target="_blank"
                                                        class="dropdown-item"
                                                    >
                                                        <i class="bi bi-credit-card me-2"></i>
                                                        View in Stripe
                                                        <i class="bi bi-box-arrow-up-right ms-1 small"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="customers.last_page > 1" class="card-footer d-flex justify-content-between align-items-center">
                        <div class="text-muted small">Page {{ customers.current_page }} of {{ customers.last_page }}</div>
                        <nav>
                            <ul class="pagination mb-0">
                                <li class="page-item" :class="{ disabled: customers.current_page === 1 }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(1)">
                                        <i class="bi bi-chevron-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item" :class="{ disabled: customers.current_page === 1 }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(customers.current_page - 1)">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>

                                <template v-for="page in customers.last_page" :key="page">
                                    <li
                                        v-if="page === 1 || page === customers.last_page || Math.abs(page - customers.current_page) < 3"
                                        class="page-item"
                                        :class="{ active: page === customers.current_page }"
                                    >
                                        <a class="page-link" href="#" @click.prevent="goToPage(page)">
                                            {{ page }}
                                        </a>
                                    </li>
                                    <li
                                        v-else-if="page === customers.current_page - 3 || page === customers.current_page + 3"
                                        class="page-item disabled"
                                    >
                                        <span class="page-link">...</span>
                                    </li>
                                </template>

                                <li class="page-item" :class="{ disabled: customers.current_page === customers.last_page }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(customers.current_page + 1)">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                                <li class="page-item" :class="{ disabled: customers.current_page === customers.last_page }">
                                    <a class="page-link" href="#" @click.prevent="goToPage(customers.last_page)">
                                        <i class="bi bi-chevron-double-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grant Subscription Modal -->
        <div class="modal fade" :class="{ show: showGrantModal }" :style="{ display: showGrantModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Manage Subscription
                            <span v-if="selectedCustomer" class="text-muted small ms-2">
                                {{ selectedCustomer.name }}
                            </span>
                        </h5>
                        <button type="button" class="btn-close" @click="showGrantModal = false"></button>
                    </div>
                    <form @submit.prevent="grantSubscription">
                        <div class="modal-body">
                            <!-- Current Subscription Info -->
                            <div
                                v-if="selectedCustomer?.subscriptions?.some((s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status))"
                                class="alert alert-info mb-4"
                            >
                                <h6 class="alert-heading mb-3">Current Subscription</h6>
                                <div class="row small">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Plan:</strong> {{ getCurrentPlanName(selectedCustomer) }}</p>
                                        <p class="mb-1">
                                            <strong>Status:</strong>
                                            <span
                                                :class="[
                                                    'badge',
                                                    getSubscriptionBadgeClass(
                                                        selectedCustomer.subscriptions.find(
                                                            (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                                        )?.stripe_status || '',
                                                    ),
                                                ]"
                                            >
                                                {{
                                                    selectedCustomer.subscriptions.find(
                                                        (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                                    )?.stripe_status
                                                }}
                                            </span>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1">
                                            <strong>Next Billing:</strong>
                                            {{
                                                selectedCustomer.subscriptions.find(
                                                    (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                                )?.current_period_end
                                                    ? new Date(
                                                          selectedCustomer.subscriptions.find(
                                                              (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                                          )!.current_period_end!,
                                                      ).toLocaleDateString()
                                                    : 'N/A'
                                            }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Payment Method:</strong>
                                            <span v-if="selectedCustomer.pm_type" class="text-success">
                                                <i class="bi bi-credit-card"></i> {{ selectedCustomer.pm_type }} ending in
                                                {{ selectedCustomer.pm_last_four }}
                                            </span>
                                            <span v-else class="text-danger"> <i class="bi bi-exclamation-circle"></i> No payment method </span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="alert alert-warning mb-4">
                                <i class="bi bi-info-circle me-2"></i>
                                This customer has no active subscription
                            </div>

                            <!-- Payment Method Warning -->
                            <div v-if="!selectedCustomer?.pm_type" class="alert alert-danger mb-4">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This customer has no payment method on file. They will need to add a payment method before
                                they can be automatically billed.
                            </div>

                            <!-- New Subscription Settings -->
                            <h6 class="mb-3">New Subscription Settings</h6>

                            <div class="mb-3">
                                <label class="form-label">Subscription Plan</label>
                                <select
                                    v-model="grantForm.subscription_price"
                                    class="form-select"
                                    :required="grantForm.action_type !== 'cancel'"
                                    @change="updatePlanPreview"
                                >
                                    <option value="">-- Select Plan --</option>
                                    <option v-for="(price, key) in stripePrices" :key="key" :value="price">
                                        {{ key.replace('_', ' ').toUpperCase() }}
                                    </option>
                                </select>
                                <div v-if="grantForm.errors.subscription_price" class="invalid-feedback d-block">
                                    {{ grantForm.errors.subscription_price }}
                                </div>
                            </div>

                            <!-- Plan Preview -->
                            <div v-if="planPreview" class="card bg-light mb-3">
                                <div class="card-body">
                                    <h6 class="card-title">Plan Preview</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Plan:</strong> {{ planPreview.name }}</p>
                                            <p class="mb-1"><strong>Price:</strong> ${{ planPreview.price }} / {{ planPreview.interval }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Tier Access:</strong> {{ planPreview.tier }}</p>
                                            <p class="mb-1"><strong>Next Billing:</strong> {{ planPreview.nextBilling }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Action Type</label>
                                <select v-model="grantForm.action_type" class="form-select" required>
                                    <option value="create">Create New Subscription</option>
                                    <option
                                        value="update"
                                        v-if="
                                            selectedCustomer?.subscriptions?.some(
                                                (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                            )
                                        "
                                    >
                                        Update Existing Subscription
                                    </option>
                                    <option
                                        value="cancel"
                                        v-if="
                                            selectedCustomer?.subscriptions?.some(
                                                (s) => !s.ends_at && ['active', 'trialing'].includes(s.stripe_status),
                                            )
                                        "
                                    >
                                        Cancel Subscription
                                    </option>
                                    <option value="manual">Manual Override (No Billing)</option>
                                </select>
                                <div class="form-text">
                                    <span v-if="grantForm.action_type === 'create'">
                                        Creates a new subscription. If payment method exists, billing will start immediately.
                                    </span>
                                    <span v-else-if="grantForm.action_type === 'update'">
                                        Updates the existing subscription plan. Changes take effect at next billing cycle.
                                    </span>
                                    <span v-else-if="grantForm.action_type === 'cancel'">
                                        Cancels the subscription at the end of the current billing period.
                                    </span>
                                    <span v-else-if="grantForm.action_type === 'manual'">
                                        Grants access without creating a Stripe subscription. No automatic billing.
                                    </span>
                                </div>
                            </div>

                            <div v-if="grantForm.action_type !== 'cancel'" class="mb-3">
                                <label class="form-label">Trial Days (optional)</label>
                                <input
                                    v-model="grantForm.trial_days"
                                    type="number"
                                    min="0"
                                    class="form-control"
                                    placeholder="Leave empty for no trial"
                                />
                                <div class="form-text">Add trial days to delay the first payment</div>
                                <div v-if="grantForm.errors.trial_days" class="invalid-feedback d-block">
                                    {{ grantForm.errors.trial_days }}
                                </div>
                            </div>

                            <!-- Summary -->
                            <div v-if="grantForm.action_type && grantForm.subscription_price" class="alert alert-warning mt-4">
                                <h6 class="alert-heading">Summary of Changes</h6>
                                <ul class="mb-0 small">
                                    <li v-if="grantForm.action_type === 'create'">A new {{ planPreview?.name }} subscription will be created</li>
                                    <li v-if="grantForm.action_type === 'update'">
                                        Subscription will change to {{ planPreview?.name }} at next billing cycle
                                    </li>
                                    <li v-if="grantForm.action_type === 'cancel'">Subscription will be cancelled at the end of the current period</li>
                                    <li v-if="grantForm.action_type === 'manual'">
                                        Customer will have {{ planPreview?.tier }} access without automatic billing
                                    </li>
                                    <li v-if="grantForm.trial_days && grantForm.action_type !== 'cancel'">
                                        {{ grantForm.trial_days }} day trial will be applied
                                    </li>
                                    <li v-if="!selectedCustomer?.pm_type && grantForm.action_type !== 'manual'">
                                        <strong class="text-danger">No automatic billing will occur until payment method is added</strong>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showGrantModal = false" :disabled="grantForm.processing">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="grantForm.processing || !grantForm.action_type">
                                <span v-if="grantForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Processing...
                                </span>
                                <span v-else>
                                    <i class="bi bi-check-circle me-2"></i>
                                    Confirm Changes
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showGrantModal" class="modal-backdrop fade show" @click="showGrantModal = false"></div>
    </AdminLayout>
</template>

<style scoped>
/* Fix dropdown menus being hidden behind table */
.dropdown-menu {
    z-index: 1050 !important;
}

/* Ensure table doesn't overflow and hide dropdowns */
.table-responsive {
    overflow: visible !important;
}

/* Fix for dropup menus at bottom of table */
.dropup .dropdown-menu {
    margin-bottom: 0.5rem;
}

/* Ensure dropdown buttons are properly styled */
.dropdown-toggle::after {
    margin-left: 0.255em;
}
</style>
