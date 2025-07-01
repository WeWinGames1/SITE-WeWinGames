<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface EmailTemplate {
    id: number;
    key: string;
    name: string;
    description: string | null;
    subject: string;
    is_active: boolean;
    updated_at: string;
}

const props = defineProps<{
    templates: EmailTemplate[];
}>();

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function getTemplateBadge(key: string): string {
    const badges: Record<string, string> = {
        'new_registration': 'bg-success',
        'forgot_password': 'bg-warning',
        'trial_expiring': 'bg-info',
        'plan_renewal': 'bg-primary',
        'payment_failed': 'bg-danger',
        'subscription_cancelled': 'bg-secondary',
        'welcome_subscriber': 'bg-success',
    };
    return badges[key] || 'bg-secondary';
}
</script>

<template>
    <AdminLayout>
        <Head title="Email Templates" />
        
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Email Templates</h1>
                            <p class="text-muted mb-0">
                                Manage transactional email templates and content
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                    <div>
                        <h6 class="mb-1">Template Variables</h6>
                        <p class="mb-0">
                            Use <code>{{variable_name}}</code> syntax to insert dynamic content. 
                            Each template shows available variables when editing.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Templates Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="text-dark fw-medium">Template</th>
                                <th class="text-dark fw-medium">Subject</th>
                                <th class="text-dark fw-medium">Status</th>
                                <th class="text-dark fw-medium">Last Updated</th>
                                <th class="text-dark fw-medium text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="template in templates" :key="template.id">
                                <td>
                                    <div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span :class="`badge ${getTemplateBadge(template.key)}`">
                                                {{ template.key }}
                                            </span>
                                            <strong>{{ template.name }}</strong>
                                        </div>
                                        <small v-if="template.description" class="text-muted">
                                            {{ template.description }}
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <code class="text-primary">{{ template.subject }}</code>
                                </td>
                                <td>
                                    <span v-if="template.is_active" class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>Active
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="bi bi-x-circle me-1"></i>Inactive
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ formatDate(template.updated_at) }}</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <Link 
                                            :href="`/admin/notifications/email-templates/${template.id}/edit`"
                                            class="btn btn-sm btn-outline-primary"
                                        >
                                            <i class="bi bi-pencil me-1"></i>
                                            Edit
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
code {
    background-color: rgba(13, 110, 253, 0.1);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
</style>