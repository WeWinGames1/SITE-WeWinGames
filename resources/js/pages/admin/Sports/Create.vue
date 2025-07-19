<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    name: '',
    description: '',
    is_active: true,
});

const errorMessage = ref<string | null>(null);

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();
    
    let isValid = true;
    const errors: Record<string, string> = {};
    
    // Required field validation
    if (!form.name || !form.name.trim()) {
        errors.name = 'The name field is required.';
        isValid = false;
    } else if (form.name.length > 255) {
        errors.name = 'The name may not be greater than 255 characters.';
        isValid = false;
    }
    
    // Note: We can't check uniqueness on client side, server will handle it
    
    // Set errors if any
    if (!isValid) {
        form.setError(errors);
    }
    
    return isValid;
}

function submit() {
    console.log('Submitting sport create:', {
        data: form.data(),
        route: route('admin.sports.store')
    });
    
    // Clear previous messages
    errorMessage.value = null;
    
    if (!validateForm()) {
        errorMessage.value = 'Please fix the validation errors before submitting.';
        return;
    }
    
    form.post(route('admin.sports.store'), {
        onError: (errors) => {
            console.error('Sport create errors:', errors);
            // Handle unique constraint error
            if (errors.name && errors.name.includes('already been taken')) {
                errorMessage.value = 'A sport with this name already exists. Please choose a different name.';
            } else {
                const firstError = Object.values(errors)[0];
                errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError || 'An error occurred while creating the sport.';
            }
        },
        onSuccess: () => {
            console.log('Sport created successfully');
        }
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Create Sport" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Create Sport</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.sports.index')">Sports</Link>
                        </li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </nav>
            </div>

            <!-- Alert Messages -->
            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ errorMessage }}
                <button type="button" class="btn-close" @click="errorMessage = null" aria-label="Close"></button>
            </div>

            <!-- Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <label for="name" class="form-label required">Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.name }"
                                        id="name"
                                        maxlength="255"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea
                                        v-model="form.description"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.description }"
                                        id="description"
                                        rows="3"
                                    ></textarea>
                                    <div v-if="form.errors.description" class="invalid-feedback">
                                        {{ form.errors.description }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="form-check-input"
                                            id="is_active"
                                        />
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Inactive sports won't be available for new teams or leagues.
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        Create Sport
                                    </button>
                                    <Link
                                        :href="route('admin.sports.index')"
                                        class="btn btn-outline-secondary"
                                    >
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

<style scoped>
.required::after {
    content: ' *';
    color: #dc3545;
}
</style>