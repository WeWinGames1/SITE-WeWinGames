<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Sport {
    id: number;
    name: string;
}

interface Props {
    sports: Sport[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    sport_id: '',
    abbreviation: '',
    description: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.leagues.store'));
}
</script>

<template>
    <AdminLayout>
        <Head title="Create League" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Create League</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.leagues.index')">Leagues</Link>
                        </li>
                        <li class="breadcrumb-item active">Create</li>
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

                                <div class="d-flex gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        Create League
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