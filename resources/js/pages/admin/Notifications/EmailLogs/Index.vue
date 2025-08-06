<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

interface EmailLog {
    id: number;
    to_email: string;
    to_name: string | null;
    subject: string;
    template_key: string | null;
    status: string;
    status_badge_class: string;
    status_label: string;
    message_id: string | null;
    error_message: string | null;
    sent_at: string | null;
    delivered_at: string | null;
    opened_at: string | null;
    created_at: string;
}

interface Stats {
    total: number;
    sent: number;
    delivered: number;
    opened: number;
    failed: number;
    bounced: number;
}

const props = defineProps<{
    logs: {
        data: EmailLog[];
        links: any[];
        meta: any;
    };
    stats: Stats;
    filters: {
        status?: string;
        search?: string;
        template_key?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const filterForm = useForm({
    status: props.filters.status || '',
    search: props.filters.search || '',
    template_key: props.filters.template_key || '',
    date_from: props.filters.date_from || '',
    date_to: props.filters.date_to || '',
});

function applyFilters() {
    filterForm.get('/admin/notifications/email-logs', {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    filterForm.reset();
    applyFilters();
}

function formatDate(date: string | null): string {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function resendEmail(log: EmailLog) {
    if (confirm('Are you sure you want to resend this email?')) {
        router.post(`/admin/notifications/email-logs/${log.id}/resend`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Email Logs" />

        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Email Logs</h1>
                            <p class="text-muted mb-0">Track all sent emails and their delivery status</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Total Emails</h6>
                            <h3 class="mb-0">{{ stats.total }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Sent</h6>
                            <h3 class="mb-0 text-info">{{ stats.sent }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Delivered</h6>
                            <h3 class="mb-0 text-success">{{ stats.delivered }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Opened</h6>
                            <h3 class="mb-0 text-primary">{{ stats.opened }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Failed</h6>
                            <h3 class="mb-0 text-danger">{{ stats.failed }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-1">Bounced</h6>
                            <h3 class="mb-0 text-warning">{{ stats.bounced }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form @submit.prevent="applyFilters">
                        <div class="row g-3">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label text-dark fw-medium">Search</label>
                                <input v-model="filterForm.search" type="text" class="form-control" placeholder="Email, name, subject..." />
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-dark fw-medium">Status</label>
                                <select v-model="filterForm.status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="sent">Sent</option>
                                    <option value="delivered">Delivered</option>
                                    <option value="opened">Opened</option>
                                    <option value="clicked">Clicked</option>
                                    <option value="failed">Failed</option>
                                    <option value="bounced">Bounced</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-dark fw-medium">Template</label>
                                <select v-model="filterForm.template_key" class="form-select">
                                    <option value="">All Templates</option>
                                    <option value="new_registration">New Registration</option>
                                    <option value="forgot_password">Forgot Password</option>
                                    <option value="trial_expiring">Trial Expiring</option>
                                    <option value="plan_renewal">Plan Renewal</option>
                                    <option value="payment_failed">Payment Failed</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-dark fw-medium">From Date</label>
                                <input v-model="filterForm.date_from" type="date" class="form-control" />
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-dark fw-medium">To Date</label>
                                <input v-model="filterForm.date_to" type="date" class="form-control" />
                            </div>
                            <div class="col-lg-1 col-md-12 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel"></i>
                                </button>
                                <button type="button" @click="clearFilters" class="btn btn-outline-secondary">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Email Logs Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-dark fw-medium">Recipient</th>
                                <th class="text-dark fw-medium">Subject</th>
                                <th class="text-dark fw-medium">Template</th>
                                <th class="text-dark fw-medium">Status</th>
                                <th class="text-dark fw-medium">Sent</th>
                                <th class="text-dark fw-medium text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id">
                                <td>
                                    <div>
                                        <div>{{ log.to_email }}</div>
                                        <small v-if="log.to_name" class="text-muted">
                                            {{ log.to_name }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 300px">
                                        {{ log.subject }}
                                    </span>
                                </td>
                                <td>
                                    <span v-if="log.template_key" class="badge bg-secondary">
                                        {{ log.template_key }}
                                    </span>
                                    <span v-else class="text-muted">-</span>
                                </td>
                                <td>
                                    <span :class="`badge ${log.status_badge_class}`">
                                        {{ log.status_label }}
                                    </span>
                                    <div v-if="log.error_message" class="small text-danger mt-1">
                                        {{ log.error_message }}
                                    </div>
                                </td>
                                <td>
                                    <small>{{ formatDate(log.sent_at || log.created_at) }}</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-1">
                                        <Link
                                            :href="`/admin/notifications/email-logs/${log.id}`"
                                            class="btn btn-sm btn-outline-primary"
                                            title="View Details"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </Link>
                                        <button
                                            v-if="log.status === 'failed'"
                                            @click="resendEmail(log)"
                                            class="btn btn-sm btn-outline-warning"
                                            title="Resend Email"
                                        >
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty state -->
                <div v-if="logs.data.length === 0" class="text-center py-5">
                    <i class="bi bi-envelope display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No email logs found</p>
                </div>

                <!-- Pagination -->
                <div v-if="logs.links.length > 3" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">Showing {{ logs.meta.from }} to {{ logs.meta.to }} of {{ logs.meta.total }} results</div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li
                                    v-for="link in logs.links"
                                    :key="link.label"
                                    class="page-item"
                                    :class="{ active: link.active, disabled: !link.url }"
                                >
                                    <button v-if="link.url" @click="router.get(link.url)" class="page-link" v-html="link.label" />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.text-truncate {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
</style>
