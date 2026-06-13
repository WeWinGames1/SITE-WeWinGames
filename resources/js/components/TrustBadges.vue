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
                <img src="/images/icons/cashapp.svg" alt="Cash App Pay" height="24" />
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

            <!-- NJ DGE Registered Vendor -->
            <a
                href="https://www.njoag.gov/about/divisions-and-offices/division-of-gaming-enforcement-home/vendor-licensing-reports/"
                target="_blank"
                rel="noopener noreferrer"
                class="trust-badge nj-dge-badge text-decoration-none"
                title="Registered Vendor – NJ Division of Gaming Enforcement"
            >
                <div
                    class="d-flex align-items-center gap-2 px-2 py-1 rounded"
                    :class="compact ? 'nj-dge-compact' : ''"
                    style="background: rgba(0, 51, 102, 0.08); border: 1px solid rgba(0, 51, 102, 0.15)"
                >
                    <img
                        src="/images/legal-nj-ag.png"
                        alt="NJ Attorney General Seal"
                        :height="compact ? 24 : 36"
                        :width="compact ? 24 : 36"
                        style="object-fit: contain"
                    />
                    <div v-if="!compact" class="d-flex flex-column" style="line-height: 1.1">
                        <span class="small fw-semibold" style="color: #003366; font-size: 0.7rem">Registered Vendor</span>
                        <span class="small text-muted" style="font-size: 0.6rem">NJ Division of Gaming Enforcement</span>
                    </div>
                </div>
            </a>
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

.nj-dge-badge {
    transition: transform 0.2s, box-shadow 0.2s;
    border-radius: 0.25rem;
}

.nj-dge-badge:hover {
    transform: scale(1.02);
}

.nj-dge-badge:hover > div {
    box-shadow: 0 2px 8px rgba(0, 51, 102, 0.15);
}

.nj-dge-compact {
    padding: 0.15rem 0.4rem !important;
}
</style>
