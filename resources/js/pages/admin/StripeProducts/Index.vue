<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Dialog as Modal } from "@/components/ui/dialog";
import { Button as PrimaryButton } from "@/components/ui/button";
import { Button as SecondaryButton } from "@/components/ui/button";
import { Label as InputLabel } from "@/components/ui/label";
import { Input as TextInput } from "@/components/ui/input";
import InputError from '@/components/InputError.vue';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/24/outline';

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
</script>

<template>
    <AppLayout :breadcrumbs="{ title: 'Stripe Product Management', href: route('admin.stripe-products.index') }">
        <Head title="Stripe Product Management" />
        
        <div class="max-w-7xl mx-auto p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Stripe Product Management</h1>
                <PrimaryButton @click="showCreateModal = true">
                    Add New Product
                </PrimaryButton>
            </div>
            
            <!-- How To Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg mb-6">
                <button 
                    @click="showInstructions = !showInstructions"
                    class="w-full p-4 flex items-center justify-between text-left hover:bg-blue-100 transition-colors"
                >
                    <h2 class="text-lg font-semibold text-blue-900">How Stripe Product Management Works</h2>
                    <ChevronDownIcon v-if="!showInstructions" class="w-5 h-5 text-blue-600" />
                    <ChevronUpIcon v-else class="w-5 h-5 text-blue-600" />
                </button>
                
                <div v-if="showInstructions" class="px-6 pb-6">
                    <div class="grid md:grid-cols-2 gap-6 text-sm text-blue-800">
                    <div>
                        <h3 class="font-semibold mb-2">🚀 Getting Started</h3>
                        <ol class="space-y-1 ml-4">
                            <li>1. Create product configurations for each tier/period combination</li>
                            <li>2. Set your desired prices and features</li>
                            <li>3. Choose one of two connection methods:</li>
                        </ol>
                        
                        <div class="mt-3 ml-6 space-y-2">
                            <div>
                                <span class="font-semibold">Option A:</span> Connect to Existing
                                <p class="text-xs mt-1">Use products already created in your Stripe dashboard</p>
                            </div>
                            <div>
                                <span class="font-semibold">Option B:</span> Create in Stripe
                                <p class="text-xs mt-1">Let the system create new products in Stripe for you</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="font-semibold mb-2">⚡ Important Notes</h3>
                        <ul class="space-y-1 ml-4 list-disc">
                            <li>Products must be connected to Stripe to be used for subscriptions</li>
                            <li>Prices can be updated anytime (affects new subscriptions only)</li>
                            <li>Connected products cannot be deleted (disconnect first)</li>
                            <li>The system uses these products automatically at checkout</li>
                            <li>Legacy env variables are still supported as fallback</li>
                        </ul>
                        
                        <div class="mt-3 p-2 bg-yellow-100 rounded">
                            <p class="text-xs text-yellow-800">
                                <strong>Pro Tip:</strong> Create products in Stripe first if you need advanced settings like trial periods or custom metadata.
                            </p>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
            
            <!-- Products by Billing Period -->
            <div class="space-y-8">
                <div v-for="(period, key) in groupedProducts" :key="key">
                    <h2 class="text-xl font-semibold mb-4 capitalize">{{ key }} Plans</h2>
                    
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stripe Connection</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="product in period" :key="product.id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full"
                                              :class="{
                                                  'bg-amber-100 text-amber-800': product.tier === 'Bronze',
                                                  'bg-gray-100 text-gray-800': product.tier === 'Silver',
                                                  'bg-yellow-100 text-yellow-800': product.tier === 'Gold',
                                                  'bg-purple-100 text-purple-800': product.tier === 'Platinum',
                                              }">
                                            {{ product.tier }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div>
                                            <div class="font-medium">{{ product.name }}</div>
                                            <div v-if="product.badge_text" class="text-sm text-green-600">{{ product.badge_text }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 font-semibold">{{ product.formatted_price }}</td>
                                    <td class="px-6 py-4">
                                        <span v-if="product.is_active" class="text-green-600">Active</span>
                                        <span v-else class="text-red-600">Inactive</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="product.is_connected" class="space-y-1">
                                            <div class="text-sm text-green-600">✓ Connected</div>
                                            <div class="text-xs text-gray-500">{{ product.stripe_price_id }}</div>
                                        </div>
                                        <span v-else class="text-gray-400">Not connected</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-2">
                                            <button @click="editProduct(product)" 
                                                    class="text-blue-600 hover:text-blue-800 text-sm">
                                                Edit
                                            </button>
                                            
                                            <template v-if="!product.is_connected">
                                                <button @click="openConnectModal(product)" 
                                                        class="text-green-600 hover:text-green-800 text-sm">
                                                    Connect to Stripe
                                                </button>
                                                <button @click="createInStripe(product)" 
                                                        class="text-purple-600 hover:text-purple-800 text-sm">
                                                    Create in Stripe
                                                </button>
                                            </template>
                                            <template v-else>
                                                <button @click="disconnectFromStripe(product)" 
                                                        class="text-orange-600 hover:text-orange-800 text-sm">
                                                    Disconnect
                                                </button>
                                            </template>
                                            
                                            <button v-if="!product.is_connected" 
                                                    @click="deleteProduct(product)" 
                                                    class="text-red-600 hover:text-red-800 text-sm">
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
        
        <!-- Create Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Create New Product</h2>
                
                <form @submit.prevent="createProduct" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Product Name" />
                        <TextInput v-model="createForm.name" id="name" class="w-full" required />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="tier" value="Tier" />
                            <select v-model="createForm.tier" id="tier" class="w-full">
                                <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                            </select>
                            <InputError :message="createForm.errors.tier" />
                        </div>
                        
                        <div>
                            <InputLabel for="billing_period" value="Billing Period" />
                            <select v-model="createForm.billing_period" id="billing_period" class="w-full">
                                <option v-for="period in billing_periods" :key="period" :value="period">
                                    {{ period.charAt(0).toUpperCase() + period.slice(1) }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.billing_period" />
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel for="price" value="Price (USD)" />
                        <TextInput v-model.number="createForm.price" id="price" type="number" step="0.01" min="0" class="w-full" required />
                        <InputError :message="createForm.errors.price" />
                    </div>
                    
                    <div>
                        <InputLabel for="badge_text" value="Badge Text (optional)" />
                        <TextInput v-model="createForm.badge_text" id="badge_text" class="w-full" placeholder="e.g., Best Value" />
                        <InputError :message="createForm.errors.badge_text" />
                    </div>
                    
                    <div>
                        <InputLabel value="Features" />
                        <div class="space-y-2">
                            <div v-for="(feature, index) in createForm.features" :key="index" class="flex gap-2">
                                <TextInput v-model="createForm.features[index]" class="flex-1" placeholder="Feature description" />
                                <button @click="removeFeature(createForm, index)" type="button" class="text-red-600">Remove</button>
                            </div>
                        </div>
                        <button @click="addFeature(createForm)" type="button" class="mt-2 text-blue-600 text-sm">+ Add Feature</button>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="createForm.processing">Create Product</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        
        <!-- Edit Modal -->
        <Modal :show="showEditModal" @close="showEditModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Edit Product</h2>
                
                <form @submit.prevent="updateProduct" class="space-y-4">
                    <div>
                        <InputLabel for="edit-name" value="Product Name" />
                        <TextInput v-model="editForm.name" id="edit-name" class="w-full" required />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    
                    <div>
                        <InputLabel for="edit-price" value="Price (USD)" />
                        <TextInput v-model.number="editForm.price" id="edit-price" type="number" step="0.01" min="0" class="w-full" required />
                        <InputError :message="editForm.errors.price" />
                    </div>
                    
                    <div>
                        <InputLabel for="edit-badge" value="Badge Text (optional)" />
                        <TextInput v-model="editForm.badge_text" id="edit-badge" class="w-full" placeholder="e.g., Best Value" />
                        <InputError :message="editForm.errors.badge_text" />
                    </div>
                    
                    <div>
                        <InputLabel value="Features" />
                        <div class="space-y-2">
                            <div v-for="(feature, index) in editForm.features" :key="index" class="flex gap-2">
                                <TextInput v-model="editForm.features[index]" class="flex-1" placeholder="Feature description" />
                                <button @click="removeFeature(editForm, index)" type="button" class="text-red-600">Remove</button>
                            </div>
                        </div>
                        <button @click="addFeature(editForm)" type="button" class="mt-2 text-blue-600 text-sm">+ Add Feature</button>
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="editForm.is_active" class="mr-2" />
                            <span>Active</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showEditModal = false">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="editForm.processing">Update Product</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        
        <!-- Connect to Stripe Modal -->
        <Modal :show="showConnectModal" @close="showConnectModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Connect to Stripe Product</h2>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="Select Stripe Product" />
                        <div v-if="loadingStripe" class="text-gray-500">Loading...</div>
                        <select v-else v-model="selectedStripeProductId" @change="fetchStripePrices($event.target.value)" class="w-full">
                            <option value="">Select a product...</option>
                            <option v-for="product in stripeProducts" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.id }})
                            </option>
                        </select>
                    </div>
                    
                    <div v-if="selectedStripeProductId && stripePrices.length > 0">
                        <InputLabel value="Select Price" />
                        <select v-model="selectedStripePriceId" class="w-full">
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
                    
                    <div class="flex justify-end gap-3">
                        <SecondaryButton @click="showConnectModal = false">Cancel</SecondaryButton>
                        <PrimaryButton @click="connectToStripe" :disabled="!selectedStripeProductId || !selectedStripePriceId">
                            Connect
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>