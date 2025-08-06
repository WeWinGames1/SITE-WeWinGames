<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    code: '',
    email: '',
    phone: '',
    commission_rate: 20,
    notes: '',
    is_active: true,
});

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();

    let isValid = true;
    const errors: Record<string, string> = {};

    // Required fields validation
    if (!form.name || !form.name.trim()) {
        errors.name = 'The name field is required.';
        isValid = false;
    } else if (form.name.length > 255) {
        errors.name = 'The name may not be greater than 255 characters.';
        isValid = false;
    }

    // Optional fields validation
    if (form.code && form.code.length > 255) {
        errors.code = 'The code may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(form.email)) {
            errors.email = 'The email must be a valid email address.';
            isValid = false;
        } else if (form.email.length > 255) {
            errors.email = 'The email may not be greater than 255 characters.';
            isValid = false;
        }
    }

    if (form.phone && form.phone.length > 255) {
        errors.phone = 'The phone may not be greater than 255 characters.';
        isValid = false;
    }

    // Commission rate validation
    if (form.commission_rate === null || form.commission_rate === undefined || form.commission_rate === '') {
        errors.commission_rate = 'The commission rate field is required.';
        isValid = false;
    } else if (form.commission_rate < 0 || form.commission_rate > 100) {
        errors.commission_rate = 'The commission rate must be between 0 and 100.';
        isValid = false;
    }

    // Set errors if any
    if (!isValid) {
        form.setError(errors);
    }

    return isValid;
}

function submit() {
    if (!validateForm()) {
        return;
    }

    form.post(route('admin.affiliates.store'));
}
</script>

<template>
    <AdminLayout>
        <Head title="Create Affiliate" />

        <div class="container-fluid">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="h3 mb-0">Create Affiliate</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.dashboard')">Admin</Link>
                        </li>
                        <li class="breadcrumb-item">
                            <Link :href="route('admin.affiliates.index')">Affiliates</Link>
                        </li>
                        <li class="breadcrumb-item active">Create</li>
                    </ol>
                </nav>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Affiliate Information</h5>

                                <div class="mb-3">
                                    <label for="name" class="form-label required">Name</label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.name }"
                                        id="name"
                                        required
                                        maxlength="255"
                                    />
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="code" class="form-label">
                                        Affiliate Code
                                        <small class="text-muted">(Leave blank to auto-generate)</small>
                                    </label>
                                    <input
                                        v-model="form.code"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.code }"
                                        id="code"
                                        maxlength="255"
                                        placeholder="e.g., PARTNER123"
                                    />
                                    <div v-if="form.errors.code" class="invalid-feedback">
                                        {{ form.errors.code }}
                                    </div>
                                    <div class="form-text">This code will be used in affiliate URLs (e.g., ?affiliate=CODE)</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input
                                                v-model="form.email"
                                                type="email"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.email }"
                                                id="email"
                                                maxlength="255"
                                            />
                                            <div v-if="form.errors.email" class="invalid-feedback">
                                                {{ form.errors.email }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="phone" class="form-label">Phone</label>
                                            <input
                                                v-model="form.phone"
                                                type="tel"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.phone }"
                                                id="phone"
                                                maxlength="255"
                                            />
                                            <div v-if="form.errors.phone" class="invalid-feedback">
                                                {{ form.errors.phone }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea
                                        v-model="form.notes"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.notes }"
                                        id="notes"
                                        rows="3"
                                    ></textarea>
                                    <div v-if="form.errors.notes" class="invalid-feedback">
                                        {{ form.errors.notes }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Commission Settings</h5>

                                <div class="mb-3">
                                    <label for="commission_rate" class="form-label required">Commission Rate (%)</label>
                                    <div class="input-group">
                                        <input
                                            v-model.number="form.commission_rate"
                                            type="number"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.commission_rate }"
                                            id="commission_rate"
                                            min="0"
                                            max="100"
                                            step="0.01"
                                            required
                                        />
                                        <span class="input-group-text">%</span>
                                        <div v-if="form.errors.commission_rate" class="invalid-feedback">
                                            {{ form.errors.commission_rate }}
                                        </div>
                                    </div>
                                    <div class="form-text">Percentage of revenue shared with this affiliate</div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Status</h5>

                                <div class="form-check">
                                    <input v-model="form.is_active" type="checkbox" class="form-check-input" id="is_active" />
                                    <label class="form-check-label" for="is_active"> Active </label>
                                </div>
                                <div class="form-text">Inactive affiliates cannot generate new referrals</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">Create Affiliate</button>
                    <Link :href="route('admin.affiliates.index')" class="btn btn-outline-secondary"> Cancel </Link>
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
