<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { useForm } from '@/composables/useInertiaForm';
import { useSessionKeepAlive } from '@/composables/useSessionKeepAlive';
import { computed, nextTick, onMounted, ref } from 'vue';

const page = usePage();
const turnstileEnabled = ref(false);
const turnstileSiteKey = ref('');
const turnstileWidget = ref<string | null>(null);
const turnstileError = ref<string>('');
const turnstileLoading = ref(true);
const turnstileRendered = ref(false);
const submissionError = ref<string>('');

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const emailAlreadyExists = ref(false);

const loginUrl = computed(() => route('login'));
const loginUrlWithEmail = computed(() => {
    if (form.email) {
        return route('login') + '?email=' + encodeURIComponent(form.email);
    }
    return route('login');
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    discord_username: '',
    favorite_team: '',
    favorite_sport: '',
    primary_betting_app: '',
    password: '',
    password_confirmation: '',
    website: '', // Honeypot field
    timestamp: 0, // Will be set on mount
    'cf-turnstile-response': '',
});

// Keep session alive while user is on registration page
useSessionKeepAlive(15); // Ping every 15 minutes

// Function to render Turnstile widget
const renderTurnstile = (container: HTMLElement) => {
    // Prevent multiple renders
    if (turnstileRendered.value) {
        // console.log('Turnstile already rendered, skipping...');
        return;
    }

    try {
        // console.log('Rendering Turnstile widget...');

        const widgetConfig = {
            sitekey: turnstileSiteKey.value,
            theme: 'dark',
            size: 'normal',
            mode: 'managed', // Explicitly set to managed mode
            appearance: 'always', // Always show the widget
            callback: function (token: string) {
                console.log('Turnstile token received:', token.substring(0, 10) + '...');
                form['cf-turnstile-response'] = token;
                turnstileError.value = '';
                console.log('Token set in form:', !!form['cf-turnstile-response']);
            },
            'expired-callback': function () {
                // console.log('Turnstile token expired');
                form['cf-turnstile-response'] = '';
                turnstileError.value = 'Token expired - please complete verification again';
            },
            'error-callback': function (error: any) {
                console.error('Turnstile error occurred:', error);
                turnstileError.value = 'Verification error - please refresh the page';
            },
            'before-interactive-callback': function () {
                // console.log('Turnstile showing interactive challenge');
            },
            'after-interactive-callback': function () {
                // console.log('Turnstile interactive challenge completed');
            },
            'unsupported-callback': function () {
                console.error('Turnstile not supported in this browser');
                turnstileError.value = 'Browser not supported';
            },
        };

        // Render Turnstile widget
        turnstileWidget.value = window.turnstile.render(container, widgetConfig);
        // console.log('Turnstile widget ID:', turnstileWidget.value);
        turnstileLoading.value = false;
        turnstileRendered.value = true;
    } catch (error: any) {
        console.error('Error rendering Turnstile:', error);
        turnstileError.value = error.message || 'Failed to load verification';
        turnstileLoading.value = false;
    }
};

onMounted(() => {
    console.log('Vue component mounted successfully');

    // Set timestamp when component mounts
    form.timestamp = Math.floor(Date.now() / 1000);
    console.log('Timestamp set to:', form.timestamp);

    // Check if Turnstile is enabled from Inertia shared data
    const pageProps = page.props as any;
    // Handle string "true"/"false" from env variables
    const envEnabled = pageProps.env?.TURNSTILE_ENABLED;
    turnstileEnabled.value = envEnabled === true || envEnabled === 'true' || envEnabled === 1 || envEnabled === '1';
    turnstileSiteKey.value = pageProps.env?.TURNSTILE_SITE_KEY || '';

    // Add global error handler to catch any issues
    window.addEventListener('error', (e) => {
        console.error('Global error caught:', e.message, e);
    });

    // Check if Inertia is working
    console.log('Inertia available:', typeof route !== 'undefined');
    console.log('Form object:', form);

    // Log form data for debugging
    const formElement = document.getElementById('registration-form');
    if (formElement) {
        formElement.addEventListener('submit', (e) => {
            console.log('Native form submit event triggered - this should not happen!');
            e.preventDefault(); // Extra prevention
        });
    }

    // Debug logging (commented out for production)
    // console.log('Turnstile Debug:', {
    //     enabled: turnstileEnabled.value,
    //     siteKey: turnstileSiteKey.value,
    //     windowTurnstile: !!window.turnstile,
    //     turnstileConfig: window.turnstileConfig,
    //     env: pageProps.env
    // });

    // Check if Turnstile script tag exists
    const turnstileScript = document.querySelector('script[src*="challenges.cloudflare.com/turnstile"]');
    // console.log('Turnstile script tag:', turnstileScript ? 'found' : 'not found');

    // If script doesn't exist, try to add it manually
    if (!turnstileScript && turnstileEnabled.value) {
        // console.log('Manually adding Turnstile script...');
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js';
        script.async = true;
        script.defer = true;
        script.onload = () => {
            // console.log('Turnstile script loaded manually');
        };
        script.onerror = (error) => {
            console.error('Failed to load Turnstile script:', error);
            turnstileError.value = 'Failed to load security verification script';
            turnstileLoading.value = false;
        };
        document.head.appendChild(script);
    }

    if (turnstileEnabled.value && turnstileSiteKey.value) {
        // Wait for DOM to be ready
        nextTick().then(() => {
            let retryCount = 0;
            const maxRetries = 50; // 5 seconds max wait

            // Wait for Turnstile script to load
            const checkTurnstile = () => {
                // console.log(`Checking for Turnstile... (attempt ${retryCount + 1})`, {
                //     turnstileExists: !!window.turnstile,
                //     turnstileType: typeof window.turnstile
                // });

                if (window.turnstile && typeof window.turnstile.render === 'function') {
                    try {
                        // Check if container exists
                        const container = document.getElementById('cf-turnstile');
                        if (!container) {
                            console.error('Turnstile container not found!');
                            turnstileError.value = 'Container element not found';
                            turnstileLoading.value = false;
                            // Try again after a short delay
                            setTimeout(() => {
                                const retryContainer = document.getElementById('cf-turnstile');
                                if (retryContainer) {
                                    console.log('Container found on retry, attempting render...');
                                    turnstileError.value = '';
                                    renderTurnstile(retryContainer);
                                }
                            }, 500);
                            return;
                        }

                        // console.log('Container found, proceeding to render...');
                        renderTurnstile(container);
                    } catch (error: any) {
                        console.error('Error in checkTurnstile:', error);
                        turnstileError.value = error.message || 'Failed to initialize verification';
                        turnstileLoading.value = false;
                    }
                } else {
                    retryCount++;
                    if (retryCount >= maxRetries) {
                        console.error('Turnstile script failed to load after', maxRetries, 'attempts');
                        turnstileError.value = 'Verification service unavailable';
                        turnstileLoading.value = false;
                    } else {
                        // Retry after 100ms
                        setTimeout(checkTurnstile, 100);
                    }
                }
            };

            checkTurnstile();
        });
    } else {
        turnstileLoading.value = false;
        if (turnstileEnabled.value && !turnstileSiteKey.value) {
            turnstileError.value = 'Missing site key configuration';
        }
    }
});

const submit = () => {
    // Don't update timestamp here - use the one from mount

    // Debug logging
    console.log('Form submission started', {
        turnstileEnabled: turnstileEnabled.value,
        hasToken: !!form['cf-turnstile-response'],
        tokenLength: form['cf-turnstile-response']?.length || 0,
        timestamp: form.timestamp,
        currentTime: Math.floor(Date.now() / 1000),
        timeDiff: Math.floor(Date.now() / 1000) - form.timestamp,
        formData: form.data(),
    });

    form.post(route('register'), {
        onStart: () => {
            console.log('Request started');
            submissionError.value = '';
        },
        onFinish: () => {
            console.log('Request finished');
            form.reset('password', 'password_confirmation');
            // Reset Turnstile if enabled
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
        onError: (errors) => {
            console.error('Registration errors:', errors);

            // Check if email already exists error
            if (errors.email && (
                errors.email.toString().toLowerCase().includes('already been taken') ||
                errors.email.toString().toLowerCase().includes('email has already been taken') ||
                errors.email.toString().toLowerCase().includes('unique')
            )) {
                emailAlreadyExists.value = true;
            } else {
                emailAlreadyExists.value = false;
            }

            // Always show general error message for any validation errors
            // This catches timestamp, honeypot, and other hidden field errors
            const errorMessages = Object.entries(errors)
                .map(([field, message]) => {
                    // Format field names to be more user-friendly
                    const fieldName = field
                        .replace(/_/g, ' ')
                        .replace(/\b\w/g, l => l.toUpperCase())
                        .replace('Cf Turnstile Response', 'Security verification');
                    return `${fieldName}: ${message}`;
                })
                .join('; ');

            if (errorMessages) {
                submissionError.value = errorMessages;
            }

            // Reset Turnstile on error
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
        onSuccess: (page) => {
            console.log('Registration successful', page);
        },
        preserveScroll: true,
        preserveState: true, // Keep form data on validation errors
    });
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Register" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #7c3aed 0%, #111827 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-md-8 col-lg-7">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Get Started</h1>
                            <p class="fs-5 text-gray-light">Join thousands of winning bettors today</p>
                        </div>

                        <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                            <div class="card-body p-5">
                                <h2 class="h4 fw-bold text-white mb-2">Create your account</h2>
                                <p class="text-gray-light mb-4">Take your first step towards winning!</p>

                                <!-- General Error Display -->
                                <div v-if="submissionError && !emailAlreadyExists" class="alert alert-danger mb-4">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
                                        <div class="flex-grow-1">
                                            <h5 class="alert-heading mb-2">Registration Error</h5>
                                            <p class="mb-0">{{ submissionError }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Already Exists Alert -->
                                <div v-if="emailAlreadyExists" class="alert alert-warning mb-4">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-exclamation-triangle-fill me-3 fs-5"></i>
                                        <div class="flex-grow-1">
                                            <h5 class="alert-heading mb-2">Account Already Exists</h5>
                                            <p class="mb-3">This email address is already registered. If this is your account, please sign in instead.</p>
                                            <a :href="loginUrlWithEmail" class="btn btn-warning btn-sm">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                Go to Login
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <form @submit.prevent="submit" id="registration-form">
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
                                        <label for="phone" class="form-label text-white fw-medium">Phone Number</label>
                                        <input
                                            id="phone"
                                            type="tel"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.phone }"
                                            required
                                            autocomplete="tel"
                                            v-model="form.phone"
                                            placeholder="+1 (555) 123-4567"
                                        />
                                        <div v-if="form.errors.phone" class="invalid-feedback">
                                            {{ form.errors.phone }}
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
                                                :class="{ 'is-invalid': form.errors.discord_username }"
                                                autocomplete="off"
                                                v-model="form.discord_username"
                                                placeholder="username or username#1234"
                                            />
                                            <div v-if="form.errors.discord_username" class="invalid-feedback">
                                                {{ form.errors.discord_username }}
                                            </div>
                                        </div>
                                        <div class="form-text text-gray-light small">Enter your Discord username (new or legacy format with #1234) for exclusive community access</div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="favorite_team" class="form-label text-white fw-medium">
                                            Favorite Sports Team
                                            <span class="text-gray-light small">(Optional)</span>
                                        </label>
                                        <input
                                            id="favorite_team"
                                            type="text"
                                            class="form-control form-control-lg"
                                            :class="{ 'is-invalid': form.errors.favorite_team }"
                                            autocomplete="off"
                                            v-model="form.favorite_team"
                                            placeholder="e.g. Los Angeles Lakers"
                                        />
                                        <div v-if="form.errors.favorite_team" class="invalid-feedback">
                                            {{ form.errors.favorite_team }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="favorite_sport" class="form-label text-white fw-medium">
                                            Favorite Sport to Bet
                                            <span class="text-gray-light small">(Optional)</span>
                                        </label>
                                        <select
                                            id="favorite_sport"
                                            class="form-select form-select-lg"
                                            :class="{ 'is-invalid': form.errors.favorite_sport }"
                                            v-model="form.favorite_sport"
                                        >
                                            <option value="">Select a sport...</option>
                                            <option value="football">Football (NFL)</option>
                                            <option value="basketball">Basketball (NBA)</option>
                                            <option value="baseball">Baseball (MLB)</option>
                                            <option value="hockey">Hockey (NHL)</option>
                                            <option value="soccer">Soccer</option>
                                            <option value="mma">MMA/UFC</option>
                                            <option value="boxing">Boxing</option>
                                            <option value="golf">Golf</option>
                                            <option value="tennis">Tennis</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div v-if="form.errors.favorite_sport" class="invalid-feedback">
                                            {{ form.errors.favorite_sport }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="primary_betting_app" class="form-label text-white fw-medium">
                                            Primary Betting App
                                            <span class="text-gray-light small">(Optional)</span>
                                        </label>
                                        <select
                                            id="primary_betting_app"
                                            class="form-select form-select-lg"
                                            :class="{ 'is-invalid': form.errors.primary_betting_app }"
                                            v-model="form.primary_betting_app"
                                        >
                                            <option value="">Select an app...</option>
                                            <option value="draftkings">DraftKings</option>
                                            <option value="fanduel">FanDuel</option>
                                            <option value="bet365">Bet365</option>
                                            <option value="caesars">Caesars Sportsbook</option>
                                            <option value="betmgm">BetMGM</option>
                                            <option value="pointsbet">PointsBet</option>
                                            <option value="barstool">Barstool Sportsbook</option>
                                            <option value="betrivers">BetRivers</option>
                                            <option value="williamhill">William Hill</option>
                                            <option value="foxbet">FOX Bet</option>
                                            <option value="wynnbet">WynnBET</option>
                                            <option value="unibet">Unibet</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <div v-if="form.errors.primary_betting_app" class="invalid-feedback">
                                            {{ form.errors.primary_betting_app }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password" class="form-label text-white fw-medium">Password</label>
                                        <div class="position-relative">
                                            <input
                                                id="password"
                                                :type="showPassword ? 'text' : 'password'"
                                                class="form-control form-control-lg pe-5"
                                                :class="{ 'is-invalid': form.errors.password }"
                                                required
                                                autocomplete="new-password"
                                                v-model="form.password"
                                                placeholder="Create a strong password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-gray-light p-0 me-3"
                                                @click="showPassword = !showPassword"
                                                style="text-decoration: none;"
                                            >
                                                <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'" class="fs-5"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password" class="invalid-feedback d-block">
                                            {{ form.errors.password }}
                                        </div>
                                    </div>

                                    <div class="mb-4">
                                        <label for="password_confirmation" class="form-label text-white fw-medium">Confirm Password</label>
                                        <div class="position-relative">
                                            <input
                                                id="password_confirmation"
                                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                                class="form-control form-control-lg pe-5"
                                                :class="{ 'is-invalid': form.errors.password_confirmation }"
                                                required
                                                autocomplete="new-password"
                                                v-model="form.password_confirmation"
                                                placeholder="Confirm your password"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-gray-light p-0 me-3"
                                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                                style="text-decoration: none;"
                                            >
                                                <i :class="showPasswordConfirmation ? 'bi bi-eye-slash' : 'bi bi-eye'" class="fs-5"></i>
                                            </button>
                                        </div>
                                        <div v-if="form.errors.password_confirmation" class="invalid-feedback d-block">
                                            {{ form.errors.password_confirmation }}
                                        </div>
                                    </div>

                                    <!-- Honeypot field (hidden) -->
                                    <input
                                        type="text"
                                        name="website"
                                        v-model="form.website"
                                        style="position: absolute; left: -9999px"
                                        autocomplete="off"
                                        tabindex="-1"
                                    />

                                    <!-- Cloudflare Turnstile -->
                                    <div v-if="turnstileEnabled" class="mb-3">
                                        <div v-if="turnstileLoading && !turnstileRendered" class="text-center py-3">
                                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                <span class="visually-hidden">Loading security verification...</span>
                                            </div>
                                            <div class="text-muted small mt-2">Loading security verification...</div>
                                        </div>
                                        <div
                                            id="cf-turnstile"
                                            class="cf-turnstile"
                                            :data-sitekey="turnstileSiteKey"
                                            data-theme="dark"
                                            data-appearance="always"
                                            style="min-height: 65px"
                                        ></div>
                                        <div v-if="turnstileError" class="alert alert-danger small mt-2">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            {{ turnstileError }}
                                        </div>
                                        <div v-if="form.errors['cf-turnstile-response']" class="text-danger small mt-1">
                                            {{ form.errors['cf-turnstile-response'] }}
                                        </div>
                                    </div>
                                    <!-- Fallback if Turnstile is disabled but should be enabled -->
                                    <div v-else-if="!turnstileEnabled && page.props.env?.TURNSTILE_ENABLED" class="alert alert-warning mb-3">
                                        <small>Security verification is temporarily unavailable. Please try again later.</small>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button
                                            type="submit"
                                            class="btn btn-primary btn-lg py-3"
                                            :disabled="form.processing"
                                            @click="console.log('Button clicked')"
                                        >
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
                                        <a href="/privacy-policy" class="text-purple text-decoration-none">Privacy Policy</a>
                                    </div>

                                    <div class="position-relative">
                                        <hr class="text-gray-medium" />
                                        <span
                                            class="position-absolute top-50 start-50 translate-middle bg-card px-3 text-gray-light small"
                                            style="background-color: var(--bs-card-bg)"
                                            >OR</span
                                        >
                                    </div>
                                </form>

                                <div class="text-center mt-4">
                                    <p class="text-gray-light mb-2">Already have an account?</p>
                                    <a :href="loginUrl" class="btn btn-outline-primary btn-lg w-100">
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
