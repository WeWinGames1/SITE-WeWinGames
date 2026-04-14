<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    token: string;
    email: string;
    name: string;
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    token: props.token,
    password: '',
    password_confirmation: '',
    discord_username: '',
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Client-side validation errors
const clientErrors = ref({
    password: '',
    password_confirmation: '',
    discord_username: '',
});

// Validation functions
const validatePassword = (value: string): string => {
    if (!value) return 'Password is required';
    if (value.length < 8) return 'Password must be at least 8 characters';
    if (!/[A-Z]/.test(value)) return 'Password must contain at least one uppercase letter';
    if (!/[a-z]/.test(value)) return 'Password must contain at least one lowercase letter';
    if (!/[0-9]/.test(value)) return 'Password must contain at least one number';
    if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)) return 'Password must contain at least one special character';
    return '';
};

const validatePasswordConfirmation = (value: string, password: string): string => {
    if (!value) return 'Please confirm your password';
    if (value !== password) return 'Passwords do not match';
    return '';
};

const validateDiscordUsername = (value: string): string => {
    if (!value?.trim()) return '';
    if (value.length > 255) return 'Discord username must not exceed 255 characters';
    const newFormat = /^[a-zA-Z0-9._]{2,32}$/;
    const oldFormat = /^[a-zA-Z0-9._-]+#[0-9]{4}$/;
    if (!newFormat.test(value) && !oldFormat.test(value)) {
        return 'Please enter a valid Discord username (e.g., username or username#1234)';
    }
    return '';
};

// Watchers
watch(
    () => form.password,
    (value) => {
        clientErrors.value.password = value ? validatePassword(value) : '';
        if (form.password_confirmation) {
            clientErrors.value.password_confirmation = validatePasswordConfirmation(form.password_confirmation, value);
        }
    },
);

watch(
    () => form.password_confirmation,
    (value) => {
        clientErrors.value.password_confirmation = value ? validatePasswordConfirmation(value, form.password) : '';
    },
);

watch(
    () => form.discord_username,
    (value) => {
        clientErrors.value.discord_username = validateDiscordUsername(value);
    },
);

const submit = () => {
    // Client-side validation
    clientErrors.value.password = validatePassword(form.password);
    clientErrors.value.password_confirmation = validatePasswordConfirmation(form.password_confirmation, form.password);

    if (clientErrors.value.password || clientErrors.value.password_confirmation) {
        return;
    }

    form.post(route('complete-registration.store'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
        onSuccess: () => {
            // Reddit Pixel - Sign Up completion
            const pixelId = (page.props as any).env?.REDDIT_PIXEL_ID;
            if ((window as any).rdt && pixelId) {
                (window as any).rdt('init', pixelId, { email: props.email });
                (window as any).rdt('track', 'SignUp');
            }
        },
    });
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Complete Your Account" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #7c3aed 0%, #111827 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-check-circle-fill text-success display-3"></i>
                            </div>
                            <h1 class="h2 fw-bold text-white mb-2">Payment Successful!</h1>
                            <p class="text-gray-light">Complete your account setup to get started</p>
                        </div>

                        <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                            <div class="card-body p-5">
                                <div class="text-center mb-4">
                                    <h2 class="h5 fw-bold text-white mb-1">Welcome, {{ name }}!</h2>
                                    <p class="text-gray-light small mb-0">{{ email }}</p>
                                </div>

                                <form @submit.prevent="submit">
                                    <div class="mb-4">
                                        <label for="password" class="form-label text-white fw-medium">Create Password</label>
                                        <div class="position-relative">
                                            <input
                                                id="password"
                                                :type="showPassword ? 'text' : 'password'"
                                                class="form-control form-control-lg"
                                                :class="{ 'is-invalid': form.errors.password || clientErrors.password }"
                                                :style="form.errors.password || clientErrors.password ? 'padding-right: 4rem !important;' : ''"
                                                v-model="form.password"
                                                placeholder="Create a strong password"
                                                required
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-link position-absolute top-50 translate-middle-y text-gray-light p-0"
                                                :style="form.errors.password || clientErrors.password ? 'right: 2.5rem;' : 'right: 0.75rem;'"
                                                @click="showPassword = !showPassword"
                                                style="text-decoration: none"
                                            >
                                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" class="fs-5"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password || clientErrors.password" class="invalid-feedback d-block">
                                            {{ form.errors.password || clientErrors.password }}
                                        </div>
                                        <div v-else class="form-text text-gray-light small">
                                            8+ characters with uppercase, lowercase, number, and special character
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label text-white fw-medium">Confirm Password</label>
                                        <div class="position-relative">
                                            <input
                                                id="password_confirmation"
                                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                                class="form-control form-control-lg"
                                                :class="{ 'is-invalid': form.errors.password_confirmation || clientErrors.password_confirmation }"
                                                :style="form.errors.password_confirmation || clientErrors.password_confirmation ? 'padding-right: 4rem !important;' : ''"
                                                v-model="form.password_confirmation"
                                                placeholder="Confirm your password"
                                                required
                                                autocomplete="new-password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-link position-absolute top-50 translate-middle-y text-gray-light p-0"
                                                :style="form.errors.password_confirmation || clientErrors.password_confirmation ? 'right: 2.5rem;' : 'right: 0.75rem;'"
                                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                                style="text-decoration: none"
                                            >
                                                <i :class="showPasswordConfirmation ? 'bi bi-eye-slash' : 'bi bi-eye'" class="fs-5"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password_confirmation || clientErrors.password_confirmation" class="invalid-feedback d-block">
                                            {{ form.errors.password_confirmation || clientErrors.password_confirmation }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="discord_username" class="form-label text-white fw-medium">
                                            Discord Username
                                            <span class="text-gray-light small">(Optional)</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-dark border-secondary text-gray-light">
                                                <i class="bi bi-discord"></i>
                                            </span>
                                            <input
                                                id="discord_username"
                                                type="text"
                                                class="form-control form-control-lg"
                                                :class="{ 'is-invalid': form.errors.discord_username || clientErrors.discord_username }"
                                                v-model="form.discord_username"
                                                placeholder="username or username#1234"
                                                autocomplete="off"
                                            />
                                        </div>
                                        <div v-if="form.errors.discord_username || clientErrors.discord_username" class="invalid-feedback d-block">
                                            {{ form.errors.discord_username || clientErrors.discord_username }}
                                        </div>
                                        <div v-else class="form-text text-gray-light small">
                                            Connect your Discord for exclusive community access and alerts
                                        </div>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg py-3" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            <span class="fw-semibold">Complete Setup & Start Winning</span>
                                            <i class="bi bi-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- What's next -->
                        <div class="card mt-4" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                            <div class="card-body">
                                <h6 class="text-white mb-3">What's next?</h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2 d-flex align-items-center text-gray-light small">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        Access your premium picks immediately
                                    </li>
                                    <li class="mb-2 d-flex align-items-center text-gray-light small">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        Get daily betting recommendations
                                    </li>
                                    <li class="d-flex align-items-center text-gray-light small">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        Manage your subscription anytime
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>
