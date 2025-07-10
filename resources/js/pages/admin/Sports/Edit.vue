<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Sport {
    id: number;
    name: string;
    slug: string;
    description?: string;
    is_active: boolean;
    leagues_count: number;
    teams_count: number;
}

interface Props {
    sport: Sport;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.sport.name,
    description: props.sport.description || '',
    is_active: props.sport.is_active,
});

function submit() {
    form.put(route('admin.sports.update', props.sport.id));
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit Sport: ${sport.name}`" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Edit Sport: {{ sport.name }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.sports.index')">Sports</Link>
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
                                        Update Sport
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

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Sport Information</h5>
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Leagues</dt>
                                <dd class="col-sm-6">{{ sport.leagues_count || 0 }}</dd>
                                
                                <dt class="col-sm-6">Teams</dt>
                                <dd class="col-sm-6">{{ sport.teams_count || 0 }}</dd>
                                
                                <dt class="col-sm-6">Slug</dt>
                                <dd class="col-sm-6">{{ sport.slug }}</dd>
                            </dl>
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