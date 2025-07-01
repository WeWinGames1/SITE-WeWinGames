<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

interface Ticket {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    priority: string;
    category: {
        id: number;
        name: string;
    } | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    is_guest_submission?: boolean;
    guest_name?: string;
    guest_email?: string;
    potential_user?: {
        id: number;
        name: string;
    };
    assigned_to?: number;
    assignedTo?: {
        id: number;
        name: string;
    };
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    tickets: {
        data: Ticket[];
        links: any;
        meta: any;
    };
    categories: Array<{id: number; name: string}>;
    adminUsers: Array<{id: number; name: string}>;
    filters: {
        status?: string;
        priority?: string;
        category_id?: string;
        assigned_to?: string;
        search?: string;
    };
}>();

const selectedTickets = ref<number[]>([]);
const bulkAction = ref('');
const bulkValue = ref('');

// Filter form
const filterForm = useForm({
    status: props.filters.status || '',
    priority: props.filters.priority || '',
    category_id: props.filters.category_id || '',
    assigned_to: props.filters.assigned_to || '',
    search: props.filters.search || '',
});

// Status badge classes
const statusClasses = {
    open: 'bg-info',
    pending: 'bg-warning',
    resolved: 'bg-success',
    closed: 'bg-secondary'
};

const priorityClasses = {
    low: 'bg-secondary',
    medium: 'bg-primary',
    high: 'bg-warning',
    urgent: 'bg-danger'
};

// Apply filters
function applyFilters() {
    filterForm.get('/admin/support-tickets', {
        preserveState: true,
        preserveScroll: true,
    });
}

// Clear filters
function clearFilters() {
    filterForm.reset();
    router.get('/admin/support-tickets');
}

// Bulk actions
function executeBulkAction() {
    if (selectedTickets.value.length === 0) {
        alert('Please select at least one ticket');
        return;
    }

    if (!bulkAction.value) {
        alert('Please select an action');
        return;
    }

    if (['assign', 'priority'].includes(bulkAction.value) && !bulkValue.value) {
        alert('Please select a value');
        return;
    }

    router.post('/admin/support-tickets/bulk-update', {
        ticket_ids: selectedTickets.value,
        action: bulkAction.value,
        value: bulkValue.value,
    }, {
        onSuccess: () => {
            selectedTickets.value = [];
            bulkAction.value = '';
            bulkValue.value = '';
        }
    });
}

// Toggle all checkboxes
function toggleAll(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedTickets.value = checked ? props.tickets.data.map(t => t.id) : [];
}

// Format date
function formatDate(date: string) {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Support Tickets" />
        
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">Support Tickets</h1>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form @submit.prevent="applyFilters">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-4 col-sm-6">
                                <label class="form-label text-dark fw-medium">Search</label>
                                <input 
                                    v-model="filterForm.search" 
                                    type="text" 
                                    class="form-control"
                                    placeholder="Ticket #, subject, user..."
                                />
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6">
                                <label class="form-label text-dark fw-medium">Status</label>
                                <select v-model="filterForm.status" class="form-select">
                                    <option value="">All</option>
                                    <option value="open">Open</option>
                                    <option value="pending">Pending</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6">
                                <label class="form-label text-dark fw-medium">Priority</label>
                                <select v-model="filterForm.priority" class="form-select">
                                    <option value="">All</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6">
                                <label class="form-label text-dark fw-medium">Category</label>
                                <select v-model="filterForm.category_id" class="form-select">
                                    <option value="">All</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 col-sm-6">
                                <label class="form-label text-dark fw-medium">Assigned To</label>
                                <select v-model="filterForm.assigned_to" class="form-select">
                                    <option value="">All</option>
                                    <option v-for="admin in adminUsers" :key="admin.id" :value="admin.id">
                                        {{ admin.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 col-lg-auto d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Filter
                                </button>
                                <button type="button" @click="clearFilters" class="btn btn-outline-secondary">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <div class="d-flex gap-2 mb-3">
                <select v-model="bulkAction" class="form-select" style="width: auto;">
                    <option value="">Bulk Actions</option>
                    <option value="close">Close Selected</option>
                    <option value="resolve">Mark as Resolved</option>
                    <option value="assign">Assign To</option>
                    <option value="priority">Change Priority</option>
                </select>
                
                <select v-if="bulkAction === 'assign'" v-model="bulkValue" class="form-select" style="width: auto;">
                    <option value="">Select Admin</option>
                    <option v-for="admin in adminUsers" :key="admin.id" :value="admin.id">
                        {{ admin.name }}
                    </option>
                </select>
                
                <select v-if="bulkAction === 'priority'" v-model="bulkValue" class="form-select" style="width: auto;">
                    <option value="">Select Priority</option>
                    <option value="low">Low</option>
                    <option value="medium">Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
                
                <button @click="executeBulkAction" class="btn btn-primary" :disabled="!bulkAction">
                    Apply
                </button>
            </div>

            <!-- Tickets Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input"
                                        @change="toggleAll"
                                    />
                                </th>
                                <th>Ticket #</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Assigned To</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ticket in tickets.data" :key="ticket.id">
                                <td>
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input"
                                        :value="ticket.id"
                                        v-model="selectedTickets"
                                    />
                                </td>
                                <td class="fw-bold">{{ ticket.ticket_number }}</td>
                                <td>{{ ticket.subject }}</td>
                                <td>
                                    <div v-if="ticket.is_guest_submission" class="d-flex align-items-center gap-2">
                                        <span class="badge bg-warning text-dark">GUEST</span>
                                        <div>
                                            <div>{{ ticket.guest_name }}</div>
                                            <small class="text-muted">{{ ticket.guest_email }}</small>
                                            <div v-if="ticket.potential_user" class="text-info small">
                                                <i class="bi bi-info-circle"></i> Possible match: {{ ticket.potential_user.name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else-if="ticket.user">
                                        <div>{{ ticket.user.name }}</div>
                                        <small class="text-muted">{{ ticket.user.email }}</small>
                                    </div>
                                    <div v-else>
                                        <span class="text-muted">No user data</span>
                                    </div>
                                </td>
                                <td>{{ ticket.category?.name || 'Uncategorized' }}</td>
                                <td>
                                    <span :class="`badge ${statusClasses[ticket.status]}`">
                                        {{ ticket.status }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="`badge ${priorityClasses[ticket.priority]}`">
                                        {{ ticket.priority }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="ticket.assignedTo">{{ ticket.assignedTo.name }}</span>
                                    <span v-else class="text-muted">Unassigned</span>
                                </td>
                                <td>{{ formatDate(ticket.created_at) }}</td>
                                <td>
                                    <a :href="`/admin/support-tickets/${ticket.id}`" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="tickets.links.length > 3" class="card-footer">
                    <nav>
                        <ul class="pagination mb-0 justify-content-center">
                            <li 
                                v-for="link in tickets.links" 
                                :key="link.label"
                                class="page-item"
                                :class="{ active: link.active, disabled: !link.url }"
                            >
                                <button 
                                    class="page-link"
                                    @click="router.get(link.url)"
                                    :disabled="!link.url"
                                    v-html="link.label"
                                >
                                </button>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>