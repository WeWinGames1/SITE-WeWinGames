<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { PlusIcon, PencilIcon, TrashIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { debounce } from 'lodash';

interface Sport {
    id: number;
    name: string;
    slug: string;
    description?: string;
    is_active: boolean;
    leagues_count: number;
    teams_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    sports: {
        data: Sport[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    filters?: {
        search?: string;
        is_active?: string;
    };
}

const props = defineProps<Props>();

const filterForm = useForm({
    search: props.filters?.search || '',
    is_active: props.filters?.is_active || '',
});

const applyFilters = debounce(() => {
    filterForm.get(route('admin.sports.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

// Watch for search changes
watch(() => filterForm.search, () => {
    applyFilters();
});

// Watch for filter changes
watch(() => filterForm.is_active, () => {
    applyFilters();
});

// Generate pagination pages with ellipsis
const paginationPages = computed(() => {
    const current = props.sports.current_page;
    const last = props.sports.last_page;
    const delta = 2;
    const pages: (number | string)[] = [];
    
    // Always show first page
    pages.push(1);
    
    // Calculate range around current page
    const rangeStart = Math.max(2, current - delta);
    const rangeEnd = Math.min(last - 1, current + delta);
    
    // Add ellipsis if needed before range
    if (rangeStart > 2) {
        pages.push('...');
    }
    
    // Add pages in range
    for (let i = rangeStart; i <= rangeEnd; i++) {
        pages.push(i);
    }
    
    // Add ellipsis if needed after range
    if (rangeEnd < last - 1) {
        pages.push('...');
    }
    
    // Always show last page if there's more than one page
    if (last > 1) {
        pages.push(last);
    }
    
    return pages;
});

function deleteSport(sport: Sport) {
    if (confirm(`Are you sure you want to delete "${sport.name}"? This will also delete all associated leagues and teams.`)) {
        router.delete(route('admin.sports.destroy', sport.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Sports Management" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Sports Management</h1>
                    <p class="text-muted mb-0">
                        Manage sports categories for teams and betting
                    </p>
                </div>
                <Link
                    :href="route('admin.sports.create')"
                    class="btn btn-primary"
                >
                    <PlusIcon style="width: 1rem; height: 1rem;" class="me-1" />
                    Add Sport
                </Link>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <MagnifyingGlassIcon style="width: 1rem; height: 1rem;" />
                                </span>
                                <input
                                    v-model="filterForm.search"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search sports..."
                                />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select v-model="filterForm.is_active" class="form-select">
                                <option value="">All Status</option>
                                <option value="true">Active</option>
                                <option value="false">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sports Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Leagues</th>
                                <th>Teams</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 100px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="sport in sports.data" :key="sport.id">
                                <td>
                                    <div>
                                        <div class="fw-medium">{{ sport.name }}</div>
                                        <div v-if="sport.description" class="text-muted small">
                                            {{ sport.description }}
                                        </div>
                                    </div>
                                </td>
                                <td>{{ sport.leagues_count || 0 }}</td>
                                <td>{{ sport.teams_count || 0 }}</td>
                                <td>
                                    <span :class="['badge', sport.is_active ? 'bg-success' : 'bg-secondary']">
                                        {{ sport.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <Link
                                            :href="route('admin.sports.edit', sport.id)"
                                            class="btn btn-outline-primary"
                                            title="Edit"
                                        >
                                            <PencilIcon style="width: 1rem; height: 1rem;" />
                                        </Link>
                                        <button
                                            @click="deleteSport(sport)"
                                            class="btn btn-outline-danger"
                                            title="Delete"
                                            :disabled="sport.leagues_count > 0 || sport.teams_count > 0"
                                        >
                                            <TrashIcon style="width: 1rem; height: 1rem;" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="sports.data.length === 0" class="text-center py-5">
                    <h5 class="mt-3">No sports found</h5>
                    <p class="text-muted">
                        Get started by adding your first sport.
                    </p>
                    <Link
                        :href="route('admin.sports.create')"
                        class="btn btn-primary mt-3"
                    >
                        <PlusIcon style="width: 1rem; height: 1rem;" class="me-1" />
                        Add Sport
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="sports.last_page > 1" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">
                            Showing {{ sports.from }} to {{ sports.to }} of {{ sports.total }} results
                        </span>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: sports.current_page === 1 }">
                                    <Link
                                        :href="`?page=${sports.current_page - 1}`"
                                        preserve-scroll
                                        class="page-link"
                                    >
                                        Previous
                                    </Link>
                                </li>
                                <template v-for="(page, index) in paginationPages" :key="index">
                                    <li v-if="page === '...'" class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <li v-else class="page-item" :class="{ active: page === sports.current_page }">
                                        <Link
                                            :href="`?page=${page}`"
                                            preserve-scroll
                                            class="page-link"
                                        >
                                            {{ page }}
                                        </Link>
                                    </li>
                                </template>
                                <li class="page-item" :class="{ disabled: sports.current_page === sports.last_page }">
                                    <Link
                                        :href="`?page=${sports.current_page + 1}`"
                                        preserve-scroll
                                        class="page-link"
                                    >
                                        Next
                                    </Link>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>