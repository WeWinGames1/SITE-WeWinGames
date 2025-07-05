<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { ref, onMounted } from 'vue';

declare global {
    interface Window {
        turnstileConfig?: {
            enabled: boolean;
            siteKey: string;
        };
        turnstile?: {
            render: (element: string, options: any) => string;
            reset: (widgetId: string) => void;
        };
    }
}

interface Category {
    id: number;
    name: string;
    description: string;
}

const props = defineProps<{
    categories: Category[];
    isAuthenticated: boolean;
}>();

const turnstileEnabled = ref(false);
const turnstileSiteKey = ref('');
const turnstileWidget = ref<string | null>(null);

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    category_id: '',
    subject: '',
    content: '',
    priority: 'medium',
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
    form.post(route('support.public.store'), {
        onFinish: () => {
            // Reset Turnstile if enabled
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
    });
};

const scrollToFAQ = (e: Event) => {
    e.preventDefault();
    const faqSection = document.getElementById('faqAccordion');
    if (faqSection) {
        faqSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Contact Support" />

        <div class="min-vh-100" style="background-color: #0a0e1a;">
            <!-- Header Section -->
            <section class="py-5" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);">
                <div class="container">
                    <div class="text-center">
                        <h1 class="display-4 fw-bold text-white mb-3">How Can We Help?</h1>
                        <p class="fs-5 text-white-50">Our support team is here to assist you</p>
                    </div>
                </div>
            </section>

            <!-- Form Section -->
            <section class="py-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <div class="card-body p-5">
                                    <h2 class="h3 fw-bold text-white mb-4">Submit a Support Ticket</h2>
                                    
                                    <form @submit.prevent="submit">
                                        <!-- Guest Information (only show if not authenticated) -->
                                        <div v-if="!isAuthenticated" class="alert alert-info mb-4" style="background-color: rgba(255, 193, 7, 0.1); border-color: #ffc107;">
                                            <div class="d-flex align-items-start">
                                                <i class="bi bi-info-circle text-warning me-3 mt-1"></i>
                                                <div>
                                                    <p class="mb-2 text-white">
                                                        You're submitting as a guest. For faster support and to track your tickets, 
                                                        consider <a href="/login" class="text-warning">logging in</a> or 
                                                        <a href="/register" class="text-warning">creating an account</a>.
                                                    </p>
                                                    <p class="mb-0 text-white-50 small">
                                                        If you're having login issues, please mention it in your ticket below.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="!isAuthenticated" class="row mb-4">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <label for="first_name" class="form-label text-white">First Name <span class="text-danger">*</span></label>
                                                <input
                                                    id="first_name"
                                                    type="text"
                                                    class="form-control"
                                                    v-model="form.first_name"
                                                    :class="{ 'is-invalid': form.errors.first_name }"
                                                    required
                                                />
                                                <div v-if="form.errors.first_name" class="invalid-feedback">
                                                    {{ form.errors.first_name }}
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="last_name" class="form-label text-white">Last Name <span class="text-danger">*</span></label>
                                                <input
                                                    id="last_name"
                                                    type="text"
                                                    class="form-control"
                                                    v-model="form.last_name"
                                                    :class="{ 'is-invalid': form.errors.last_name }"
                                                    required
                                                />
                                                <div v-if="form.errors.last_name" class="invalid-feedback">
                                                    {{ form.errors.last_name }}
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="!isAuthenticated" class="mb-4">
                                            <label for="email" class="form-label text-white">Email Address <span class="text-danger">*</span></label>
                                            <input
                                                id="email"
                                                type="email"
                                                class="form-control"
                                                v-model="form.email"
                                                :class="{ 'is-invalid': form.errors.email }"
                                                required
                                            />
                                            <div v-if="form.errors.email" class="invalid-feedback">
                                                {{ form.errors.email }}
                                            </div>
                                        </div>

                                        <!-- Ticket Information -->
                                        <div class="row mb-4">
                                            <div class="col-md-8 mb-3 mb-md-0">
                                                <label for="category" class="form-label text-white">Category <span class="text-danger">*</span></label>
                                                <select 
                                                    id="category"
                                                    class="form-select"
                                                    v-model="form.category_id"
                                                    :class="{ 'is-invalid': form.errors.category_id }"
                                                    required
                                                >
                                                    <option value="">Select a category</option>
                                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                                <div v-if="form.errors.category_id" class="invalid-feedback">
                                                    {{ form.errors.category_id }}
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="priority" class="form-label text-white">Priority <span class="text-danger">*</span></label>
                                                <select 
                                                    id="priority"
                                                    class="form-select"
                                                    v-model="form.priority"
                                                    :class="{ 'is-invalid': form.errors.priority }"
                                                    required
                                                >
                                                    <option value="low">Low</option>
                                                    <option value="medium">Medium</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                                <div v-if="form.errors.priority" class="invalid-feedback">
                                                    {{ form.errors.priority }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="subject" class="form-label text-white">Subject <span class="text-danger">*</span></label>
                                            <input
                                                id="subject"
                                                type="text"
                                                class="form-control"
                                                v-model="form.subject"
                                                :class="{ 'is-invalid': form.errors.subject }"
                                                placeholder="Brief description of your issue"
                                                required
                                            />
                                            <div v-if="form.errors.subject" class="invalid-feedback">
                                                {{ form.errors.subject }}
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label for="content" class="form-label text-white">Message <span class="text-danger">*</span></label>
                                            <textarea
                                                id="content"
                                                class="form-control"
                                                v-model="form.content"
                                                :class="{ 'is-invalid': form.errors.content }"
                                                rows="6"
                                                placeholder="Please provide as much detail as possible..."
                                                required
                                            ></textarea>
                                            <div v-if="form.errors.content" class="invalid-feedback">
                                                {{ form.errors.content }}
                                            </div>
                                        </div>

                                        <!-- Cloudflare Turnstile -->
                                        <div v-if="turnstileEnabled" class="mb-4">
                                            <div id="cf-turnstile"></div>
                                            <div v-if="form.errors['cf-turnstile-response']" class="text-danger small mt-1">
                                                {{ form.errors['cf-turnstile-response'] }}
                                            </div>
                                        </div>

                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-warning btn-lg text-dark fw-bold" :disabled="form.processing">
                                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </span>
                                                Submit Ticket
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Contact Information -->
                            <div class="text-center mt-5">
                                <h3 class="h5 text-white mb-4">Other Ways to Reach Us</h3>
                                <div class="row g-4">
                                    <div class="col-md-4">
                                        <div class="p-4 rounded" style="background-color: rgba(255, 255, 255, 0.05);">
                                            <i class="bi bi-envelope text-warning fs-2 mb-3 d-block"></i>
                                            <h5 class="text-white mb-2">Email</h5>
                                            <p class="text-white-50 small mb-0">
                                                <a href="mailto:support@wewingames.com" class="text-white-50 text-decoration-none">
                                                    support@wewingames.com
                                                </a>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-4 rounded" style="background-color: rgba(255, 255, 255, 0.05);">
                                            <i class="bi bi-clock text-warning fs-2 mb-3 d-block"></i>
                                            <h5 class="text-white mb-2">Response Time</h5>
                                            <p class="text-white-50 small mb-0">24-48 hours</p>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-4 rounded" style="background-color: rgba(255, 255, 255, 0.05);">
                                            <i class="bi bi-question-circle text-warning fs-2 mb-3 d-block"></i>
                                            <h5 class="text-white mb-2">FAQ</h5>
                                            <p class="small mb-0">
                                                <a href="#faq" @click="scrollToFAQ" class="text-warning text-decoration-none">View common questions</a>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
/* Form control styles */
.form-control, .form-select {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: #2e4057;
    color: white;
}

.form-control:focus, .form-select:focus {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: #ffc107;
    color: white;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.6);
    opacity: 0.8;
}

.form-select option {
    background-color: #1a2332;
    color: white;
}

/* Ensure the first disabled option is visible */
.form-select option[value=""] {
    color: rgba(255, 255, 255, 0.6);
}
</style>