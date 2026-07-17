<script setup lang="ts">
import TrustBadges from '@/components/TrustBadges.vue';
import { useTwitterPixel } from '@/composables/useTwitterPixel';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { loadStripe } from '@stripe/stripe-js';
import axios from 'axios';
import { computed, onMounted, ref, watch } from 'vue';

declare global {
    interface Window {
        dataLayer: Record<string, any>[];
    }
}

interface PaymentMethod {
    id: string;
    brand: string;
    last4: string;
    exp_month: number;
    exp_year: number;
    is_default: boolean;
}

interface Props {
    plan: {
        name: string;
        price: string;
        period: string;
        priceId: string;
        productId?: string;
    };
    stripeKey: string;
    discountCodes?: any[];
    paymentMethods?: PaymentMethod[];
    hasStripeId?: boolean;
}

const props = defineProps<Props>();
const page = usePage();
const { trackPurchase, trackCheckoutInitiated } = useTwitterPixel();
const form = useForm({
    payment_method: '',
    coupon: '',
    price_id: props.plan.priceId,
});

const stripe = ref<any>(null);
const elements = ref<any>(null);
const cardElement = ref<any>(null);
const processing = ref(false);
const cardError = ref('');
const discount = ref<any>(null);
const useExistingCard = ref(false);
const selectedPaymentMethod = ref('');
const showNewCardForm = ref(false);

// Check if user has payment methods
const hasPaymentMethods = computed(() => props.paymentMethods && props.paymentMethods.length > 0);

// Set default payment method if available
if (hasPaymentMethods.value) {
    const defaultMethod = props.paymentMethods?.find((m) => m.is_default);
    if (defaultMethod) {
        selectedPaymentMethod.value = defaultMethod.id;
        useExistingCard.value = true;
    }
}

// Calculate total with discount
const total = computed(() => {
    const basePrice = parseFloat(props.plan.price.replace('$', ''));
    if (!discount.value) return basePrice;

    if (discount.value.percent_off) {
        return basePrice * (1 - discount.value.percent_off / 100);
    } else if (discount.value.amount_off) {
        return Math.max(0, basePrice - discount.value.amount_off / 100);
    }
    return basePrice;
});

const initializeCardElement = async () => {
    if (!stripe.value) {
        stripe.value = await loadStripe(props.stripeKey);
    }

    if (!elements.value) {
        elements.value = stripe.value.elements();
    }

    // Create card element
    cardElement.value = elements.value.create('card', {
        style: {
            base: {
                fontSize: '16px',
                color: '#495057',
                '::placeholder': {
                    color: '#6c757d',
                },
            },
        },
    });

    // Wait for next tick to ensure element exists
    await new Promise((resolve) => setTimeout(resolve, 100));

    const cardElementDiv = document.getElementById('card-element');
    if (cardElementDiv) {
        cardElement.value.mount('#card-element');

        // Listen for card errors
        cardElement.value.on('change', (event: any) => {
            cardError.value = event.error ? event.error.message : '';
        });
    }
};

onMounted(async () => {
    // X (Twitter) Checkout Initiated conversion — the customer has begun checkout
    const checkoutUser = page.props.auth.user;
    trackCheckoutInitiated({
        value: total.value,
        currency: 'USD',
        email_address: checkoutUser?.email ?? null,
        phone_number: checkoutUser?.phone ?? null,
    });

    // Initialize Stripe (but not card element yet)
    stripe.value = await loadStripe(props.stripeKey);

    // If no existing payment methods, show new card form
    if (!hasPaymentMethods.value) {
        showNewCardForm.value = true;
        await initializeCardElement();
    }

    // Check for discount codes passed via URL
    const urlParams = new URLSearchParams(window.location.search);
    const discountCode = urlParams.get('discountCode');

    if (discountCode) {
        form.coupon = discountCode;
        await validateCoupon();
    }
    // Note: Auto-applied FIRSTMONTH50 discount has been removed.
    // Customers can manually enter discount codes if provided.

    // Check if we need to handle 3D Secure authentication
    const flash = page.props.flash as any;
    if (flash?.requires_action && flash?.payment_intent_client_secret) {
        await handle3DSecure();
    }
});

// Track if FIRSTMONTH50 was auto-applied
const autoAppliedDiscount = ref(false);

// Watch for manual coupon changes to clear existing discount
watch(
    () => form.coupon,
    (newValue, oldValue) => {
        // Only clear if user is typing (not on initial load)
        if (oldValue !== undefined && newValue !== oldValue) {
            discount.value = null;
            form.clearErrors('coupon');
        }
    },
);

const validateCoupon = async () => {
    // Clear any previous errors
    form.clearErrors('coupon');

    if (!form.coupon) {
        discount.value = null;
        return;
    }

    try {
        const response = await axios.post(route('subscription.validate-coupon'), {
            code: form.coupon,
            product_id: props.plan.productId,
        });

        if (response.data.valid) {
            discount.value = response.data.discount;
            form.clearErrors('coupon');
            // Track if we're replacing an auto-applied discount
            if (autoAppliedDiscount.value && form.coupon !== 'FIRSTMONTH50') {
                autoAppliedDiscount.value = false;
            }
        } else {
            form.errors.coupon = response.data.message || 'Invalid discount code';
            discount.value = null;
            // Don't clear the field, let the user see what they typed
        }
    } catch (error) {
        form.errors.coupon = 'Invalid discount code';
        discount.value = null;
        // Don't clear the field, let the user see what they typed
    }
};

const clearDiscount = () => {
    form.coupon = '';
    discount.value = null;
    form.clearErrors('coupon');
    autoAppliedDiscount.value = false;
};

// Log client-side Stripe errors for debugging (fire and forget)
const logStripeError = (error: any, context: string) => {
    axios
        .post(route('subscription.log-client-error'), {
            error_code: error.code || null,
            error_message: error.message || 'Unknown error',
            error_type: error.type || null,
            context: context,
        })
        .catch(() => {
            // Silently fail - don't interrupt user flow
        });
};

const handle3DSecure = async () => {
    processing.value = true;
    cardError.value = 'Completing authentication...';

    const flash = page.props.flash as any;

    try {
        const { error } = await stripe.value.confirmCardPayment(flash?.payment_intent_client_secret);

        if (error) {
            cardError.value = error.message || 'Authentication failed. Please try again.';
            // Log 3D Secure error for debugging
            logStripeError(error, '3DSecure');
            processing.value = false;
        } else {
            // Authentication successful - fire purchase tracking event
            const purchaseData = flash?.purchase_data;
            if (purchaseData) {
                window.dataLayer = window.dataLayer || [];
                window.dataLayer.push({ ecommerce: null });
                window.dataLayer.push({
                    event: 'purchase',
                    ecommerce: {
                        value: purchaseData.plan_price,
                        currency: 'USD',
                        items: [
                            {
                                item_name: purchaseData.plan_name,
                                price: purchaseData.plan_price,
                            },
                        ],
                    },
                });

                // Reddit Pixel - Advanced Matching for Purchase
                const pixelId = (page.props as any).env?.REDDIT_PIXEL_ID;
                if ((window as any).rdt && pixelId) {
                    const user = page.props.auth.user;
                    if (user) {
                        (window as any).rdt('init', pixelId, {
                            email: user.email,
                            phoneNumber: user.phone,
                        });
                    }
                    (window as any).rdt('track', 'Purchase', {
                        currency: 'USD',
                        value: purchaseData.plan_price,
                    });
                }

                // X (Twitter) purchase conversion. Only fire when there was an
                // actual charge (conversion_id = PaymentIntent id is null for
                // $0 / 100%-off / trial subs), matching the server-side exclusion
                // and keeping browser/server dedup aligned.
                if (purchaseData.conversion_id) {
                    trackPurchase({
                        value: purchaseData.plan_price,
                        currency: 'USD',
                        conversion_id: purchaseData.conversion_id,
                        email_address: page.props.auth.user?.email ?? null,
                    });
                }
            }
            // Redirect to dashboard
            window.location.href = route('dashboard');
        }
    } catch (error: any) {
        cardError.value = 'Authentication failed. Please try again.';
        // Log unexpected error
        logStripeError({ message: error?.message || 'Unknown 3DS error' }, '3DSecure');
        processing.value = false;
    }
};

const submit = async () => {
    processing.value = true;
    cardError.value = '';

    try {
        if (useExistingCard.value && selectedPaymentMethod.value) {
            // Use existing payment method
            form.payment_method = selectedPaymentMethod.value;
        } else {
            // Create new payment method
            const { error, paymentMethod } = await stripe.value.createPaymentMethod({
                type: 'card',
                card: cardElement.value,
            });

            if (error) {
                cardError.value = error.message;
                // Log client-side Stripe error for debugging
                logStripeError(error, 'createPaymentMethod');
                processing.value = false;
                return;
            }

            form.payment_method = paymentMethod.id;
        }

        // Submit to backend
        form.post(route('subscription.process'), {
            onSuccess: (response) => {
                // Check if 3D Secure is required
                const flash = page.props.flash as any;
                if (flash?.requires_action) {
                    handle3DSecure();
                } else {
                    // Google Tag Manager - Purchase Event
                    window.dataLayer = window.dataLayer || [];
                    window.dataLayer.push({ ecommerce: null });
                    window.dataLayer.push({
                        event: 'purchase',
                        ecommerce: {
                            value: total.value,
                            currency: 'USD',
                            items: [
                                {
                                    item_name: props.plan.name,
                                    price: total.value,
                                },
                            ],
                        },
                    });

                    // Reddit Pixel - Advanced Matching for Purchase (Subscription)
                    // Cast to any to access dynamic env property
                    const pixelId = (page.props as any).env?.REDDIT_PIXEL_ID;

                    if ((window as any).rdt && pixelId) {
                        const user = page.props.auth.user;
                        if (user) {
                            (window as any).rdt('init', pixelId, {
                                email: user.email,
                                phoneNumber: user.phone,
                            });
                        }
                        // Track Purchase with value
                        (window as any).rdt('track', 'Purchase', {
                            currency: 'USD',
                            value: total.value,
                        });
                    }

                    // X (Twitter) purchase conversion. Only fire when there was an
                    // actual charge (conversion_id = PaymentIntent id is null for
                    // $0 / 100%-off / trial subs), matching the server-side
                    // exclusion and keeping browser/server dedup aligned.
                    const xConversionId = (page.props.flash as any)?.purchase_data?.conversion_id ?? null;
                    if (xConversionId) {
                        trackPurchase({
                            value: total.value,
                            currency: 'USD',
                            conversion_id: xConversionId,
                            email_address: page.props.auth.user?.email ?? null,
                        });
                    }
                }
            },
            onError: () => {
                // Display payment error message
                if (form.errors.payment) {
                    cardError.value = form.errors.payment;
                } else {
                    cardError.value = 'Payment failed. Please check your card details and try again.';
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        });
    } catch (error: any) {
        cardError.value = 'An error occurred. Please try again.';
        // Log unexpected error
        logStripeError({ message: error?.message || 'Unknown submit error' }, 'submit');
        processing.value = false;
    }
};
</script>

<template>
    <CustomerLayout>
        <Head title="Checkout" />

        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="mb-4">Complete Your Subscription</h2>

                    <div class="row">
                        <!-- Order Summary -->
                        <div class="col-md-5 order-md-2 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Order Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <div>
                                            <h6 class="mb-0">{{ plan.name }} Plan</h6>
                                            <small class="text-muted">Billed {{ plan.period }}</small>
                                        </div>
                                        <span class="fw-bold">{{ plan.price }}</span>
                                    </div>

                                    <div v-if="discount" class="d-flex justify-content-between text-success mb-3">
                                        <span>Discount ({{ form.coupon }})</span>
                                        <span>-{{ discount.percent_off ? discount.percent_off + '%' : '$' + discount.amount_off / 100 }}</span>
                                    </div>

                                    <hr />

                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-0">Total</h6>
                                        <h5 class="mb-0">${{ total.toFixed(2) }}</h5>
                                    </div>

                                    <div class="mt-3">
                                        <small class="text-muted d-block">
                                            <i class="bi bi-shield-check me-1"></i>
                                            Secure payment powered by Stripe
                                        </small>
                                        <small v-if="discount && form.coupon === 'FIRSTMONTH50'" class="text-muted d-block mt-1">
                                            * First payment only. Renewal at {{ plan.price }}/{{ plan.period }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Benefits -->
                            <div class="card mt-3">
                                <div class="card-body">
                                    <h6 class="mb-3">What's included:</h6>
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            Instant access to all {{ plan.name }} picks
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            24/7 customer support
                                        </li>
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            Cancel anytime
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div class="col-md-7 order-md-1">
                            <form @submit.prevent="submit">
                                <!-- Discount Code -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <label class="form-label">Discount Code (Optional)</label>
                                        <div class="input-group">
                                            <input
                                                v-model="form.coupon"
                                                type="text"
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.coupon }"
                                                placeholder="Enter discount code"
                                                @keyup.enter="validateCoupon"
                                            />
                                            <button type="button" class="btn btn-outline-secondary" @click="validateCoupon">Apply</button>
                                            <button v-if="discount" type="button" class="btn btn-outline-danger" @click="clearDiscount">Clear</button>
                                            <div v-if="form.errors.coupon" class="invalid-feedback">
                                                {{ form.errors.coupon }}
                                            </div>
                                        </div>
                                        <div v-if="discount" class="text-success mt-2">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Discount applied: {{ form.coupon }} ({{
                                                discount.percent_off ? discount.percent_off + '% off' : '$' + discount.amount_off / 100 + ' off'
                                            }})
                                        </div>
                                        <small v-if="autoAppliedDiscount && form.coupon !== 'FIRSTMONTH50'" class="text-muted d-block mt-1">
                                            Note: Only one discount code can be used per subscription. Your new code has replaced the automatic first
                                            month discount.
                                        </small>
                                    </div>
                                </div>

                                <!-- Payment Details -->
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title mb-4">Payment Details</h5>

                                        <!-- Existing Payment Methods -->
                                        <div v-if="hasPaymentMethods" class="mb-4">
                                            <div class="form-check mb-3" v-for="method in paymentMethods" :key="method.id">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    :id="`method-${method.id}`"
                                                    :value="method.id"
                                                    v-model="selectedPaymentMethod"
                                                    @change="
                                                        useExistingCard = true;
                                                        showNewCardForm = false;
                                                    "
                                                />
                                                <label class="form-check-label d-flex align-items-center" :for="`method-${method.id}`">
                                                    <i class="bi bi-credit-card-fill text-primary me-2"></i>
                                                    <span>
                                                        <strong>{{ method.brand }}</strong> ending in {{ method.last4 }}
                                                        <small class="text-muted d-block">Expires {{ method.exp_month }}/{{ method.exp_year }}</small>
                                                    </span>
                                                    <span v-if="method.is_default" class="badge bg-success ms-auto">Default</span>
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input
                                                    class="form-check-input"
                                                    type="radio"
                                                    id="new-card"
                                                    value="new"
                                                    @change="
                                                        useExistingCard = false;
                                                        showNewCardForm = true;
                                                        initializeCardElement();
                                                    "
                                                />
                                                <label class="form-check-label" for="new-card">
                                                    <i class="bi bi-plus-circle text-primary me-2"></i>
                                                    <strong>Add a new card</strong>
                                                </label>
                                            </div>
                                        </div>

                                        <!-- New Card Form -->
                                        <div v-if="showNewCardForm" class="mb-3">
                                            <label class="form-label">Card Information</label>
                                            <div id="card-element" class="form-control" style="padding: 12px"></div>
                                        </div>

                                        <!-- Error Messages -->
                                        <div v-if="cardError || form.errors.payment" class="alert alert-danger mt-3">
                                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                            {{ cardError || form.errors.payment }}
                                        </div>

                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Your subscription will renew automatically. You can cancel anytime from your billing settings.
                                        </div>

                                        <button type="submit" class="btn btn-primary btn-lg w-100" :disabled="processing || form.processing">
                                            <span v-if="processing || form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            {{ processing || form.processing ? 'Processing...' : `Subscribe for $${total.toFixed(2)}` }}
                                        </button>

                                        <p class="text-center text-muted mt-3 mb-0">
                                            <small>
                                                By subscribing, you agree to our
                                                <a href="/terms" class="text-decoration-none">Terms of Service</a>
                                                and
                                                <a href="/privacy" class="text-decoration-none">Privacy Policy</a>
                                            </small>
                                        </p>

                                        <TrustBadges :compact="true" class="mt-3" />
                                    </div>
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
#card-element {
    min-height: 40px;
}
</style>
