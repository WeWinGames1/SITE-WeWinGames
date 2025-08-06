<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const logout = () => {
    form.post(route('logout'));
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Email Verification" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-6">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Verify Your Email</h1>
                            <p class="fs-5 text-gray-light">Almost there! Please check your inbox</p>
                        </div>

                        <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057">
                            <div class="card-body p-5">
                                <div class="text-center">
                                    <div class="mb-4">
                                        <i class="bi bi-envelope-check fs-1 text-warning"></i>
                                    </div>

                                    <h2 class="h4 fw-bold text-white mb-3">Check Your Email</h2>

                                    <p class="text-gray-light mb-4">
                                        Thanks for signing up! Before getting started, please verify your email address by clicking on the link we
                                        just emailed to you.
                                    </p>

                                    <p class="text-gray-light mb-4">If you didn't receive the email, we will gladly send you another.</p>

                                    <div v-if="status === 'verification-link-sent'" class="alert alert-success mb-4">
                                        <i class="bi bi-check-circle me-2"></i>
                                        A new verification link has been sent to your email address.
                                    </div>

                                    <form @submit.prevent="submit" class="mb-4">
                                        <button type="submit" class="btn btn-warning btn-lg w-100 py-3 text-dark fw-bold" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                            <i v-else class="bi bi-envelope me-2"></i>
                                            <span>Resend Verification Email</span>
                                        </button>
                                    </form>

                                    <div class="position-relative my-4">
                                        <hr class="text-gray-medium" />
                                        <span
                                            class="position-absolute top-50 start-50 translate-middle bg-dark px-3 text-gray-light small"
                                            style="background-color: #1a2332"
                                            >OR</span
                                        >
                                    </div>

                                    <button @click="logout" type="button" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-box-arrow-right me-2"></i>
                                        Log Out
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Help Section -->
                        <div class="text-center mt-4">
                            <p class="text-gray-light small">
                                <i class="bi bi-question-circle me-1"></i>
                                Having trouble? Please check your spam folder or
                                <Link href="/support" class="text-warning text-decoration-none">contact support</Link>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.btn-outline-secondary {
    color: #6c757d;
    border-color: #2e4057;
}

.btn-outline-secondary:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: #6c757d;
    color: #adb5bd;
}

.alert-success {
    background-color: rgba(25, 135, 84, 0.1);
    border-color: #198754;
    color: #75b798;
}
</style>
