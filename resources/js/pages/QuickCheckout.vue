<script setup lang="ts">
import OrderBumpModal from '@/components/OrderBumpModal.vue';
import TrustBadges from '@/components/TrustBadges.vue';
import { useTwitterPixel } from '@/composables/useTwitterPixel';
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
const { trackPurchase } = useTwitterPixel();

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
const selectedTier = ref(props.selectedPlan.tier.toLowerCase());

// Order bump modal
const showOrderBump = ref(false);
const pendingDailySelection = ref(false);
const orderBumpDiscountValid = ref(false);
const orderBumpDiscountAmount = ref(10); // Default, will be updated on validation

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

// Get prices for display
const getPrice = (period: string, tier: string): number => {
    return props.allProducts[period]?.[tier]?.amount || 0;
};

// Calculate monthly savings
const getMonthlySavings = (tier: string): string => {
    const monthlyPrice = getPrice('monthly', tier);
    const dailyPricePerMonth = getPrice('daily', tier) * 30;
    if (dailyPricePerMonth > monthlyPrice) {
        const savings = Math.round(((dailyPricePerMonth - monthlyPrice) / dailyPricePerMonth) * 100);
        return `Save ${savings}%`;
    }
    return '';
};

// Handle period selection with order bump
const selectPeriod = (period: 'daily' | 'weekly' | 'monthly') => {
    if (period === 'daily' && (selectedPeriod.value === 'weekly' || selectedPeriod.value === 'monthly') && orderBumpDiscountValid.value) {
        // Show order bump modal when downgrading to daily (only if discount valid)
        pendingDailySelection.value = true;
        showOrderBump.value = true;
    } else {
        selectedPeriod.value = period;
    }
};

const handleOrderBumpAccept = () => {
    // Switch to weekly and apply discount
    selectedPeriod.value = 'weekly';
    form.coupon = 'DAILYUPGRADE10';
    validateCoupon();
    showOrderBump.value = false;
    pendingDailySelection.value = false;
};

const handleOrderBumpDecline = () => {
    // Continue with daily selection
    selectedPeriod.value = 'daily';
    showOrderBump.value = false;
    pendingDailySelection.value = false;
};

const handleOrderBumpClose = () => {
    showOrderBump.value = false;
    pendingDailySelection.value = false;
};

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
const paymentElement = ref<any>(null);
const paymentError = ref('');
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

    // Initialize Stripe with Payment Element
    stripe.value = await loadStripe(props.stripeKey);
    elements.value = stripe.value.elements({
        mode: 'subscription',
        amount: Math.round(currentPlan.value.priceAmount * 100),
        currency: 'usd',
        paymentMethodTypes: ['card', 'cashapp'],
        paymentMethodCreation: 'manual',
        appearance: {
            theme: 'night',
            variables: {
                colorPrimary: '#FFC727',
                colorBackground: '#142348',
                colorText: '#ffffff',
                colorDanger: '#ef4444',
                fontFamily: 'Inter, system-ui, sans-serif',
                borderRadius: '8px',
            },
        },
    });

    paymentElement.value = elements.value.create('payment', {
        layout: 'tabs',
        wallets: {
            applePay: 'auto',
            googlePay: 'auto',
        },
    });

    await nextTick();
    const paymentDiv = document.getElementById('payment-element');
    if (paymentDiv) {
        paymentElement.value.mount('#payment-element');
        paymentElement.value.on('change', (event: any) => {
            paymentError.value = event.error ? event.error.message : '';
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

    // Pre-validate order bump discount code (DAILYUPGRADE10)
    try {
        const weeklyProduct = props.allProducts['weekly']?.['platinum'] || props.allProducts['weekly']?.['gold'];
        if (weeklyProduct) {
            const response = await axios.post(route('quick-checkout.validate-coupon'), {
                code: 'DAILYUPGRADE10',
                product_id: weeklyProduct.product_id,
            });
            if (response.data.valid) {
                orderBumpDiscountValid.value = true;
                if (response.data.discount?.amount_off) {
                    orderBumpDiscountAmount.value = response.data.discount.amount_off / 100;
                } else if (response.data.discount?.percent_off) {
                    // Calculate amount for display purposes
                    orderBumpDiscountAmount.value = Math.round(weeklyProduct.amount * (response.data.discount.percent_off / 100));
                }
            }
        }
    } catch {
        // Silent fail - order bump just won't show
        orderBumpDiscountValid.value = false;
    }
});

// Update Stripe Elements amount when plan OR discount changes.
// `total` already factors in the applied discount, so the wallet/Apple Pay
// deposit sheet reflects the discounted amount instead of the base price.
watch(total, async (newAmount) => {
    if (elements.value) {
        elements.value.update({
            amount: Math.max(50, Math.round(newAmount * 100)),
        });
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
    paymentError.value = '';

    try {
        // Submit payment element to get payment method
        const { error: submitError } = await elements.value.submit();
        if (submitError) {
            paymentError.value = submitError.message;
            processing.value = false;
            return;
        }

        // Create payment method
        const { error, paymentMethod } = await stripe.value.createPaymentMethod({
            elements: elements.value,
            params: {
                billing_details: {
                    name: form.name,
                    email: form.email,
                    phone: form.phone,
                },
            },
        });

        if (error) {
            paymentError.value = error.message;
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

                // X (Twitter) purchase conversion
                trackPurchase({ value: total.value, currency: 'USD' });
            },
            onError: () => {
                if (form.errors.payment) {
                    paymentError.value = form.errors.payment;
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
        // Show more specific error if available
        const message =
            error?.response?.data?.message || error?.response?.data?.errors?.payment || error?.message || 'An error occurred. Please try again.';
        paymentError.value = message;
        processing.value = false;
        console.error('Checkout error:', error);
    }
};
</script>

<template>
    <WelcomeLayout>
        <Head title="Quick Checkout" />

        <div class="min-vh-100 d-flex align-items-center bg-gradient-checkout">
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
                                <div class="card bg-navy-800 border-0">
                                    <div class="card-header bg-transparent border-bottom" style="border-color: rgba(255, 255, 255, 0.1) !important">
                                        <h5 class="mb-0 text-white">Choose Your Plan</h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Tier Selection - Platinum Featured -->
                                        <div class="mb-4">
                                            <label class="form-label text-white small mb-2">Select Tier</label>
                                            <div class="d-flex flex-column gap-3">
                                                <!-- Platinum Card (Featured) -->
                                                <div
                                                    class="tier-card p-3 rounded-3 cursor-pointer"
                                                    :class="selectedTier === 'platinum' ? 'tier-card-selected' : 'tier-card-default'"
                                                    @click="selectedTier = 'platinum'"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <span class="badge bg-gold-500 text-dark mb-2">BEST ROI</span>
                                                            <h5 class="fw-bold text-white mb-1">Platinum</h5>
                                                            <small class="text-gray-light">All sports, all picks, expert analysis</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fs-4 fw-bold text-gold-500">${{ getPrice(selectedPeriod, 'platinum') }}</div>
                                                            <small class="text-gray-light">/{{ selectedPeriod }}</small>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Gold Card -->
                                                <div
                                                    class="tier-card p-3 rounded-3 cursor-pointer"
                                                    :class="selectedTier === 'gold' ? 'tier-card-selected' : 'tier-card-default'"
                                                    @click="selectedTier = 'gold'"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h5 class="fw-bold text-white mb-1">Gold</h5>
                                                            <small class="text-gray-light">Premium picks, great value</small>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="fs-4 fw-bold text-white">${{ getPrice(selectedPeriod, 'gold') }}</div>
                                                            <small class="text-gray-light">/{{ selectedPeriod }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Period Selection - Monthly/Weekly Featured -->
                                        <div class="mb-4">
                                            <label class="form-label text-white small mb-2">Billing Period</label>
                                            <div class="d-flex flex-column gap-2">
                                                <!-- Monthly Card (Featured) -->
                                                <div
                                                    class="period-card p-3 rounded-3 cursor-pointer"
                                                    :class="selectedPeriod === 'monthly' ? 'period-card-selected' : 'period-card-default'"
                                                    @click="selectPeriod('monthly')"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-check-circle-fill text-success" v-if="selectedPeriod === 'monthly'"></i>
                                                            <i class="bi bi-circle text-muted" v-else></i>
                                                            <div>
                                                                <span class="fw-semibold text-white">Monthly</span>
                                                                <span v-if="getMonthlySavings(selectedTier)" class="badge bg-success ms-2 small">{{
                                                                    getMonthlySavings(selectedTier)
                                                                }}</span>
                                                            </div>
                                                        </div>
                                                        <span class="fw-bold text-white">${{ getPrice('monthly', selectedTier) }}</span>
                                                    </div>
                                                </div>

                                                <!-- Weekly Card -->
                                                <div
                                                    class="period-card p-3 rounded-3 cursor-pointer"
                                                    :class="selectedPeriod === 'weekly' ? 'period-card-selected' : 'period-card-default'"
                                                    @click="selectPeriod('weekly')"
                                                >
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <i class="bi bi-check-circle-fill text-success" v-if="selectedPeriod === 'weekly'"></i>
                                                            <i class="bi bi-circle text-muted" v-else></i>
                                                            <span class="fw-semibold text-white">Weekly</span>
                                                        </div>
                                                        <span class="fw-bold text-white">${{ getPrice('weekly', selectedTier) }}</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Daily as small link -->
                                            <div class="text-center mt-3">
                                                <button type="button" class="btn btn-link btn-sm text-muted p-0" @click="selectPeriod('daily')">
                                                    <i class="bi bi-clock me-1"></i>
                                                    Need just one day? Daily pass ${{ getPrice('daily', selectedTier) }}
                                                </button>
                                            </div>
                                        </div>

                                        <hr style="border-color: rgba(255, 255, 255, 0.1)" />

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

                                        <hr style="border-color: rgba(255, 255, 255, 0.1)" />

                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0 text-white">Total</h6>
                                            <h5 class="mb-0 text-gold-500">${{ total.toFixed(2) }}</h5>
                                        </div>

                                        <!-- Features -->
                                        <div
                                            class="mt-4 pt-3 border-top"
                                            style="border-color: rgba(255, 255, 255, 0.1) !important"
                                            v-if="currentPlan.features?.length"
                                        >
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
                                    </div>
                                </div>
                            </div>

                            <!-- Checkout Form -->
                            <div class="col-lg-7 order-lg-1">
                                <div class="card bg-navy-800 border-0">
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

                                            <hr style="border-color: rgba(255, 255, 255, 0.1)" class="my-4" />

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

                                            <!-- Payment Element -->
                                            <div class="mb-3">
                                                <label class="form-label text-white fw-medium">Payment Method</label>
                                                <div id="payment-element" class="payment-element-container"></div>
                                            </div>

                                            <!-- Error Messages -->
                                            <div v-if="paymentError || form.errors.payment" class="alert alert-danger">
                                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                                {{ paymentError || form.errors.payment }}
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

                                            <!-- Trust Badges -->
                                            <TrustBadges :compact="true" class="mb-3" />

                                            <div class="alert alert-info small">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Your subscription will renew automatically. You can cancel anytime from your billing settings.
                                            </div>

                                            <button type="submit" class="btn btn-gold btn-lg w-100 py-3" :disabled="processing || form.processing">
                                                <span v-if="processing || form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                                {{ processing || form.processing ? 'Processing...' : `Pay $${total.toFixed(2)} & Get Access` }}
                                            </button>

                                            <p class="text-center text-gray-light small mt-3 mb-0">
                                                By subscribing, you agree to our
                                                <a href="/terms" class="text-gold-500 text-decoration-none">Terms of Service</a>
                                                and
                                                <a href="/privacy-policy" class="text-gold-500 text-decoration-none">Privacy Policy</a>
                                            </p>
                                        </form>

                                        <div class="text-center mt-4 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.1) !important">
                                            <p class="text-gray-light small mb-2">Already have an account?</p>
                                            <a :href="route('login')" class="btn btn-outline-light">
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
                                <i class="bi bi-shield-check text-gold-500 fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Secure Payment</p>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-lightning-charge text-gold-500 fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Instant Access</p>
                            </div>
                            <div class="col-4">
                                <i class="bi bi-x-circle text-gold-500 fs-2 mb-2"></i>
                                <p class="text-gray-light small mb-0">Cancel Anytime</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Bump Modal -->
        <OrderBumpModal
            :show="showOrderBump"
            :current-period="'daily'"
            :current-tier="selectedTier"
            :upgrade-period="'weekly'"
            :current-price="getPrice('daily', selectedTier)"
            :upgrade-price="getPrice('weekly', selectedTier)"
            :discount-amount="orderBumpDiscountAmount"
            @accept="handleOrderBumpAccept"
            @decline="handleOrderBumpDecline"
            @close="handleOrderBumpClose"
        />
    </WelcomeLayout>
</template>

<style scoped>
.bg-gradient-checkout {
    background: linear-gradient(135deg, var(--navy-800) 0%, var(--ink-900) 100%);
}

.bg-navy-800 {
    background-color: var(--navy-800) !important;
}

.payment-element-container {
    min-height: 50px;
    padding: 12px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.tier-card {
    transition: all 0.2s ease;
}

.tier-card-default {
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid transparent;
}

.tier-card-default:hover {
    background: rgba(255, 255, 255, 0.08);
    border-color: rgba(255, 199, 39, 0.3);
}

.tier-card-selected {
    background: rgba(255, 199, 39, 0.1);
    border: 2px solid var(--gold-500);
}

.period-card {
    transition: all 0.2s ease;
}

.period-card-default {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.period-card-default:hover {
    background: rgba(255, 255, 255, 0.06);
}

.period-card-selected {
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.5);
}

.cursor-pointer {
    cursor: pointer;
}

.form-control {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.form-control:focus {
    background-color: rgba(255, 255, 255, 0.08);
    border-color: var(--gold-500);
    color: #fff;
    box-shadow: 0 0 0 0.25rem rgba(255, 199, 39, 0.15);
}

.form-control::placeholder {
    color: rgba(255, 255, 255, 0.4);
}

.input-group .btn-outline-secondary {
    border-color: rgba(255, 255, 255, 0.2);
    color: rgba(255, 255, 255, 0.8);
}

.input-group .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.3);
    color: #fff;
}

.alert-info {
    background: rgba(6, 182, 212, 0.1);
    border-color: rgba(6, 182, 212, 0.2);
    color: #67e8f9;
}
</style>
