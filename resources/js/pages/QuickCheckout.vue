<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

declare global {
    interface Window {
        dataLayer: Record<string, any>[];
        turnstile: any;
    }
}

interface Product {
    price_id: string;
    product_id: string;
    amount: number;
    tier: string;
    billing_period: string;
    features: string[];
}

interface Props {
    selectedPlan: {
        tier: string;
        name: string;
        price: string;
        priceAmount: number;
        period: string;
        priceId: string;
        productId: string;
        features: string[];
    };
    allProducts: Record<string, Record<string, Product>>;
    stripeKey: string;
    discountCode?: string;
}

const props = defineProps<Props>();
const page = usePage();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    payment_method: '',
    price_id: props.selectedPlan.priceId,
    coupon: props.discountCode || '',
    website: '',
    timestamp: 0,
    'cf-turnstile-response': '',
});

// Plan selection
const selectedPeriod = ref<'daily' | 'weekly' | 'monthly'>(props.selectedPlan.period as any);
const selectedTier = ref(props.selectedPlan.tier);

// Computed current plan
const currentPlan = computed(() => {
    const periodProducts = props.allProducts[selectedPeriod.value];
    if (!periodProducts) return props.selectedPlan;
    const product = periodProducts[selectedTier.value.toLowerCase()];
    if (!product) return props.selectedPlan;
    return {
        tier: product.tier,
        name: product.tier.charAt(0).toUpperCase() + product.tier.slice(1),
        price: '$' + parseFloat(product.amount).toFixed(0),
        priceAmount: parseFloat(product.amount),
        period: product.billing_period,
        priceId: product.price_id,
        productId: product.product_id,
        features: product.features || [],
    };
});

// Update form price_id when plan changes
watch(
    () => currentPlan.value.priceId,
    (newPriceId) => {
        form.price_id = newPriceId;
    },
);

// Stripe Elements
const stripe = ref<any>(null);
const elements = ref<any>(null);
const cardElement = ref<any>(null);
const cardError = ref('');
const processing = ref(false);

// Discount code
const discount = ref<any>(null);
const validatingCoupon = ref(false);

// Turnstile
const turnstileEnabled = ref(false);
const turnstileSiteKey = ref('');
const turnstileWidget = ref<string | null>(null);
const turnstileError = ref('');
const turnstileLoading = ref(true);
const turnstileRendered = ref(false);

// Client validation errors
const clientErrors = ref({
    name: '',
    email: '',
    phone: '',
});

// Total with discount
const total = computed(() => {
    const basePrice = currentPlan.value.priceAmount;
    if (!discount.value) return basePrice;

    if (discount.value.percent_off) {
        return basePrice * (1 - discount.value.percent_off / 100);
    } else if (discount.value.amount_off) {
        return Math.max(0, basePrice - discount.value.amount_off / 100);
    }
    return basePrice;
});

// Validation functions
const validateName = (value: string): string => {
    if (!value?.trim()) return 'Name is required';
    if (value.length < 2) return 'Name must be at least 2 characters';
    if (!/^[a-zA-Z\s\-\.]+$/.test(value)) return 'Name can only contain letters, spaces, hyphens, and periods';
    return '';
};

const validateEmail = (value: string): string => {
    if (!value?.trim()) return 'Email is required';
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(value)) return 'Please enter a valid email address';
    return '';
};

const validatePhone = (value: string): string => {
    if (!value?.trim()) return 'Phone number is required';
    const phoneRegex = /^[+]?[(]?[0-9]{1,3}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,4}[-\s\.]?[0-9]{1,9}$/;
    if (!phoneRegex.test(value)) return 'Please enter a valid phone number';
    return '';
};

// Watchers for validation
watch(
    () => form.name,
    (value) => {
        clientErrors.value.name = value ? validateName(value) : '';
    },
);
watch(
    () => form.email,
    (value) => {
        clientErrors.value.email = value ? validateEmail(value) : '';
    },
);
watch(
    () => form.phone,
    (value) => {
        clientErrors.value.phone = value ? validatePhone(value) : '';
    },
);

// Coupon validation
const validateCoupon = async () => {
    if (!form.coupon) {
        discount.value = null;
        return;
    }

    validatingCoupon.value = true;
    try {
        const response = await axios.post(route('quick-checkout.validate-coupon'), {
            code: form.coupon,
            product_id: currentPlan.value.productId,
        });

        if (response.data.valid) {
            discount.value = response.data.discount;
            form.clearErrors('coupon');
        } else {
            form.errors.coupon = response.data.message || 'Invalid discount code';
            discount.value = null;
        }
    } catch (error) {
        form.errors.coupon = 'Invalid discount code';
        discount.value = null;
    } finally {
        validatingCoupon.value = false;
    }
};

const clearDiscount = () => {
    form.coupon = '';
    discount.value = null;
    form.clearErrors('coupon');
};

// Turnstile rendering
const renderTurnstile = (container: HTMLElement) => {
    if (turnstileRendered.value) return;

    try {
        turnstileWidget.value = window.turnstile.render(container, {
            sitekey: turnstileSiteKey.value,
            theme: 'dark',
            callback: (token: string) => {
                form['cf-turnstile-response'] = token;
                turnstileError.value = '';
            },
            'expired-callback': () => {
                form['cf-turnstile-response'] = '';
                turnstileError.value = 'Token expired - please complete verification again';
            },
            'error-callback': () => {
                turnstileError.value = 'Verification error - please refresh the page';
            },
        });
        turnstileLoading.value = false;
        turnstileRendered.value = true;
    } catch (error: any) {
        turnstileError.value = error.message || 'Failed to load verification';
        turnstileLoading.value = false;
    }
};

onMounted(async () => {
    form.timestamp = Math.floor(Date.now() / 1000);

    // Initialize Stripe
    stripe.value = await loadStripe(props.stripeKey);
    elements.value = stripe.value.elements();

    cardElement.value = elements.value.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#ffffff',
                '::placeholder': { color: '#6c757d' },
            },
        },
    });

    await nextTick();
    const cardDiv = document.getElementById('card-element');
    if (cardDiv) {
        cardElement.value.mount('#card-element');
        cardElement.value.on('change', (event: any) => {
            cardError.value = event.error ? event.error.message : '';
        });
    }

    // Initialize Turnstile
    const pageProps = page.props as any;
    const envEnabled = pageProps.env?.TURNSTILE_ENABLED;
    turnstileEnabled.value = envEnabled === true || envEnabled === 'true' || envEnabled === 1 || envEnabled === '1';
    turnstileSiteKey.value = pageProps.env?.TURNSTILE_SITE_KEY || '';

    if (turnstileEnabled.value && turnstileSiteKey.value) {
        await nextTick();
        const checkTurnstile = () => {
            if (window.turnstile?.render) {
                const container = document.getElementById('cf-turnstile');
                if (container) {
                    renderTurnstile(container);
                }
            } else {
                setTimeout(checkTurnstile, 100);
            }
        };
        checkTurnstile();
    } else {
        turnstileLoading.value = false;
    }

    // Validate initial discount code if present
    if (form.coupon) {
        await validateCoupon();
    }
});

const submit = async () => {
    // Client-side validation
    clientErrors.value.name = validateName(form.name);
    clientErrors.value.email = validateEmail(form.email);
    clientErrors.value.phone = validatePhone(form.phone);

    if (clientErrors.value.name || clientErrors.value.email || clientErrors.value.phone) {
        return;
    }

    processing.value = true;
    cardError.value = '';

    try {
        // Create payment method
        const { error, paymentMethod } = await stripe.value.createPaymentMethod({
            type: 'card',
            card: cardElement.value,
            billing_details: {
                name: form.name,
                email: form.email,
                phone: form.phone,
            },
        });

        if (error) {
            cardError.value = error.message;
            processing.value = false;
            return;
        }

        form.payment_method = paymentMethod.id;

        form.post(route('quick-checkout.process'), {
            onSuccess: () => {
                // Track purchase
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: 'purchase',
                    ecommerce: {
                        value: total.value,
                        currency: 'USD',
                        items: [{ item_name: currentPlan.value.name + ' Plan', price: total.value }],
                    },
                });

                // Reddit Pixel
                const pixelId = (page.props as any).env?.REDDIT_PIXEL_ID;
                if ((window as any).rdt && pixelId) {
                    (window as any).rdt('init', pixelId, {
                        email: form.email,
                        phoneNumber: form.phone,
                    });
                    (window as any).rdt('track', 'Purchase', {
                        currency: 'USD',
                        value: total.value,
                    });
                }
            },
            onError: () => {
                if (form.errors.payment) {
                    cardError.value = form.errors.payment;
                }
            },
            onFinish: () => {
                processing.value = false;
                if (turnstileEnabled.value && window.turnstile && turnstileWidget.value) {
                    window.turnstile.reset(turnstileWidget.value);
                }
            },
        });
    } catch (error: any) {
        cardError.value = 'An error occurred. Please try again.';
        processing.value = false;
    }
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Quick Checkout" />

        <div class="min-vh-100 d-flex align-items-center" style="background: linear-gradient(135deg, #7c3aed 0%, #111827 100%)">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="text-center mb-4">
                            <h1 class="display-4 fw-bold text-white mb-2">Start Winning Today</h1>
                            <p class="fs-5 text-gray-light">Get instant access to premium picks</p>
                        </div>

                        <div class="row g-4">
                            <!-- Order Summary -->
                            <div class="col-lg-5 order-lg-2">
                                <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                                    <div class="card-header bg-transparent border-bottom border-secondary">
                                        <h5 class="mb-0 text-white">Order Summary</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Plan Selection -->
                                        <div class="mb-4">
                                            <label class="form-label text-white small mb-2">Billing Period</label>
                                            <div class="btn-group w-100" role="group">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm"
                                                    :class="selectedPeriod === 'monthly' ? 'btn-primary' : 'btn-outline-secondary'"
                                                    @click="selectedPeriod = 'monthly'"
                                                >
                                                    Monthly
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm"
                                                    :class="selectedPeriod === 'weekly' ? 'btn-primary' : 'btn-outline-secondary'"
                                                    @click="selectedPeriod = 'weekly'"
                                                >
                                                    Weekly
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm"
                                                    :class="selectedPeriod === 'daily' ? 'btn-primary' : 'btn-outline-secondary'"
                                                    @click="selectedPeriod = 'daily'"
                                                >
                                                    Daily
                                                </button>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label text-white small mb-2">Plan</label>
                                            <div class="d-flex flex-column gap-2">
                                                <button
                                                    v-for="tier in ['gold', 'platinum']"
                                                    :key="tier"
                                                    type="button"
                                                    class="btn text-start"
                                                    :class="selectedTier === tier ? 'btn-primary' : 'btn-outline-secondary'"
                                                    @click="selectedTier = tier"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span class="fw-semibold">{{ tier.charAt(0).toUpperCase() + tier.slice(1) }}</span>
                                                        <span v-if="allProducts[selectedPeriod]?.[tier]">
                                                            ${{ allProducts[selectedPeriod][tier].amount }}
                                                        </span>
                                                    </div>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Selected Plan Details -->
                                        <div class="d-flex justify-content-between mb-3">
                                            <div>
                                                <h6 class="mb-0 text-white">{{ currentPlan.name }} Plan</h6>
                                                <small class="text-gray-light">Billed {{ currentPlan.period }}</small>
                                            </div>
                                            <span class="fw-bold text-white">{{ currentPlan.price }}</span>
                                        </div>

                                        <div v-if="discount" class="d-flex justify-content-between text-success mb-3">
                                            <span>Discount ({{ form.coupon }})</span>
                                            <span
                                                >-{{
                                                    discount.percent_off ? discount.percent_off + '%' : '$' + (discount.amount_off / 100).toFixed(2)
                                                }}</span
                                            >
                                        </div>

                                        <hr class="border-secondary" />

                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0 text-white">Total</h6>
                                            <h5 class="mb-0 text-white">${{ total.toFixed(2) }}</h5>
                                        </div>

                                        <!-- Features -->
                                        <div class="mt-4 pt-3 border-top border-secondary" v-if="currentPlan.features?.length">
                                            <h6 class="text-white small mb-3">What's included:</h6>
                                            <ul class="list-unstyled mb-0">
                                                <li
                                                    v-for="feature in currentPlan.features.slice(0, 4)"
                                                    :key="feature"
                                                    class="mb-2 small text-gray-light"
                                                >
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    {{ feature }}
                                                </li>
                                            </ul>
                                        </div>

                                        <div class="mt-3">
                                            <small class="text-gray-light d-block">
                                                <i class="bi bi-shield-check me-1"></i>
                                                Secure payment powered by Stripe
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Form -->
                            <div class="col-lg-7 order-lg-1">
                                <div class="card" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border)">
                                    <div class="card-body p-4">
                                        <h5 class="text-white mb-4">Your Information</h5>

                                        <form @submit.prevent="submit">
                                            <div class="mb-3">
                                                <label for="name" class="form-label text-white fw-medium">Full Name</label>
                                                <input
                                                    id="name"
                                                    type="text"
                                                    class="form-control form-control-lg"
                                                    :class="{ 'is-invalid': form.errors.name || clientErrors.name }"
                                                    v-model="form.name"
                                                    placeholder="John Doe"
                                                    required
                                                    autocomplete="name"
                                                />
                                                <div v-if="form.errors.name || clientErrors.name" class="invalid-feedback d-block">
                                                    {{ form.errors.name || clientErrors.name }}
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="email" class="form-label text-white fw-medium">Email Address</label>
                                                <input
                                                    id="email"
                                                    type="email"
                                                    class="form-control form-control-lg"
                                                    :class="{ 'is-invalid': form.errors.email || clientErrors.email }"
                                                    v-model="form.email"
                                                    placeholder="you@example.com"
                                                    required
                                                    autocomplete="email"
                                                />
                                                <div
                                                    v-if="form.errors.email || clientErrors.email"
                                                    class="invalid-feedback d-block"
                                                    v-html="form.errors.email || clientErrors.email"
                                                ></div>
                                            </div>

                                            <div class="mb-3">
                                                <label for="phone" class="form-label text-white fw-medium">Phone Number</label>
                                                <input
                                                    id="phone"
                                                    type="tel"
                                                    class="form-control form-control-lg"
                                                    :class="{ 'is-invalid': form.errors.phone || clientErrors.phone }"
                                                    v-model="form.phone"
                                                    placeholder="+1 (555) 123-4567"
                                                    required
                                                    autocomplete="tel"
                                                />
                                                <div v-if="form.errors.phone || clientErrors.phone" class="invalid-feedback d-block">
                                                    {{ form.errors.phone || clientErrors.phone }}
                                                </div>
                                            </div>

                                            <hr class="border-secondary my-4" />

                                            <h5 class="text-white mb-4">Payment Details</h5>

                                            <!-- Discount Code -->
                                            <div class="mb-3">
                                                <label class="form-label text-white fw-medium">Discount Code (Optional)</label>
                                                <div class="input-group">
                                                    <input
                                                        v-model="form.coupon"
                                                        type="text"
                                                        class="form-control"
                                                        :class="{ 'is-invalid': form.errors.coupon }"
                                                        placeholder="Enter discount code"
                                                        @keyup.enter="validateCoupon"
                                                    />
                                                    <button
                                                        type="button"
                                                        class="btn btn-outline-secondary"
                                                        @click="validateCoupon"
                                                        :disabled="validatingCoupon"
                                                    >
                                                        <span v-if="validatingCoupon" class="spinner-border spinner-border-sm"></span>
                                                        <span v-else>Apply</span>
                                                    </button>
                                                    <button v-if="discount" type="button" class="btn btn-outline-danger" @click="clearDiscount">
                                                        Clear
                                                    </button>
                                                </div>
                                                <div v-if="form.errors.coupon" class="invalid-feedback d-block">{{ form.errors.coupon }}</div>
                                                <div v-if="discount" class="text-success small mt-2">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    Discount applied!
                                                </div>
                                            </div>

                                            <!-- Card Element -->
                                            <div class="mb-3">
                                                <label class="form-label text-white fw-medium">Card Information</label>
                                                <div
                                                    id="card-element"
                                                    class="form-control"
                                                    style="padding: 12px; min-height: 40px; background: #1a1a2e"
                                                ></div>
                                            </div>

                                            <!-- Error Messages -->
                                            <div v-if="cardError || form.errors.payment" class="alert alert-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                {{ cardError || form.errors.payment }}
                                            </div>

                                            <!-- Honeypot -->
                                            <input type="text" v-model="form.website" style="position: absolute; left: -9999px" tabindex="-1" />

                                            <!-- Turnstile -->
                                            <div v-if="turnstileEnabled" class="mb-3">
                                                <div v-if="turnstileLoading && !turnstileRendered" class="text-center py-3">
                                                    <div class="spinner-border spinner-border-sm text-primary"></div>
                                                    <div class="text-muted small mt-2">Loading security verification...</div>
                                                </div>
                                                <div
                                                    id="cf-turnstile"
                                                    :data-sitekey="turnstileSiteKey"
                                                    data-theme="dark"
                                                    style="min-height: 65px"
                                                ></div>
                                                <div v-if="turnstileError" class="alert alert-danger small mt-2">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    {{ turnstileError }}
                                                </div>
                                            </div>

                                            <div class="alert alert-info">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Your subscription will renew automatically. You can cancel anytime from your billing settings.
                                            </div>

                                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3" :disabled="processing || form.processing">
                                                <span v-if="processing || form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ processing || form.processing ? 'Processing...' : `Pay $${total.toFixed(2)} & Get Access` }}
                                            </button>

                                            <p class="text-center text-gray-light small mt-3 mb-0">
                                                By subscribing, you agree to our
                                                <a href="/terms" class="text-purple text-decoration-none">Terms of Service</a>
                                                and
                                                <a href="/privacy-policy" class="text-purple text-decoration-none">Privacy Policy</a>
                                            </p>
                                        </form>

                                        <div class="text-center mt-4 pt-3 border-top border-secondary">
                                            <p class="text-gray-light small mb-2">Already have an account?</p>
                                            <a :href="route('login')" class="btn btn-outline-primary">
                                                <i class="bi bi-box-arrow-in-right me-2"></i>
                                                Sign In
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Benefits -->
                        <div class="row mt-5 text-center">
                            <div class="col-4">
                                <i class="bi bi-shield-check text-purple fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Secure Payment</p>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-lightning-charge text-purple fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Instant Access</p>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-x-circle text-purple fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Cancel Anytime</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
#card-element {
    min-height: 40px;
}
</style>
