<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Sport {
    id: number;
    name: string;
}

interface League {
    id: number;
    name: string;
    slug: string;
    sport_id: number;
    abbreviation?: string;
    description?: string;
    is_active: boolean;
    teams_count: number;
}

interface Props {
    league: League;
    sports: Sport[];
}

const props = defineProps<Props>();

const form = useForm({
    name: props.league.name,
    sport_id: props.league.sport_id,
    abbreviation: props.league.abbreviation || '',
    description: props.league.description || '',
    is_active: props.league.is_active,
});

function submit() {
    form.put(route('admin.leagues.update', props.league.id));
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit League: ${league.name}`" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Edit League</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.leagues.index')">Leagues</Link>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
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
                                        required
                                    />
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="sport_id" class="form-label required">Sport</label>
                                    <select
                                        v-model="form.sport_id"
                                        class="form-select"
                                        :class="{ 'is-invalid': form.errors.sport_id }"
                                        id="sport_id"
                                        required
                                    >
                                        <option value="">Select a sport</option>
                                        <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                            {{ sport.name }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.sport_id" class="invalid-feedback">
                                        {{ form.errors.sport_id }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="abbreviation" class="form-label">Abbreviation</label>
                                    <input
                                        v-model="form.abbreviation"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.abbreviation }"
                                        id="abbreviation"
                                        placeholder="e.g., NBA, NFL, MLB"
                                    />
                                    <div v-if="form.errors.abbreviation" class="invalid-feedback">
                                        {{ form.errors.abbreviation }}
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
                                        Inactive leagues won't be available for new teams.
                                    </div>
                                </div>

                                <div v-if="league.teams_count > 0" class="alert alert-info">
                                    <strong>Note:</strong> This league has {{ league.teams_count }} associated team(s).
                                </div>

                                <div class="d-flex gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        Update League
                                    </button>
                                    <Link
                                        :href="route('admin.leagues.index')"
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