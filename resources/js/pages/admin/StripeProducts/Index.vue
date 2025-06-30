<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface StripeProduct {
    id: number;
    name: string;
    tier: string;
    billing_period: string;
    price: number;
    formatted_price: string;
    stripe_product_id: string | null;
    stripe_price_id: string | null;
    is_active: boolean;
    is_connected: boolean;
    features: string[];
    badge_text: string | null;
    sort_order: number;
}

interface StripeApiProduct {
    id: string;
    name: string;
    description: string;
    active: boolean;
}

interface StripeApiPrice {
    id: string;
    unit_amount: number;
    unit_amount_dollars: number;
    currency: string;
    recurring: {
        interval: string;
        interval_count: number;
    } | null;
}

const props = defineProps<{
    products: StripeProduct[];
    tiers: string[];
    billing_periods: string[];
}>();

// Modal states
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showConnectModal = ref(false);
const selectedProduct = ref<StripeProduct | null>(null);
const showInstructions = ref(true);

// Stripe data
const stripeProducts = ref<StripeApiProduct[]>([]);
const stripePrices = ref<StripeApiPrice[]>([]);
const loadingStripe = ref(false);
const selectedStripeProductId = ref('');
const selectedStripePriceId = ref('');

// Forms
const createForm = useForm({
    name: '',
    tier: 'Bronze',
    billing_period: 'monthly',
    price: 0,
    features: [''],
    badge_text: '',
    sort_order: 0,
});

const editForm = useForm({
    name: '',
    price: 0,
    features: [''],
    badge_text: '',
    sort_order: 0,
    is_active: true,
});

// Grouped products by billing period
const groupedProducts = computed(() => {
    const grouped: Record<string, StripeProduct[]> = {};
    props.billing_periods.forEach(period => {
        grouped[period] = props.products
            .filter(p => p.billing_period === period)
            .sort((a, b) => {
                const tierOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];
                return tierOrder.indexOf(a.tier) - tierOrder.indexOf(b.tier);
            });
    });
    return grouped;
});

// Feature management
function addFeature(form: typeof createForm | typeof editForm) {
    form.features.push('');
}

function removeFeature(form: typeof createForm | typeof editForm, index: number) {
    form.features.splice(index, 1);
}

// CRUD operations
function createProduct() {
    createForm.post(route('admin.stripe-products.store'), {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

function editProduct(product: StripeProduct) {
    selectedProduct.value = product;
    editForm.name = product.name;
    editForm.price = product.price;
    editForm.features = product.features.length > 0 ? [...product.features] : [''];
    editForm.badge_text = product.badge_text || '';
    editForm.sort_order = product.sort_order;
    editForm.is_active = product.is_active;
    showEditModal.value = true;
}

function updateProduct() {
    if (!selectedProduct.value) return;
    
    editForm.put(route('admin.stripe-products.update', selectedProduct.value.id), {
        onSuccess: () => {
            showEditModal.value = false;
            editForm.reset();
        },
    });
}

function deleteProduct(product: StripeProduct) {
    if (product.is_connected) {
        alert('Cannot delete a product connected to Stripe. Disconnect it first.');
        return;
    }
    
    if (confirm(`Delete ${product.name}?`)) {
        router.delete(route('admin.stripe-products.destroy', product.id));
    }
}

// Stripe operations
async function fetchStripeProducts() {
    loadingStripe.value = true;
    try {
        const response = await fetch(route('admin.stripe-products.fetch-stripe'));
        const data = await response.json();
        if (data.success) {
            stripeProducts.value = data.products;
        }
    } catch (error) {
        // console.error('Failed to fetch Stripe products:', error);
    } finally {
        loadingStripe.value = false;
    }
}

async function fetchStripePrices(productId: string) {
    try {
        const response = await fetch(route('admin.stripe-products.fetch-prices'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ product_id: productId }),
        });
        const data = await response.json();
        if (data.success) {
            stripePrices.value = data.prices;
        }
    } catch (error) {
        // console.error('Failed to fetch prices:', error);
    }
}

function openConnectModal(product: StripeProduct) {
    selectedProduct.value = product;
    showConnectModal.value = true;
    fetchStripeProducts();
}

async function connectToStripe() {
    if (!selectedProduct.value || !selectedStripeProductId.value || !selectedStripePriceId.value) return;
    
    try {
        const response = await fetch(route('admin.stripe-products.connect', selectedProduct.value.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                stripe_product_id: selectedStripeProductId.value,
                stripe_price_id: selectedStripePriceId.value,
            }),
        });
        
        const data = await response.json();
        if (data.success) {
            router.reload();
            showConnectModal.value = false;
        } else {
            alert(data.error || 'Failed to connect to Stripe');
        }
    } catch (error) {
        // console.error('Failed to connect:', error);
        alert('Failed to connect to Stripe');
    }
}

async function createInStripe(product: StripeProduct) {
    if (confirm(`Create ${product.name} in Stripe?`)) {
        try {
            const response = await fetch(route('admin.stripe-products.create-in-stripe', product.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            const data = await response.json();
            if (data.success) {
                router.reload();
            } else {
                alert(data.error || 'Failed to create in Stripe');
            }
        } catch (error) {
            // console.error('Failed to create in Stripe:', error);
            alert('Failed to create in Stripe');
        }
    }
}

async function disconnectFromStripe(product: StripeProduct) {
    if (confirm(`Disconnect ${product.name} from Stripe?`)) {
        try {
            const response = await fetch(route('admin.stripe-products.disconnect', product.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            
            const data = await response.json();
            if (data.success) {
                router.reload();
            } else {
                alert(data.error || 'Failed to disconnect');
            }
        } catch (error) {
            // console.error('Failed to disconnect:', error);
            alert('Failed to disconnect from Stripe');
        }
    }
}

// Get tier badge class
function getTierBadgeClass(tier: string) {
    switch (tier) {
        case 'Bronze': return 'bg-warning';
        case 'Silver': return 'bg-secondary';
        case 'Gold': return 'bg-warning';
        case 'Platinum': return 'bg-purple';
        default: return 'bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Stripe Product Management" />
        
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Stripe Product Management</h1>
                <button class="btn btn-primary" @click="showCreateModal = true">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add New Product
                </button>
            </div>
            
            <!-- How To Section -->
            <div class="card bg-info bg-opacity-10 border-info mb-4">
                <div class="card-header bg-transparent border-info cursor-pointer" @click="showInstructions = !showInstructions">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-info">
                            <i class="bi bi-info-circle me-2"></i>
                            How Stripe Product Management Works
                        </h5>
                        <i :class="showInstructions ? 'bi-chevron-up' : 'bi-chevron-down'" class="bi text-info"></i>
                    </div>
                </div>
                
                <div v-if="showInstructions" class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">🚀 Getting Started</h6>
                            <ol class="small text-dark">
                                <li>Create product configurations for each tier/period combination</li>
                                <li>Set your desired prices and features</li>
                                <li>Choose one of two connection methods:</li>
                            </ol>
                            
                            <div class="ms-4 mt-3">
                                <div class="mb-3">
                                    <div class="fw-bold text-primary">Option A: Connect to Existing</div>
                                    <p class="text-muted small mb-0">Use products already created in your Stripe dashboard</p>
                                </div>
                                <div class="mb-0">
                                    <div class="fw-bold text-success">Option B: Create in Stripe</div>
                                    <p class="text-muted small mb-0">Let the system create new products in Stripe for you</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">⚡ Important Notes</h6>
                            <ul class="small text-dark">
                                <li>Products must be connected to Stripe to be used for subscriptions</li>
                                <li>Prices can be updated anytime (affects new subscriptions only)</li>
                                <li>Connected products cannot be deleted (disconnect first)</li>
                                <li>The system uses these products automatically at checkout</li>
                                <li>Legacy env variables are still supported as fallback</li>
                            </ul>
                            
                            <div class="alert alert-warning small p-2 mt-3">
                                <strong>Pro Tip:</strong> Create products in Stripe first if you need advanced settings like trial periods or custom metadata.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products by Billing Period -->
            <div class="row g-4">
                <div v-for="(period, key) in groupedProducts" :key="key" class="col-12">
                    <h4 class="text-capitalize mb-3">{{ key }} Plans</h4>
                    
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Tier</th>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Stripe Connection</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in period" :key="product.id">
                                        <td>
                                            <span :class="`badge ${getTierBadgeClass(product.tier)}`">
                                                {{ product.tier }}
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <div class="fw-medium">{{ product.name }}</div>
                                                <div v-if="product.badge_text" class="small text-success">{{ product.badge_text }}</div>
                                            </div>
                                        </td>
                                        <td class="fw-bold">{{ product.formatted_price }}</td>
                                        <td>
                                            <span v-if="product.is_active" class="text-success">
                                                <i class="bi bi-check-circle me-1"></i>Active
                                            </span>
                                            <span v-else class="text-danger">
                                                <i class="bi bi-x-circle me-1"></i>Inactive
                                            </span>
                                        </td>
                                        <td>
                                            <div v-if="product.is_connected">
                                                <div class="text-success small">
                                                    <i class="bi bi-check-circle-fill me-1"></i>Connected
                                                </div>
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ product.stripe_price_id }}</div>
                                            </div>
                                            <span v-else class="text-muted">Not connected</span>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                <button @click="editProduct(product)" 
                                                        class="btn btn-sm btn-outline-primary">
                                                    Edit
                                                </button>
                                                
                                                <template v-if="!product.is_connected">
                                                    <button @click="openConnectModal(product)" 
                                                            class="btn btn-sm btn-outline-success">
                                                        Connect
                                                    </button>
                                                    <button @click="createInStripe(product)" 
                                                            class="btn btn-sm btn-outline-purple">
                                                        Create
                                                    </button>
                                                </template>
                                                <template v-else>
                                                    <button @click="disconnectFromStripe(product)" 
                                                            class="btn btn-sm btn-outline-warning">
                                                        Disconnect
                                                    </button>
                                                </template>
                                                
                                                <button v-if="!product.is_connected" 
                                                        @click="deleteProduct(product)" 
                                                        class="btn btn-sm btn-outline-danger">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Create Modal -->
        <div class="modal fade" :class="{ show: showCreateModal, 'd-block': showCreateModal }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Product</h5>
                        <button type="button" class="btn-close" @click="showCreateModal = false"></button>
                    </div>
                    <form @submit.prevent="createProduct">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name</label>
                                <input v-model="createForm.name" id="name" type="text" class="form-control" required />
                                <div v-if="createForm.errors.name" class="text-danger small mt-1">{{ createForm.errors.name }}</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tier" class="form-label">Tier</label>
                                    <select v-model="createForm.tier" id="tier" class="form-select">
                                        <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                                    </select>
                                    <div v-if="createForm.errors.tier" class="text-danger small mt-1">{{ createForm.errors.tier }}</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="billing_period" class="form-label">Billing Period</label>
                                    <select v-model="createForm.billing_period" id="billing_period" class="form-select">
                                        <option v-for="period in billing_periods" :key="period" :value="period">
                                            {{ period.charAt(0).toUpperCase() + period.slice(1) }}
                                        </option>
                                    </select>
                                    <div v-if="createForm.errors.billing_period" class="text-danger small mt-1">{{ createForm.errors.billing_period }}</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">Price (USD)</label>
                                <input v-model.number="createForm.price" id="price" type="number" step="0.01" min="0" class="form-control" required />
                                <div v-if="createForm.errors.price" class="text-danger small mt-1">{{ createForm.errors.price }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="badge_text" class="form-label">Badge Text (optional)</label>
                                <input v-model="createForm.badge_text" id="badge_text" type="text" class="form-control" placeholder="e.g., Best Value" />
                                <div v-if="createForm.errors.badge_text" class="text-danger small mt-1">{{ createForm.errors.badge_text }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Features</label>
                                <div v-for="(feature, index) in createForm.features" :key="index" class="d-flex gap-2 mb-2">
                                    <input v-model="createForm.features[index]" type="text" class="form-control" placeholder="Feature description" />
                                    <button @click="removeFeature(createForm, index)" type="button" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <button @click="addFeature(createForm)" type="button" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-plus me-1"></i> Add Feature
                                </button>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showCreateModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="createForm.processing">
                                <span v-if="createForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Create Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showCreateModal" class="modal-backdrop fade show"></div>
        
        <!-- Edit Modal -->
        <div class="modal fade" :class="{ show: showEditModal, 'd-block': showEditModal }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Product</h5>
                        <button type="button" class="btn-close" @click="showEditModal = false"></button>
                    </div>
                    <form @submit.prevent="updateProduct">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="edit-name" class="form-label">Product Name</label>
                                <input v-model="editForm.name" id="edit-name" type="text" class="form-control" required />
                                <div v-if="editForm.errors.name" class="text-danger small mt-1">{{ editForm.errors.name }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit-price" class="form-label">Price (USD)</label>
                                <input v-model.number="editForm.price" id="edit-price" type="number" step="0.01" min="0" class="form-control" required />
                                <div v-if="editForm.errors.price" class="text-danger small mt-1">{{ editForm.errors.price }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="edit-badge" class="form-label">Badge Text (optional)</label>
                                <input v-model="editForm.badge_text" id="edit-badge" type="text" class="form-control" placeholder="e.g., Best Value" />
                                <div v-if="editForm.errors.badge_text" class="text-danger small mt-1">{{ editForm.errors.badge_text }}</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Features</label>
                                <div v-for="(feature, index) in editForm.features" :key="index" class="d-flex gap-2 mb-2">
                                    <input v-model="editForm.features[index]" type="text" class="form-control" placeholder="Feature description" />
                                    <button @click="removeFeature(editForm, index)" type="button" class="btn btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                                <button @click="addFeature(editForm)" type="button" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-plus me-1"></i> Add Feature
                                </button>
                            </div>
                            
                            <div class="form-check">
                                <input type="checkbox" v-model="editForm.is_active" class="form-check-input" id="edit-active" />
                                <label class="form-check-label" for="edit-active">Active</label>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" @click="showEditModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="editForm.processing">
                                <span v-if="editForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div v-if="showEditModal" class="modal-backdrop fade show"></div>
        
        <!-- Connect to Stripe Modal -->
        <div class="modal fade" :class="{ show: showConnectModal, 'd-block': showConnectModal }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Connect to Stripe Product</h5>
                        <button type="button" class="btn-close" @click="showConnectModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Stripe Product</label>
                            <div v-if="loadingStripe" class="text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Loading...
                            </div>
                            <select v-else v-model="selectedStripeProductId" @change="fetchStripePrices($event.target.value)" class="form-select">
                                <option value="">Select a product...</option>
                                <option v-for="product in stripeProducts" :key="product.id" :value="product.id">
                                    {{ product.name }} ({{ product.id }})
                                </option>
                            </select>
                        </div>
                        
                        <div v-if="selectedStripeProductId && stripePrices.length > 0" class="mb-3">
                            <label class="form-label">Select Price</label>
                            <select v-model="selectedStripePriceId" class="form-select">
                                <option value="">Select a price...</option>
                                <option v-for="price in stripePrices" :key="price.id" :value="price.id">
                                    ${{ price.unit_amount_dollars }} {{ price.currency.toUpperCase() }}
                                    <template v-if="price.recurring">
                                        / {{ price.recurring.interval }}
                                    </template>
                                    ({{ price.id }})
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showConnectModal = false">Cancel</button>
                        <button @click="connectToStripe" class="btn btn-primary" :disabled="!selectedStripeProductId || !selectedStripePriceId">
                            Connect
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showConnectModal" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>

<style scoped>
.bg-purple {
    background-color: #6f42c1 !important;
}

.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}

.btn-outline-purple:hover {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}

.cursor-pointer {
    cursor: pointer;
}
</style>