<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Affiliate {
    id: number;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    commission_rate: number;
    notes: string | null;
    is_active: boolean;
    users_count: number;
    active_users_count: number;
    created_at: string;
}

interface Customer {
    id: number;
    name: string;
    email: string;
    discord_username: string | null;
    created_at: string;
    affiliate_bound_at: string | null;
    affiliate_bound_plan: string | null;
    subscriptions: any[];
}

interface Props {
    affiliate: Affiliate;
    customers: {
        data: Customer[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    appUrl: string;
}

const props = defineProps<Props>();
const copiedCode = ref<string | null>(null);

function copyShareUrl() {
    const url = `${props.appUrl}/?affiliate=${props.affiliate.code}`;
    
    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            copiedCode.value = props.affiliate.code;
            setTimeout(() => {
                copiedCode.value = null;
            }, 2000);
        }).catch(() => {
            // Fallback if clipboard API fails
            fallbackCopyToClipboard(url);
        });
    } else {
        // Fallback for older browsers or non-HTTPS
        fallbackCopyToClipboard(url);
    }
}

function fallbackCopyToClipboard(text: string) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        copiedCode.value = props.affiliate.code;
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
        alert(`Copy failed. Please copy manually: ${text}`);
    }
    
    document.body.removeChild(textArea);
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getCurrentPlan(customer: Customer): string {
    if (!customer.subscriptions || customer.subscriptions.length === 0) {
        return 'Free';
    }
    
    const activeSubscription = customer.subscriptions.find(sub => sub.stripe_status === 'active');
    return activeSubscription ? activeSubscription.name || 'Paid' : 'Free';
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Affiliate: ${affiliate.name}`" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">{{ affiliate.name }}</h1>
                    <p class="text-muted">Affiliate Details and Customer Management</p>
                </div>
                <div class="d-flex gap-2">
                    <button
                        @click="copyShareUrl"
                        class="btn btn-outline-primary"
                        :title="copiedCode === affiliate.code ? 'Copied!' : 'Copy Share URL'"
                    >
                        <i :class="['bi', copiedCode === affiliate.code ? 'bi-check' : 'bi-clipboard']"></i>
                        {{ copiedCode === affiliate.code ? 'Copied!' : 'Copy Share URL' }}
                    </button>
                    <Link :href="route('admin.affiliates.edit', affiliate.id)" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>
                        Edit Affiliate
                    </Link>
                </div>
            </div>

            <!-- Affiliate Info Cards -->
            <div class="row g-4 mb-4">
                <!-- Basic Info -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Name</label>
                                    <div class="fw-bold">{{ affiliate.name }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Code</label>
                                    <div class="fw-bold">
                                        <code>{{ affiliate.code }}</code>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Email</label>
                                    <div>{{ affiliate.email || '-' }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Phone</label>
                                    <div>{{ affiliate.phone || '-' }}</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Commission Rate</label>
                                    <div class="fw-bold text-success">{{ affiliate.commission_rate }}%</div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted">Status</label>
                                    <div>
                                        <span :class="['badge', affiliate.is_active ? 'bg-success' : 'bg-secondary']">
                                            {{ affiliate.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-12" v-if="affiliate.notes">
                                    <label class="form-label small text-muted">Notes</label>
                                    <div class="text-muted">{{ affiliate.notes }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Performance Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-4 text-center">
                                <div class="col-6">
                                    <div class="h2 mb-0 text-primary">{{ affiliate.users_count }}</div>
                                    <small class="text-muted">Total Customers</small>
                                </div>
                                <div class="col-6">
                                    <div class="h2 mb-0 text-success">{{ affiliate.active_users_count }}</div>
                                    <small class="text-muted">Active Customers</small>
                                </div>
                                <div class="col-12">
                                    <hr class="my-3">
                                    <div class="small text-muted">
                                        Member since {{ formatDate(affiliate.created_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Affiliated Customers ({{ customers.total }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Discord</th>
                                <th>Registered</th>
                                <th>Bound At</th>
                                <th>Current Plan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="customer in customers.data" :key="customer.id">
                                <td>
                                    <div class="fw-bold">{{ customer.name }}</div>
                                </td>
                                <td>{{ customer.email }}</td>
                                <td>{{ customer.discord_username || '-' }}</td>
                                <td>{{ formatDate(customer.created_at) }}</td>
                                <td>
                                    {{ customer.affiliate_bound_at ? formatDate(customer.affiliate_bound_at) : 'On registration' }}
                                </td>
                                <td>
                                    <span :class="['badge', getCurrentPlan(customer) === 'Free' ? 'bg-secondary' : 'bg-success']">
                                        {{ getCurrentPlan(customer) }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="customers.data.length === 0" class="text-center py-5">
                    <i class="bi bi-person-x text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">No customers found for this affiliate.</p>
                </div>

                <!-- Pagination -->
                <div v-if="customers.last_page > 1" class="card-footer">
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: customers.current_page === 1 }">
                                <Link
                                    class="page-link"
                                    :href="route('admin.affiliates.show', { affiliate: affiliate.id, page: customers.current_page - 1 })"
                                    :disabled="customers.current_page === 1"
                                >
                                    Previous
                                </Link>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">
                                    Page {{ customers.current_page }} of {{ customers.last_page }}
                                </span>
                            </li>
                            <li class="page-item" :class="{ disabled: customers.current_page === customers.last_page }">
                                <Link
                                    class="page-link"
                                    :href="route('admin.affiliates.show', { affiliate: affiliate.id, page: customers.current_page + 1 })"
                                    :disabled="customers.current_page === customers.last_page"
                                >
                                    Next
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>

            <!-- Back Button -->
            <div class="mt-4">
                <Link :href="route('admin.affiliates.index')" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Affiliates
                </Link>
            </div>
        </div>
    </AdminLayout>
</template>