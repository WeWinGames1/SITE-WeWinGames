<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

interface JobPosition {
    id: number;
    title: string;
}

interface Props {
    positions?: JobPosition[];
}

const props = defineProps<Props>();
const page = usePage();
const turnstileEnabled = ref(page.props.env?.TURNSTILE_ENABLED === 'true');
const turnstileSiteKey = ref(page.props.env?.TURNSTILE_SITE_KEY || '');
const turnstileWidget = ref<string | null>(null);

const form = useForm({
    first_name: '',
    last_name: '',
    phone: '',
    email: '',
    position: '',
    about: '',
    turnstile_token: '',
});

// Use dynamic positions if provided, otherwise fall back to hardcoded list
const positionOptions = computed(() => {
    if (props.positions && props.positions.length > 0) {
        return props.positions.map((p) => p.title);
    }
    return ['Sales Representative', 'Regional Manager', 'Business Development', 'Marketing', 'Customer Service', 'Other'];
});

const showSuccessMessage = ref(false);

onMounted(() => {
    if (turnstileEnabled.value && window.turnstile) {
        turnstileWidget.value = window.turnstile.render('#turnstile-container', {
            sitekey: turnstileSiteKey.value,
            callback: (token: string) => {
                form.turnstile_token = token;
            },
        });
    }
});

onBeforeUnmount(() => {
    if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
        window.turnstile.remove(turnstileWidget.value);
    }
});

const submitForm = () => {
    form.post('/careers/submit-resume', {
        onSuccess: () => {
            showSuccessMessage.value = true;
            form.reset();

            // Reset Turnstile if enabled
            if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }

            // Hide success message after 5 seconds
            setTimeout(() => {
                showSuccessMessage.value = false;
            }, 5000);
        },
        onFinish: () => {
            // Reset Turnstile on error as well
            if (form.errors && turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                window.turnstile.reset(turnstileWidget.value);
            }
        },
    });
};
</script>

<template>
    <div class="card shadow-lg" style="background-color: #1a2332; border: 2px solid #ffc107">
        <div class="card-header py-4" style="background-color: #0d1829; border-bottom: 2px solid #ffc107">
            <h3 class="h4 fw-bold text-white mb-0 text-center">
                <i class="bi bi-file-earmark-person me-2 text-warning"></i>
                Submit Your Resume
            </h3>
        </div>
        <div class="card-body p-5">
            <!-- Success Message -->
            <div v-if="showSuccessMessage" class="alert alert-success alert-dismissible fade show mb-4">
                <i class="bi bi-check-circle me-2"></i>
                <strong>Success!</strong> Your application has been submitted. We'll review it and get back to you soon.
                <button type="button" class="btn-close" @click="showSuccessMessage = false"></button>
            </div>

            <form @submit.prevent="submitForm">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-person me-1 text-warning"></i>
                            First Name <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.first_name"
                            type="text"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': form.errors.first_name }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            placeholder="John"
                            required
                        />
                        <InputError :message="form.errors.first_name" class="mt-2" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-person me-1 text-warning"></i>
                            Last Name <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.last_name"
                            type="text"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': form.errors.last_name }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            placeholder="Doe"
                            required
                        />
                        <InputError :message="form.errors.last_name" class="mt-2" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-telephone me-1 text-warning"></i>
                            Phone <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.phone"
                            type="tel"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': form.errors.phone }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            placeholder="(555) 123-4567"
                            required
                        />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-envelope me-1 text-warning"></i>
                            Email <span class="text-danger">*</span>
                        </label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': form.errors.email }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            placeholder="john.doe@example.com"
                            required
                        />
                        <InputError :message="form.errors.email" class="mt-2" />
                    </div>
                    <div class="col-12">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-briefcase me-1 text-warning"></i>
                            Position Applied For <span class="text-danger">*</span>
                        </label>
                        <select
                            v-model="form.position"
                            class="form-select form-select-lg"
                            :class="{ 'is-invalid': form.errors.position }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            required
                        >
                            <option value="">Select a position</option>
                            <option v-for="position in positionOptions" :key="position" :value="position">
                                {{ position }}
                            </option>
                        </select>
                        <InputError :message="form.errors.position" class="mt-2" />
                    </div>
                    <div class="col-12">
                        <label class="form-label text-white fw-semibold">
                            <i class="bi bi-file-text me-1 text-warning"></i>
                            Tell Us About Yourself <span class="text-danger">*</span>
                        </label>
                        <textarea
                            v-model="form.about"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.about }"
                            style="background-color: #0d1829; border: 1px solid #2e4057; color: white"
                            rows="5"
                            placeholder="Please include your relevant experience, which state/city you're interested in, and why you'd be a great fit for WeWinGames..."
                            required
                        ></textarea>
                        <InputError :message="form.errors.about" class="mt-2" />
                        <small class="form-text text-gray-light"> Include your experience, preferred location, and availability. </small>
                    </div>

                    <!-- Turnstile -->
                    <div v-if="turnstileEnabled" class="col-12">
                        <div id="turnstile-container" class="d-flex justify-content-center"></div>
                        <InputError :message="form.errors.turnstile_token" class="mt-2 text-center" />
                    </div>
                </div>

                <div class="d-flex justify-content-center mt-5">
                    <button type="submit" class="btn btn-warning btn-lg px-5 py-3 fw-bold" :disabled="form.processing">
                        <span v-if="!form.processing">
                            <i class="bi bi-send me-2"></i>
                            Submit Application
                        </span>
                        <span v-else>
                            <span class="spinner-border spinner-border-sm me-2"></span>
                            Submitting...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.form-control:focus,
.form-select:focus {
    background-color: #0d1829;
    border-color: #ffc107;
    color: white;
    box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
}

.form-control::placeholder {
    color: #6c757d;
}

.form-select option {
    background-color: #0d1829;
    color: white;
}

.text-gray-light {
    color: #adb5bd;
}
</style>
