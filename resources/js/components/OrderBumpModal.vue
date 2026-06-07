<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    show: boolean;
    currentPeriod: string;
    currentTier: string;
    upgradePeriod: string;
    currentPrice: number;
    upgradePrice: number;
    discountAmount: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    accept: [];
    decline: [];
    close: [];
}>();

const isVisible = ref(props.show);

watch(
    () => props.show,
    (newVal) => {
        isVisible.value = newVal;
    },
);

const savings = () => {
    const dailyPerWeek = props.currentPrice * 7;
    const weeklyPrice = props.upgradePrice - props.discountAmount;
    return Math.max(0, dailyPerWeek - weeklyPrice).toFixed(0);
};

const handleAccept = () => {
    emit('accept');
};

const handleDecline = () => {
    emit('decline');
};

const handleClose = () => {
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="isVisible" class="modal-overlay" @click.self="handleClose">
                <div class="modal-container">
                    <div class="modal-content">
                        <!-- Header -->
                        <div class="modal-header text-center">
                            <div class="upgrade-badge mb-3">
                                <i class="bi bi-lightning-charge-fill"></i>
                                SPECIAL OFFER
                            </div>
                            <h4 class="fw-bold text-white mb-2">Wait! Upgrade to Weekly & Save!</h4>
                            <p class="text-gray-light mb-0">Get more value with our exclusive upgrade offer</p>
                        </div>

                        <!-- Body -->
                        <div class="modal-body">
                            <!-- Comparison -->
                            <div class="comparison-grid">
                                <div class="comparison-item current">
                                    <div class="comparison-label">Your Selection</div>
                                    <div class="comparison-value">
                                        <span class="period">{{ currentTier }} {{ currentPeriod }}</span>
                                        <span class="price">${{ currentPrice }}/day</span>
                                    </div>
                                </div>

                                <div class="comparison-arrow">
                                    <i class="bi bi-arrow-right-circle-fill text-gold-500"></i>
                                </div>

                                <div class="comparison-item upgrade">
                                    <div class="comparison-label">Upgrade To</div>
                                    <div class="comparison-value">
                                        <span class="period">{{ currentTier }} {{ upgradePeriod }}</span>
                                        <div class="price-group">
                                            <span class="price-original">${{ upgradePrice }}</span>
                                            <span class="price-discounted">${{ upgradePrice - discountAmount }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Savings Highlight -->
                            <div class="savings-highlight">
                                <i class="bi bi-piggy-bank-fill me-2"></i>
                                Save up to <strong>${{ savings() }}</strong> compared to daily passes!
                            </div>

                            <!-- Benefits -->
                            <div class="benefits-list">
                                <div class="benefit-item">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>7 days of premium picks</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>${{ discountAmount }} instant discount applied</span>
                                </div>
                                <div class="benefit-item">
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                    <span>Cancel anytime - no commitment</span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="modal-footer">
                            <button type="button" class="btn btn-gold btn-lg w-100 mb-2" @click="handleAccept">
                                <i class="bi bi-arrow-up-circle me-2"></i>
                                Yes, Upgrade & Save ${{ discountAmount }}!
                            </button>
                            <button type="button" class="btn btn-link text-muted w-100" @click="handleDecline">
                                No thanks, continue with daily pass
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
    padding: 1rem;
}

.modal-container {
    width: 100%;
    max-width: 500px;
}

.modal-content {
    background: linear-gradient(135deg, var(--navy-800) 0%, var(--navy-900) 100%);
    border-radius: 1rem;
    border: 2px solid var(--gold-500);
    overflow: hidden;
}

.modal-header {
    padding: 2rem 2rem 1rem;
    background: linear-gradient(180deg, rgba(255, 199, 39, 0.1) 0%, transparent 100%);
}

.upgrade-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, var(--gold-500) 0%, var(--gold-600) 100%);
    color: #000;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.375rem 1rem;
    border-radius: 20px;
    letter-spacing: 0.5px;
}

.modal-body {
    padding: 1.5rem 2rem;
}

.comparison-grid {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.comparison-item {
    flex: 1;
    text-align: center;
    padding: 1rem;
    border-radius: 0.75rem;
}

.comparison-item.current {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.comparison-item.upgrade {
    background: rgba(255, 199, 39, 0.1);
    border: 2px solid var(--gold-500);
}

.comparison-label {
    font-size: 0.7rem;
    text-transform: uppercase;
    color: var(--bs-gray-light);
    margin-bottom: 0.5rem;
    letter-spacing: 0.5px;
}

.comparison-value .period {
    display: block;
    font-weight: 600;
    color: white;
    text-transform: capitalize;
    margin-bottom: 0.25rem;
}

.comparison-value .price {
    font-size: 0.9rem;
    color: var(--bs-gray-light);
}

.price-group {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.price-original {
    font-size: 0.85rem;
    color: var(--bs-gray-light);
    text-decoration: line-through;
}

.price-discounted {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--gold-500);
}

.comparison-arrow {
    font-size: 1.5rem;
}

.savings-highlight {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%);
    border: 1px solid rgba(16, 185, 129, 0.3);
    color: #10b981;
    padding: 0.875rem 1rem;
    border-radius: 0.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.benefits-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--bs-body-color);
    font-size: 0.9rem;
}

.modal-footer {
    padding: 1rem 2rem 2rem;
}

/* Transition */
.modal-enter-active,
.modal-leave-active {
    transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
    transform: scale(0.9) translateY(20px);
}

.modal-enter-to .modal-container,
.modal-leave-from .modal-container {
    transform: scale(1) translateY(0);
}
</style>
