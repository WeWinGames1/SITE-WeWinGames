<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref, computed } from 'vue';

interface Faq {
    id: number;
    question: string;
    answer: string;
    category: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    faqs: {
        data: Faq[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    categories: string[];
}>();

const selectedCategory = ref<string>('');
const searchQuery = ref<string>('');

const filteredFaqs = computed(() => {
    let filtered = props.faqs.data;
    
    if (selectedCategory.value) {
        filtered = filtered.filter(faq => faq.category === selectedCategory.value);
    }
    
    if (searchQuery.value) {
        const query = searchQuery.value.toLowerCase();
        filtered = filtered.filter(faq => 
            faq.question.toLowerCase().includes(query) || 
            faq.answer.toLowerCase().includes(query)
        );
    }
    
    return filtered;
});

function deleteFaq(id: number) {
    if (confirm('Are you sure you want to delete this FAQ?')) {
        router.delete(route('admin.faqs.destroy', id));
    }
}

function toggleActive(faq: Faq) {
    router.post(route('admin.faqs.toggle', faq.id), {}, {
        preserveScroll: true
    });
}

function updateOrder(faqs: Faq[]) {
    const faqsWithOrder = faqs.map((faq, index) => ({
        id: faq.id,
        sort_order: index
    }));
    
    router.post(route('admin.faqs.update-order'), {
        faqs: faqsWithOrder
    }, {
        preserveScroll: true
    });
}

function moveUp(index: number) {
    if (index > 0) {
        const faqs = [...filteredFaqs.value];
        [faqs[index], faqs[index - 1]] = [faqs[index - 1], faqs[index]];
        updateOrder(faqs);
    }
}

function moveDown(index: number) {
    if (index < filteredFaqs.value.length - 1) {
        const faqs = [...filteredFaqs.value];
        [faqs[index], faqs[index + 1]] = [faqs[index + 1], faqs[index]];
        updateOrder(faqs);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="FAQs" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Frequently Asked Questions</h1>
                    <p class="text-muted mb-0">Manage your FAQ items</p>
                </div>
                <Link 
                    :href="route('admin.faqs.create')" 
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-circle me-2"></i>
                    Add FAQ
                </Link>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Search FAQs</label>
                            <input 
                                v-model="searchQuery"
                                type="text" 
                                class="form-control" 
                                placeholder="Search questions or answers..."
                            >
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Filter by Category</label>
                            <select v-model="selectedCategory" class="form-select">
                                <option value="">All Categories</option>
                                <option v-for="category in props.categories" :key="category" :value="category">
                                    {{ category }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button @click="selectedCategory = ''; searchQuery = ''" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise me-2"></i>
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FAQs Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <div v-if="filteredFaqs.length === 0" class="text-center py-5">
                        <i class="bi bi-question-circle display-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">
                            <span v-if="searchQuery || selectedCategory">No FAQs found matching your criteria.</span>
                            <span v-else>No FAQs found. Add your first FAQ to get started.</span>
                        </p>
                        <Link 
                            v-if="!searchQuery && !selectedCategory"
                            :href="route('admin.faqs.create')" 
                            class="btn btn-primary mt-3"
                        >
                            <i class="bi bi-plus-circle me-2"></i>
                            Add First FAQ
                        </Link>
                    </div>
                    
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-dark fw-medium" width="80">Order</th>
                                    <th class="text-dark fw-medium">Question</th>
                                    <th class="text-dark fw-medium">Category</th>
                                    <th class="text-dark fw-medium text-center">Status</th>
                                    <th class="text-dark fw-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(faq, index) in filteredFaqs" :key="faq.id">
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button 
                                                @click="moveUp(index)"
                                                :disabled="index === 0"
                                                class="btn btn-outline-secondary"
                                                title="Move up"
                                            >
                                                <i class="bi bi-arrow-up"></i>
                                            </button>
                                            <button 
                                                @click="moveDown(index)"
                                                :disabled="index === filteredFaqs.length - 1"
                                                class="btn btn-outline-secondary"
                                                title="Move down"
                                            >
                                                <i class="bi bi-arrow-down"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <h6 class="mb-1">{{ faq.question }}</h6>
                                            <p class="text-muted small mb-0" style="max-width: 500px;">
                                                {{ faq.answer.substring(0, 100) }}{{ faq.answer.length > 100 ? '...' : '' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <span v-if="faq.category" class="badge bg-secondary">
                                            {{ faq.category }}
                                        </span>
                                        <span v-else class="text-muted">-</span>
                                    </td>
                                    <td class="text-center">
                                        <button 
                                            @click="toggleActive(faq)"
                                            :class="faq.is_active ? 'btn btn-sm btn-success' : 'btn btn-sm btn-danger'"
                                        >
                                            <i :class="faq.is_active ? 'bi bi-check-circle' : 'bi bi-x-circle'"></i>
                                            {{ faq.is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <Link 
                                                :href="route('admin.faqs.edit', faq.id)"
                                                class="btn btn-outline-primary"
                                                title="Edit"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </Link>
                                            <button 
                                                @click="deleteFaq(faq.id)"
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
            <div v-if="props.faqs.last_page > 1" class="d-flex justify-content-center">
                <nav>
                    <ul class="pagination">
                        <li class="page-item" :class="{ disabled: props.faqs.current_page === 1 }">
                            <Link 
                                :href="route('admin.faqs.index', { page: props.faqs.current_page - 1 })"
                                class="page-link"
                                preserve-state
                            >
                                Previous
                            </Link>
                        </li>
                        <li 
                            v-for="page in props.faqs.last_page" 
                            :key="page"
                            class="page-item" 
                            :class="{ active: page === props.faqs.current_page }"
                        >
                            <Link 
                                :href="route('admin.faqs.index', { page })"
                                class="page-link"
                                preserve-state
                            >
                                {{ page }}
                            </Link>
                        </li>
                        <li class="page-item" :class="{ disabled: props.faqs.current_page === props.faqs.last_page }">
                            <Link 
                                :href="route('admin.faqs.index', { page: props.faqs.current_page + 1 })"
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
    </AdminLayout>
</template>