<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Admin {
    id: number;
    name: string;
    email: string;
}

interface User {
    id: number;
    name: string;
    email: string;
}

const props = defineProps<{ 
    admins: Admin[];
    users: User[];
}>();

const addForm = useForm({ user_id: '' });
const removeForm = useForm({ user_id: '' });
const showAddModal = ref(false);
const adminToRemove = ref<Admin | null>(null);
const showRemoveModal = ref(false);
const showCreateCustomerModal = ref(false);
const createCustomerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function openAddModal() {
    showAddModal.value = true;
}

function closeAddModal() {
    showAddModal.value = false;
    addForm.reset('user_id');
}

function addAdmin() {
    if (addForm.user_id) {
        addForm.post(route('admin.admins.add'), {
            preserveScroll: true,
            onSuccess: () => {
                closeAddModal();
            },
            onError: (errors) => {
                if (errors.response?.status === 419) {
                    alert('Your session has expired. Please refresh the page and try again.');
                    window.location.reload();
                }
            }
        });
    }
}

function confirmRemoveAdmin(admin: Admin) {
    adminToRemove.value = admin;
    showRemoveModal.value = true;
}

function removeAdmin() {
    if (adminToRemove.value) {
        removeForm.user_id = adminToRemove.value.id;
        removeForm.post(route('admin.admins.remove'), {
            preserveScroll: true,
            onSuccess: () => {
                showRemoveModal.value = false;
                adminToRemove.value = null;
                removeForm.reset('user_id');
            },
        });
    }
}

function openCreateCustomerModal() {
    showCreateCustomerModal.value = true;
}

function closeCreateCustomerModal() {
    showCreateCustomerModal.value = false;
    createCustomerForm.reset();
}

function createCustomer() {
    createCustomerForm.post(route('admin.customers.create'), {
        preserveScroll: true,
        onSuccess: () => {
            closeCreateCustomerModal();
        },
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Manage Admins" />
        
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">Admin Users</h1>
                <div class="d-flex gap-2">
                    <button 
                        type="button" 
                        class="btn btn-success"
                        @click="openCreateCustomerModal"
                    >
                        <i class="bi bi-person-plus-fill me-2"></i>Create Customer
                    </button>
                    <button 
                        type="button" 
                        class="btn btn-primary"
                        @click="openAddModal"
                    >
                        <i class="bi bi-person-plus me-2"></i>Add Admin
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Total Admins</h6>
                                    <h3 class="mb-0">{{ admins.length }}</h3>
                                </div>
                                <div class="text-primary">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Super Admin</h6>
                                    <h3 class="mb-0">1</h3>
                                </div>
                                <div class="text-warning">
                                    <i class="bi bi-shield-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Regular Admins</h6>
                                    <h3 class="mb-0">{{ admins.length - 1 }}</h3>
                                </div>
                                <div class="text-success">
                                    <i class="bi bi-person-check fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-muted mb-1">Available Users</h6>
                                    <h3 class="mb-0">{{ users.length }}</h3>
                                </div>
                                <div class="text-info">
                                    <i class="bi bi-person-add fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Admins Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Current Admin Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Added</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="admin in admins" :key="admin.id">
                                    <td>{{ admin.id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3">
                                                <i class="bi bi-person text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium">{{ admin.name }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ admin.email }}</td>
                                    <td>
                                        <span v-if="admin.id === 1" class="badge bg-warning">
                                            <i class="bi bi-shield-check me-1"></i>Super Admin
                                        </span>
                                        <span v-else class="badge bg-primary">
                                            <i class="bi bi-person-check me-1"></i>Admin
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ new Date(admin.created_at).toLocaleDateString() }}</span>
                                    </td>
                                    <td>
                                        <button
                                            v-if="admin.id !== 1"
                                            type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            @click="confirmRemoveAdmin(admin)"
                                        >
                                            <i class="bi bi-trash me-1"></i>Remove
                                        </button>
                                        <span v-else class="text-muted small">
                                            <i class="bi bi-lock me-1"></i>Protected
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Admin Modal -->
        <div 
            class="modal fade" 
            :class="{ show: showAddModal }"
            :style="{ display: showAddModal ? 'block' : 'none' }"
            tabindex="-1"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Admin</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            @click="closeAddModal"
                        ></button>
                    </div>
                    <form @submit.prevent="addAdmin">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="user_select" class="form-label">Select User</label>
                                <select 
                                    id="user_select"
                                    v-model="addForm.user_id" 
                                    class="form-select"
                                    :class="{ 'is-invalid': addForm.errors.user_id }"
                                >
                                    <option value="">Choose a user to make admin...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="addForm.errors.user_id" class="invalid-feedback">
                                    {{ addForm.errors.user_id }}
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Note:</strong> This will grant the selected user full administrative access to the system.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button 
                                type="button" 
                                class="btn btn-secondary" 
                                @click="closeAddModal"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="btn btn-primary"
                                :disabled="addForm.processing || !addForm.user_id"
                            >
                                <span v-if="addForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Adding...
                                </span>
                                <span v-else>
                                    <i class="bi bi-person-plus me-2"></i>Add Admin
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Remove Admin Confirmation Modal -->
        <div 
            class="modal fade" 
            :class="{ show: showRemoveModal }"
            :style="{ display: showRemoveModal ? 'block' : 'none' }"
            tabindex="-1"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Remove Admin Access</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            @click="showRemoveModal = false"
                        ></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Warning!</strong> You are about to remove admin privileges from:
                        </div>
                        <div v-if="adminToRemove" class="mb-3">
                            <p class="mb-1"><strong>Name:</strong> {{ adminToRemove.name }}</p>
                            <p class="mb-1"><strong>Email:</strong> {{ adminToRemove.email }}</p>
                        </div>
                        <p>This action will revoke all administrative access for this user. They will become a regular user.</p>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            @click="showRemoveModal = false"
                        >
                            Cancel
                        </button>
                        <button 
                            type="button" 
                            class="btn btn-danger"
                            @click="removeAdmin"
                            :disabled="removeForm.processing"
                        >
                            <span v-if="removeForm.processing">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Removing...
                            </span>
                            <span v-else>
                                <i class="bi bi-trash me-2"></i>Remove Admin
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Customer Modal -->
        <div 
            class="modal fade" 
            :class="{ show: showCreateCustomerModal }"
            :style="{ display: showCreateCustomerModal ? 'block' : 'none' }"
            tabindex="-1"
        >
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Customer</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            @click="closeCreateCustomerModal"
                        ></button>
                    </div>
                    <form @submit.prevent="createCustomer">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Name</label>
                                <input 
                                    id="customer_name"
                                    v-model="createCustomerForm.name" 
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': createCustomerForm.errors.name }"
                                    required
                                />
                                <div v-if="createCustomerForm.errors.name" class="invalid-feedback">
                                    {{ createCustomerForm.errors.name }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_email" class="form-label">Email</label>
                                <input 
                                    id="customer_email"
                                    v-model="createCustomerForm.email" 
                                    type="email"
                                    class="form-control"
                                    :class="{ 'is-invalid': createCustomerForm.errors.email }"
                                    required
                                />
                                <div v-if="createCustomerForm.errors.email" class="invalid-feedback">
                                    {{ createCustomerForm.errors.email }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_password" class="form-label">Password</label>
                                <input 
                                    id="customer_password"
                                    v-model="createCustomerForm.password" 
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': createCustomerForm.errors.password }"
                                    required
                                />
                                <div v-if="createCustomerForm.errors.password" class="invalid-feedback">
                                    {{ createCustomerForm.errors.password }}
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="customer_password_confirmation" class="form-label">Confirm Password</label>
                                <input 
                                    id="customer_password_confirmation"
                                    v-model="createCustomerForm.password_confirmation" 
                                    type="password"
                                    class="form-control"
                                    :class="{ 'is-invalid': createCustomerForm.errors.password_confirmation }"
                                    required
                                />
                                <div v-if="createCustomerForm.errors.password_confirmation" class="invalid-feedback">
                                    {{ createCustomerForm.errors.password_confirmation }}
                                </div>
                            </div>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Note:</strong> This will create a new customer account. The user will receive a welcome email with their login credentials.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button 
                                type="button" 
                                class="btn btn-secondary" 
                                @click="closeCreateCustomerModal"
                            >
                                Cancel
                            </button>
                            <button 
                                type="submit" 
                                class="btn btn-success"
                                :disabled="createCustomerForm.processing"
                            >
                                <span v-if="createCustomerForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Creating...
                                </span>
                                <span v-else>
                                    <i class="bi bi-person-plus-fill me-2"></i>Create Customer
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Backdrop -->
        <div 
            v-if="showAddModal || showRemoveModal || showCreateCustomerModal" 
            class="modal-backdrop fade show"
            @click="showAddModal = false; showRemoveModal = false; showCreateCustomerModal = false"
        ></div>
    </AdminLayout>
</template>

<style scoped>
.avatar-sm {
    width: 40px;
    height: 40px;
}
</style>