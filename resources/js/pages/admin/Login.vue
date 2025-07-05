<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('admin.login'), {
        onFinish: () => {
            form.reset('password');
        },
        onError: (errors) => {
            // The global error handler in app.ts will handle 419 errors
            console.error('Login error:', errors);
        },
    });
};

// Animation classes for the floating icons
const floatingIcons = [
    { icon: 'bi-bar-chart', style: 'top: 10%; left: 10%;', delay: '0s' },
    { icon: 'bi-people', style: 'top: 20%; right: 20%;', delay: '1s' },
    { icon: 'bi-currency-dollar', style: 'bottom: 20%; left: 20%;', delay: '2s' },
    { icon: 'bi-file-text', style: 'bottom: 10%; right: 10%;', delay: '3s' },
];
</script>

<template>
    <div class="admin-login-page min-vh-100 d-flex align-items-center justify-content-center position-relative overflow-hidden">
        <Head title="Admin Login" />

        <!-- Animated Background Elements -->
        <div class="position-absolute top-0 start-0 w-100 h-100">
            <!-- Grid Pattern -->
            <div class="grid-pattern position-absolute top-0 start-0 w-100 h-100"></div>
            
            <!-- Floating Icons -->
            <div v-for="(item, index) in floatingIcons" :key="index" 
                class="position-absolute opacity-10 text-white floating-icon"
                :style="`${item.style} animation-delay: ${item.delay};`">
                <i :class="[item.icon, 'display-1']"></i>
            </div>
            
            <!-- Gradient Overlay -->
            <div class="position-absolute top-0 start-0 w-100 h-100 gradient-overlay"></div>
        </div>

        <!-- Login Card -->
        <div class="position-relative z-index-1 w-100" style="max-width: 400px; z-index: 10;">
            <div class="login-card rounded shadow">
                <!-- Header -->
                <div class="card-header-gradient p-5 text-center">
                    <div class="icon-wrapper d-inline-flex align-items-center justify-content-center rounded-circle mb-3">
                        <i class="bi bi-shield-check display-4 text-white"></i>
                    </div>
                    <h1 class="h3 fw-bold text-white mb-2">Admin Portal</h1>
                    <p class="text-white-50 mb-0">WeWinGames Management System</p>
                </div>

                <!-- Login Form -->
                <div class="p-4">
                    <form @submit.prevent="submit">
                        <!-- Email Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label text-light">
                                Administrator Email
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary">
                                    <i class="bi bi-person text-muted"></i>
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    v-model="form.email"
                                    class="form-control bg-dark border-secondary text-white"
                                    placeholder="admin@example.com"
                                    required
                                    autofocus
                                    autocomplete="username"
                                />
                            </div>
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="password" class="form-label text-light">
                                Password
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-dark border-secondary">
                                    <i class="bi bi-lock text-muted"></i>
                                </span>
                                <input
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    v-model="form.password"
                                    class="form-control bg-dark border-secondary text-white"
                                    placeholder="••••••••"
                                    required
                                    autocomplete="current-password"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="btn btn-outline-secondary"
                                >
                                    <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                                </button>
                            </div>
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>

                        <!-- Remember Me -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    v-model="form.remember"
                                    class="form-check-input"
                                    id="remember"
                                />
                                <label class="form-check-label text-light" for="remember">
                                    Remember me
                                </label>
                            </div>
                        </div>

                        <!-- Status Message -->
                        <div v-if="status" class="alert alert-success mb-3">
                            {{ status }}
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            class="btn btn-primary w-100 py-2"
                            :disabled="form.processing"
                        >
                            <span v-if="!form.processing">Access Admin Portal</span>
                            <span v-else>
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Authenticating...
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Footer -->
                <div class="card-footer text-center py-3">
                    <small class="text-muted">
                        Authorized personnel only. All activities are logged and monitored.
                    </small>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.admin-login-page {
    background: linear-gradient(135deg, #1a1f2e 0%, #0f1218 100%);
}

.grid-pattern {
    background-image: 
        linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
    background-size: 50px 50px;
}

.gradient-overlay {
    background: linear-gradient(to top, rgba(0, 0, 0, 0.5), transparent);
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
        opacity: 0.1;
    }
    50% {
        transform: translateY(-20px) rotate(10deg);
        opacity: 0.2;
    }
}

.floating-icon {
    animation: float 6s ease-in-out infinite;
}

.opacity-10 {
    opacity: 0.1;
}

.login-card {
    background: rgba(33, 37, 41, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    overflow: hidden;
}

.card-header-gradient {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
}

.icon-wrapper {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
}

.form-control:focus {
    background-color: #212529;
    border-color: #0d6efd;
    color: white;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-control::placeholder {
    color: #6c757d;
}

.input-group-text {
    color: #6c757d;
}

.card-footer {
    background: rgba(0, 0, 0, 0.2);
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.text-white-50 {
    color: rgba(255, 255, 255, 0.5) !important;
}
</style>