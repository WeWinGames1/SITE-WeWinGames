<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ResumeSubmission {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    position: string;
    about: string;
    status: 'new' | 'reviewed' | 'contacted' | 'hired' | 'rejected';
    notes: string | null;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
    updated_at: string;
    full_name: string;
}

interface Props {
    submission: ResumeSubmission;
}

const props = defineProps<Props>();

const showNotesModal = ref(false);
const notesForm = useForm({
    status: props.submission.status,
    notes: props.submission.notes || '',
});

function updateStatus(status: string) {
    router.put(route('admin.resume-submissions.update-status', props.submission.id), {
        status: status,
        notes: props.submission.notes,
    });
}

function updateNotes() {
    notesForm.put(route('admin.resume-submissions.update-status', props.submission.id), {
        onSuccess: () => {
            showNotesModal.value = false;
        },
    });
}

function deleteSubmission() {
    if (confirm('Are you sure you want to delete this submission?')) {
        router.delete(route('admin.resume-submissions.destroy', props.submission.id));
    }
}

// Status badge colors
function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'new':
            return 'bg-primary';
        case 'reviewed':
            return 'bg-info';
        case 'contacted':
            return 'bg-warning';
        case 'hired':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Resume - ${submission.full_name}`" />

        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <Link :href="route('admin.dashboard')">Dashboard</Link>
                            </li>
                            <li class="breadcrumb-item">
                                <Link :href="route('admin.resume-submissions.index')">Resume Submissions</Link>
                            </li>
                            <li class="breadcrumb-item active">{{ submission.full_name }}</li>
                        </ol>
                    </nav>
                    <h1 class="h2 mb-0 mt-2">{{ submission.full_name }}</h1>
                </div>
                <div class="d-flex gap-2">
                    <button @click="showNotesModal = true" class="btn btn-secondary">
                        <i class="bi bi-sticky me-2"></i>
                        Edit Notes
                    </button>
                    <button @click="deleteSubmission" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i>
                        Delete
                    </button>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Applicant Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Applicant Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <dl>
                                        <dt class="text-muted">Name</dt>
                                        <dd class="mb-3">{{ submission.first_name }} {{ submission.last_name }}</dd>

                                        <dt class="text-muted">Email</dt>
                                        <dd class="mb-3">
                                            <a :href="`mailto:${submission.email}`">{{ submission.email }}</a>
                                        </dd>

                                        <dt class="text-muted">Phone</dt>
                                        <dd class="mb-3">
                                            <a :href="`tel:${submission.phone}`">{{ submission.phone }}</a>
                                        </dd>
                                    </dl>
                                </div>
                                <div class="col-md-6">
                                    <dl>
                                        <dt class="text-muted">Position Applied For</dt>
                                        <dd class="mb-3">{{ submission.position }}</dd>

                                        <dt class="text-muted">Submitted On</dt>
                                        <dd class="mb-3">{{ new Date(submission.created_at).toLocaleString() }}</dd>

                                        <dt class="text-muted">Last Updated</dt>
                                        <dd class="mb-3">{{ new Date(submission.updated_at).toLocaleString() }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- About Section -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">About the Applicant</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-pre-wrap">{{ submission.about }}</div>
                        </div>
                    </div>

                    <!-- Technical Details -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Technical Details</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-3 text-muted">IP Address</dt>
                                <dd class="col-sm-9">{{ submission.ip_address || 'Not recorded' }}</dd>

                                <dt class="col-sm-3 text-muted">User Agent</dt>
                                <dd class="col-sm-9 text-break">
                                    <small>{{ submission.user_agent || 'Not recorded' }}</small>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Status -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <span :class="`badge ${getStatusBadgeClass(submission.status)} fs-6 px-3 py-2`">
                                    {{ submission.status.toUpperCase() }}
                                </span>
                            </div>
                            <div class="d-grid gap-2">
                                <button v-if="submission.status !== 'new'" @click="updateStatus('new')" class="btn btn-sm btn-outline-primary">
                                    Mark as New
                                </button>
                                <button v-if="submission.status !== 'reviewed'" @click="updateStatus('reviewed')" class="btn btn-sm btn-outline-info">
                                    Mark as Reviewed
                                </button>
                                <button
                                    v-if="submission.status !== 'contacted'"
                                    @click="updateStatus('contacted')"
                                    class="btn btn-sm btn-outline-warning"
                                >
                                    Mark as Contacted
                                </button>
                                <button v-if="submission.status !== 'hired'" @click="updateStatus('hired')" class="btn btn-sm btn-outline-success">
                                    Mark as Hired
                                </button>
                                <button
                                    v-if="submission.status !== 'rejected'"
                                    @click="updateStatus('rejected')"
                                    class="btn btn-sm btn-outline-danger"
                                >
                                    Mark as Rejected
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Notes</h5>
                            <button @click="showNotesModal = true" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <p v-if="submission.notes" class="text-pre-wrap mb-0">{{ submission.notes }}</p>
                            <p v-else class="text-muted mb-0">No notes yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes Modal -->
        <div v-if="showNotesModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0, 0, 0, 0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Notes</h5>
                        <button @click="showNotesModal = false" type="button" class="btn-close"></button>
                    </div>
                    <form @submit.prevent="updateNotes">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select v-model="notesForm.status" class="form-select">
                                    <option value="new">New</option>
                                    <option value="reviewed">Reviewed</option>
                                    <option value="contacted">Contacted</option>
                                    <option value="hired">Hired</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes</label>
                                <textarea
                                    v-model="notesForm.notes"
                                    class="form-control"
                                    rows="5"
                                    placeholder="Add any notes about this applicant..."
                                ></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button @click="showNotesModal = false" type="button" class="btn btn-secondary">Cancel</button>
                            <button type="submit" class="btn btn-primary" :disabled="notesForm.processing">
                                <span v-if="notesForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.text-pre-wrap {
    white-space: pre-wrap;
}
</style>
