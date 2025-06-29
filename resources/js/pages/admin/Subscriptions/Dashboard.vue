<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Button as PrimaryButton } from "@/components/ui/button";
import { Button as SecondaryButton } from "@/components/ui/button";
import { Input as TextInput } from "@/components/ui/input";
import { Dialog as Modal } from "@/components/ui/dialog";
import { Label as InputLabel } from "@/components/ui/label";
import InputError from '@/components/InputError.vue';
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
        active: 'bg-green-100 text-green-800',
        trialing: 'bg-blue-100 text-blue-800',
        canceled: 'bg-red-100 text-red-800',
        past_due: 'bg-yellow-100 text-yellow-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
}

function getTierColor(tier: string | null): string {
    if (!tier) return 'bg-gray-100 text-gray-800';
    
    const colors: Record<string, string> = {
        Bronze: 'bg-amber-100 text-amber-800',
        Silver: 'bg-gray-100 text-gray-800',
        Gold: 'bg-yellow-100 text-yellow-800',
        Platinum: 'bg-purple-100 text-purple-800',
    };
    return colors[tier] || 'bg-gray-100 text-gray-800';
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
    <AppLayout :breadcrumbs="{ title: 'Subscription Dashboard', href: route('admin.subscriptions.index') }">
        <Head title="Subscription Dashboard" />
        
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold mb-4">Subscription Dashboard</h1>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <UsersIcon class="h-10 w-10 text-green-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Active Subscriptions</p>
                                <p class="text-2xl font-bold">{{ stats.total_active }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <CurrencyDollarIcon class="h-10 w-10 text-blue-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Monthly Recurring Revenue</p>
                                <p class="text-2xl font-bold">${{ stats.mrr.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <CalendarIcon class="h-10 w-10 text-yellow-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Renewals (30 days)</p>
                                <p class="text-2xl font-bold">{{ stats.upcoming_renewals }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center">
                            <ChartBarIcon class="h-10 w-10 text-purple-500" />
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Tier Breakdown</p>
                                <div class="text-xs mt-1">
                                    <div v-for="(count, tier) in stats.tier_breakdown" :key="tier">
                                        {{ tier }}: {{ count }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions Bar -->
                <div class="bg-white rounded-lg shadow p-4 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <TextInput 
                                v-model="filterForm.search" 
                                class="pl-10 pr-4" 
                                placeholder="Search by name or email..."
                            />
                        </div>
                        
                        <!-- Filters -->
                        <button @click="showFilters = !showFilters" class="flex items-center text-gray-600 hover:text-gray-900">
                            <FunnelIcon class="h-5 w-5 mr-1" />
                            Filters
                            <span v-if="Object.values(props.filters).filter(v => v).length > 0" 
                                  class="ml-2 bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">
                                {{ Object.values(props.filters).filter(v => v).length }}
                            </span>
                        </button>
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <button 
                            v-if="selectedCount > 0"
                            @click="exportSelected" 
                            class="flex items-center text-blue-600 hover:text-blue-800"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5 mr-1" />
                            Export ({{ selectedCount }})
                        </button>
                        
                        <PrimaryButton @click="openGrantModal()">
                            <PlusIcon class="h-4 w-4 mr-1" />
                            Grant Subscription
                        </PrimaryButton>
                    </div>
                </div>
                
                <!-- Filters Panel -->
                <div v-if="showFilters" class="bg-gray-50 rounded-lg p-4 mt-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <InputLabel value="Status" />
                            <select v-model="filterForm.status" class="w-full">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="trialing">Trialing</option>
                                <option value="canceled">Cancelled</option>
                                <option value="past_due">Past Due</option>
                            </select>
                        </div>
                        
                        <div>
                            <InputLabel value="Tier" />
                            <select v-model="filterForm.tier" class="w-full">
                                <option value="">All Tiers</option>
                                <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <InputLabel value="Renewal Period" />
                            <select v-model="filterForm.renewal_period" class="w-full">
                                <option value="">Any Time</option>
                                <option value="7">Next 7 days</option>
                                <option value="30">Next 30 days</option>
                                <option value="60">Next 60 days</option>
                            </select>
                        </div>
                        
                        <div class="flex items-end space-x-2">
                            <SecondaryButton @click="applyFilters" class="flex-1">Apply</SecondaryButton>
                            <button @click="clearFilters" class="text-gray-500 hover:text-gray-700">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Customers Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input 
                                    type="checkbox" 
                                    :checked="allSelected"
                                    @change="toggleAll"
                                    class="rounded"
                                />
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tier</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Started</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Renews</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="customer in customers.data" :key="customer.id">
                            <td class="px-6 py-4">
                                <input 
                                    type="checkbox" 
                                    :value="customer.id"
                                    v-model="selectedCustomers"
                                    class="rounded"
                                />
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium">{{ customer.name }}</div>
                                    <div class="text-sm text-gray-500">{{ customer.email }}</div>
                                    <div v-if="customer.is_ambassador || customer.is_gifted || customer.has_override" class="mt-1 space-x-2">
                                        <span v-if="customer.is_ambassador" class="inline-flex text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                            Ambassador
                                        </span>
                                        <span v-if="customer.is_gifted" class="inline-flex text-xs bg-green-100 text-green-800 px-2 py-1 rounded">
                                            Gifted
                                        </span>
                                        <span v-if="customer.has_override" class="inline-flex text-xs bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                            Override
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getTierColor(customer.tier)" class="inline-flex text-xs px-2 py-1 rounded-full font-semibold">
                                    {{ customer.tier || 'Unknown' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(customer.status)" class="inline-flex text-xs px-2 py-1 rounded-full">
                                    {{ customer.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ new Date(customer.created_at).toLocaleDateString() }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div v-if="customer.current_period_end">
                                        {{ new Date(customer.current_period_end).toLocaleDateString() }}
                                    </div>
                                    <div v-else class="text-gray-400">No end date</div>
                                    <div class="text-xs text-gray-500">
                                        <span v-if="customer.days_until_renewal === null">No renewal date</span>
                                        <span v-else-if="customer.days_until_renewal < 0">Expired</span>
                                        <span v-else-if="customer.days_until_renewal === 0">Today</span>
                                        <span v-else>{{ Math.round(customer.days_until_renewal) }} days</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2 text-sm">
                                    <button @click="openGrantModal(customer)" class="text-blue-600 hover:text-blue-800">
                                        Grant
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button @click="cancelSubscription(customer)" class="text-orange-600 hover:text-orange-800">
                                        Cancel
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button @click="cancelSubscription(customer, true)" class="text-red-600 hover:text-red-800">
                                        Cancel Now
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div v-if="customers.links.length > 3" class="bg-gray-50 px-6 py-3 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ customers.meta.from }} to {{ customers.meta.to }} of {{ customers.meta.total }} results
                    </div>
                    <div class="flex space-x-1">
                        <template v-for="link in customers.links" :key="link.label">
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
        
        <!-- Grant Subscription Modal -->
        <Modal :show="showGrantModal" @close="showGrantModal = false">
            <div class="p-6">
                <h2 class="text-lg font-semibold mb-4">Grant Manual Subscription</h2>
                
                <form @submit.prevent="grantSubscription" class="space-y-4">
                    <div v-if="selectedUser">
                        <p class="text-sm text-gray-600">
                            Granting subscription to: <strong>{{ selectedUser.name }}</strong> ({{ selectedUser.email }})
                        </p>
                    </div>
                    
                    <div>
                        <InputLabel for="tier" value="Subscription Tier" />
                        <select v-model="grantForm.tier" id="tier" class="w-full" required>
                            <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
                        </select>
                        <InputError :message="grantForm.errors.tier" />
                    </div>
                    
                    <div>
                        <InputLabel for="duration" value="Duration (days)" />
                        <TextInput v-model.number="grantForm.duration_days" id="duration" type="number" min="1" class="w-full" required />
                        <InputError :message="grantForm.errors.duration_days" />
                    </div>
                    
                    <div>
                        <InputLabel for="reason" value="Reason (optional)" />
                        <textarea 
                            v-model="grantForm.reason" 
                            id="reason" 
                            class="w-full rounded-md border-gray-300 shadow-sm"
                            rows="3"
                        />
                        <InputError :message="grantForm.errors.reason" />
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <SecondaryButton @click="showGrantModal = false">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="grantForm.processing">
                            Grant Subscription
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>