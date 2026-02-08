<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const loginUrl = computed(() => route('login'));
const registerUrl = computed(() => route('register'));

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Forgot password" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Forgot Password?</h1>
                            <p class="fs-5 text-gray-light">No worries, we'll send you reset instructions</p>
                        </div>

                        <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057">
                            <div class="card-body p-5">
                                <h2 class="h4 fw-bold text-white mb-4">Reset your password</h2>

                                <div v-if="status" class="alert alert-success mb-4">
                                    {{ status }}
                                </div>

                                <form @submit.prevent="submit">
                                    <div class="mb-4">
                                        <label for="email" class="form-label text-white fw-medium">Email Address</label>
                                        <input
                                            id="email"
                                            type="email"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.email }"
                                            required
                                            autofocus
                                            autocomplete="email"
                                            v-model="form.email"
                                            placeholder="you@example.com"
                                        />
                                        <div v-if="form.errors.email" class="invalid-feedback">
                                            {{ form.errors.email }}
                                        </div>
                                        <p class="text-gray-light small mt-2">
                                            Enter the email associated with your account and we'll send you a password reset link.
                                        </p>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-warning btn-lg py-3 text-dark fw-bold" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                            <span class="fw-semibold">Send Reset Link</span>
                                            <i class="bi bi-envelope ms-2"></i>
                                        </button>
                                    </div>

                                    <div class="position-relative">
                                        <hr class="text-gray-medium" />
                                        <span
                                            class="position-absolute top-50 start-50 translate-middle bg-dark px-3 text-gray-light small"
                                            style="background-color: #1a2332"
                                            >OR</span
                                        >
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-gray-light mb-2">Remember your password?</p>
                                    <a :href="loginUrl" class="btn btn-outline-warning btn-lg w-100">
                                        <i class="bi bi-arrow-left me-2"></i>
                                        Back to Login
                                    </a>
                                </div>

                                <div class="text-center mt-3">
                                    <p class="text-gray-light small mb-0">
                                        Don't have an account?
                                        <a :href="registerUrl" class="text-warning text-decoration-none">Create one</a>
                                    </p>
                                </div>
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

.btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000 !important;
}
</style>
