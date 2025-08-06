<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { MagnifyingGlassIcon, PencilIcon, PhotoIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';

interface Sport {
    id: number;
    name: string;
}

interface League {
    id: number;
    name: string;
    sport_id: number;
}

interface TeamAlias {
    id: number;
    alias: string;
}

interface Team {
    id: number;
    name: string;
    slug: string;
    sport?: Sport;
    league?: League;
    sport_id: number;
    league_id?: number;
    abbreviation?: string;
    city?: string;
    state?: string;
    country?: string;
    logo_url?: string;
    description?: string;
    is_active: boolean;
    aliases?: TeamAlias[];
    bets_as_team_one_count: number;
    bets_as_team_two_count: number;
}

interface Props {
    teams: {
        data: Team[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        from: number;
        to: number;
    };
    sports: Sport[];
    leagues: League[];
    filters: {
        sport_id?: string;
        league_id?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const filterForm = useForm({
    sport_id: props.filters.sport_id || '',
    league_id: props.filters.league_id || '',
    search: props.filters.search || '',
});

const showFilters = ref(false);

// Filter leagues based on selected sport
const filteredLeagues = computed(() => {
    if (!filterForm.sport_id) return props.leagues;
    return props.leagues.filter((league) => league.sport_id === parseInt(filterForm.sport_id));
});

// Apply filters
const applyFilters = debounce(() => {
    router.get(route('admin.teams.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 300);

// Clear filters
function clearFilters() {
    filterForm.reset();
    applyFilters();
}

// Watch for search changes
watch(
    () => filterForm.search,
    () => {
        applyFilters();
    },
);

// Watch for filter changes
watch(
    () => [filterForm.sport_id, filterForm.league_id],
    () => {
        applyFilters();
    },
);

// Generate pagination pages with ellipsis
const paginationPages = computed(() => {
    const current = props.teams.current_page;
    const last = props.teams.last_page;
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

function deleteTeam(team: Team) {
    if (confirm(`Are you sure you want to delete "${team.name}"? This action cannot be undone.`)) {
        router.delete(route('admin.teams.destroy', team.id), {
            preserveScroll: true,
        });
    }
}

function getTotalBets(team: Team): number {
    return team.bets_as_team_one_count + team.bets_as_team_two_count;
}
</script>

<template>
    <AdminLayout>
        <Head title="Teams Management" />

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Teams Management</h1>
                    <p class="text-muted mb-0">Manage teams across all sports and leagues</p>
                </div>
                <Link :href="route('admin.teams.create')" class="btn btn-primary">
                    <PlusIcon style="width: 1rem; height: 1rem" class="me-1" />
                    Add Team
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
                                <input v-model="filterForm.search" type="search" class="form-control" placeholder="Search teams or aliases..." />
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select v-model="filterForm.sport_id" class="form-select">
                                <option value="">All Sports</option>
                                <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                    {{ sport.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select v-model="filterForm.league_id" class="form-select">
                                <option value="">All Leagues</option>
                                <option v-for="league in filteredLeagues" :key="league.id" :value="league.id">
                                    {{ league.name }}
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button
                                v-if="filterForm.sport_id || filterForm.league_id || filterForm.search"
                                @click="clearFilters"
                                type="button"
                                class="btn btn-outline-secondary w-100"
                            >
                                Clear Filters
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Teams Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px">Logo</th>
                                <th>Team</th>
                                <th>Sport</th>
                                <th>League</th>
                                <th>Location</th>
                                <th>Aliases</th>
                                <th>Bets</th>
                                <th>Status</th>
                                <th class="text-center" style="width: 100px">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="team in teams.data" :key="team.id">
                                <td>
                                    <div class="d-flex align-items-center justify-content-center" style="width: 40px; height: 40px">
                                        <img
                                            v-if="team.logo_url"
                                            :src="`/storage/${team.logo_url}`"
                                            :alt="team.name"
                                            class="img-fluid"
                                            style="max-width: 40px; max-height: 40px"
                                        />
                                        <PhotoIcon v-else class="text-muted" style="width: 1.5rem; height: 1.5rem" />
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="fw-medium">{{ team.name }}</div>
                                        <div v-if="team.abbreviation" class="text-muted small">
                                            {{ team.abbreviation }}
                                        </div>
                                    </div>
                                </td>
                                <td>{{ team.sport?.name || 'N/A' }}</td>
                                <td>{{ team.league?.name || 'N/A' }}</td>
                                <td>
                                    <div v-if="team.city || team.state || team.country" class="small">
                                        {{ [team.city, team.state, team.country].filter(Boolean).join(', ') }}
                                    </div>
                                    <div v-else class="text-muted">-</div>
                                </td>
                                <td>
                                    <div v-if="team.aliases && team.aliases.length > 0">
                                        <span v-for="(alias, index) in team.aliases.slice(0, 2)" :key="alias.id" class="badge bg-secondary me-1">
                                            {{ alias.alias }}
                                        </span>
                                        <span v-if="team.aliases.length > 2" class="text-muted small"> +{{ team.aliases.length - 2 }} </span>
                                    </div>
                                    <div v-else class="text-muted">-</div>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ getTotalBets(team) }}
                                    </span>
                                </td>
                                <td>
                                    <span :class="['badge', team.is_active ? 'bg-success' : 'bg-secondary']">
                                        {{ team.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <Link :href="route('admin.teams.edit', team.id)" class="btn btn-outline-primary" title="Edit">
                                            <PencilIcon style="width: 1rem; height: 1rem" />
                                        </Link>
                                        <button
                                            @click="deleteTeam(team)"
                                            class="btn btn-outline-danger"
                                            title="Delete"
                                            :disabled="getTotalBets(team) > 0"
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
                <div v-if="teams.data.length === 0" class="text-center py-5">
                    <h5 class="mt-3">No teams found</h5>
                    <p class="text-muted">Try adjusting your filters or add a new team.</p>
                    <Link :href="route('admin.teams.create')" class="btn btn-primary mt-3">
                        <PlusIcon style="width: 1rem; height: 1rem" class="me-1" />
                        Add Team
                    </Link>
                </div>

                <!-- Pagination -->
                <div v-if="teams.last_page > 1" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"> Showing {{ teams.from }} to {{ teams.to }} of {{ teams.total }} teams </span>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li class="page-item" :class="{ disabled: teams.current_page === 1 }">
                                    <Link :href="`?page=${teams.current_page - 1}`" preserve-scroll class="page-link"> Previous </Link>
                                </li>
                                <template v-for="(page, index) in paginationPages" :key="index">
                                    <li v-if="page === '...'" class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <li v-else class="page-item" :class="{ active: page === teams.current_page }">
                                        <Link :href="`?page=${page}`" preserve-scroll class="page-link">
                                            {{ page }}
                                        </Link>
                                    </li>
                                </template>
                                <li class="page-item" :class="{ disabled: teams.current_page === teams.last_page }">
                                    <Link :href="`?page=${teams.current_page + 1}`" preserve-scroll class="page-link"> Next </Link>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
