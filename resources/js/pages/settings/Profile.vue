<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref, watch } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user.data;
const vapid_key = page.props.env?.VAPID_PUBLIC_KEY;

const form = useForm({
    name: user.name,
    email: user.email,
    phone: user.phone || '',
    notification_preferences: {
        email: user.notification_preferences?.email || false,
        push: user.notification_preferences?.push || false,
        sms: user.notification_preferences?.sms || false,
    },
});

// Account deletion form
const deleteForm = useForm({
    nameConfirmation: '',
});

const deleteButtonDisabled = ref(true);

// Watch for name confirmation changes
watch(
    () => deleteForm.nameConfirmation,
    (newVal) => {
        deleteButtonDisabled.value = newVal !== user.name;
    },
);

const subscribe = async () => {
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.ready;
        const permission = await Notification.requestPermission();
        if (permission === 'granted') {
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapid_key),
            });
            await axios.post('/api/push/subscribe', subscription);
        }
    }
};

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
}

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

onMounted(() => {
    if (form.notification_preferences.push) {
        subscribe();
    }
});

watch(
    () => form.notification_preferences.push,
    (newVal, oldVal) => {
        if (newVal && !oldVal) {
            subscribe();
        }
    },
);
</script>

<template>
    <CustomerLayout>
        <Head title="Profile Settings" />

        <div class="container py-4">
            <!-- Settings Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Settings</h2>
                    <nav class="nav nav-pills">
                        <Link :href="route('profile.edit')" class="nav-link active"> <i class="bi bi-person me-2"></i>Profile </Link>
                        <Link :href="route('betting-preferences.edit')" class="nav-link"> <i class="bi bi-graph-up me-2"></i>Betting Preferences </Link>
                        <Link :href="route('billing.edit')" class="nav-link"> <i class="bi bi-credit-card me-2"></i>Billing </Link>
                        <Link :href="route('password.edit')" class="nav-link"> <i class="bi bi-shield-lock me-2"></i>Security </Link>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Profile Information</h5>
                            <p class="text-muted mb-0 small">Update your name, email address, and notification preferences</p>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <!-- Name -->
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input
                                        id="name"
                                        type="text"
                                        class="form-control"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                        placeholder="Full name"
                                    />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email address</label>
                                    <input
                                        id="email"
                                        type="email"
                                        class="form-control"
                                        v-model="form.email"
                                        required
                                        autocomplete="username"
                                        placeholder="Email address"
                                    />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>

                                <!-- Phone Number -->
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input
                                        id="phone"
                                        type="tel"
                                        class="form-control"
                                        v-model="form.phone"
                                        required
                                        autocomplete="tel"
                                        placeholder="+1 (555) 123-4567"
                                    />
                                    <InputError class="mt-2" :message="form.errors.phone" />
                                </div>

                                <!-- Notification Preferences -->
                                <div class="mb-4">
                                    <label class="form-label">Notification Preferences</label>
                                    <div class="d-flex gap-4">
                                        <div class="form-check">
                                            <input
                                                type="checkbox"
                                                v-model="form.notification_preferences.email"
                                                class="form-check-input"
                                                id="emailNotifications"
                                            />
                                            <label class="form-check-label" for="emailNotifications"> Email Notifications </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                type="checkbox"
                                                v-model="form.notification_preferences.push"
                                                class="form-check-input"
                                                id="pushNotifications"
                                            />
                                            <label class="form-check-label" for="pushNotifications"> Push Notifications </label>
                                        </div>
                                        <div class="form-check">
                                            <input
                                                type="checkbox"
                                                v-model="form.notification_preferences.sms"
                                                class="form-check-input"
                                                id="smsNotifications"
                                            />
                                            <label class="form-check-label" for="smsNotifications"> SMS Notifications </label>
                                        </div>
                                    </div>
                                    <!-- SMS Opt-in Agreement Text -->
                                    <div v-if="form.notification_preferences.sms" class="alert alert-info mt-3 mb-0" style="font-size: 0.875rem;">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>SMS Agreement:</strong> You agree to receive recurring promotional messages. You also agree to our
                                        <a href="/terms-of-service" target="_blank" class="alert-link">Terms of Service</a> and
                                        <a href="/privacy-policy" target="_blank" class="alert-link">Privacy Policy</a>.
                                        Msg freq. varies. Msg & Data rates may apply. Reply STOP to end or HELP for help.
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.notification_preferences" />
                                </div>

                                <!-- Email Verification -->
                                <div v-if="mustVerifyEmail && !user.email_verified_at" class="alert alert-warning">
                                    <p class="mb-0">
                                        Your email address is unverified.
                                        <Link
                                            :href="route('verification.send')"
                                            method="post"
                                            as="button"
                                            class="btn btn-link p-0 text-decoration-underline"
                                        >
                                            Click here to resend the verification email.
                                        </Link>
                                    </p>
                                </div>

                                <div v-if="status === 'verification-link-sent'" class="alert alert-success">
                                    A new verification link has been sent to your email address.
                                </div>

                                <!-- Save Button -->
                                <div class="d-flex align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Saving...
                                        </span>
                                        <span v-else>Save Changes</span>
                                    </button>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <span v-show="form.recentlySuccessful" class="text-success">
                                            <i class="bi bi-check-circle me-1"></i>Saved successfully
                                        </span>
                                    </Transition>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Account Section -->
                    <div class="card mt-4 border-danger">
                        <div class="card-header bg-danger bg-opacity-10">
                            <h5 class="mb-0 text-danger">Delete Account</h5>
                            <p class="mb-0 small">Permanently delete your account and all of your data</p>
                        </div>
                        <div class="card-body">
                            <p>
                                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account,
                                please download any data or information that you wish to retain.
                            </p>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                Delete Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Account Modal -->
            <div class="modal fade" id="deleteAccountModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-danger bg-opacity-10">
                            <h5 class="modal-title text-danger">Delete Account</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> This action cannot be undone!
                            </div>

                            <p class="mb-3">Are you sure you want to delete your account? All of your data will be permanently deleted.</p>

                            <p class="mb-2">To confirm, please type your full name.</p>

                            <input
                                v-model="deleteForm.nameConfirmation"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': deleteForm.nameConfirmation && deleteForm.nameConfirmation !== user.name }"
                                placeholder="Type your full name to confirm"
                            />
                            <div class="invalid-feedback" v-if="deleteForm.nameConfirmation && deleteForm.nameConfirmation !== user.name">
                                Name doesn't match. Please type your full name exactly as shown above.
                            </div>
                            <InputError class="mt-2" :message="deleteForm.errors.nameConfirmation" />
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <Link
                                href="/profile"
                                method="delete"
                                as="button"
                                class="btn btn-danger"
                                :class="{ disabled: deleteButtonDisabled }"
                                :disabled="deleteButtonDisabled"
                                preserve-scroll
                            >
                                <i class="bi bi-trash me-2"></i>
                                Delete Account Permanently
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>

<style scoped>
.transition {
    transition-property: opacity;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

.opacity-0 {
    opacity: 0;
}
</style>
