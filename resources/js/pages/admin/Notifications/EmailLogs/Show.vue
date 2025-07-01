<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

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
    metadata: Record<string, any> | null;
    sent_at: string | null;
    delivered_at: string | null;
    opened_at: string | null;
    clicked_at: string | null;
    bounced_at: string | null;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    log: EmailLog;
}>();

function formatDate(date: string | null): string {
    if (!date) return '-';
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
}

function resendEmail() {
    if (confirm('Are you sure you want to resend this email?')) {
        router.post(`/admin/notifications/email-logs/${props.log.id}/resend`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Email Log #${log.id}`" />
        
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Email Log Details</h1>
                            <p class="text-muted mb-0">
                                View detailed information about this email
                            </p>
                        </div>
                        <Link 
                            href="/admin/notifications/email-logs"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to Logs
                        </Link>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Email Details -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Email Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="text-muted small">To Email</label>
                                    <p class="mb-0 fw-medium">{{ log.to_email }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">To Name</label>
                                    <p class="mb-0 fw-medium">{{ log.to_name || '-' }}</p>
                                </div>
                                <div class="col-12">
                                    <label class="text-muted small">Subject</label>
                                    <p class="mb-0 fw-medium">{{ log.subject }}</p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Template</label>
                                    <p class="mb-0">
                                        <span v-if="log.template_key" class="badge bg-secondary">
                                            {{ log.template_key }}
                                        </span>
                                        <span v-else>-</span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Message ID</label>
                                    <p class="mb-0">
                                        <code v-if="log.message_id">{{ log.message_id }}</code>
                                        <span v-else>-</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Delivery Timeline</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <div class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Created</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.created_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="log.sent_at" class="timeline-item">
                                    <div class="timeline-marker bg-info"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Sent</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.sent_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="log.delivered_at" class="timeline-item">
                                    <div class="timeline-marker bg-success"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Delivered</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.delivered_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="log.opened_at" class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Opened</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.opened_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="log.clicked_at" class="timeline-item">
                                    <div class="timeline-marker bg-primary"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Clicked</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.clicked_at) }}</p>
                                    </div>
                                </div>
                                <div v-if="log.bounced_at" class="timeline-item">
                                    <div class="timeline-marker bg-danger"></div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1">Bounced</h6>
                                        <p class="mb-0 text-muted">{{ formatDate(log.bounced_at) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Error Details -->
                    <div v-if="log.error_message" class="card mb-4">
                        <div class="card-header bg-danger bg-opacity-10 text-danger">
                            <h5 class="mb-0">Error Details</h5>
                        </div>
                        <div class="card-body">
                            <pre class="mb-0 text-danger">{{ log.error_message }}</pre>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div v-if="log.metadata && Object.keys(log.metadata).length > 0" class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Additional Metadata</h5>
                        </div>
                        <div class="card-body">
                            <pre class="mb-0">{{ JSON.stringify(log.metadata, null, 2) }}</pre>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Status -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Status</h5>
                        </div>
                        <div class="card-body text-center">
                            <span :class="`badge ${log.status_badge_class} fs-6 px-3 py-2`">
                                {{ log.status_label }}
                            </span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div v-if="log.status === 'failed'" class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <button 
                                @click="resendEmail"
                                class="btn btn-warning w-100"
                            >
                                <i class="bi bi-arrow-repeat me-2"></i>
                                Resend Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background-color: #e9ecef;
}

.timeline-item {
    position: relative;
    padding-bottom: 1.5rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.1);
}

.timeline-content {
    padding-left: 0;
}

pre {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>