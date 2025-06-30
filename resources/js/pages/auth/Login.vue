<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const page = usePage();
const isLocal = page.props.env?.APP_ENV === 'local';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
        onSuccess: () => {
            console.log('Login successful, redirecting...');
        },
        onError: (errors) => {
            console.error('Login errors:', errors);
        }
    });
};

// Test credentials for local environment
const testCredentials = [
    { role: 'Admin', email: 'admin@wewingames.test', password: 'password' },
    { role: 'Subscriber', email: 'subscriber@wewingames.test', password: 'password' },
];

function fillCredentials(email: string, password: string) {
    form.email = email;
    form.password = password;
}
</script>

<template>
    <WelcomeLayout>
        <Head title="Log in" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Welcome Back</h1>
                            <p class="fs-5 text-gray-light">Sign in to access your picks</p>
                        </div>
                        
                        <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057;">
                            <div class="card-body p-5">
                                <h2 class="h4 fw-bold text-white mb-4">Sign in to your account</h2>

                                <div v-if="status" class="alert alert-success mb-4">
                                    {{ status }}
                                </div>

                                <!-- Test Credentials for Local Environment -->
                                <div v-if="isLocal" class="mb-4 p-3 rounded" style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107;">
                                    <p class="mb-2 fw-bold text-warning"><i class="bi bi-info-circle me-2"></i>Demo Accounts</p>
                                    <div class="d-grid gap-2">
                                        <button 
                                            v-for="cred in testCredentials" 
                                            :key="cred.email"
                                            type="button"
                                            class="btn btn-sm btn-outline-warning text-start"
                                            @click="fillCredentials(cred.email, cred.password)"
                                        >
                                            <i class="bi bi-person-fill me-2"></i>
                                            <strong>{{ cred.role }}:</strong> <span class="text-gray-light">{{ cred.email }}</span>
                                        </button>
                                    </div>
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
                                    </div>

                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label for="password" class="form-label text-white fw-medium mb-0">Password</label>
                                            <a v-if="canResetPassword" :href="route('password.request')" class="text-warning text-decoration-none small">
                                                Forgot password?
                                            </a>
                                        </div>
                                        <input
                                            id="password"
                                            type="password"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.password }"
                                            required
                                            autocomplete="current-password"
                                            v-model="form.password"
                                            placeholder="Enter your password"
                                        />
                                        <div v-if="form.errors.password" class="invalid-feedback">
                                            {{ form.errors.password }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <div class="form-check">
                                            <input
                                                id="remember"
                                                type="checkbox"
                                                class="form-check-input"
                                                v-model="form.remember"
                                            />
                                            <label for="remember" class="form-check-label text-gray-light">
                                                Remember me for 30 days
                                            </label>
                                        </div>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-warning btn-lg py-3 text-dark fw-bold" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </span>
                                            <span class="fw-semibold">Sign In</span>
                                            <i class="bi bi-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="position-relative">
                                        <hr class="text-gray-medium">
                                        <span class="position-absolute top-50 start-50 translate-middle bg-dark px-3 text-gray-light small" style="background-color: #1a2332;">OR</span>
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-gray-light mb-2">Don't have an account?</p>
                                    <a :href="route('register')" class="btn btn-outline-warning btn-lg w-100">
                                        <i class="bi bi-person-plus me-2"></i>
                                        Create Account
                                    </a>
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

.form-check-input {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: #2e4057;
}

.form-check-input:checked {
    background-color: #ffc107;
    border-color: #ffc107;
}

.btn-outline-warning:hover {
    background-color: #ffc107;
    border-color: #ffc107;
    color: #000 !important;
}
</style>