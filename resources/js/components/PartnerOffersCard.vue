<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

interface PartnerOffer {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    offer_text: string;
    description?: string | null;
    external_url: string;
    click_url?: string;
    type: 'sportsbook' | 'prediction' | 'casino';
    badge_text: string | null;
}

interface Props {
    offers: PartnerOffer[];
    variant?: 'compact' | 'full';
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'compact',
    title: 'Partner Offers',
});

async function trackClick(offer: PartnerOffer, source: string) {
    try {
        await axios.post(`/partner-offers/${offer.id}/click`, { source });
    } catch (error) {
        // Silent fail - don't interrupt user flow
    }
}

function handleClick(e: Event, offer: PartnerOffer) {
    const source = props.variant === 'compact' ? 'picks_sidebar' : 'offers_page';
    trackClick(offer, source);
}
</script>

<template>
    <div :class="['partner-offers-card', `variant-${variant}`]">
        <!-- Compact Variant (Sidebar) -->
        <template v-if="variant === 'compact'">
            <div class="card border-gold-500" style="background: linear-gradient(135deg, var(--navy-800) 0%, var(--navy-900) 100%)">
                <div class="card-header bg-transparent border-0 pb-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-star-fill text-gold-500"></i>
                        <h6 class="mb-0 text-white fw-bold">{{ title }}</h6>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="d-flex flex-column gap-2">
                        <a
                            v-for="offer in offers.slice(0, 3)"
                            :key="offer.id"
                            :href="offer.click_url || offer.external_url"
                            :target="offer.click_url?.startsWith('/') ? '_self' : '_blank'"
                            :rel="offer.click_url?.startsWith('/') ? undefined : 'noopener sponsored'"
                            class="offer-item d-flex align-items-center gap-2 p-2 rounded text-decoration-none"
                            @click="handleClick($event, offer)"
                        >
                            <div class="offer-logo flex-shrink-0">
                                <img v-if="offer.logo" :src="offer.logo" :alt="offer.name" height="28" class="rounded" />
                                <div v-else class="logo-placeholder rounded d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(255,255,255,0.1)">
                                    <i class="bi bi-link-45deg text-muted small"></i>
                                </div>
                            </div>
                            <div class="offer-info flex-grow-1 min-w-0">
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-semibold text-white small text-truncate">{{ offer.name }}</span>
                                    <span v-if="offer.badge_text" class="badge bg-warning text-dark" style="font-size: 0.6rem">{{ offer.badge_text }}</span>
                                </div>
                                <div class="text-gray-light small text-truncate" style="font-size: 0.75rem">{{ offer.offer_text }}</div>
                            </div>
                            <i class="bi bi-chevron-right text-muted small flex-shrink-0"></i>
                        </a>
                    </div>

                    <Link href="/partners-offers" class="btn btn-outline-light btn-sm w-100 mt-3">
                        <i class="bi bi-grid me-1"></i>
                        View All Offers
                    </Link>
                </div>
            </div>
        </template>

        <!-- Full Variant (Offers Page) -->
        <template v-else>
            <div class="row g-4">
                <div v-for="offer in offers" :key="offer.id" class="col-md-6 col-lg-4">
                    <div class="card h-100 offer-card" style="background: var(--navy-800); border-color: rgba(255,255,255,0.1)">
                        <div class="card-body d-flex flex-column">
                            <!-- Logo & Badge -->
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="offer-logo-large">
                                    <img v-if="offer.logo" :src="offer.logo" :alt="offer.name" height="40" class="rounded" />
                                    <div v-else class="h4 mb-0 text-white fw-bold">{{ offer.name }}</div>
                                </div>
                                <span v-if="offer.badge_text" class="badge bg-gold-500 text-dark">{{ offer.badge_text }}</span>
                            </div>

                            <!-- Offer Text -->
                            <h5 class="text-white fw-bold mb-2">{{ offer.offer_text }}</h5>
                            <p v-if="offer.description" class="text-gray-light small flex-grow-1 mb-3">{{ offer.description }}</p>

                            <!-- Type Badge -->
                            <div class="mb-3">
                                <span
                                    :class="[
                                        'badge',
                                        offer.type === 'sportsbook' ? 'bg-primary' : offer.type === 'prediction' ? 'bg-info' : 'bg-warning text-dark',
                                    ]"
                                >
                                    {{ offer.type }}
                                </span>
                            </div>

                            <!-- CTA -->
                            <a
                                :href="offer.click_url || offer.external_url"
                                :target="offer.click_url?.startsWith('/') ? '_self' : '_blank'"
                                :rel="offer.click_url?.startsWith('/') ? undefined : 'noopener sponsored'"
                                class="btn btn-gold w-100"
                                @click="handleClick($event, offer)"
                            >
                                Claim Offer
                                <i class="bi bi-box-arrow-up-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.border-gold-500 {
    border: 2px solid var(--gold-500) !important;
}

.offer-item {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s ease;
}

.offer-item:hover {
    background: rgba(255, 199, 39, 0.1);
    border-color: rgba(255, 199, 39, 0.3);
}

.offer-card {
    transition: all 0.3s ease;
}

.offer-card:hover {
    transform: translateY(-4px);
    border-color: var(--gold-500) !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.min-w-0 {
    min-width: 0;
}
</style>
