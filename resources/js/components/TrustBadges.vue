<script setup lang="ts">
interface Props {
    variant?: 'horizontal' | 'vertical';
    showPaymentMethods?: boolean;
    compact?: boolean;
}

withDefaults(defineProps<Props>(), {
    variant: 'horizontal',
    showPaymentMethods: true,
    compact: false,
});
</script>

<template>
    <div :class="['trust-badges', variant === 'vertical' ? 'trust-badges-vertical' : 'trust-badges-horizontal']">
        <!-- Payment Methods -->
        <div v-if="showPaymentMethods" class="payment-methods mb-3">
            <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                <img src="/images/icons/visa.svg" alt="Visa" height="24" />
                <img src="/images/icons/mastercard.svg" alt="Mastercard" height="24" />
                <img src="/images/icons/amex.svg" alt="American Express" height="24" />
                <img src="/images/icons/apple-pay.svg" alt="Apple Pay" height="24" />
                <img src="/images/icons/google-pay.svg" alt="Google Pay" height="24" />
            </div>
        </div>

        <!-- Trust Badges -->
        <div :class="['d-flex align-items-center justify-content-center gap-3 flex-wrap', compact ? 'gap-2' : 'gap-3']">
            <!-- Stripe Badge -->
            <div class="trust-badge">
                <div class="d-flex align-items-center gap-1 px-2 py-1 rounded" style="background: rgba(99, 91, 255, 0.1)">
                    <i class="bi bi-shield-lock-fill text-primary"></i>
                    <span class="small text-muted">Powered by</span>
                    <strong class="small text-primary">Stripe</strong>
                </div>
            </div>

            <!-- SSL Secure -->
            <div class="trust-badge">
                <div class="d-flex align-items-center gap-1 px-2 py-1 rounded" style="background: rgba(16, 185, 129, 0.1)">
                    <i class="bi bi-lock-fill text-success"></i>
                    <span class="small text-success fw-medium">SSL Secure</span>
                </div>
            </div>

            <!-- 21+ Badge -->
            <div class="trust-badge">
                <img src="/images/legal-21plus.png" alt="21+ Only" :height="compact ? 24 : 32" />
            </div>

            <!-- Responsible Gaming -->
            <div v-if="!compact" class="trust-badge">
                <img src="/images/legal-rg.jpg" alt="Responsible Gaming" :height="compact ? 24 : 32" />
            </div>

            <!-- NCPG -->
            <div v-if="!compact" class="trust-badge">
                <img src="/images/legal-ncpg.png" alt="National Council on Problem Gambling" :height="compact ? 24 : 32" />
            </div>
        </div>

        <!-- Responsible Gaming Notice -->
        <div v-if="!compact" class="text-center mt-3">
            <small class="text-muted" style="font-size: 0.7rem">
                21+ | Gambling can be addictive, please play responsibly | If you need help, call 1-800-GAMBLER
            </small>
        </div>
    </div>
</template>

<style scoped>
.trust-badges {
    padding: 1rem 0;
}

.trust-badges-vertical .trust-badge {
    margin-bottom: 0.5rem;
}

.trust-badge img {
    opacity: 0.9;
    transition: opacity 0.2s;
}

.trust-badge img:hover {
    opacity: 1;
}
</style>
