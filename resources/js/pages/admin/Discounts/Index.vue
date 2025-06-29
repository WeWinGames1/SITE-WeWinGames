<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button as PrimaryButton } from "@/components/ui/button";
import { Button as SecondaryButton } from "@/components/ui/button";
import { Input as TextInput } from "@/components/ui/input";
import { Dialog as Modal } from "@/components/ui/dialog";
import { Label as InputLabel } from "@/components/ui/label";
import InputError from '@/components/InputError.vue';
import { 
    PlusIcon,
    MagnifyingGlassIcon,
    EyeIcon,
    PencilIcon,
    XCircleIcon,
    ClipboardDocumentIcon,
    CheckCircleIcon
} from '@heroicons/vue/24/outline';

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

// Methods
function applyFilters() {
    filterForm.get(route('admin.discounts.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    filterForm.reset();
    router.get(route('admin.discounts.index'));
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
        // console.error('Failed to fetch discount details:', error);
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

function getStatusColor(code: DiscountCode): string {
    const status = getStatusLabel(code);
    switch (status) {
        case 'Active':
            return 'bg-green-100 text-green-800';
        case 'Inactive':
            return 'bg-gray-100 text-gray-800';
        case 'Expired':
            return 'bg-red-100 text-red-800';
        case 'Limit Reached':
            return 'bg-yellow-100 text-yellow-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="{ title: 'Discount Codes', href: route('admin.discounts.index') }">
        <Head title="Discount Codes" />
        
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Discount Code Management</h1>
                <PrimaryButton @click="showCreateModal = true">
                    <PlusIcon class="h-4 w-4 mr-1" />
                    Create Discount Code
                </PrimaryButton>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <TextInput 
                            v-model="filterForm.search" 
                            @keyup.enter="applyFilters"
                            class="pl-10 pr-4 w-full" 
                            placeholder="Search codes..."
                        />
                    </div>
                    
                    <select v-model="filterForm.status" @change="applyFilters" class="w-48">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    
                    <SecondaryButton @click="clearFilters">Clear</SecondaryButton>
                </div>
            </div>
            
            <!-- Discount Codes Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Discount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Usage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Until</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="code in discountCodes.data" :key="code.id">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <span class="font-mono font-medium">{{ code.code }}</span>
                                        <button 
                                            @click="copyToClipboard(code.code)"
                                            class="text-gray-400 hover:text-gray-600"
                                        >
                                            <ClipboardDocumentIcon v-if="copiedCode !== code.code" class="h-4 w-4" />
                                            <CheckCircleIcon v-else class="h-4 w-4 text-green-500" />
                                        </button>
                                    </div>
                                    <div v-if="code.description" class="text-sm text-gray-500 mt-1">
                                        {{ code.description }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold">{{ code.formatted_discount }}</span>
                                <span class="text-sm text-gray-500 ml-1">{{ code.discount_type }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ getApplyToLabel(code.apply_to, code.months_count) }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div>{{ code.times_used }} / {{ code.max_uses || '∞' }}</div>
                                    <div class="text-xs text-gray-500">
                                        Max {{ code.max_uses_per_customer }} per customer
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                {{ code.valid_until ? new Date(code.valid_until).toLocaleDateString() : 'No expiry' }}
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(code)" class="inline-flex text-xs px-2 py-1 rounded-full">
                                    {{ getStatusLabel(code) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <button @click="viewDetails(code)" class="text-blue-600 hover:text-blue-800">
                                        <EyeIcon class="h-4 w-4" />
                                    </button>
                                    <button 
                                        v-if="code.is_active"
                                        @click="deactivateCode(code)" 
                                        class="text-red-600 hover:text-red-800"
                                    >
                                        <XCircleIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div v-if="discountCodes.links.length > 3" class="bg-gray-50 px-6 py-3 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ discountCodes.meta.from }} to {{ discountCodes.meta.to }} of {{ discountCodes.meta.total }} results
                    </div>
                    <div class="flex space-x-1">
                        <template v-for="link in discountCodes.links" :key="link.label">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                class="px-3 py-1 text-sm rounded"
                                :class="link.active ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Create Discount Modal -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Create Discount Code</h2>
                
                <form @submit.prevent="createDiscount" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="code" value="Discount Code" />
                            <TextInput 
                                v-model="createForm.code" 
                                id="code" 
                                class="w-full" 
                                placeholder="Leave blank to auto-generate"
                                :class="{ 'font-mono': createForm.code }"
                            />
                            <InputError :message="createForm.errors.code" />
                        </div>
                        
                        <div>
                            <InputLabel for="description" value="Description (optional)" />
                            <TextInput v-model="createForm.description" id="description" class="w-full" />
                            <InputError :message="createForm.errors.description" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <InputLabel for="discount_type" value="Discount Type" />
                            <select v-model="createForm.discount_type" id="discount_type" class="w-full">
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                            </select>
                            <InputError :message="createForm.errors.discount_type" />
                        </div>
                        
                        <div>
                            <InputLabel for="discount_amount" value="Amount" />
                            <div class="relative">
                                <span v-if="createForm.discount_type === 'fixed'" class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">$</span>
                                <TextInput 
                                    v-model.number="createForm.discount_amount" 
                                    id="discount_amount" 
                                    type="number" 
                                    step="0.01" 
                                    min="0"
                                    :class="createForm.discount_type === 'fixed' ? 'pl-8' : ''"
                                    class="w-full" 
                                    required 
                                />
                                <span v-if="createForm.discount_type === 'percentage'" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500">%</span>
                            </div>
                            <InputError :message="createForm.errors.discount_amount" />
                        </div>
                        
                        <div>
                            <InputLabel for="apply_to" value="Apply To" />
                            <select v-model="createForm.apply_to" id="apply_to" class="w-full">
                                <option value="first_payment">First Payment Only</option>
                                <option value="forever">All Payments (Forever)</option>
                                <option value="specific_months">Specific Number of Months</option>
                            </select>
                            <InputError :message="createForm.errors.apply_to" />
                        </div>
                    </div>
                    
                    <div v-if="createForm.apply_to === 'specific_months'">
                        <InputLabel for="months_count" value="Number of Months" />
                        <TextInput v-model.number="createForm.months_count" id="months_count" type="number" min="1" class="w-full" required />
                        <InputError :message="createForm.errors.months_count" />
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="max_uses" value="Total Usage Limit (optional)" />
                            <TextInput v-model.number="createForm.max_uses" id="max_uses" type="number" min="1" class="w-full" placeholder="Unlimited" />
                            <InputError :message="createForm.errors.max_uses" />
                        </div>
                        
                        <div>
                            <InputLabel for="max_uses_per_customer" value="Max Uses Per Customer" />
                            <TextInput v-model.number="createForm.max_uses_per_customer" id="max_uses_per_customer" type="number" min="1" class="w-full" required />
                            <InputError :message="createForm.errors.max_uses_per_customer" />
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="valid_from" value="Valid From (optional)" />
                            <TextInput v-model="createForm.valid_from" id="valid_from" type="datetime-local" class="w-full" />
                            <InputError :message="createForm.errors.valid_from" />
                        </div>
                        
                        <div>
                            <InputLabel for="valid_until" value="Valid Until (optional)" />
                            <TextInput v-model="createForm.valid_until" id="valid_until" type="datetime-local" class="w-full" />
                            <InputError :message="createForm.errors.valid_until" />
                        </div>
                    </div>
                    
                    <div>
                        <InputLabel value="Applicable Products (optional)" />
                        <p class="text-sm text-gray-500 mb-2">Leave empty to apply to all products</p>
                        <div class="space-y-2 max-h-48 overflow-y-auto border rounded-md p-2">
                            <label v-for="product in products" :key="product.id" class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    :value="product.id"
                                    v-model="createForm.applicable_products"
                                    class="rounded mr-2"
                                />
                                <span class="text-sm">{{ product.name }}</span>
                            </label>
                        </div>
                        <InputError :message="createForm.errors.applicable_products" />
                    </div>
                    
                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="createForm.create_in_stripe" class="rounded mr-2" />
                            <span>Create this discount in Stripe as well</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <SecondaryButton @click="showCreateModal = false">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="createForm.processing">
                            Create Discount Code
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
        
        <!-- Details Modal -->
        <Modal :show="showDetailsModal" @close="showDetailsModal = false; selectedCode = null; codeDetails = null" max-width="2xl">
            <div v-if="selectedCode && codeDetails" class="p-6">
                <h2 class="text-lg font-semibold mb-4">Discount Code Details</h2>
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Code</h3>
                            <p class="font-mono text-lg">{{ codeDetails.discount_code.code }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Status</h3>
                            <span :class="getStatusColor(codeDetails.discount_code)" class="inline-flex text-xs px-2 py-1 rounded-full">
                                {{ getStatusLabel(codeDetails.discount_code) }}
                            </span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Discount</h3>
                            <p>{{ codeDetails.discount_code.formatted_discount }} ({{ codeDetails.discount_code.discount_type }})</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Applies To</h3>
                            <p>{{ getApplyToLabel(codeDetails.discount_code.apply_to, codeDetails.discount_code.months_count) }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Usage</h3>
                            <p>{{ codeDetails.discount_code.times_used }} / {{ codeDetails.discount_code.max_uses || '∞' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Per Customer Limit</h3>
                            <p>{{ codeDetails.discount_code.max_uses_per_customer }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Valid From</h3>
                            <p>{{ codeDetails.discount_code.valid_from ? new Date(codeDetails.discount_code.valid_from).toLocaleString() : 'Always' }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Valid Until</h3>
                            <p>{{ codeDetails.discount_code.valid_until ? new Date(codeDetails.discount_code.valid_until).toLocaleString() : 'Never expires' }}</p>
                        </div>
                    </div>
                    
                    <div v-if="codeDetails.discount_code.description">
                        <h3 class="text-sm font-medium text-gray-500">Description</h3>
                        <p>{{ codeDetails.discount_code.description }}</p>
                    </div>
                    
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Redemptions ({{ codeDetails.redemptions.length }})</h3>
                        <div v-if="codeDetails.redemptions.length > 0" class="border rounded-lg overflow-hidden">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Customer</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Amount</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="redemption in codeDetails.redemptions" :key="redemption.id">
                                        <td class="px-4 py-2">
                                            <div>
                                                <div class="text-sm font-medium">{{ redemption.user.name }}</div>
                                                <div class="text-xs text-gray-500">{{ redemption.user.email }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm">${{ redemption.discount_applied }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            {{ new Date(redemption.created_at).toLocaleDateString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <p v-else class="text-sm text-gray-500">No redemptions yet</p>
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <SecondaryButton @click="showDetailsModal = false; selectedCode = null; codeDetails = null">
                        Close
                    </SecondaryButton>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>