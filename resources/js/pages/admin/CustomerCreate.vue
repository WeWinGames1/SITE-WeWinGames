<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errorMessage = ref<string | null>(null);
const successMessage = ref<string | null>(null);

function submit() {
    // Clear previous messages
    errorMessage.value = null;
    successMessage.value = null;
    
    // Validate required fields
    if (!form.name.trim()) {
        errorMessage.value = 'Please enter a name.';
        return;
    }
    
    if (!form.email.trim()) {
        errorMessage.value = 'Please enter an email address.';
        return;
    }
    
    if (!form.password) {
        errorMessage.value = 'Please enter a password.';
        return;
    }
    
    if (form.password !== form.password_confirmation) {
        errorMessage.value = 'Password confirmation does not match.';
        return;
    }
    
    form.post(route('admin.customers.store'), {
        onSuccess: () => {
            successMessage.value = 'Customer created successfully!';
            // Reset form
            form.reset();
            // Clear success message after 3 seconds
            setTimeout(() => {
                successMessage.value = null;
            }, 3000);
        },
        onError: (errors) => {
            // Get the first error message
            const firstError = Object.values(errors)[0];
            errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError || 'An error occurred while creating the customer.';
        },
    });
}
</script>

<template>
    <Head title="Create Customer" />

    <AdminLayout>
        <div class="container-fluid py-4">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">Create New Customer</h3>
                            <Link :href="route('admin.customers.index')" class="btn btn-secondary btn-sm">
                                <i class="bi bi-arrow-left me-2"></i>
                                Back to Customers
                            </Link>
                        </div>
                        <div class="card-body">
                            <!-- Success Alert -->
                            <div v-if="successMessage" class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i>
                                {{ successMessage }}
                                <button type="button" class="btn-close" @click="successMessage = null"></button>
                            </div>

                            <!-- Error Alert -->
                            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                {{ errorMessage }}
                                <button type="button" class="btn-close" @click="errorMessage = null"></button>
                            </div>

                            <form @submit.prevent="submit">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Name *</label>
                                            <input
                                                type="text"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.name }"
                                                id="name"
                                                v-model="form.name"
                                                placeholder="Enter customer name"
                                                required
                                            />
                                            <div v-if="form.errors.name" class="invalid-feedback">
                                                {{ form.errors.name }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email *</label>
                                            <input
                                                type="email"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.email }"
                                                id="email"
                                                v-model="form.email"
                                                placeholder="Enter email address"
                                                required
                                            />
                                            <div v-if="form.errors.email" class="invalid-feedback">
                                                {{ form.errors.email }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password" class="form-label">Password *</label>
                                            <input
                                                type="password"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.password }"
                                                id="password"
                                                v-model="form.password"
                                                placeholder="Enter password"
                                                required
                                            />
                                            <div v-if="form.errors.password" class="invalid-feedback">
                                                {{ form.errors.password }}
                                            </div>
                                            <small class="form-text text-muted">
                                                Password must be at least 8 characters long
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="password_confirmation" class="form-label">Confirm Password *</label>
                                            <input
                                                type="password"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.password_confirmation }"
                                                id="password_confirmation"
                                                v-model="form.password_confirmation"
                                                placeholder="Confirm password"
                                                required
                                            />
                                            <div v-if="form.errors.password_confirmation" class="invalid-feedback">
                                                {{ form.errors.password_confirmation }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing">
                                            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                                            Creating...
                                        </span>
                                        <span v-else>
                                            <i class="bi bi-person-plus me-2"></i>
                                            Create Customer
                                        </span>
                                    </button>
                                    <Link :href="route('admin.customers.index')" class="btn btn-secondary ms-2">
                                        Cancel
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>