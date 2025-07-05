<template>
    <AdminLayout>
        <Head title="Under Construction Settings" />

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Under Construction Settings</h1>
                    <p class="text-muted mb-0">Manage site maintenance mode and visitor messages</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input
                                            v-model="form.is_enabled"
                                            type="checkbox"
                                            class="form-check-input"
                                            id="is_enabled"
                                        />
                                        <label class="form-check-label" for="is_enabled">
                                            Enable Under Construction Mode
                                        </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input
                                                v-model="form.start_date"
                                                type="datetime-local"
                                                class="form-control"
                                                id="start_date"
                                                :class="{ 'is-invalid': form.errors.start_date }"
                                            />
                                            <div v-if="form.errors.start_date" class="invalid-feedback">
                                                {{ form.errors.start_date }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input
                                                v-model="form.end_date"
                                                type="datetime-local"
                                                class="form-control"
                                                id="end_date"
                                                :class="{ 'is-invalid': form.errors.end_date }"
                                            />
                                            <div v-if="form.errors.end_date" class="invalid-feedback">
                                                {{ form.errors.end_date }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="blurb" class="form-label">Message</label>
                                    <textarea
                                        v-model="form.blurb"
                                        class="form-control"
                                        id="blurb"
                                        rows="3"
                                        placeholder="Enter the message to display on the under construction page..."
                                        :class="{ 'is-invalid': form.errors.blurb }"
                                    ></textarea>
                                    <div v-if="form.errors.blurb" class="invalid-feedback">
                                        {{ form.errors.blurb }}
                                    </div>
                                    <div class="form-text">
                                        You can use HTML tags to format your message.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="!form.processing">Save Settings</span>
                                        <span v-else>
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Saving...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">How it works</h5>
                            <p class="card-text">
                                When enabled, visitors to your site will see an under construction page instead of the regular content.
                            </p>
                            <ul>
                                <li>
                                    <strong>Enable/Disable:</strong> Toggle to turn the under construction mode on or off immediately.
                                </li>
                                <li>
                                    <strong>Start Date:</strong> Optional. If set, the under construction mode will automatically activate at this date/time.
                                </li>
                                <li>
                                    <strong>End Date:</strong> Optional. If set, the under construction mode will automatically deactivate at this date/time.
                                </li>
                                <li>
                                    <strong>Message:</strong> The message displayed to visitors. You can use HTML for formatting.
                                </li>
                            </ul>
                            <p class="card-text">
                                <strong>Note:</strong> Admin users will always be able to access the site, even when under construction mode is active.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';

interface UnderConstructionSetting {
    id?: number;
    is_enabled: boolean;
    start_date: string | null;
    end_date: string | null;
    blurb: string | null;
}

interface Props {
    settings?: UnderConstructionSetting;
}

const props = defineProps<Props>();

const formatDateForInput = (dateString: string | null): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    is_enabled: props.settings?.is_enabled || false,
    start_date: formatDateForInput(props.settings?.start_date || null),
    end_date: formatDateForInput(props.settings?.end_date || null),
    blurb: props.settings?.blurb || '<p>We are currently updating our website. Please check back soon!</p>',
});

const submit = () => {
    form.post(route('admin.under-construction.update'), {
        preserveScroll: true,
    });
};
</script>

<style scoped>
/* Component styles are handled by Bootstrap */
</style>