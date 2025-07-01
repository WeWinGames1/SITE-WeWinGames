<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref } from 'vue';

interface TicketReply {
    id: number;
    content: string;
    is_internal: boolean;
    user: {
        id: number;
        name: string;
        is_admin: boolean;
    } | null;
    created_at: string;
}

interface Ticket {
    id: number;
    ticket_number: string;
    subject: string;
    content: string;
    status: string;
    priority: string;
    assigned_to?: number;
    category: {
        id: number;
        name: string;
    } | null;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
    guest_name?: string;
    guest_email?: string;
    is_guest_submission?: boolean;
    assignedTo?: {
        id: number;
        name: string;
    };
    replies: TicketReply[];
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    ticket: Ticket;
    adminUsers: Array<{id: number; name: string}>;
}>();

const replyForm = useForm({
    content: '',
    is_internal: false,
});

const statusForm = useForm({
    status: props.ticket.status,
});

const priorityForm = useForm({
    priority: props.ticket.priority,
});

const assignForm = useForm({
    assigned_to: props.ticket.assigned_to || '',
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

// Submit reply
function submitReply() {
    replyForm.post(`/admin/support-tickets/${props.ticket.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
        },
    });
}

// Update status
function updateStatus() {
    statusForm.put(`/admin/support-tickets/${props.ticket.id}/status`, {
        preserveScroll: true,
    });
}

// Update priority
function updatePriority() {
    priorityForm.put(`/admin/support-tickets/${props.ticket.id}/priority`, {
        preserveScroll: true,
    });
}

// Update assignment
function updateAssignment() {
    assignForm.put(`/admin/support-tickets/${props.ticket.id}/assign`, {
        preserveScroll: true,
    });
}

// Impersonate user
function impersonateUser() {
    if (props.ticket.user) {
        router.post(`/admin/customers/${props.ticket.user.id}/impersonate`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Ticket #${ticket.ticket_number}`" />
        
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Ticket #{{ ticket.ticket_number }}</h1>
                            <p class="text-muted mb-0">{{ ticket.subject }}</p>
                        </div>
                        <a href="/admin/support-tickets" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to Tickets
                        </a>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Main Content -->
                <div class="col-lg-8">
                    <!-- Original Message -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ ticket.user ? ticket.user.name : ticket.guest_name }}</strong>
                                    <span class="text-muted ms-2">{{ ticket.user ? ticket.user.email : ticket.guest_email }}</span>
                                    <span class="text-muted ms-2">{{ formatDate(ticket.created_at) }}</span>
                                    <span v-if="ticket.is_guest_submission" class="badge bg-info ms-2">Guest</span>
                                </div>
                                <div>
                                    <button v-if="ticket.user" @click="impersonateUser" class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-person-badge me-1"></i> Impersonate
                                    </button>
                                    <span class="badge bg-primary">Original Message</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ticket-content" v-html="ticket.content"></div>
                        </div>
                    </div>

                    <!-- Replies -->
                    <div v-for="reply in ticket.replies" :key="reply.id" class="card mb-4">
                        <div class="card-header" :class="{
                            'bg-warning bg-opacity-10': reply.is_internal,
                            'bg-info bg-opacity-10': reply.user?.is_admin && !reply.is_internal,
                            'bg-light': !reply.user?.is_admin
                        }">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ reply.user?.name || 'Unknown User' }}</strong>
                                    <span v-if="reply.user?.is_admin" class="badge bg-info ms-2">Support Team</span>
                                    <span v-if="reply.is_internal" class="badge bg-warning ms-2">Internal Note</span>
                                    <span class="text-muted ms-2">{{ formatDate(reply.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ticket-content" v-html="reply.content"></div>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Add Reply</h5>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submitReply">
                                <div class="mb-3">
                                    <textarea 
                                        v-model="replyForm.content" 
                                        class="form-control"
                                        :class="{ 'is-invalid': replyForm.errors.content }"
                                        rows="5"
                                        placeholder="Type your reply here..."
                                        required
                                    ></textarea>
                                    <div v-if="replyForm.errors.content" class="invalid-feedback">
                                        {{ replyForm.errors.content }}
                                    </div>
                                </div>
                                <div class="form-check mb-3">
                                    <input 
                                        v-model="replyForm.is_internal" 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="internalNote"
                                    />
                                    <label class="form-check-label" for="internalNote">
                                        Internal note (not visible to customer)
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary" :disabled="replyForm.processing">
                                    <span v-if="replyForm.processing">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Sending...
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-send me-2"></i>
                                        Send Reply
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Ticket Info -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Ticket Details</h5>
                        </div>
                        <div class="card-body">
                            <!-- Status -->
                            <div class="mb-3">
                                <label class="form-label small text-dark fw-medium">Status</label>
                                <div class="input-group">
                                    <select v-model="statusForm.status" class="form-select">
                                        <option value="open">Open</option>
                                        <option value="pending">Pending</option>
                                        <option value="resolved">Resolved</option>
                                        <option value="closed">Closed</option>
                                    </select>
                                    <button @click="updateStatus" class="btn btn-outline-secondary" :disabled="statusForm.processing">
                                        Update
                                    </button>
                                </div>
                            </div>

                            <!-- Priority -->
                            <div class="mb-3">
                                <label class="form-label small text-dark fw-medium">Priority</label>
                                <div class="input-group">
                                    <select v-model="priorityForm.priority" class="form-select">
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                    <button @click="updatePriority" class="btn btn-outline-secondary" :disabled="priorityForm.processing">
                                        Update
                                    </button>
                                </div>
                            </div>

                            <!-- Assignment -->
                            <div class="mb-3">
                                <label class="form-label small text-dark fw-medium">Assigned To</label>
                                <div class="input-group">
                                    <select v-model="assignForm.assigned_to" class="form-select">
                                        <option value="">Unassigned</option>
                                        <option v-for="admin in adminUsers" :key="admin.id" :value="admin.id">
                                            {{ admin.name }}
                                        </option>
                                    </select>
                                    <button @click="updateAssignment" class="btn btn-outline-secondary" :disabled="assignForm.processing">
                                        Update
                                    </button>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label small text-dark fw-medium">Category</label>
                                <div>{{ ticket.category?.name || 'Uncategorized' }}</div>
                            </div>

                            <!-- Created -->
                            <div class="mb-3">
                                <label class="form-label small text-dark fw-medium">Created</label>
                                <div>{{ formatDate(ticket.created_at) }}</div>
                            </div>

                            <!-- Updated -->
                            <div>
                                <label class="form-label small text-dark fw-medium">Last Updated</label>
                                <div>{{ formatDate(ticket.updated_at) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Customer Info</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong>{{ ticket.user ? ticket.user.name : ticket.guest_name }}</strong>
                            </div>
                            <div class="mb-3">
                                <a :href="`mailto:${ticket.user ? ticket.user.email : ticket.guest_email}`" class="text-decoration-none">
                                    {{ ticket.user ? ticket.user.email : ticket.guest_email }}
                                </a>
                            </div>
                            <a v-if="ticket.user" :href="`/admin/customers?search=${ticket.user.email}`" class="btn btn-sm btn-outline-primary">
                                View Customer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.ticket-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>