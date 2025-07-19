<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, withDefaults } from 'vue';

interface Props {
    stats?: {
        total_users?: number;
        push_enabled?: number;
        email_enabled?: number;
        subscribers_by_tier?: Record<string, number>;
    };
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({
        total_users: 0,
        push_enabled: 0,
        email_enabled: 0,
        subscribers_by_tier: {}
    })
});

const activeTab = ref<'send' | 'test'>('send');

// Predefined icon options
const iconOptions = [
    { label: 'Default Logo', value: '/images/icons/icon-192x192.png', preview: '/images/icons/icon-96x96.png' },
    { label: 'Trophy', value: '/images/trophy.webp', preview: '/images/trophy.webp' },
    { label: 'Profit Picks', value: '/images/profit-picks.png', preview: '/images/profit-picks.png' },
    { label: 'Sports', value: '/images/sports.webp', preview: '/images/sports.webp' },
    { label: 'Gold Plan', value: '/images/plan-icon-gold.svg', preview: '/images/plan-icon-gold.svg' },
    { label: 'Silver Plan', value: '/images/plan-icon-silver.svg', preview: '/images/plan-icon-silver.svg' },
    { label: 'Platinum Plan', value: '/images/plan-icon-platinum.svg', preview: '/images/plan-icon-platinum.svg' },
];

const sendForm = useForm({
    title: '',
    body: '',
    url: '',
    icon: '/images/icons/icon-192x192.png',
    recipients: 'push_enabled',
    tier: 'silver',
});

const testForm = useForm({
    title: 'Test Notification',
    body: 'This is a test push notification from WeWinGames',
    url: '/',
    icon: '/images/icons/icon-192x192.png',
});

function validateSendForm(): boolean {
    // Clear previous errors
    sendForm.clearErrors();
    
    let isValid = true;
    const errors: Record<string, string> = {};
    
    // Required fields validation
    if (!sendForm.title || !sendForm.title.trim()) {
        errors.title = 'The title field is required.';
        isValid = false;
    } else if (sendForm.title.length > 255) {
        errors.title = 'The title may not be greater than 255 characters.';
        isValid = false;
    }
    
    if (!sendForm.body || !sendForm.body.trim()) {
        errors.body = 'The body field is required.';
        isValid = false;
    } else if (sendForm.body.length > 500) {
        errors.body = 'The body may not be greater than 500 characters.';
        isValid = false;
    }
    
    // Optional URL validation
    if (sendForm.url) {
        try {
            new URL(sendForm.url);
        } catch (e) {
            errors.url = 'The url must be a valid URL.';
            isValid = false;
        }
    }
    
    // Recipients validation
    if (!sendForm.recipients) {
        errors.recipients = 'The recipients field is required.';
        isValid = false;
    } else if (!['all', 'push_enabled', 'tier'].includes(sendForm.recipients)) {
        errors.recipients = 'The selected recipients value is invalid.';
        isValid = false;
    }
    
    // Tier validation (required if recipients is 'tier')
    if (sendForm.recipients === 'tier') {
        if (!sendForm.tier) {
            errors.tier = 'The tier field is required when recipients is tier.';
            isValid = false;
        } else if (!['silver', 'gold', 'platinum'].includes(sendForm.tier)) {
            errors.tier = 'The selected tier value is invalid.';
            isValid = false;
        }
    }
    
    // Set errors if any
    if (!isValid) {
        sendForm.setError(errors);
    }
    
    return isValid;
}

function validateTestForm(): boolean {
    // Clear previous errors
    testForm.clearErrors();
    
    let isValid = true;
    const errors: Record<string, string> = {};
    
    // Required fields validation
    if (!testForm.title || !testForm.title.trim()) {
        errors.title = 'The title field is required.';
        isValid = false;
    } else if (testForm.title.length > 255) {
        errors.title = 'The title may not be greater than 255 characters.';
        isValid = false;
    }
    
    if (!testForm.body || !testForm.body.trim()) {
        errors.body = 'The body field is required.';
        isValid = false;
    } else if (testForm.body.length > 500) {
        errors.body = 'The body may not be greater than 500 characters.';
        isValid = false;
    }
    
    // Optional URL validation
    if (testForm.url) {
        try {
            new URL(testForm.url);
        } catch (e) {
            errors.url = 'The url must be a valid URL.';
            isValid = false;
        }
    }
    
    // Set errors if any
    if (!isValid) {
        testForm.setError(errors);
    }
    
    return isValid;
}

function sendNotification() {
    if (!validateSendForm()) {
        return;
    }
    
    sendForm.post(route('admin.notifications.push.send'), {
        preserveScroll: false,
    });
}

function sendTestNotification() {
    if (!validateTestForm()) {
        return;
    }
    
    testForm.post(route('admin.notifications.push.test'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Send Push Notification" />
        
        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="mb-4 d-flex align-items-center">
                <Link 
                    :href="route('admin.notifications.push.index')" 
                    class="btn btn-link text-decoration-none me-3"
                >
                    <i class="bi bi-arrow-left"></i> Back
                </Link>
                <div>
                    <h1 class="h2 mb-0">Send Push Notification</h1>
                    <p class="text-muted mb-0">Send real-time notifications to your users</p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="mb-0">{{ (stats.total_users || 0).toLocaleString() }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Push Enabled</h6>
                            <h3 class="mb-0 text-success">{{ (stats.push_enabled || 0).toLocaleString() }}</h3>
                            <small class="text-muted" v-if="stats.total_users && stats.total_users > 0">
                                {{ (((stats.push_enabled || 0) / stats.total_users) * 100).toFixed(1) }}% of users
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Email Enabled</h6>
                            <h3 class="mb-0 text-info">{{ (stats.email_enabled || 0).toLocaleString() }}</h3>
                            <small class="text-muted" v-if="stats.total_users && stats.total_users > 0">
                                {{ (((stats.email_enabled || 0) / stats.total_users) * 100).toFixed(1) }}% of users
                            </small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">By Tier</h6>
                            <div class="small">
                                <div v-if="stats.subscribers_by_tier?.silver">
                                    <span class="badge bg-secondary me-1">Silver</span>
                                    {{ stats.subscribers_by_tier.silver }}
                                </div>
                                <div v-if="stats.subscribers_by_tier?.gold">
                                    <span class="badge bg-warning text-dark me-1">Gold</span>
                                    {{ stats.subscribers_by_tier.gold }}
                                </div>
                                <div v-if="stats.subscribers_by_tier?.platinum">
                                    <span class="badge bg-dark me-1">Platinum</span>
                                    {{ stats.subscribers_by_tier.platinum }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3">
                <li class="nav-item">
                    <button 
                        class="nav-link" 
                        :class="{ active: activeTab === 'send' }"
                        @click="activeTab = 'send'"
                        type="button"
                    >
                        Send Notification
                    </button>
                </li>
                <li class="nav-item">
                    <button 
                        class="nav-link" 
                        :class="{ active: activeTab === 'test' }"
                        @click="activeTab = 'test'"
                        type="button"
                    >
                        Test Notification
                    </button>
                </li>
            </ul>

            <!-- Send Notification Tab -->
            <div v-if="activeTab === 'send'" class="card">
                <div class="card-body">
                    <form @submit.prevent="sendNotification">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label required">Title</label>
                                    <input
                                        v-model="sendForm.title"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': sendForm.errors.title }"
                                        id="title"
                                        placeholder="New picks available!"
                                        maxlength="255"
                                        required
                                    />
                                    <div v-if="sendForm.errors.title" class="invalid-feedback">
                                        {{ sendForm.errors.title }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="body" class="form-label required">Message</label>
                                    <textarea
                                        v-model="sendForm.body"
                                        class="form-control"
                                        :class="{ 'is-invalid': sendForm.errors.body }"
                                        id="body"
                                        rows="3"
                                        placeholder="Check out today's expert picks for NBA games"
                                        maxlength="500"
                                        required
                                    ></textarea>
                                    <div class="form-text">
                                        {{ sendForm.body.length }}/500 characters
                                    </div>
                                    <div v-if="sendForm.errors.body" class="invalid-feedback">
                                        {{ sendForm.errors.body }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="url" class="form-label">Click Action URL</label>
                                    <input
                                        v-model="sendForm.url"
                                        type="url"
                                        class="form-control"
                                        :class="{ 'is-invalid': sendForm.errors.url }"
                                        id="url"
                                        placeholder="https://wewingames.com/todays-picks"
                                    />
                                    <div class="form-text">
                                        Where users go when they click the notification (optional)
                                    </div>
                                    <div v-if="sendForm.errors.url" class="invalid-feedback">
                                        {{ sendForm.errors.url }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notification Icon</label>
                                    <div class="row g-2">
                                        <div v-for="iconOption in iconOptions" :key="iconOption.value" class="col-auto">
                                            <label class="icon-selector-label">
                                                <input
                                                    v-model="sendForm.icon"
                                                    type="radio"
                                                    :value="iconOption.value"
                                                    name="sendIcon"
                                                    class="visually-hidden"
                                                />
                                                <div 
                                                    class="icon-selector"
                                                    :class="{ 'selected': sendForm.icon === iconOption.value }"
                                                >
                                                    <img 
                                                        :src="iconOption.preview" 
                                                        :alt="iconOption.label"
                                                        class="icon-preview"
                                                    />
                                                    <small class="d-block text-center mt-1">{{ iconOption.label }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div v-if="sendForm.errors.icon" class="invalid-feedback d-block">
                                        {{ sendForm.errors.icon }}
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Target Audience</h6>
                                        
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input
                                                    v-model="sendForm.recipients"
                                                    class="form-check-input"
                                                    type="radio"
                                                    value="all"
                                                    id="recipientsAll"
                                                />
                                                <label class="form-check-label" for="recipientsAll">
                                                    All users with push enabled
                                                </label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input
                                                    v-model="sendForm.recipients"
                                                    class="form-check-input"
                                                    type="radio"
                                                    value="push_enabled"
                                                    id="recipientsPush"
                                                />
                                                <label class="form-check-label" for="recipientsPush">
                                                    Push notification subscribers
                                                </label>
                                            </div>
                                            
                                            <div class="form-check">
                                                <input
                                                    v-model="sendForm.recipients"
                                                    class="form-check-input"
                                                    type="radio"
                                                    value="tier"
                                                    id="recipientsTier"
                                                />
                                                <label class="form-check-label" for="recipientsTier">
                                                    Specific subscription tier
                                                </label>
                                            </div>
                                        </div>

                                        <div v-if="sendForm.recipients === 'tier'" class="mb-3">
                                            <label class="form-label">Select Tier</label>
                                            <select v-model="sendForm.tier" class="form-select">
                                                <option value="silver">Silver</option>
                                                <option value="gold">Gold</option>
                                                <option value="platinum">Platinum</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="sendForm.processing"
                            >
                                <span v-if="sendForm.processing">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Sending...
                                </span>
                                <span v-else>
                                    <i class="bi bi-send me-2"></i>
                                    Send Notification
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Test Notification Tab -->
            <div v-if="activeTab === 'test'" class="card">
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Send a test notification to your own device. Make sure you have push notifications enabled in your profile settings.
                    </p>
                    
                    <form @submit.prevent="sendTestNotification">
                        <div class="row">
                            <div class="col-lg-8">
                                <div class="mb-3">
                                    <label for="testTitle" class="form-label">Title</label>
                                    <input
                                        v-model="testForm.title"
                                        type="text"
                                        class="form-control"
                                        :class="{ 'is-invalid': testForm.errors.title }"
                                        id="testTitle"
                                        maxlength="255"
                                        required
                                    />
                                    <div v-if="testForm.errors.title" class="invalid-feedback">
                                        {{ testForm.errors.title }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="testBody" class="form-label">Message</label>
                                    <textarea
                                        v-model="testForm.body"
                                        class="form-control"
                                        :class="{ 'is-invalid': testForm.errors.body }"
                                        id="testBody"
                                        rows="3"
                                        maxlength="500"
                                        required
                                    ></textarea>
                                    <div v-if="testForm.errors.body" class="invalid-feedback">
                                        {{ testForm.errors.body }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="testUrl" class="form-label">Click Action URL</label>
                                    <input
                                        v-model="testForm.url"
                                        type="url"
                                        class="form-control"
                                        :class="{ 'is-invalid': testForm.errors.url }"
                                        id="testUrl"
                                    />
                                    <div v-if="testForm.errors.url" class="invalid-feedback">
                                        {{ testForm.errors.url }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Notification Icon</label>
                                    <div class="row g-2">
                                        <div v-for="iconOption in iconOptions" :key="iconOption.value" class="col-auto">
                                            <label class="icon-selector-label">
                                                <input
                                                    v-model="testForm.icon"
                                                    type="radio"
                                                    :value="iconOption.value"
                                                    name="testIcon"
                                                    class="visually-hidden"
                                                />
                                                <div 
                                                    class="icon-selector"
                                                    :class="{ 'selected': testForm.icon === iconOption.value }"
                                                >
                                                    <img 
                                                        :src="iconOption.preview" 
                                                        :alt="iconOption.label"
                                                        class="icon-preview"
                                                    />
                                                    <small class="d-block text-center mt-1">{{ iconOption.label }}</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="testForm.processing"
                                >
                                    <span v-if="testForm.processing">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Sending...
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-bell me-2"></i>
                                        Send Test Notification
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.required::after {
    content: ' *';
    color: #dc3545;
}

.icon-selector-label {
    cursor: pointer;
}

.icon-selector {
    border: 2px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    text-align: center;
    transition: all 0.2s ease;
    background-color: #fff;
    min-width: 100px;
}

.icon-selector:hover {
    border-color: #0d6efd;
    background-color: #f8f9fa;
}

.icon-selector.selected {
    border-color: #0d6efd;
    background-color: #e7f1ff;
}

.icon-preview {
    width: 48px;
    height: 48px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

.icon-selector small {
    font-size: 0.75rem;
    color: #6c757d;
}

.icon-selector.selected small {
    color: #0d6efd;
    font-weight: 500;
}
</style>