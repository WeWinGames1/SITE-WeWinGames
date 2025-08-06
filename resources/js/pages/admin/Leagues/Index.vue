<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { MagnifyingGlassIcon, PencilIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, watch } from 'vue';

interface Sport {
    id: number;
    name: string;
}

interface League {
    id: number;
    name: string;
    slug: string;
    sport?: Sport;
    sport_id: number;
    abbreviation?: string;
    description?: string;
    is_active: boolean;
    teams_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    leagues: {
        data: League[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    sports?: Sport[];
    filters?: {
        search?: string;
        sport_id?: string;
        is_active?: string;
    };
}

const props = defineProps<Props>();

const filterForm = useForm({
    search: props.filters?.search || '',
    sport_id: props.filters?.sport_id || '',
    is_active: props.filters?.is_active || '',
});

const applyFilters = debounce(() => {
    filterForm.get(route('admin.leagues.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

// Watch for search changes
watch(
    () => filterForm.search,
    () => {
        applyFilters();
    },
);

// Watch for filter changes
watch(
    () => [filterForm.sport_id, filterForm.is_active],
    () => {
        applyFilters();
    },
);

// Generate pagination pages with ellipsis
const paginationPages = computed(() => {
    const current = props.leagues.current_page;
    const last = props.leagues.last_page;
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

function deleteLeague(league: League) {
    if (confirm(`Are you sure you want to delete "${league.name}"? This will also delete all associated teams.`)) {
        router.delete(route('admin.leagues.destroy', league.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Leagues Management" />

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Leagues Management</h1>
                    <p class="text-muted mb-0">Manage leagues within each sport</p>
                </div>
                <Link :href="route('admin.leagues.create')" class="btn btn-primary">
                    <PlusIcon style="width: 1rem; height: 1rem" class="me-1" />
                    Add League
                </Link>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <MagnifyingGlassIcon style="width: 1rem; height: 1rem" />
                                </span>
                                <input v-model="filterForm.search" type="text" class="form-control" placeholder="Search leagues..." />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select v-model="filterForm.sport_id" class="form-select">
                                <option value="">All Sports</option>
                                <option v-for="sport in sports" :key="sport.id" :value="sport.id.toString()">
                                    {{ sport.name }}
                                </option>
                            </select>
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

            <!-- Leagues Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Sport</th>
                                <th>Abbreviation</th>
                                <th>Teams</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="league in leagues.data" :key="league.id">
                                <td>
                                    <div>
                                        <div class="fw-medium">{{ league.name }}</div>
                                        <div v-if="league.description" class="text-muted small">
                                            {{ league.description }}
                                        </div>
                                    </div>
                                </td>
                                <td>{{ league.sport?.name || 'N/A' }}</td>
                                <td>{{ league.abbreviation || '-' }}</td>
                                <td>{{ league.teams_count || 0 }}</td>
                                <td>
                                    <span :class="['badge', league.is_active ? 'bg-success' : 'bg-secondary']">
                                        {{ league.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <Link :href="route('admin.leagues.edit', league.id)" class="btn btn-outline-primary" title="Edit">
                                            <PencilIcon style="width: 1rem; height: 1rem" />
                                        </Link>
                                        <button
                                            @click="deleteLeague(league)"
                                            class="btn btn-outline-danger"
                                            title="Delete"
                                            :disabled="league.teams_count > 0"
                                        >
                                            <TrashIcon style="width: 1rem; height: 1rem" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="leagues.data.length === 0" class="text-center py-5">
                    <h5 class="mt-3">No leagues found</h5>
                    <p class="text-muted">Get started by adding your first league.</p>
                    <Link :href="route('admin.leagues.create')" class="btn btn-primary mt-3">
                        <PlusIcon style="width: 1rem; height: 1rem" class="me-1" />
                        Add League
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="leagues.last_page > 1" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"> Showing {{ leagues.from }} to {{ leagues.to }} of {{ leagues.total }} results </span>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: leagues.current_page === 1 }">
                                    <Link :href="`?page=${leagues.current_page - 1}`" preserve-scroll class="page-link"> Previous </Link>
                                </li>
                                <template v-for="(page, index) in paginationPages" :key="index">
                                    <li v-if="page === '...'" class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <li v-else class="page-item" :class="{ active: page === leagues.current_page }">
                                        <Link :href="`?page=${page}`" preserve-scroll class="page-link">
                                            {{ page }}
                                        </Link>
                                    </li>
                                </template>
                                <li class="page-item" :class="{ disabled: leagues.current_page === leagues.last_page }">
                                    <Link :href="`?page=${leagues.current_page + 1}`" preserve-scroll class="page-link"> Next </Link>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
