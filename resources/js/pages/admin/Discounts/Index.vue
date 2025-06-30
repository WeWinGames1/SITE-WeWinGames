<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { debounce } from 'lodash';

interface DiscountCode {
    id: number;
    code: string;
    description: string | null;
    discount_type: 'percentage' | 'fixed';
    discount_amount: number;
    formatted_discount: string;
    apply_to: 'first_payment' | 'forever' | 'specific_months';
    months_count: number | null;
    max_uses: number | null;
    max_uses_per_customer: number;
    times_used: number;
    valid_from: string | null;
    valid_until: string | null;
    is_active: boolean;
    is_valid: boolean;
    redemptions_count: number;
    created_at: string;
    creator: {
        id: number;
        name: string;
    } | null;
}

interface Product {
    id: number;
    name: string;
    tier: string;
    billing_period: string;
    stripe_product_id: string | null;
}

interface Props {
    discountCodes: {
        data: DiscountCode[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
        search?: string;
    };
    products: Product[];
}

const props = defineProps<Props>();

// State
const showCreateModal = ref(false);
const showDetailsModal = ref(false);
const selectedCode = ref<DiscountCode | null>(null);
const codeDetails = ref<any>(null);
const copiedCode = ref<string | null>(null);

// Forms
const filterForm = useForm({
    status: props.filters.status || '',
    search: props.filters.search || '',
});

const createForm = useForm({
    code: '',
    description: '',
    discount_type: 'percentage' as 'percentage' | 'fixed',
    discount_amount: 10,
    apply_to: 'first_payment' as 'first_payment' | 'forever' | 'specific_months',
    months_count: null as number | null,
    max_uses: null as number | null,
    max_uses_per_customer: 1,
    valid_from: '',
    valid_until: '',
    applicable_products: [] as number[],
    minimum_amount: null as number | null,
    create_in_stripe: false,
});

// Debounced search
const debouncedSearch = debounce((value: string) => {
    filterForm.search = value;
    applyFilters();
}, 300);

// Methods
function applyFilters() {
    router.get(route('admin.discounts.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    filterForm.reset();
    applyFilters();
}

function createDiscount() {
    createForm.post(route('admin.discounts.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

async function viewDetails(code: DiscountCode) {
    selectedCode.value = code;
    showDetailsModal.value = true;
    
    // Fetch detailed info including redemptions
    try {
        const response = await fetch(route('admin.discounts.show', code.id));
        const data = await response.json();
        codeDetails.value = data;
    } catch (error) {
        console.error('Failed to fetch discount details:', error);
    }
}

function deactivateCode(code: DiscountCode) {
    if (confirm(`Deactivate discount code ${code.code}?`)) {
        router.post(route('admin.discounts.deactivate', code.id), {}, {
            preserveScroll: true,
        });
    }
}

function copyToClipboard(code: string) {
    navigator.clipboard.writeText(code);
    copiedCode.value = code;
    setTimeout(() => {
        copiedCode.value = null;
    }, 2000);
}

function getApplyToLabel(applyTo: string, months?: number | null): string {
    switch (applyTo) {
        case 'first_payment':
            return 'First payment only';
        case 'forever':
            return 'All payments';
        case 'specific_months':
            return `First ${months} month${months !== 1 ? 's' : ''}`;
        default:
            return applyTo;
    }
}

function getStatusLabel(code: DiscountCode): string {
    if (!code.is_active) return 'Inactive';
    if (!code.is_valid) {
        if (code.valid_until && new Date(code.valid_until) < new Date()) {
            return 'Expired';
        }
        if (code.max_uses && code.times_used >= code.max_uses) {
            return 'Limit Reached';
        }
        return 'Invalid';
    }
    return 'Active';
}

function getStatusBadgeClass(code: DiscountCode): string {
    const status = getStatusLabel(code);
    switch (status) {
        case 'Active':
            return 'badge bg-success';
        case 'Inactive':
            return 'badge bg-secondary';
        case 'Expired':
            return 'badge bg-danger';
        case 'Limit Reached':
            return 'badge bg-warning text-dark';
        default:
            return 'badge bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Discount Codes" />
        
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="row mb-4">
                <div class="col">
                    <h1 class="h3 mb-1">Discount Code Management</h1>
                    <p class="text-muted">Create and manage discount codes for subscriptions</p>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary" @click="showCreateModal = true">
                        <i class="bi bi-plus-lg"></i>
                        Create Discount Code
                    </button>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
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
                                    placeholder="Search codes..."
                                    :value="filterForm.search"
                                    @input="debouncedSearch($event.target.value)"
                                />
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label small">Status</label>
                            <select v-model="filterForm.status" @change="applyFilters" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="expired">Expired</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <button type="button" class="btn btn-secondary w-100" @click="clearFilters">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Discount Codes Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Applies To</th>
                                <th>Usage</th>
                                <th>Valid Until</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="code in discountCodes.data" :key="code.id">
                                <td>
                                    <div>
                                        <div class="d-flex align-items-center">
                                            <code class="fw-medium me-2">{{ code.code }}</code>
                                            <button 
                                                @click="copyToClipboard(code.code)"
                                                class="btn btn-sm btn-link p-0"
                                                title="Copy to clipboard"
                                            >
                                                <i v-if="copiedCode !== code.code" class="bi bi-clipboard"></i>
                                                <i v-else class="bi bi-check-circle text-success"></i>
                                            </button>
                                        </div>
                                        <small v-if="code.description" class="text-muted">
                                            {{ code.description }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <strong>{{ code.formatted_discount }}</strong>
                                    <span class="text-muted ms-1 small">{{ code.discount_type }}</span>
                                </td>
                                <td>
                                    <small>{{ getApplyToLabel(code.apply_to, code.months_count) }}</small>
                                </td>
                                <td>
                                    <div>
                                        <div>{{ code.times_used }} / {{ code.max_uses || '∞' }}</div>
                                        <small class="text-muted">
                                            Max {{ code.max_uses_per_customer }} per customer
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <small>{{ code.valid_until ? new Date(code.valid_until).toLocaleDateString() : 'No expiry' }}</small>
                                </td>
                                <td>
                                    <span :class="getStatusBadgeClass(code)">
                                        {{ getStatusLabel(code) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button @click="viewDetails(code)" 
                                                class="btn btn-outline-primary"
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button v-if="code.is_active"
                                                @click="deactivateCode(code)" 
                                                class="btn btn-outline-danger"
                                                title="Deactivate">
                                            <i class="bi bi-x-circle"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty state -->
                <div v-if="discountCodes.data.length === 0" class="text-center py-5">
                    <i class="bi bi-percent display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No discount codes found</p>
                    <button type="button" class="btn btn-primary" @click="showCreateModal = true">
                        <i class="bi bi-plus-lg"></i>
                        Create your first discount
                    </button>
                </div>
                
                <!-- Pagination -->
                <div v-if="discountCodes.links.length > 3" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ discountCodes.meta.from }} to {{ discountCodes.meta.to }} of {{ discountCodes.meta.total }} results
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li v-for="link in discountCodes.links" :key="link.label" 
                                    class="page-item" 
                                    :class="{ 'active': link.active, 'disabled': !link.url }">
                                    <button v-if="link.url"
                                            @click="router.get(link.url)"
                                            class="page-link"
                                            v-html="link.label" />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Create Discount Modal -->
        <div class="modal fade" :class="{ 'show': showCreateModal }" :style="{ display: showCreateModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Discount Code</h5>
                        <button type="button" class="btn-close" @click="showCreateModal = false"></button>
                    </div>
                    <form @submit.prevent="createDiscount">
                        <div class="modal-body">
                            <!-- Basic Information -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Basic Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="code" class="form-label">Discount Code</label>
                                            <input 
                                                v-model="createForm.code" 
                                                id="code" 
                                                type="text"
                                                class="form-control text-uppercase font-monospace" 
                                                :class="{ 'is-invalid': createForm.errors.code }"
                                                placeholder="e.g., SAVE20"
                                                @input="e => createForm.code = e.target.value.toUpperCase()"
                                            />
                                            <div class="form-text">Enter a unique code or leave blank to auto-generate</div>
                                            <div v-if="createForm.errors.code" class="invalid-feedback">{{ createForm.errors.code }}</div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="description" class="form-label">Internal Description</label>
                                            <input 
                                                v-model="createForm.description" 
                                                id="description" 
                                                type="text"
                                                class="form-control" 
                                                :class="{ 'is-invalid': createForm.errors.description }"
                                                placeholder="e.g., Black Friday 2024 promotion"
                                            />
                                            <div class="form-text">For admin reference only (not shown to customers)</div>
                                            <div v-if="createForm.errors.description" class="invalid-feedback">{{ createForm.errors.description }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Discount Configuration -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Discount Configuration</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label for="discount_type" class="form-label">Discount Type</label>
                                            <select v-model="createForm.discount_type" id="discount_type" class="form-select">
                                                <option value="percentage">Percentage Off</option>
                                                <option value="fixed">Fixed Amount Off</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="discount_amount" class="form-label">Discount Amount</label>
                                            <div class="input-group">
                                                <span v-if="createForm.discount_type === 'fixed'" class="input-group-text">$</span>
                                                <input 
                                                    v-model.number="createForm.discount_amount" 
                                                    id="discount_amount" 
                                                    type="number" 
                                                    step="0.01" 
                                                    min="0"
                                                    :max="createForm.discount_type === 'percentage' ? 100 : null"
                                                    class="form-control" 
                                                    :class="{ 'is-invalid': createForm.errors.discount_amount }"
                                                    required 
                                                />
                                                <span v-if="createForm.discount_type === 'percentage'" class="input-group-text">%</span>
                                                <div v-if="createForm.errors.discount_amount" class="invalid-feedback">{{ createForm.errors.discount_amount }}</div>
                                            </div>
                                            <div class="form-text">
                                                <span v-if="createForm.discount_type === 'percentage'">Enter 0-100</span>
                                                <span v-else>Amount in dollars</span>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <label for="apply_to" class="form-label">Duration</label>
                                            <select v-model="createForm.apply_to" id="apply_to" class="form-select">
                                                <option value="first_payment">First Payment Only</option>
                                                <option value="forever">All Payments (Forever)</option>
                                                <option value="specific_months">Specific Number of Months</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div v-if="createForm.apply_to === 'specific_months'" class="row g-3 mt-1">
                                        <div class="col-md-4">
                                            <label for="months_count" class="form-label">Number of Months</label>
                                            <input v-model.number="createForm.months_count" 
                                                   id="months_count" 
                                                   type="number" 
                                                   min="1" 
                                                   class="form-control" 
                                                   :class="{ 'is-invalid': createForm.errors.months_count }"
                                                   required />
                                            <div class="form-text">Discount will apply for this many billing cycles</div>
                                            <div v-if="createForm.errors.months_count" class="invalid-feedback">{{ createForm.errors.months_count }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Usage Limits -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Usage Limits</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="max_uses" class="form-label">Total Usage Limit</label>
                                            <input 
                                                v-model.number="createForm.max_uses" 
                                                id="max_uses" 
                                                type="number" 
                                                min="1" 
                                                class="form-control" 
                                                :class="{ 'is-invalid': createForm.errors.max_uses }"
                                                placeholder="Unlimited"
                                            />
                                            <div class="form-text">Maximum total redemptions (leave empty for unlimited)</div>
                                            <div v-if="createForm.errors.max_uses" class="invalid-feedback">{{ createForm.errors.max_uses }}</div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="max_uses_per_customer" class="form-label">Per Customer Limit</label>
                                            <input 
                                                v-model.number="createForm.max_uses_per_customer" 
                                                id="max_uses_per_customer" 
                                                type="number" 
                                                min="1" 
                                                class="form-control" 
                                                :class="{ 'is-invalid': createForm.errors.max_uses_per_customer }"
                                                required 
                                            />
                                            <div class="form-text">How many times each customer can use this code</div>
                                            <div v-if="createForm.errors.max_uses_per_customer" class="invalid-feedback">{{ createForm.errors.max_uses_per_customer }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Validity Period -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Validity Period</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="valid_from" class="form-label">Start Date</label>
                                            <input v-model="createForm.valid_from" 
                                                   id="valid_from" 
                                                   type="datetime-local" 
                                                   class="form-control"
                                                   :class="{ 'is-invalid': createForm.errors.valid_from }" />
                                            <div class="form-text">When this code becomes active (optional)</div>
                                            <div v-if="createForm.errors.valid_from" class="invalid-feedback">{{ createForm.errors.valid_from }}</div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label for="valid_until" class="form-label">Expiration Date</label>
                                            <input v-model="createForm.valid_until" 
                                                   id="valid_until" 
                                                   type="datetime-local" 
                                                   class="form-control"
                                                   :class="{ 'is-invalid': createForm.errors.valid_until }" />
                                            <div class="form-text">When this code expires (optional)</div>
                                            <div v-if="createForm.errors.valid_until" class="invalid-feedback">{{ createForm.errors.valid_until }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Product Restrictions -->
                            <div class="card mb-4">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0">Product Restrictions</h6>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted mb-3">Select which products this discount applies to. Leave empty to apply to all products.</p>
                                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                                        <div v-for="product in products" :key="product.id" class="form-check">
                                            <input 
                                                type="checkbox" 
                                                :id="`product-${product.id}`"
                                                :value="product.id"
                                                v-model="createForm.applicable_products"
                                                class="form-check-input"
                                            />
                                            <label :for="`product-${product.id}`" class="form-check-label">
                                                {{ product.name }} - {{ product.tier }} ({{ product.billing_period }})
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="createForm.errors.applicable_products" class="text-danger mt-2">{{ createForm.errors.applicable_products }}</div>
                                </div>
                            </div>
                            
                            <!-- Stripe Integration -->
                            <div class="alert alert-info">
                                <div class="form-check">
                                    <input type="checkbox" v-model="createForm.create_in_stripe" class="form-check-input" id="create_in_stripe" />
                                    <label class="form-check-label" for="create_in_stripe">
                                        <strong>Create in Stripe</strong>
                                        <br>
                                        <small>Also create this discount code in your Stripe account for automatic application</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="createForm.processing">
                                <span v-if="createForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Creating...
                                </span>
                                <span v-else>Create Discount Code</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showCreateModal" class="modal-backdrop fade show"></div>
        
        <!-- Details Modal -->
        <div class="modal fade" :class="{ 'show': showDetailsModal }" :style="{ display: showDetailsModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Discount Code Details</h5>
                        <button type="button" class="btn-close" @click="showDetailsModal = false; selectedCode = null; codeDetails = null"></button>
                    </div>
                    <div v-if="selectedCode && codeDetails" class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Code</h6>
                                <p class="font-monospace fs-5 mb-0">{{ codeDetails.discount_code.code }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Status</h6>
                                <span :class="getStatusBadgeClass(codeDetails.discount_code)">
                                    {{ getStatusLabel(codeDetails.discount_code) }}
                                </span>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Discount</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.formatted_discount }} ({{ codeDetails.discount_code.discount_type }})</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Applies To</h6>
                                <p class="mb-0">{{ getApplyToLabel(codeDetails.discount_code.apply_to, codeDetails.discount_code.months_count) }}</p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Usage</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.times_used }} / {{ codeDetails.discount_code.max_uses || '∞' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Per Customer Limit</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.max_uses_per_customer }}</p>
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Valid From</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.valid_from ? new Date(codeDetails.discount_code.valid_from).toLocaleString() : 'Always' }}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-1">Valid Until</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.valid_until ? new Date(codeDetails.discount_code.valid_until).toLocaleString() : 'Never expires' }}</p>
                            </div>
                            
                            <div v-if="codeDetails.discount_code.description" class="col-12">
                                <h6 class="text-muted mb-1">Description</h6>
                                <p class="mb-0">{{ codeDetails.discount_code.description }}</p>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3">Redemptions ({{ codeDetails.redemptions.length }})</h6>
                        <div v-if="codeDetails.redemptions.length > 0" class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="redemption in codeDetails.redemptions" :key="redemption.id">
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ redemption.user.name }}</div>
                                                <small class="text-muted">{{ redemption.user.email }}</small>
                                            </div>
                                        </td>
                                        <td>${{ redemption.discount_applied }}</td>
                                        <td>
                                            <small>{{ new Date(redemption.created_at).toLocaleDateString() }}</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-muted">No redemptions yet</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showDetailsModal = false; selectedCode = null; codeDetails = null">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDetailsModal" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>