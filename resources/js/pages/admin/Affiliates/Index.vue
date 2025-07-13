<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { debounce } from 'lodash';

interface Affiliate {
    id: number;
    name: string;
    code: string;
    email: string | null;
    phone: string | null;
    commission_rate: number;
    is_active: boolean;
    users_count: number;
    active_users_count: number;
    created_at: string;
}

interface Props {
    affiliates: {
        data: Affiliate[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        status?: string;
    };
    appUrl: string;
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');
const showCustomersModal = ref(false);
const selectedAffiliate = ref<Affiliate | null>(null);
const customers = ref<any[]>([]);
const loadingCustomers = ref(false);
const copiedCode = ref<string | null>(null);

const debouncedSearch = debounce((value: string) => {
    router.get(route('admin.affiliates.index'), 
        { search: value, status: status.value },
        { preserveState: true, preserveScroll: true }
    );
}, 300);

function updateSearch(value: string) {
    search.value = value;
    debouncedSearch(value);
}

function updateStatus(value: string) {
    status.value = value;
    router.get(route('admin.affiliates.index'), 
        { search: search.value, status: value },
        { preserveState: true, preserveScroll: true }
    );
}

function copyShareUrl(affiliate: Affiliate) {
    const url = `${props.appUrl}/?affiliate=${affiliate.code}`;
    
    // Try modern clipboard API first
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(() => {
            copiedCode.value = affiliate.code;
            setTimeout(() => {
                copiedCode.value = null;
            }, 2000);
        }).catch(() => {
            // Fallback if clipboard API fails
            fallbackCopyToClipboard(url, affiliate.code);
        });
    } else {
        // Fallback for older browsers or non-HTTPS
        fallbackCopyToClipboard(url, affiliate.code);
    }
}

function fallbackCopyToClipboard(text: string, code: string) {
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
        copiedCode.value = code;
        setTimeout(() => {
            copiedCode.value = null;
        }, 2000);
    } catch (err) {
        console.error('Failed to copy text: ', err);
        alert(`Copy failed. Please copy manually: ${text}`);
    }
    
    document.body.removeChild(textArea);
}

async function showCustomers(affiliate: Affiliate) {
    selectedAffiliate.value = affiliate;
    showCustomersModal.value = true;
    loadingCustomers.value = true;
    
    try {
        const response = await fetch(route('admin.affiliates.customers', affiliate.id));
        const data = await response.json();
        customers.value = data.customers;
    } catch (error) {
        console.error('Failed to load customers:', error);
    } finally {
        loadingCustomers.value = false;
    }
}

function deleteAffiliate(affiliate: Affiliate) {
    if (confirm(`Are you sure you want to delete affiliate "${affiliate.name}"?`)) {
        router.delete(route('admin.affiliates.destroy', affiliate.id));
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Affiliates" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Affiliate Management</h1>
                    <p class="text-muted">Manage affiliate partners and track their referrals</p>
                </div>
                <Link :href="route('admin.affiliates.create')" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add New Affiliate
                </Link>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Filter Affiliates</h6>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label small">Search</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    type="search"
                                    class="form-control"
                                    placeholder="Search by name, code, or email..."
                                    :value="search"
                                    @input="updateSearch($event.target.value)"
                                />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select class="form-select" :value="status" @change="updateStatus($event.target.value)">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button
                                v-if="search || status"
                                @click="search = ''; status = ''; updateSearch(''); updateStatus('')"
                                type="button"
                                class="btn btn-outline-secondary w-100"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Affiliates Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Email</th>
                                <th>Commission</th>
                                <th>Customers</th>
                                <th>Active</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="affiliate in affiliates.data" :key="affiliate.id">
                                <td>
                                    <Link :href="route('admin.affiliates.show', affiliate.id)" class="text-decoration-none">
                                        {{ affiliate.name }}
                                    </Link>
                                </td>
                                <td>
                                    <code>{{ affiliate.code }}</code>
                                </td>
                                <td>{{ affiliate.email || '-' }}</td>
                                <td>{{ affiliate.commission_rate }}%</td>
                                <td>
                                    <button 
                                        @click="showCustomers(affiliate)"
                                        class="btn btn-sm btn-link p-0"
                                        :disabled="affiliate.users_count === 0"
                                    >
                                        {{ affiliate.users_count }}
                                    </button>
                                </td>
                                <td>{{ affiliate.active_users_count }}</td>
                                <td>
                                    <span :class="['badge', affiliate.is_active ? 'bg-success' : 'bg-secondary']">
                                        {{ affiliate.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button
                                            @click="copyShareUrl(affiliate)"
                                            class="btn btn-sm btn-outline-primary"
                                            :title="copiedCode === affiliate.code ? 'Copied!' : 'Copy Share URL'"
                                        >
                                            <i :class="['bi', copiedCode === affiliate.code ? 'bi-check' : 'bi-clipboard']"></i>
                                        </button>
                                        <Link
                                            :href="route('admin.affiliates.edit', affiliate.id)"
                                            class="btn btn-sm btn-outline-secondary"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </Link>
                                        <button
                                            @click="deleteAffiliate(affiliate)"
                                            class="btn btn-sm btn-outline-danger"
                                            :disabled="affiliate.users_count > 0"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="affiliates.data.length === 0" class="text-center py-5">
                    <p class="text-muted mb-0">No affiliates found.</p>
                </div>

                <!-- Pagination -->
                <div v-if="affiliates.last_page > 1" class="card-footer">
                    <nav>
                        <ul class="pagination mb-0">
                            <li class="page-item" :class="{ disabled: affiliates.current_page === 1 }">
                                <Link
                                    class="page-link"
                                    :href="route('admin.affiliates.index', { page: affiliates.current_page - 1, search: search, status: status })"
                                    :disabled="affiliates.current_page === 1"
                                >
                                    Previous
                                </Link>
                            </li>
                            <li class="page-item active">
                                <span class="page-link">
                                    Page {{ affiliates.current_page }} of {{ affiliates.last_page }}
                                </span>
                            </li>
                            <li class="page-item" :class="{ disabled: affiliates.current_page === affiliates.last_page }">
                                <Link
                                    class="page-link"
                                    :href="route('admin.affiliates.index', { page: affiliates.current_page + 1, search: search, status: status })"
                                    :disabled="affiliates.current_page === affiliates.last_page"
                                >
                                    Next
                                </Link>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Customers Modal -->
        <div v-if="showCustomersModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Customers for {{ selectedAffiliate?.name }}
                        </h5>
                        <button type="button" class="btn-close" @click="showCustomersModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="loadingCustomers" class="text-center py-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else-if="customers.length === 0" class="text-center py-4">
                            <p class="text-muted mb-0">No customers found.</p>
                        </div>
                        <div v-else class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Discord</th>
                                        <th>Registered</th>
                                        <th>Bound At</th>
                                        <th>Plan</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="customer in customers" :key="customer.id">
                                        <td>{{ customer.name }}</td>
                                        <td>{{ customer.email }}</td>
                                        <td>{{ customer.discord_username || '-' }}</td>
                                        <td>{{ customer.registered_at }}</td>
                                        <td>{{ customer.bound_at || 'On registration' }}</td>
                                        <td>{{ customer.current_plan }}</td>
                                        <td>
                                            <span :class="['badge', customer.is_active ? 'bg-success' : 'bg-secondary']">
                                                {{ customer.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showCustomersModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>