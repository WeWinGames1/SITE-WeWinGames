<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref } from 'vue';

interface JobPosition {
    id: number;
    title: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
}

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
    created_at: string;
    full_name: string;
}

interface Props {
    submissions: {
        data: ResumeSubmission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    jobPositions: JobPosition[];
    submissionPositions: string[];
    filters: {
        status?: string;
        position?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

// Filters
const filters = ref({
    status: props.filters.status || 'all',
    position: props.filters.position || '',
    search: props.filters.search || '',
});

function applyFilters() {
    router.get(route('admin.resume-submissions.index'), {
        status: filters.value.status !== 'all' ? filters.value.status : undefined,
        position: filters.value.position || undefined,
        search: filters.value.search || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function resetFilters() {
    filters.value = {
        status: 'all',
        position: '',
        search: '',
    };
    applyFilters();
}

// Job Position Management
const showPositionModal = ref(false);
const editingPosition = ref<JobPosition | null>(null);

const positionForm = useForm({
    title: '',
    description: '',
    is_active: true,
    sort_order: 0,
});

function openAddPositionModal() {
    editingPosition.value = null;
    positionForm.reset();
    positionForm.clearErrors();
    showPositionModal.value = true;
}

function openEditPositionModal(position: JobPosition) {
    editingPosition.value = position;
    positionForm.title = position.title;
    positionForm.description = position.description || '';
    positionForm.is_active = position.is_active;
    positionForm.sort_order = position.sort_order;
    positionForm.clearErrors();
    showPositionModal.value = true;
}

function submitPosition() {
    if (editingPosition.value) {
        positionForm.put(route('admin.resume-submissions.positions.update', editingPosition.value.id), {
            onSuccess: () => {
                showPositionModal.value = false;
                positionForm.reset();
            },
        });
    } else {
        positionForm.post(route('admin.resume-submissions.positions.store'), {
            onSuccess: () => {
                showPositionModal.value = false;
                positionForm.reset();
            },
        });
    }
}

function togglePosition(position: JobPosition) {
    router.post(route('admin.resume-submissions.positions.toggle', position.id));
}

function deletePosition(position: JobPosition) {
    if (confirm(`Are you sure you want to delete the position "${position.title}"?`)) {
        router.delete(route('admin.resume-submissions.positions.destroy', position.id));
    }
}

// Resume Submission Management
function updateStatus(submission: ResumeSubmission, status: string) {
    router.put(route('admin.resume-submissions.update-status', submission.id), {
        status: status,
    });
}

function deleteSubmission(submission: ResumeSubmission) {
    if (confirm('Are you sure you want to delete this submission?')) {
        router.delete(route('admin.resume-submissions.destroy', submission.id));
    }
}

// Status badge colors
function getStatusBadgeClass(status: string) {
    switch (status) {
        case 'new': return 'bg-primary';
        case 'reviewed': return 'bg-info';
        case 'contacted': return 'bg-warning';
        case 'hired': return 'bg-success';
        case 'rejected': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Resume Submissions" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Resume Submissions</h1>
                    <p class="text-muted mb-0">Manage job applications and available positions</p>
                </div>
                <button @click="openAddPositionModal" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Manage Positions
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Submissions</h6>
                            <h3 class="mb-0">{{ submissions.total }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">New Submissions</h6>
                            <h3 class="mb-0 text-primary">
                                {{ submissions.data.filter(s => s.status === 'new').length }}
                            </h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Active Positions</h6>
                            <h3 class="mb-0">{{ jobPositions.filter(p => p.is_active).length }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Positions</h6>
                            <h3 class="mb-0">{{ jobPositions.length }}</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Job Positions List -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Available Job Positions</h5>
                </div>
                <div class="card-body">
                    <div v-if="jobPositions.length === 0" class="text-center py-3">
                        <p class="text-muted mb-0">No job positions created yet.</p>
                    </div>
                    <div v-else class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Description</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Order</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="position in jobPositions" :key="position.id">
                                    <td class="fw-medium">{{ position.title }}</td>
                                    <td class="text-muted small">{{ position.description || '-' }}</td>
                                    <td class="text-center">
                                        <button 
                                            @click="togglePosition(position)"
                                            :class="position.is_active ? 'btn btn-sm btn-success' : 'btn btn-sm btn-danger'"
                                        >
                                            {{ position.is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-center">{{ position.sort_order }}</td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <button @click="openEditPositionModal(position)" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button @click="deletePosition(position)" class="btn btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select v-model="filters.status" @change="applyFilters" class="form-select">
                                <option value="all">All Statuses</option>
                                <option value="new">New</option>
                                <option value="reviewed">Reviewed</option>
                                <option value="contacted">Contacted</option>
                                <option value="hired">Hired</option>
                                <option value="rejected">Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Position</label>
                            <select v-model="filters.position" @change="applyFilters" class="form-select">
                                <option value="">All Positions</option>
                                <option v-for="position in submissionPositions" :key="position" :value="position">
                                    {{ position }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input 
                                v-model="filters.search"
                                @keyup.enter="applyFilters"
                                type="text" 
                                class="form-control" 
                                placeholder="Search by name, email, or phone..."
                            >
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button @click="resetFilters" class="btn btn-secondary w-100">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submissions Table -->
            <div class="card">
                <div class="card-body">
                    <div v-if="submissions.data.length === 0" class="text-center py-5">
                        <i class="bi bi-file-earmark-person display-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No resume submissions found.</p>
                    </div>
                    
                    <div v-else class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Contact</th>
                                    <th>Position</th>
                                    <th class="text-center">Status</th>
                                    <th>Submitted</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="submission in submissions.data" :key="submission.id">
                                    <td>
                                        <div>
                                            <strong>{{ submission.full_name }}</strong>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><i class="bi bi-envelope me-1"></i> {{ submission.email }}</div>
                                            <div><i class="bi bi-telephone me-1"></i> {{ submission.phone }}</div>
                                        </div>
                                    </td>
                                    <td>{{ submission.position }}</td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button 
                                                :class="`btn btn-sm ${getStatusBadgeClass(submission.status)} dropdown-toggle`"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                            >
                                                {{ submission.status }}
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a @click="updateStatus(submission, 'new')" class="dropdown-item" href="#">New</a></li>
                                                <li><a @click="updateStatus(submission, 'reviewed')" class="dropdown-item" href="#">Reviewed</a></li>
                                                <li><a @click="updateStatus(submission, 'contacted')" class="dropdown-item" href="#">Contacted</a></li>
                                                <li><a @click="updateStatus(submission, 'hired')" class="dropdown-item" href="#">Hired</a></li>
                                                <li><a @click="updateStatus(submission, 'rejected')" class="dropdown-item" href="#">Rejected</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                    <td class="small text-muted">
                                        {{ new Date(submission.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <Link 
                                                :href="route('admin.resume-submissions.show', submission.id)"
                                                class="btn btn-outline-primary"
                                                title="View Details"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </Link>
                                            <button 
                                                @click="deleteSubmission(submission)"
                                                class="btn btn-outline-danger"
                                                title="Delete"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="submissions.last_page > 1" class="d-flex justify-content-center mt-4">
                <nav>
                    <ul class="pagination">
                        <li class="page-item" :class="{ disabled: submissions.current_page === 1 }">
                            <Link 
                                :href="route('admin.resume-submissions.index', { ...filters, page: submissions.current_page - 1 })"
                                class="page-link"
                                preserve-state
                            >
                                Previous
                            </Link>
                        </li>
                        <li 
                            v-for="page in submissions.last_page" 
                            :key="page"
                            class="page-item" 
                            :class="{ active: page === submissions.current_page }"
                        >
                            <Link 
                                :href="route('admin.resume-submissions.index', { ...filters, page })"
                                class="page-link"
                                preserve-state
                            >
                                {{ page }}
                            </Link>
                        </li>
                        <li class="page-item" :class="{ disabled: submissions.current_page === submissions.last_page }">
                            <Link 
                                :href="route('admin.resume-submissions.index', { ...filters, page: submissions.current_page + 1 })"
                                class="page-link"
                                preserve-state
                            >
                                Next
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Position Modal -->
        <div v-if="showPositionModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ editingPosition ? 'Edit Position' : 'Add New Position' }}
                        </h5>
                        <button @click="showPositionModal = false" type="button" class="btn-close"></button>
                    </div>
                    <form @submit.prevent="submitPosition">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Position Title <span class="text-danger">*</span></label>
                                <input 
                                    v-model="positionForm.title"
                                    type="text" 
                                    class="form-control"
                                    :class="{ 'is-invalid': positionForm.errors.title }"
                                    required
                                >
                                <div v-if="positionForm.errors.title" class="invalid-feedback">
                                    {{ positionForm.errors.title }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea 
                                    v-model="positionForm.description"
                                    class="form-control"
                                    :class="{ 'is-invalid': positionForm.errors.description }"
                                    rows="3"
                                ></textarea>
                                <div v-if="positionForm.errors.description" class="invalid-feedback">
                                    {{ positionForm.errors.description }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sort Order</label>
                                <input 
                                    v-model.number="positionForm.sort_order"
                                    type="number" 
                                    class="form-control"
                                    :class="{ 'is-invalid': positionForm.errors.sort_order }"
                                    min="0"
                                >
                                <div v-if="positionForm.errors.sort_order" class="invalid-feedback">
                                    {{ positionForm.errors.sort_order }}
                                </div>
                                <small class="form-text text-muted">Lower numbers appear first.</small>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input 
                                        v-model="positionForm.is_active"
                                        type="checkbox" 
                                        class="form-check-input"
                                        id="is_active"
                                    >
                                    <label class="form-check-label" for="is_active">
                                        Active (shown in dropdown)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button @click="showPositionModal = false" type="button" class="btn btn-secondary">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" :disabled="positionForm.processing">
                                <span v-if="positionForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                                {{ editingPosition ? 'Update' : 'Create' }} Position
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>