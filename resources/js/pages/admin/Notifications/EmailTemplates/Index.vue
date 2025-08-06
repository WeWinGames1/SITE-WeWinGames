<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface EmailTemplate {
    id: number;
    key: string;
    name: string;
    description: string | null;
    subject: string;
    is_active: boolean;
    updated_at: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{
    templates: EmailTemplate[];
}>();

const showTestEmailModal = ref(false);
const selectedTemplate = ref<EmailTemplate | null>(null);
const selectedUserId = ref('');
const customers = ref<User[]>([]);
const searchQuery = ref('');
const isLoading = ref(false);
const isSending = ref(false);
const testEmailMessage = ref('');
const testEmailError = ref('');

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getTemplateBadge(key: string): string {
    const badges: Record<string, string> = {
        new_registration: 'bg-success',
        forgot_password: 'bg-warning',
        trial_expiring: 'bg-info',
        plan_renewal: 'bg-primary',
        payment_failed: 'bg-danger',
        subscription_cancelled: 'bg-secondary',
        welcome_subscriber: 'bg-success',
    };
    return badges[key] || 'bg-secondary';
}

async function searchCustomers() {
    if (searchQuery.value.length < 2) {
        customers.value = [];
        return;
    }

    isLoading.value = true;
    try {
        // Ensure CSRF token is set
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }

        const response = await axios.get('/admin/api/customers/search', {
            params: { q: searchQuery.value },
        });
        customers.value = response.data.customers;
    } catch (error) {
        console.error('Failed to search customers:', error);
        customers.value = [];
    } finally {
        isLoading.value = false;
    }
}

function openTestEmailModal(template: EmailTemplate) {
    selectedTemplate.value = template;
    showTestEmailModal.value = true;
    testEmailMessage.value = '';
    testEmailError.value = '';
    searchQuery.value = '';
    selectedUserId.value = '';
    customers.value = [];
}

function closeTestEmailModal() {
    showTestEmailModal.value = false;
    selectedTemplate.value = null;
    selectedUserId.value = '';
    customers.value = [];
    searchQuery.value = '';
}

async function sendTestEmail() {
    if (!selectedUserId.value || !selectedTemplate.value) {
        testEmailError.value = 'Please select a customer';
        return;
    }

    isSending.value = true;
    testEmailError.value = '';
    testEmailMessage.value = '';

    try {
        // Ensure CSRF token is set
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }

        const response = await axios.post(`/admin/notifications/email-templates/${selectedTemplate.value.id}/send-test`, {
            user_id: selectedUserId.value,
        });

        if (response.data.success) {
            testEmailMessage.value = response.data.message;
            setTimeout(() => {
                closeTestEmailModal();
            }, 2000);
        } else {
            testEmailError.value = response.data.message || 'Failed to send test email';
        }
    } catch (error: any) {
        if (error.response?.status === 419) {
            testEmailError.value = 'Session expired. Please refresh the page and try again.';
        } else {
            testEmailError.value = error.response?.data?.message || 'Failed to send test email';
        }
    } finally {
        isSending.value = false;
    }
}

onMounted(() => {
    // Load recent customers on mount
    searchCustomers();
});
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
                            <p class="text-muted mb-0">Manage transactional email templates and content</p>
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
                            Use <code>{{ variable_name }}</code> syntax to insert dynamic content. Each template shows available variables when
                            editing.
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
                                    <span v-if="template.is_active" class="badge bg-success"> <i class="bi bi-check-circle me-1"></i>Active </span>
                                    <span v-else class="badge bg-secondary"> <i class="bi bi-x-circle me-1"></i>Inactive </span>
                                </td>
                                <td>
                                    <small class="text-muted">{{ formatDate(template.updated_at) }}</small>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button @click="openTestEmailModal(template)" class="btn btn-sm btn-outline-info" title="Send test email">
                                            <i class="bi bi-envelope me-1"></i>
                                            Test
                                        </button>
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

        <!-- Test Email Modal -->
        <div v-if="showTestEmailModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5)">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-envelope me-2"></i>
                            Send Test Email: {{ selectedTemplate?.name }}
                        </h5>
                        <button type="button" class="btn-close" @click="closeTestEmailModal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Success Message -->
                        <div v-if="testEmailMessage" class="alert alert-success mb-3">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ testEmailMessage }}
                        </div>

                        <!-- Error Message -->
                        <div v-if="testEmailError" class="alert alert-danger mb-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            {{ testEmailError }}
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Search Customer</label>
                            <input
                                type="text"
                                class="form-control"
                                v-model="searchQuery"
                                @input="searchCustomers"
                                placeholder="Search by name or email..."
                            />
                        </div>

                        <div v-if="isLoading" class="text-center py-3">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Searching...</span>
                        </div>

                        <div v-else-if="customers.length > 0" class="mb-3">
                            <label class="form-label">Select Customer</label>
                            <div class="list-group" style="max-height: 300px; overflow-y: auto">
                                <button
                                    v-for="customer in customers"
                                    :key="customer.id"
                                    type="button"
                                    class="list-group-item list-group-item-action"
                                    :class="{ active: selectedUserId === customer.id.toString() }"
                                    @click="selectedUserId = customer.id.toString()"
                                >
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-medium">{{ customer.name }}</div>
                                            <small class="text-muted">{{ customer.email }}</small>
                                        </div>
                                        <i v-if="selectedUserId === customer.id.toString()" class="bi bi-check-circle text-success"></i>
                                    </div>
                                </button>
                            </div>
                        </div>

                        <div v-else-if="searchQuery.length >= 2" class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            No customers found matching "{{ searchQuery }}"
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Note:</strong> The test email will be sent with actual customer data, including their name, email, and
                            subscription details.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeTestEmailModal" :disabled="isSending">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="sendTestEmail" :disabled="!selectedUserId || isSending">
                            <span v-if="isSending">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Sending...
                            </span>
                            <span v-else>
                                <i class="bi bi-send me-2"></i>
                                Send Test Email
                            </span>
                        </button>
                    </div>
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
