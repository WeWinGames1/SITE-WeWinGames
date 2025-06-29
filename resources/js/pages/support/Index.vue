<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import NewTicketModal from './NewTicketModal.vue';
import { ref, computed } from 'vue';

interface Ticket {
    id: number;
    ticket_number: string;
    subject: string;
    status: string;
    priority: string;
    category: {
        name: string;
    };
    created_at: string;
    updated_at: string;
    latest_reply?: {
        content: string;
        created_at: string;
    };
}

const page = usePage();
const tickets = ref<Ticket[]>(page.props.tickets || []);
const showNewTicketModal = ref(false);

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

// View ticket
function viewTicket(ticketId: number) {
    router.visit(`/support/tickets/${ticketId}`);
}
</script>

<template>
    <CustomerLayout>
        <Head title="Support Tickets" />
        
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">Support Tickets</h1>
                        <button 
                            @click="showNewTicketModal = true" 
                            class="btn btn-primary"
                            data-bs-toggle="modal" 
                            data-bs-target="#newTicketModal"
                        >
                            <i class="bi bi-plus-circle me-2"></i>
                            New Ticket
                        </button>
                    </div>

                    <!-- Tickets List -->
                    <div class="card">
                        <div class="card-body">
                            <div v-if="tickets.length === 0" class="text-center py-5">
                                <i class="bi bi-inbox display-1 text-muted"></i>
                                <p class="text-muted mt-3">No support tickets yet</p>
                                <button 
                                    @click="showNewTicketModal = true"
                                    class="btn btn-primary mt-2"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#newTicketModal"
                                >
                                    Create your first ticket
                                </button>
                            </div>

                            <div v-else class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Ticket #</th>
                                            <th>Subject</th>
                                            <th>Category</th>
                                            <th>Status</th>
                                            <th>Priority</th>
                                            <th>Last Update</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="ticket in tickets" :key="ticket.id">
                                            <td class="fw-bold">{{ ticket.ticket_number }}</td>
                                            <td>
                                                <div>{{ ticket.subject }}</div>
                                                <small v-if="ticket.latest_reply" class="text-muted">
                                                    {{ ticket.latest_reply.content.substring(0, 50) }}...
                                                </small>
                                            </td>
                                            <td>{{ ticket.category.name }}</td>
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
                                            <td>{{ formatDate(ticket.updated_at) }}</td>
                                            <td>
                                                <button 
                                                    @click="viewTicket(ticket.id)"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    View
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Ticket Modal Component -->
        <NewTicketModal />
    </CustomerLayout>
</template>