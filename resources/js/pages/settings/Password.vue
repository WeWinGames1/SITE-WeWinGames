<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: any) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                if (passwordInput.value instanceof HTMLInputElement) {
                    passwordInput.value.focus();
                }
            }

            if (errors.current_password) {
                form.reset('current_password');
                if (currentPasswordInput.value instanceof HTMLInputElement) {
                    currentPasswordInput.value.focus();
                }
            }
        },
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Security Settings" />

        <div class="container py-4">
            <!-- Settings Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Settings</h2>
                    <nav class="nav nav-pills">
                        <Link :href="route('profile.edit')" class="nav-link"> <i class="bi bi-person me-2"></i>Profile </Link>
                        <Link :href="route('betting-preferences.edit')" class="nav-link"> <i class="bi bi-graph-up me-2"></i>Betting Preferences </Link>
                        <Link :href="route('billing.edit')" class="nav-link"> <i class="bi bi-credit-card me-2"></i>Billing </Link>
                        <Link :href="route('password.edit')" class="nav-link active"> <i class="bi bi-shield-lock me-2"></i>Security </Link>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Update Password</h5>
                            <p class="text-muted mb-0 small">Ensure your account is using a long, random password to stay secure.</p>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="updatePassword">
                                <!-- Current Password -->
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        v-model="form.current_password"
                                        type="password"
                                        class="form-control"
                                        autocomplete="current-password"
                                    />
                                    <InputError :message="form.errors.current_password" class="mt-2" />
                                </div>

                                <!-- New Password -->
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input
                                        id="password"
                                        ref="passwordInput"
                                        v-model="form.password"
                                        type="password"
                                        class="form-control"
                                        autocomplete="new-password"
                                    />
                                    <InputError :message="form.errors.password" class="mt-2" />
                                </div>

                                <!-- Confirm Password -->
                                <div class="mb-4">
                                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        class="form-control"
                                        autocomplete="new-password"
                                    />
                                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                                </div>

                                <!-- Submit Button -->
                                <div class="d-flex align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Updating...
                                        </span>
                                        <span v-else>Update Password</span>
                                    </button>

                                    <Transition
                                        enter-active-class="transition ease-in-out"
                                        enter-from-class="opacity-0"
                                        leave-active-class="transition ease-in-out"
                                        leave-to-class="opacity-0"
                                    >
                                        <span v-show="form.recentlySuccessful" class="text-success">
                                            <i class="bi bi-check-circle me-1"></i>Password updated successfully
                                        </span>
                                    </Transition>
                                </div>
                            </form>
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
