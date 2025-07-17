<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';

interface Category {
    id: number;
    slug: string;
    name: string;
    description?: string;
    posts_count: number;
    order_column: number;
}

interface Props {
    show: boolean;
}

interface Emits {
    (e: 'close'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const categories = ref<Category[]>([]);
const loading = ref(false);
const editingCategory = ref<Category | null>(null);
const showForm = ref(false);

const form = useForm({
    name: '',
    slug: '',
    description: '',
});

const isEditing = computed(() => editingCategory.value !== null);

// Load categories when modal is shown
watch(() => props.show, (newValue) => {
    if (newValue) {
        loadCategories();
    }
});

async function loadCategories() {
    loading.value = true;
    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await axios.get(route('admin.blog-categories.index'), {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        categories.value = response.data;
    } catch (error) {
        console.error('Error loading categories:', error);
        console.error('Response:', error.response?.data);
    } finally {
        loading.value = false;
    }
}

function generateSlug() {
    if (!isEditing.value && form.name) {
        form.slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
}

function startCreate() {
    editingCategory.value = null;
    form.reset();
    showForm.value = true;
}

function startEdit(category: Category) {
    editingCategory.value = category;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description || '';
    showForm.value = true;
}

function cancelEdit() {
    editingCategory.value = null;
    form.reset();
    showForm.value = false;
}

async function submit() {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const headers = {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
        };
        
        if (isEditing.value) {
            await axios.put(route('admin.blog-categories.update', editingCategory.value!.id), form.data(), { headers });
        } else {
            await axios.post(route('admin.blog-categories.store'), form.data(), { headers });
        }
        await loadCategories();
        cancelEdit();
    } catch (error: any) {
        if (error.response?.data?.errors) {
            Object.keys(error.response.data.errors).forEach(key => {
                form.setError(key as any, error.response.data.errors[key][0]);
            });
        }
    }
}

async function deleteCategory(category: Category) {
    if (!confirm(`Are you sure you want to delete "${category.name}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        await axios.delete(route('admin.blog-categories.destroy', category.id), {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        await loadCategories();
    } catch (error: any) {
        if (error.response?.status === 422) {
            alert(error.response.data.message);
        } else {
            alert('Error deleting category. Please try again.');
        }
    }
}

function close() {
    cancelEdit();
    emit('close');
}
</script>

<template>
    <div class="modal fade" :class="{ show }" :style="{ display: show ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Blog Categories</h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body">
                    <!-- Add/Edit Form -->
                    <div v-if="showForm" class="card mb-4">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3">{{ isEditing ? 'Edit Category' : 'Add New Category' }}</h6>
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <label class="form-label">Category Name <span class="text-danger">*</span></label>
                                    <input 
                                        v-model="form.name" 
                                        type="text" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.name }"
                                        @input="generateSlug"
                                        placeholder="e.g., Sports Analysis"
                                        required
                                    />
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">URL Slug <span class="text-danger">*</span></label>
                                    <input 
                                        v-model="form.slug" 
                                        type="text" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.slug }"
                                        placeholder="e.g., sports-analysis"
                                        pattern="[a-z0-9-]+"
                                        required
                                    />
                                    <div class="form-text">Only lowercase letters, numbers, and hyphens allowed</div>
                                    <div v-if="form.errors.slug" class="invalid-feedback">
                                        {{ form.errors.slug }}
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea 
                                        v-model="form.description" 
                                        class="form-control" 
                                        :class="{ 'is-invalid': form.errors.description }"
                                        rows="2"
                                        placeholder="Optional description for this category"
                                    ></textarea>
                                    <div v-if="form.errors.description" class="invalid-feedback">
                                        {{ form.errors.description }}
                                    </div>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        {{ isEditing ? 'Update' : 'Create' }} Category
                                    </button>
                                    <button 
                                        type="button" 
                                        class="btn btn-secondary"
                                        @click="cancelEdit"
                                    >
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Categories List -->
                    <div v-if="!showForm" class="mb-3">
                        <button @click="startCreate" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-2"></i>
                            Add New Category
                        </button>
                    </div>
                    
                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    
                    <!-- Categories Table -->
                    <div v-else-if="categories.length > 0" class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Posts</th>
                                    <th width="120">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="category in categories" :key="category.id">
                                    <td>
                                        <div>
                                            <strong>{{ category.name }}</strong>
                                            <div v-if="category.description" class="text-muted small">
                                                {{ category.description }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <code>{{ category.slug }}</code>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ category.posts_count }} posts</span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button 
                                                @click="startEdit(category)" 
                                                class="btn btn-outline-primary"
                                                title="Edit"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button 
                                                @click="deleteCategory(category)" 
                                                class="btn btn-outline-danger"
                                                :disabled="category.posts_count > 0"
                                                :title="category.posts_count > 0 ? 'Cannot delete category with posts' : 'Delete'"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-tags text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted">No categories found.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="close">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<style scoped>
.modal {
    overflow-y: auto;
}
</style>