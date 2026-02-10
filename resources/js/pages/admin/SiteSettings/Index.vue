<template>
    <AdminLayout>
        <Head title="Site Settings" />

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Site Settings</h1>
                    <p class="text-muted mb-0">Manage global site configuration options</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Group settings by category -->
                <div v-for="(groupSettings, groupName) in groupedSettings" :key="groupName" class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0 text-capitalize">{{ formatGroupName(groupName) }}</h5>
                    </div>
                    <div class="card-body">
                        <div v-for="setting in groupSettings" :key="setting.key" class="mb-4">
                            <!-- Boolean toggle -->
                            <div v-if="setting.type === 'boolean'" class="form-check form-switch">
                                <input
                                    :id="setting.key"
                                    v-model="form.settings[setting.key]"
                                    type="checkbox"
                                    class="form-check-input"
                                    role="switch"
                                />
                                <label :for="setting.key" class="form-check-label fw-medium">
                                    {{ setting.label }}
                                </label>
                                <div v-if="setting.description" class="form-text">
                                    {{ setting.description }}
                                </div>
                            </div>

                            <!-- String input -->
                            <div v-else-if="setting.type === 'string'">
                                <label :for="setting.key" class="form-label fw-medium">{{ setting.label }}</label>
                                <input :id="setting.key" v-model="form.settings[setting.key]" type="text" class="form-control" />
                                <div v-if="setting.description" class="form-text">
                                    {{ setting.description }}
                                </div>
                            </div>

                            <!-- Integer input -->
                            <div v-else-if="setting.type === 'integer'">
                                <label :for="setting.key" class="form-label fw-medium">{{ setting.label }}</label>
                                <input :id="setting.key" v-model.number="form.settings[setting.key]" type="number" class="form-control" />
                                <div v-if="setting.description" class="form-text">
                                    {{ setting.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="!form.processing">
                            <i class="bi bi-check-lg me-2"></i>
                            Save Settings
                        </span>
                        <span v-else>
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Saving...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface Setting {
    id: number;
    key: string;
    value: string | boolean | number;
    type: string;
    group: string;
    label: string;
    description: string | null;
}

interface Props {
    settings: Setting[];
    groupedSettings: Record<string, Setting[]>;
}

const props = defineProps<Props>();

// Build initial form values from settings
const initialSettings: Record<string, string | boolean | number> = {};
props.settings.forEach((setting) => {
    if (setting.type === 'boolean') {
        initialSettings[setting.key] = Boolean(setting.value === '1' || setting.value === true || setting.value === 1);
    } else if (setting.type === 'integer') {
        initialSettings[setting.key] = Number(setting.value);
    } else {
        initialSettings[setting.key] = setting.value;
    }
});

const form = useForm({
    settings: initialSettings,
});

const formatGroupName = (name: string): string => {
    return name.replace(/_/g, ' ').replace(/-/g, ' ');
};

const submit = () => {
    // Transform settings object to array format for the controller
    const settingsArray = Object.entries(form.settings).map(([key, value]) => ({
        key,
        value,
    }));

    form.transform(() => ({
        settings: settingsArray,
    })).post(route('admin.site-settings.update'), {
        preserveScroll: true,
    });
};
</script>
