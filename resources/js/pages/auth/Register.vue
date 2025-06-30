<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { onMounted, ref } from 'vue';

const page = usePage();
const turnstileEnabled = ref(false);
const turnstileSiteKey = ref('');
const turnstileWidget = ref<string | null>(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    website: '', // Honeypot field
    timestamp: Math.floor(Date.now() / 1000), // Current timestamp
    'cf-turnstile-response': '',
});

onMounted(() => {
    // Check if Turnstile is enabled from backend config
    if (window.turnstileConfig) {
        turnstileEnabled.value = window.turnstileConfig.enabled;
        turnstileSiteKey.value = window.turnstileConfig.siteKey;
        
        if (turnstileEnabled.value && window.turnstile) {
            // Render Turnstile widget
            turnstileWidget.value = window.turnstile.render('#cf-turnstile', {
                sitekey: turnstileSiteKey.value,
                callback: function(token: string) {
                    form['cf-turnstile-response'] = token;
                },
                'expired-callback': function() {
                    form['cf-turnstile-response'] = '';
                },
            });
        }
    }
});

const submit = () => {
    // Update timestamp if form was cached
    form.timestamp = Math.floor(Date.now() / 1000);
    
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            // Reset Turnstile if enabled
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
    });
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Register" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #7C3AED 0%, #111827 100%);">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-5">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Get Started</h1>
                            <p class="fs-5 text-gray-light">Join thousands of winning bettors today</p>
                        </div>
                        
                        <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
                            <div class="card-body p-5">
                                <h2 class="h4 fw-bold text-white mb-2">Create your account</h2>
                                <p class="text-gray-light mb-4">Start with a 7-day free trial</p>

                            <form @submit.prevent="submit">
                                <div class="mb-4">
                                    <label for="name" class="form-label text-white fw-medium">Full Name</label>
                                    <input
                                        id="name"
                                        type="text"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': form.errors.name }"
                                        required
                                        autofocus
                                        autocomplete="name"
                                        v-model="form.name"
                                        placeholder="John Doe"
                                    />
                                    <div v-if="form.errors.name" class="invalid-feedback">
                                        {{ form.errors.name }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="email" class="form-label text-white fw-medium">Email Address</label>
                                    <input
                                        id="email"
                                        type="email"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': form.errors.email }"
                                        required
                                        autocomplete="email"
                                        v-model="form.email"
                                        placeholder="you@example.com"
                                    />
                                    <div v-if="form.errors.email" class="invalid-feedback">
                                        {{ form.errors.email }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="password" class="form-label text-white fw-medium">Password</label>
                                    <input
                                        id="password"
                                        type="password"
                                        class="form-control form-control-lg"
                                        :class="{ 'is-invalid': form.errors.password }"
                                        required
                                        autocomplete="new-password"
                                        v-model="form.password"
                                        placeholder="Create a strong password"
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
                                        placeholder="Confirm your password"
                                    />
                                    <div v-if="form.errors.password_confirmation" class="invalid-feedback">
                                        {{ form.errors.password_confirmation }}
                                    </div>
                                </div>

                                <!-- Honeypot field (hidden) -->
                                <input
                                    type="text"
                                    name="website"
                                    v-model="form.website"
                                    style="position: absolute; left: -9999px;"
                                    autocomplete="off"
                                    tabindex="-1"
                                />

                                <!-- Cloudflare Turnstile -->
                                <div v-if="turnstileEnabled" class="mb-3">
                                    <div id="cf-turnstile"></div>
                                    <div v-if="form.errors['cf-turnstile-response']" class="text-danger small mt-1">
                                        {{ form.errors['cf-turnstile-response'] }}
                                    </div>
                                </div>

                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg py-3" :disabled="form.processing">
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                        <span class="fw-semibold">Create Account</span>
                                        <i class="bi bi-arrow-right ms-2"></i>
                                    </button>
                                </div>

                                <div class="text-center text-gray-light small mb-4">
                                    By creating an account, you agree to our
                                    <a href="/terms" class="text-purple text-decoration-none">Terms of Service</a>
                                    and
                                    <a href="/privacy" class="text-purple text-decoration-none">Privacy Policy</a>
                                </div>
                                
                                <div class="position-relative">
                                    <hr class="text-gray-medium">
                                    <span class="position-absolute top-50 start-50 translate-middle bg-card px-3 text-gray-light small" style="background-color: var(--bs-card-bg);">OR</span>
                                </div>
                            </form>

                            <div class="text-center mt-4">
                                <p class="text-gray-light mb-2">Already have an account?</p>
                                <a :href="route('login')" class="btn btn-outline-primary btn-lg w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>
                                    Sign In
                                </a>
                            </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Benefits -->
                    <div class="row mt-5 text-center">
                        <div class="col-4">
                            <i class="bi bi-shield-check text-purple fs-2 mb-2"></i>
                            <p class="text-gray-light small mb-0">Secure & Safe</p>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-clock-history text-purple fs-2 mb-2"></i>
                            <p class="text-gray-light small mb-0">7-Day Free Trial</p>
                        </div>
                        <div class="col-4">
                            <i class="bi bi-trophy text-purple fs-2 mb-2"></i>
                            <p class="text-gray-light small mb-0">Proven Results</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>