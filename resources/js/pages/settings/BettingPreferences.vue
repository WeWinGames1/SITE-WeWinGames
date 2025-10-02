<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

interface Props {
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user.data;

const form = useForm({
    favorite_team: user.favorite_team || '',
    favorite_sport: user.favorite_sport || '',
    primary_betting_app: user.primary_betting_app || '',
});

const submit = () => {
    form.patch(route('betting-preferences.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <CustomerLayout>
        <Head title="Betting Preferences" />

        <div class="container py-4">
            <!-- Settings Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Settings</h2>
                    <nav class="nav nav-pills">
                        <Link :href="route('profile.edit')" class="nav-link">
                            <i class="bi bi-person me-2"></i>Profile
                        </Link>
                        <Link :href="route('betting-preferences.edit')" class="nav-link active">
                            <i class="bi bi-graph-up me-2"></i>Betting Preferences
                        </Link>
                        <Link :href="route('billing.edit')" class="nav-link">
                            <i class="bi bi-credit-card me-2"></i>Billing
                        </Link>
                        <Link :href="route('password.edit')" class="nav-link">
                            <i class="bi bi-shield-lock me-2"></i>Security
                        </Link>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Betting Preferences</h5>
                            <p class="text-muted mb-0 small">Customize your betting experience with your preferences</p>
                        </div>
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <!-- Favorite Sports Team -->
                                <div class="mb-4">
                                    <label for="favorite_team" class="form-label">
                                        <i class="bi bi-star me-2 text-warning"></i>
                                        Favorite Sports Team
                                    </label>
                                    <input
                                        id="favorite_team"
                                        type="text"
                                        class="form-control"
                                        v-model="form.favorite_team"
                                        autocomplete="off"
                                        placeholder="e.g. Los Angeles Lakers"
                                    />
                                    <div class="form-text text-white">
                                        Tell us your favorite team to help us personalize your experience
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.favorite_team" />
                                </div>

                                <!-- Favorite Sport to Bet -->
                                <div class="mb-4">
                                    <label for="favorite_sport" class="form-label">
                                        <i class="bi bi-trophy me-2 text-primary"></i>
                                        Favorite Sport to Bet
                                    </label>
                                    <select id="favorite_sport" class="form-select" v-model="form.favorite_sport">
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
                                    <div class="form-text text-white">
                                        We'll prioritize showing you picks for your favorite sports
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.favorite_sport" />
                                </div>

                                <!-- Primary Betting App -->
                                <div class="mb-4">
                                    <label for="primary_betting_app" class="form-label">
                                        <i class="bi bi-phone me-2 text-success"></i>
                                        Primary Betting App
                                    </label>
                                    <select id="primary_betting_app" class="form-select" v-model="form.primary_betting_app">
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
                                    <div class="form-text text-white">
                                        Know which app you use most? We can tailor our recommendations
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.primary_betting_app" />
                                </div>

                                <!-- Info Alert -->
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Why we ask:</strong> Your preferences help us personalize your dashboard,
                                    show you relevant picks, and improve your overall betting experience.
                                </div>

                                <!-- Save Button -->
                                <div class="d-flex align-items-center gap-3">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="form.processing">
                                            <span class="spinner-border spinner-border-sm me-2"></span>
                                            Saving...
                                        </span>
                                        <span v-else>
                                            <i class="bi bi-check-circle me-2"></i>
                                            Save Preferences
                                        </span>
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

                    <!-- Additional Info Card -->
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3">
                                <i class="bi bi-lightbulb me-2 text-warning"></i>
                                Pro Tips
                            </h6>
                            <ul class="mb-0">
                                <li class="mb-2">Setting your favorite sport helps us show you the most relevant picks first</li>
                                <li class="mb-2">Your favorite team information can help us avoid showing you conflicting picks</li>
                                <li>Knowing your primary betting app helps us provide app-specific tips and bonuses</li>
                            </ul>
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
