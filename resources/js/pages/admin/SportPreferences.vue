<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface SportPreference {
    id?: number;
    sport_name: string;
    priority: number;
    is_active: boolean;
}

const props = defineProps<{
    preferences: SportPreference[];
    availableSports: string[];
}>();

const preferences = ref<SportPreference[]>([...props.preferences]);
const newSport = ref('');
const showAddForm = ref(false);

// Get sports that haven't been added yet
const unusedSports = computed(() => {
    const usedSports = preferences.value.map((p) => p.sport_name);
    return props.availableSports.filter((sport) => !usedSports.includes(sport));
});

// Form for updating all preferences
const updateForm = useForm({
    preferences: preferences.value,
});

// Move sport up in the list (lower priority number = higher in list)
const moveUp = (index: number) => {
    if (index === 0) return;

    const temp = preferences.value[index];
    preferences.value[index] = preferences.value[index - 1];
    preferences.value[index - 1] = temp;

    updatePriorities();
};

// Move sport down in the list
const moveDown = (index: number) => {
    if (index === preferences.value.length - 1) return;

    const temp = preferences.value[index];
    preferences.value[index] = preferences.value[index + 1];
    preferences.value[index + 1] = temp;

    updatePriorities();
};

// Update priorities after reordering
const updatePriorities = () => {
    preferences.value.forEach((pref, index) => {
        pref.priority = index;
    });
    updateForm.preferences = preferences.value;
};

// Add new sport preference
const addSport = () => {
    if (!newSport.value || newSport.value.trim() === '') {
        // Could add error display here if needed
        return;
    }

    // Check if sport already exists
    const sportExists = preferences.value.some((p) => p.sport_name === newSport.value);
    if (sportExists) {
        // Sport already in preferences
        return;
    }

    preferences.value.push({
        sport_name: newSport.value,
        priority: preferences.value.length,
        is_active: true,
    });

    updateForm.preferences = preferences.value;
    newSport.value = '';
    showAddForm.value = false;
};

// Remove sport preference
const removeSport = (index: number) => {
    preferences.value.splice(index, 1);
    updatePriorities();
};

// Toggle active status
const toggleActive = (index: number) => {
    preferences.value[index].is_active = !preferences.value[index].is_active;
    updateForm.preferences = preferences.value;
};

// Validate form before submission
const validateForm = (): boolean => {
    // Clear previous errors
    updateForm.clearErrors();

    let isValid = true;
    const errors: Record<string, string> = {};

    // Validate preferences array
    if (!updateForm.preferences || updateForm.preferences.length === 0) {
        errors['preferences'] = 'At least one sport preference is required.';
        isValid = false;
    } else {
        // Validate each preference
        updateForm.preferences.forEach((preference, index) => {
            // Validate sport_name
            if (!preference.sport_name || preference.sport_name.trim() === '') {
                errors[`preferences.${index}.sport_name`] = 'Sport name is required.';
                isValid = false;
            }

            // Validate priority
            if (preference.priority === undefined || preference.priority === null) {
                errors[`preferences.${index}.priority`] = 'Priority is required.';
                isValid = false;
            } else if (preference.priority < 0) {
                errors[`preferences.${index}.priority`] = 'Priority must be 0 or greater.';
                isValid = false;
            }

            // Validate is_active
            if (preference.is_active === undefined || preference.is_active === null) {
                errors[`preferences.${index}.is_active`] = 'Active status is required.';
                isValid = false;
            }
        });
    }

    // Set errors if any
    if (!isValid) {
        updateForm.setError(errors);
    }

    return isValid;
};

// Save all preferences
const savePreferences = () => {
    if (!validateForm()) {
        return;
    }

    updateForm.put(route('admin.sport-preferences.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Update local state with response
            preferences.value = [...props.preferences];
            updateForm.preferences = preferences.value;
        },
    });
};
</script>

<template>
    <AdminLayout title="Sport Preferences">
        <div class="container-fluid px-4">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Sport Preferences</h5>
                            <button v-if="!showAddForm && unusedSports.length > 0" @click="showAddForm = true" class="btn btn-sm btn-light">
                                <i class="bi bi-plus-circle me-1"></i> Add Sport
                            </button>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-4">
                                Use the arrow buttons to reorder sports. The order determines which sports' picks are shown first in the ticker and on
                                the public pages.
                            </p>

                            <!-- Add new sport form -->
                            <div v-if="showAddForm" class="mb-4 p-3 bg-light rounded">
                                <h6 class="mb-3">Add New Sport</h6>
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <select v-model="newSport" class="form-select" placeholder="Select a sport">
                                            <option value="">Select a sport...</option>
                                            <option v-for="sport in unusedSports" :key="sport" :value="sport">
                                                {{ sport }}
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button @click="addSport" :disabled="!newSport" class="btn btn-primary me-2">
                                            <i class="bi bi-plus me-1"></i> Add
                                        </button>
                                        <button @click="showAddForm = false" class="btn btn-secondary">Cancel</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Sport preferences list -->
                            <div v-if="preferences.length > 0">
                                <div class="list-group">
                                    <div
                                        v-for="(preference, index) in preferences"
                                        :key="preference.sport_name"
                                        class="list-group-item d-flex justify-content-between align-items-center"
                                    >
                                        <div class="d-flex align-items-center">
                                            <div class="btn-group btn-group-sm me-3">
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
                                                    :disabled="index === preferences.length - 1"
                                                    class="btn btn-outline-secondary"
                                                    title="Move down"
                                                >
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                            </div>
                                            <span class="badge bg-secondary me-3">{{ index + 1 }}</span>
                                            <strong>{{ preference.sport_name }}</strong>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="form-check form-switch">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    :id="`active-${index}`"
                                                    :checked="preference.is_active"
                                                    @change="toggleActive(index)"
                                                />
                                                <label class="form-check-label" :for="`active-${index}`">
                                                    {{ preference.is_active ? 'Active' : 'Inactive' }}
                                                </label>
                                            </div>
                                            <button @click="removeSport(index)" class="btn btn-sm btn-danger" title="Remove sport">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <!-- Display general errors -->
                                    <div v-if="updateForm.errors && Object.keys(updateForm.errors).length > 0" class="alert alert-danger mb-3">
                                        <h6 class="alert-heading">Please fix the following errors:</h6>
                                        <ul class="mb-0">
                                            <li v-for="(error, key) in updateForm.errors" :key="key">
                                                {{ error }}
                                            </li>
                                        </ul>
                                    </div>

                                    <button @click="savePreferences" :disabled="updateForm.processing" class="btn btn-primary">
                                        <i class="bi bi-save me-1"></i>
                                        {{ updateForm.processing ? 'Saving...' : 'Save Preferences' }}
                                    </button>
                                </div>
                            </div>
                            <div v-else class="text-center py-5 text-muted">
                                <i class="bi bi-list-ul display-4 mb-3"></i>
                                <p>No sport preferences configured yet.</p>
                                <button @click="showAddForm = true" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i> Add First Sport
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Info box -->
                    <div class="card mt-4 border-info">
                        <div class="card-body">
                            <h6 class="card-title text-info"><i class="bi bi-info-circle me-1"></i> How Sport Preferences Work</h6>
                            <ul class="mb-0">
                                <li>The order of sports determines which picks are shown first in the ticker and on public pages</li>
                                <li>Only active sports will be considered for the ticker</li>
                                <li>The ticker shows the last 10 picks, prioritizing preferred sports</li>
                                <li>On public pages, a maximum of 2 bronze picks from preferred sports will be shown to non-subscribers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
