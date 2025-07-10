<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { PlusIcon, XMarkIcon } from '@heroicons/vue/24/outline';

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
}

interface Props {
    team: Team;
    sports: Sport[];
    leagues: League[];
}

const props = defineProps<Props>();

// Debug props
console.log('Team Edit Props:', {
    team: props.team,
    sports: props.sports,
    leagues: props.leagues,
});

const form = useForm({
    name: props.team.name,
    sport_id: props.team.sport_id.toString(),
    league_id: props.team.league_id?.toString() || '',
    abbreviation: props.team.abbreviation || '',
    city: props.team.city || '',
    state: props.team.state || '',
    country: props.team.country || '',
    description: props.team.description || '',
    logo: null as File | null,
    is_active: props.team.is_active,
    aliases: props.team.aliases?.map(a => a.alias) || [],
});

const newAlias = ref('');

// Filter leagues based on selected sport
const filteredLeagues = computed(() => {
    if (!form.sport_id) return [];
    return props.leagues.filter(league => league.sport_id === parseInt(form.sport_id));
});

// Reset league when sport changes
watch(() => form.sport_id, (newSportId, oldSportId) => {
    // Only reset if the sport actually changed and the current league doesn't belong to the new sport
    if (newSportId !== oldSportId && form.league_id) {
        const currentLeague = props.leagues.find(l => l.id.toString() === form.league_id);
        if (currentLeague && currentLeague.sport_id !== parseInt(newSportId)) {
            form.league_id = '';
        }
    }
});

function handleLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.logo = target.files[0];
    }
}

function addAlias() {
    if (newAlias.value.trim()) {
        form.aliases.push(newAlias.value.trim());
        newAlias.value = '';
    }
}

function removeAlias(index: number) {
    form.aliases.splice(index, 1);
}

function submit() {
    form.post(route('admin.teams.update', props.team.id), {
        _method: 'put',
    });
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit Team: ${team.name}`" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h2 mb-0">Edit Team: {{ team.name }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.teams.index')">Teams</Link>
                        </li>
                        <li class="breadcrumb-item active">Edit</li>
                    </ol>
                </nav>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Team Information</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label required">Team Name</label>
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

                                    <div class="col-md-6">
                                        <label for="abbreviation" class="form-label">Abbreviation</label>
                                        <input
                                            v-model="form.abbreviation"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.abbreviation }"
                                            id="abbreviation"
                                            placeholder="e.g., LAL, NYY"
                                        />
                                        <div v-if="form.errors.abbreviation" class="invalid-feedback">
                                            {{ form.errors.abbreviation }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="sport_id" class="form-label required">Sport</label>
                                        <select
                                            v-model="form.sport_id"
                                            class="form-select"
                                            :class="{ 'is-invalid': form.errors.sport_id }"
                                            id="sport_id"
                                            required
                                        >
                                            <option value="">Select a sport</option>
                                            <option v-for="sport in sports" :key="sport.id" :value="sport.id.toString()">
                                                {{ sport.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.sport_id" class="invalid-feedback">
                                            {{ form.errors.sport_id }}
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label for="league_id" class="form-label">League</label>
                                        <select
                                            v-model="form.league_id"
                                            class="form-select"
                                            :class="{ 'is-invalid': form.errors.league_id }"
                                            id="league_id"
                                            :disabled="!form.sport_id"
                                        >
                                            <option value="">Select a league</option>
                                            <option v-for="league in filteredLeagues" :key="league.id" :value="league.id.toString()">
                                                {{ league.name }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.league_id" class="invalid-feedback">
                                            {{ form.errors.league_id }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Location</h5>
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City</label>
                                        <input
                                            v-model="form.city"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.city }"
                                            id="city"
                                        />
                                        <div v-if="form.errors.city" class="invalid-feedback">
                                            {{ form.errors.city }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="state" class="form-label">State/Province</label>
                                        <input
                                            v-model="form.state"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.state }"
                                            id="state"
                                        />
                                        <div v-if="form.errors.state" class="invalid-feedback">
                                            {{ form.errors.state }}
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label for="country" class="form-label">Country</label>
                                        <input
                                            v-model="form.country"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.country }"
                                            id="country"
                                        />
                                        <div v-if="form.errors.country" class="invalid-feedback">
                                            {{ form.errors.country }}
                                        </div>
                                    </div>

                                    <div class="col-12">
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
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Team Aliases</h5>
                                <p class="text-muted">Add alternative names for this team to improve matching during imports.</p>
                                
                                <div class="input-group mb-3">
                                    <input
                                        v-model="newAlias"
                                        @keydown.enter.prevent="addAlias"
                                        type="text"
                                        class="form-control"
                                        placeholder="Enter an alias (e.g., LA Lakers, L.A. Lakers)"
                                    />
                                    <button
                                        @click="addAlias"
                                        type="button"
                                        class="btn btn-outline-secondary"
                                    >
                                        <PlusIcon style="width: 1rem; height: 1rem;" />
                                        Add
                                    </button>
                                </div>

                                <div v-if="form.aliases.length > 0" class="d-flex flex-wrap gap-2">
                                    <span
                                        v-for="(alias, index) in form.aliases"
                                        :key="index"
                                        class="badge bg-secondary d-flex align-items-center gap-1"
                                    >
                                        {{ alias }}
                                        <button
                                            @click="removeAlias(index)"
                                            type="button"
                                            class="btn-close btn-close-white"
                                            style="font-size: 0.5rem;"
                                        ></button>
                                    </span>
                                </div>
                                <div v-else class="text-muted">
                                    No aliases added yet.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Team Logo</h5>
                                
                                <div v-if="team.logo_url" class="mb-3 text-center">
                                    <img 
                                        :src="`/storage/${team.logo_url}`"
                                        :alt="team.name"
                                        class="img-fluid"
                                        style="max-width: 150px;"
                                    />
                                </div>
                                
                                <div class="mb-3">
                                    <label for="logo" class="form-label">Upload New Logo</label>
                                    <input
                                        @change="handleLogoChange"
                                        type="file"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.logo }"
                                        id="logo"
                                        accept="image/*"
                                    />
                                    <div v-if="form.errors.logo" class="invalid-feedback">
                                        {{ form.errors.logo }}
                                    </div>
                                    <div class="form-text">
                                        Recommended: 200x200px PNG or JPG
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Status</h5>
                                
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
                                    Inactive teams won't be available for new bets.
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Team Information</h5>
                                <dl class="row mb-0">
                                    <dt class="col-sm-6">Slug</dt>
                                    <dd class="col-sm-6">{{ team.slug }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        Update Team
                    </button>
                    <Link
                        :href="route('admin.teams.index')"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </Link>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.required::after {
    content: ' *';
    color: #dc3545;
}
</style>