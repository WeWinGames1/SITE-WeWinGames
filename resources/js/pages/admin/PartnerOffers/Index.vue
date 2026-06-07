<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface PartnerOffer {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    offer_text: string;
    external_url: string;
    type: 'sportsbook' | 'prediction' | 'casino';
    badge_text: string | null;
    show_on_picks: boolean;
    show_on_offers_page: boolean;
    sort_order: number;
    is_active: boolean;
    click_count: number;
    discount_code?: {
        id: number;
        code: string;
    } | null;
}

interface Props {
    offers: PartnerOffer[];
}

const props = defineProps<Props>();
const draggingId = ref<number | null>(null);

// Search and filter state
const searchQuery = ref('');
const typeFilter = ref<string>('all');
const statusFilter = ref<string>('all');

// Filtered offers
const filteredOffers = computed(() => {
    return props.offers.filter((offer) => {
        // Search filter
        const matchesSearch =
            searchQuery.value === '' ||
            offer.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            offer.offer_text.toLowerCase().includes(searchQuery.value.toLowerCase());

        // Type filter
        const matchesType = typeFilter.value === 'all' || offer.type === typeFilter.value;

        // Status filter
        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && offer.is_active) ||
            (statusFilter.value === 'inactive' && !offer.is_active);

        return matchesSearch && matchesType && matchesStatus;
    });
});

function clearFilters() {
    searchQuery.value = '';
    typeFilter.value = 'all';
    statusFilter.value = 'all';
}

function toggleActive(offer: PartnerOffer) {
    router.post(route('admin.partner-offers.toggle', offer.id), {}, { preserveScroll: true });
}

function deleteOffer(offer: PartnerOffer) {
    if (confirm(`Are you sure you want to delete "${offer.name}"?`)) {
        router.delete(route('admin.partner-offers.destroy', offer.id));
    }
}

function handleDragStart(e: DragEvent, offer: PartnerOffer) {
    draggingId.value = offer.id;
    e.dataTransfer!.effectAllowed = 'move';
    e.dataTransfer!.setData('text/plain', String(offer.id));
}

function handleDragOver(e: DragEvent) {
    e.preventDefault();
    e.dataTransfer!.dropEffect = 'move';
}

function handleDrop(e: DragEvent, targetOffer: PartnerOffer) {
    e.preventDefault();
    const draggedId = Number(e.dataTransfer!.getData('text/plain'));
    if (draggedId === targetOffer.id) return;

    const offers = [...props.offers];
    const draggedIndex = offers.findIndex((o) => o.id === draggedId);
    const targetIndex = offers.findIndex((o) => o.id === targetOffer.id);

    const [removed] = offers.splice(draggedIndex, 1);
    offers.splice(targetIndex, 0, removed);

    const updatedOffers = offers.map((o, i) => ({ id: o.id, sort_order: i }));
    router.post(route('admin.partner-offers.update-order'), { offers: updatedOffers }, { preserveScroll: true });
    draggingId.value = null;
}

function handleDragEnd() {
    draggingId.value = null;
}

function getTypeColor(type: string): string {
    switch (type) {
        case 'sportsbook':
            return 'bg-primary';
        case 'prediction':
            return 'bg-info';
        case 'casino':
            return 'bg-warning text-dark';
        default:
            return 'bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Partner Offers" />

        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Partner Offers</h1>
                    <p class="text-muted">Manage affiliate partner offers and sportsbook links</p>
                </div>
                <Link :href="route('admin.partner-offers.create')" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    Add Partner Offer
                </Link>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded p-3">
                                        <i class="bi bi-link-45deg text-primary fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Total Offers</h6>
                                    <h3 class="mb-0">{{ offers.length }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-success bg-opacity-10 rounded p-3">
                                        <i class="bi bi-check-circle text-success fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Active</h6>
                                    <h3 class="mb-0">{{ offers.filter((o) => o.is_active).length }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-info bg-opacity-10 rounded p-3">
                                        <i class="bi bi-lightning text-info fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">On Picks Page</h6>
                                    <h3 class="mb-0">{{ offers.filter((o) => o.show_on_picks).length }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-warning bg-opacity-10 rounded p-3">
                                        <i class="bi bi-cursor text-warning fs-4"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h6 class="text-muted mb-0">Total Clicks</h6>
                                    <h3 class="mb-0">{{ offers.reduce((sum, o) => sum + o.click_count, 0) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search by name or offer text..."
                                />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Type</label>
                            <select v-model="typeFilter" class="form-select">
                                <option value="all">All Types</option>
                                <option value="sportsbook">Sportsbook</option>
                                <option value="prediction">Prediction</option>
                                <option value="casino">Casino</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Status</label>
                            <select v-model="statusFilter" class="form-select">
                                <option value="all">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button
                                v-if="searchQuery || typeFilter !== 'all' || statusFilter !== 'all'"
                                @click="clearFilters"
                                class="btn btn-outline-secondary w-100"
                            >
                                <i class="bi bi-x-lg me-1"></i>
                                Clear
                            </button>
                        </div>
                        <div class="col-md-2 text-end">
                            <span class="text-muted small">
                                Showing {{ filteredOffers.length }} of {{ offers.length }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Offers Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <small class="text-muted">Drag rows to reorder</small>
                    <small class="text-muted">{{ filteredOffers.length }} offers</small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px"></th>
                                <th style="width: 60px">Logo</th>
                                <th>Name</th>
                                <th>Offer</th>
                                <th>Type</th>
                                <th>Coupon</th>
                                <th>Visibility</th>
                                <th>Clicks</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 150px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="offer in filteredOffers"
                                :key="offer.id"
                                :draggable="true"
                                @dragstart="handleDragStart($event, offer)"
                                @dragover="handleDragOver"
                                @drop="handleDrop($event, offer)"
                                @dragend="handleDragEnd"
                                :class="{ 'opacity-50': draggingId === offer.id }"
                                style="cursor: move"
                            >
                                <td class="align-middle">
                                    <i class="bi bi-grip-vertical text-muted"></i>
                                </td>
                                <td class="align-middle">
                                    <img v-if="offer.logo" :src="offer.logo" :alt="offer.name" height="32" class="rounded" />
                                    <div v-else class="bg-secondary rounded d-flex align-items-center justify-content-center" style="width: 32px; height: 32px">
                                        <i class="bi bi-image text-white small"></i>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <strong>{{ offer.name }}</strong>
                                    <span v-if="offer.badge_text" class="badge bg-warning text-dark ms-2 small">{{ offer.badge_text }}</span>
                                </td>
                                <td class="align-middle">
                                    <small class="text-muted">{{ offer.offer_text.substring(0, 40) }}...</small>
                                </td>
                                <td class="align-middle">
                                    <span :class="['badge', getTypeColor(offer.type)]">{{ offer.type }}</span>
                                </td>
                                <td class="align-middle">
                                    <code v-if="offer.discount_code" class="small">{{ offer.discount_code.code }}</code>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex gap-2">
                                        <span v-if="offer.show_on_picks" class="badge bg-success small">Picks</span>
                                        <span v-if="offer.show_on_offers_page" class="badge bg-info small">Offers</span>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="fw-semibold">{{ offer.click_count.toLocaleString() }}</span>
                                </td>
                                <td class="align-middle">
                                    <button @click="toggleActive(offer)" :class="['btn btn-sm', offer.is_active ? 'btn-success' : 'btn-secondary']">
                                        {{ offer.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="text-center align-middle">
                                    <div class="btn-group" role="group">
                                        <a :href="offer.external_url" target="_blank" class="btn btn-sm btn-outline-info" title="Open Link">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                        <Link :href="route('admin.partner-offers.edit', offer.id)" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </Link>
                                        <button @click="deleteOffer(offer)" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state - no offers at all -->
                <div v-if="offers.length === 0" class="text-center py-5">
                    <i class="bi bi-link-45deg text-muted display-4"></i>
                    <p class="text-muted mt-3 mb-0">No partner offers yet.</p>
                    <Link :href="route('admin.partner-offers.create')" class="btn btn-primary mt-3">
                        <i class="bi bi-plus-lg me-2"></i>
                        Add First Offer
                    </Link>
                </div>

                <!-- Empty state - no matching results -->
                <div v-else-if="filteredOffers.length === 0" class="text-center py-5">
                    <i class="bi bi-search text-muted display-4"></i>
                    <p class="text-muted mt-3 mb-0">No offers match your filters.</p>
                    <button @click="clearFilters" class="btn btn-outline-primary mt-3">
                        <i class="bi bi-x-lg me-2"></i>
                        Clear Filters
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
