<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    status?: string;
    email: string;
    bypassEnabled: boolean;
}>();

const showChangeEmail = ref(false);
const showBypassCode = ref(false);

const resendForm = useForm({});

const codeForm = useForm({
    code: '',
});

const emailForm = useForm({
    email: '',
    password: '',
});

const submitResend = () => {
    resendForm.post(route('verification.send'));
};

const submitCode = () => {
    codeForm.post(route('verification.code'), {
        onSuccess: () => {
            codeForm.reset();
        },
    });
};

const submitEmailChange = () => {
    emailForm.post(route('verification.update-email'), {
        onSuccess: () => {
            emailForm.reset('password');
            showChangeEmail.value = false;
        },
        preserveScroll: true,
    });
};

const logout = () => {
    resendForm.post(route('logout'));
};

const toggleChangeEmail = () => {
    showChangeEmail.value = !showChangeEmail.value;
    if (showChangeEmail.value) {
        emailForm.email = props.email;
    }
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

                                    <!-- Display user's email -->
                                    <div class="alert mb-4" style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.3)">
                                        <p class="mb-1 text-gray-light small">Verification email sent to:</p>
                                        <p class="mb-0 text-warning fw-bold">{{ email }}</p>
                                    </div>

                                    <p class="text-gray-light mb-4">
                                        Please verify your email address by clicking on the link we just emailed to you.
                                    </p>

                                    <p class="text-gray-light mb-4">If you didn't receive the email, check your spam folder or click below to resend.</p>

                                    <!-- Status Messages -->
                                    <div v-if="status === 'verification-link-sent'" class="alert alert-success mb-4">
                                        <i class="bi bi-check-circle me-2"></i>
                                        A new verification link has been sent to your email address.
                                    </div>

                                    <div v-if="status === 'verification-email-updated'" class="alert alert-success mb-4">
                                        <i class="bi bi-check-circle me-2"></i>
                                        Your email has been updated and a new verification link has been sent.
                                    </div>

                                    <!-- Resend Button -->
                                    <form @submit.prevent="submitResend" class="mb-4">
                                        <button type="submit" class="btn btn-warning btn-lg w-100 py-3 text-dark fw-bold" :disabled="resendForm.processing">
                                            <span v-if="resendForm.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                            <i v-else class="bi bi-envelope me-2"></i>
                                            <span>Resend Verification Email</span>
                                        </button>
                                    </form>

                                    <!-- Bypass Code Section (if enabled) -->
                                    <div v-if="bypassEnabled" class="mb-4">
                                        <button
                                            type="button"
                                            class="btn btn-link text-gray-light text-decoration-none small p-0"
                                            @click="showBypassCode = !showBypassCode"
                                        >
                                            <i class="bi bi-key me-1"></i>
                                            Have a verification code from support?
                                            <i :class="showBypassCode ? 'bi-chevron-up' : 'bi-chevron-down'" class="ms-1"></i>
                                        </button>

                                        <div v-if="showBypassCode" class="mt-3">
                                            <form @submit.prevent="submitCode">
                                                <div class="input-group">
                                                    <input
                                                        v-model="codeForm.code"
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': codeForm.errors.code }"
                                                        placeholder="Enter verification code"
                                                        style="background-color: #0d1421; border-color: #2e4057; color: #fff"
                                                    />
                                                    <button
                                                        type="submit"
                                                        class="btn btn-outline-warning"
                                                        :disabled="codeForm.processing || !codeForm.code"
                                                    >
                                                        <span v-if="codeForm.processing" class="spinner-border spinner-border-sm" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </span>
                                                        <span v-else>Verify</span>
                                                    </button>
                                                </div>
                                                <div v-if="codeForm.errors.code" class="text-danger small mt-2 text-start">
                                                    {{ codeForm.errors.code }}
                                                </div>
                                            </form>
                                        </div>
                                    </div>

                                    <div class="position-relative my-4">
                                        <hr class="text-gray-medium" />
                                        <span
                                            class="position-absolute top-50 start-50 translate-middle bg-dark px-3 text-gray-light small"
                                            style="background-color: #1a2332"
                                            >OR</span
                                        >
                                    </div>

                                    <!-- Wrong Email Section -->
                                    <div class="mb-4">
                                        <button
                                            type="button"
                                            class="btn btn-link text-gray-light text-decoration-none small p-0"
                                            @click="toggleChangeEmail"
                                        >
                                            <i class="bi bi-pencil me-1"></i>
                                            Wrong email address? Change it here
                                            <i :class="showChangeEmail ? 'bi-chevron-up' : 'bi-chevron-down'" class="ms-1"></i>
                                        </button>

                                        <div v-if="showChangeEmail" class="mt-3 text-start">
                                            <form @submit.prevent="submitEmailChange">
                                                <div class="mb-3">
                                                    <label for="newEmail" class="form-label text-gray-light small">New Email Address</label>
                                                    <input
                                                        id="newEmail"
                                                        v-model="emailForm.email"
                                                        type="email"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': emailForm.errors.email }"
                                                        placeholder="your@email.com"
                                                        style="background-color: #0d1421; border-color: #2e4057; color: #fff"
                                                    />
                                                    <div v-if="emailForm.errors.email" class="invalid-feedback">
                                                        {{ emailForm.errors.email }}
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label for="password" class="form-label text-gray-light small">Confirm Your Password</label>
                                                    <input
                                                        id="password"
                                                        v-model="emailForm.password"
                                                        type="password"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': emailForm.errors.password }"
                                                        placeholder="Enter your password"
                                                        style="background-color: #0d1421; border-color: #2e4057; color: #fff"
                                                    />
                                                    <div v-if="emailForm.errors.password" class="invalid-feedback">
                                                        {{ emailForm.errors.password }}
                                                    </div>
                                                </div>

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-warning w-100"
                                                    :disabled="emailForm.processing || !emailForm.email || !emailForm.password"
                                                >
                                                    <span v-if="emailForm.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                        <span class="visually-hidden">Loading...</span>
                                                    </span>
                                                    <i v-else class="bi bi-check-lg me-2"></i>
                                                    Update Email & Resend Verification
                                                </button>
                                            </form>
                                        </div>
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

.form-control:focus {
    background-color: #0d1421;
    border-color: #ffc107;
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
}

.form-control::placeholder {
    color: #6c757d;
}
</style>
