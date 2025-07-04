<script setup lang="ts">
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { ref } from 'vue';

interface TicketReply {
    id: number;
    content: string;
    is_internal: boolean;
    user: {
        name: string;
        is_admin: boolean;
    };
    created_at: string;
}

interface Ticket {
    id: number;
    ticket_number: string;
    subject: string;
    content: string;
    status: string;
    priority: string;
    category: {
        name: string;
    };
    user: {
        name: string;
        email: string;
    };
    replies: TicketReply[];
    created_at: string;
    updated_at: string;
}

const page = usePage();
const ticket = ref<Ticket>(page.props.ticket);

const replyForm = useForm({
    content: '',
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
    replyForm.post(`/support/tickets/${ticket.value.id}/replies`, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
        },
    });
}

// Close ticket
function closeTicket() {
    if (confirm('Are you sure you want to close this ticket?')) {
        router.put(`/support/tickets/${ticket.value.id}/close`);
    }
}

// Reopen ticket
function reopenTicket() {
    router.put(`/support/tickets/${ticket.value.id}/reopen`);
}
</script>

<template>
    <CustomerLayout>
        <Head :title="`Ticket #${ticket.ticket_number}`" />
        
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-12">
                    <!-- Ticket Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="h3 mb-0">Ticket #{{ ticket.ticket_number }}</h1>
                            <p class="text-muted mb-0">{{ ticket.subject }}</p>
                        </div>
                        <div>
                            <button 
                                v-if="ticket.status !== 'closed'" 
                                @click="closeTicket"
                                class="btn btn-outline-secondary me-2"
                            >
                                <i class="bi bi-x-circle me-1"></i> Close Ticket
                            </button>
                            <button 
                                v-else
                                @click="reopenTicket"
                                class="btn btn-outline-primary"
                            >
                                <i class="bi bi-arrow-repeat me-1"></i> Reopen Ticket
                            </button>
                            <a href="/support" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Tickets
                            </a>
                        </div>
                    </div>

                    <!-- Ticket Info -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <small class="text-muted">Status</small>
                                    <div>
                                        <span :class="`badge ${statusClasses[ticket.status]}`">
                                            {{ ticket.status }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Priority</small>
                                    <div>
                                        <span :class="`badge ${priorityClasses[ticket.priority]}`">
                                            {{ ticket.priority }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Category</small>
                                    <div>{{ ticket.category.name }}</div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Created</small>
                                    <div>{{ formatDate(ticket.created_at) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Original Message -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark">{{ ticket.user.name }}</strong>
                                    <span class="text-muted ms-2">{{ formatDate(ticket.created_at) }}</span>
                                </div>
                                <span class="badge bg-primary">Original Message</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ticket-content" v-html="ticket.content"></div>
                        </div>
                    </div>

                    <!-- Replies -->
                    <div v-for="reply in ticket.replies" :key="reply.id" class="card mb-4">
                        <div class="card-header" :class="reply.user.is_admin ? 'bg-info bg-opacity-10' : 'bg-light'">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong class="text-dark">{{ reply.user.name }}</strong>
                                    <span v-if="reply.user.is_admin" class="badge bg-info ms-2">Support Team</span>
                                    <span class="text-muted ms-2">{{ formatDate(reply.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="ticket-content" v-html="reply.content"></div>
                        </div>
                    </div>

                    <!-- Reply Form -->
                    <div v-if="ticket.status !== 'closed'" class="card">
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

                    <div v-else class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        This ticket is closed. Please reopen it if you need further assistance.
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<style scoped>
.ticket-content {
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>