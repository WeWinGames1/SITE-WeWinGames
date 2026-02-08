<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface Props {
    token: string;
    email: string;
}

const props = defineProps<Props>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Reset password" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Reset Your Password</h1>
                            <p class="fs-5 text-gray-light">Enter your new password below</p>
                        </div>

                        <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057">
                            <div class="card-body p-5">
                                <h2 class="h4 fw-bold text-white mb-4">Create new password</h2>

                                <form @submit.prevent="submit">
                                    <div class="mb-4">
                                        <label for="email" class="form-label text-white fw-medium">Email Address</label>
                                        <input
                                            id="email"
                                            type="email"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.email }"
                                            v-model="form.email"
                                            readonly
                                            style="background-color: rgba(255, 255, 255, 0.02); cursor: not-allowed"
                                        />
                                        <div v-if="form.errors.email" class="invalid-feedback">
                                            {{ form.errors.email }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label text-white fw-medium">New Password</label>
                                        <input
                                            id="password"
                                            type="password"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.password }"
                                            required
                                            autofocus
                                            autocomplete="new-password"
                                            v-model="form.password"
                                            placeholder="Enter new password"
                                        />
                                        <div v-if="form.errors.password" class="invalid-feedback">
                                            {{ form.errors.password }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label text-white fw-medium">Confirm Password</label>
                                        <input
                                            id="password_confirmation"
                                            type="password"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.password_confirmation }"
                                            required
                                            autocomplete="new-password"
                                            v-model="form.password_confirmation"
                                            placeholder="Confirm new password"
                                        />
                                        <div v-if="form.errors.password_confirmation" class="invalid-feedback">
                                            {{ form.errors.password_confirmation }}
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning btn-lg py-3 text-dark fw-bold" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                            <span class="fw-semibold">Reset Password</span>
                                            <i class="bi bi-check-lg ms-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Security Note -->
                    <div class="text-center mt-5">
                        <p class="text-gray-light small mb-0">
                            <i class="bi bi-shield-lock me-2"></i>
                            Your data is encrypted and secure
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
/* Custom styles for form controls */
.form-control {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: #2e4057;
    color: white;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: #ffc107;
    color: white;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.form-control[readonly] {
    background-color: rgba(255, 255, 255, 0.02);
    border-color: #2e4057;
    color: rgba(255, 255, 255, 0.7);
}
</style>
