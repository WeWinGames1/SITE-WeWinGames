<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import InputError from '@/components/InputError.vue';
import { ref } from 'vue';

const props = defineProps<{
    categories: string[];
}>();

const showNewCategory = ref(false);
const newCategory = ref('');

const form = useForm({
    question: '',
    answer: '',
    category: '',
    is_active: true,
    sort_order: 0
});

function handleCategoryChange() {
    if (form.category === '__new__') {
        showNewCategory.value = true;
        form.category = '';
    } else {
        showNewCategory.value = false;
        newCategory.value = '';
    }
}

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();
    
    let isValid = true;
    const errors: Record<string, string> = {};
    
    // Required fields validation
    if (!form.question || !form.question.trim()) {
        errors.question = 'The question field is required.';
        isValid = false;
    } else if (form.question.length > 500) {
        errors.question = 'The question may not be greater than 500 characters.';
        isValid = false;
    }
    
    if (!form.answer || !form.answer.trim()) {
        errors.answer = 'The answer field is required.';
        isValid = false;
    }
    
    // Optional fields validation
    if (form.category && form.category.length > 100) {
        errors.category = 'The category may not be greater than 100 characters.';
        isValid = false;
    }
    
    if (newCategory.value && newCategory.value.length > 100) {
        errors.category = 'The category may not be greater than 100 characters.';
        isValid = false;
    }
    
    // Numeric validation
    if (form.sort_order !== null && form.sort_order !== undefined) {
        if (!Number.isInteger(form.sort_order)) {
            errors.sort_order = 'The sort order must be an integer.';
            isValid = false;
        }
    }
    
    // Set errors if any
    if (!isValid) {
        form.setError(errors);
    }
    
    return isValid;
}

function submit() {
    if (showNewCategory.value && newCategory.value) {
        form.category = newCategory.value;
    }
    
    if (!validateForm()) {
        return;
    }
    
    form.post(route('admin.faqs.store'));
}
</script>

<template>
    <AdminLayout>
        <Head title="Create FAQ" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Create FAQ</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Dashboard</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.faqs.index')">FAQs</Link>
                        </li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </nav>
            </div>

            <!-- Create Form -->
            <div class="card">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Question -->
                                <div class="mb-3">
                                    <label for="question" class="form-label">Question <span class="text-danger">*</span></label>
                                    <input 
                                        id="question"
                                        v-model="form.question"
                                        type="text" 
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.question }"
                                        placeholder="Enter the question"
                                        maxlength="500"
                                        required
                                    >
                                    <InputError class="mt-2" :message="form.errors.question" />
                                </div>

                                <!-- Answer -->
                                <div class="mb-3">
                                    <label for="answer" class="form-label">Answer <span class="text-danger">*</span></label>
                                    <textarea 
                                        id="answer"
                                        v-model="form.answer"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.answer }"
                                        rows="6"
                                        placeholder="Enter the answer"
                                        required
                                    ></textarea>
                                    <InputError class="mt-2" :message="form.errors.answer" />
                                    <small class="form-text text-muted">You can use basic HTML for formatting.</small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Category -->
                                <div class="mb-3">
                                    <label for="category" class="form-label">Category</label>
                                    <select 
                                        v-if="!showNewCategory"
                                        id="category"
                                        v-model="form.category"
                                        @change="handleCategoryChange"
                                        class="form-select"
                                        :class="{ 'is-invalid': form.errors.category }"
                                    >
                                        <option value="">No Category</option>
                                        <option v-for="category in props.categories" :key="category" :value="category">
                                            {{ category }}
                                        </option>
                                        <option value="__new__">+ Add New Category</option>
                                    </select>
                                    <div v-else class="input-group">
                                        <input 
                                            v-model="newCategory"
                                            type="text" 
                                            class="form-control"
                                            placeholder="Enter new category"
                                            maxlength="100"
                                        >
                                        <button 
                                            @click="showNewCategory = false; form.category = ''"
                                            type="button" 
                                            class="btn btn-outline-secondary"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.category" />
                                </div>

                                <!-- Sort Order -->
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input 
                                        id="sort_order"
                                        v-model.number="form.sort_order"
                                        type="number" 
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.sort_order }"
                                        min="0"
                                    >
                                    <InputError class="mt-2" :message="form.errors.sort_order" />
                                    <small class="form-text text-muted">Lower numbers appear first.</small>
                                </div>

                                <!-- Status -->
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input 
                                            id="is_active"
                                            v-model="form.is_active"
                                            type="checkbox" 
                                            class="form-check-input"
                                            role="switch"
                                        >
                                        <label class="form-check-label" for="is_active">
                                            Active
                                        </label>
                                    </div>
                                    <small class="form-text text-muted">Only active FAQs are shown to users.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between mt-4">
                            <Link 
                                :href="route('admin.faqs.index')"
                                class="btn btn-secondary"
                            >
                                <i class="bi bi-arrow-left me-2"></i>
                                Cancel
                            </Link>
                            <button 
                                type="submit" 
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Creating...
                                </span>
                                <span v-else>
                                    <i class="bi bi-check-circle me-2"></i>
                                    Create FAQ
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>