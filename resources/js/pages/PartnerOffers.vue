<script setup lang="ts">
import PartnerOffersCard from '@/components/PartnerOffersCard.vue';
import TrustBadges from '@/components/TrustBadges.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface PartnerOffer {
    id: number;
    name: string;
    slug: string;
    logo: string | null;
    offer_text: string;
    description: string | null;
    external_url: string;
    type: 'sportsbook' | 'prediction' | 'casino';
    badge_text: string | null;
}

const page = usePage();
const offers = computed(() => (page.props.offers || []) as PartnerOffer[]);
const sportsbooks = computed(() => (page.props.sportsbooks || []) as PartnerOffer[]);
const predictions = computed(() => (page.props.predictions || []) as PartnerOffer[]);
const casinos = computed(() => (page.props.casinos || []) as PartnerOffer[]);

const selectedType = ref<string>('all');

const filteredOffers = computed(() => {
    if (selectedType.value === 'all') return offers.value;
    return offers.value.filter((o) => o.type === selectedType.value);
});

const typeCounts = computed(() => ({
    all: offers.value.length,
    sportsbook: sportsbooks.value.length,
    prediction: predictions.value.length,
    casino: casinos.value.length,
}));
</script>

<template>
    <WelcomeLayout>
        <Head title="Partner Offers - Sports Betting Bonuses & Free Bets" />

        <div class="min-vh-100" style="background: linear-gradient(180deg, var(--navy-900) 0%, var(--ink-900) 100%)">
            <!-- Hero Section -->
            <section class="py-5" style="background: linear-gradient(135deg, var(--navy-800) 0%, var(--navy-900) 100%)">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 fw-bold text-white mb-3">Partner Offers</h1>
                            <p class="fs-5 text-gray-light mb-4">
                                Find the latest free bet offers and bonuses from legalized sportsbooks. Sign up through our links to get exclusive
                                welcome bonuses.
                            </p>
                            <div class="d-flex align-items-center gap-3 flex-wrap">
                                <span class="badge bg-gold-500 text-dark px-3 py-2">
                                    <i class="bi bi-gift me-2"></i>
                                    {{ offers.length }} Active Offers
                                </span>
                                <span class="badge bg-success px-3 py-2">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Licensed & Secure
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center d-none d-lg-block">
                            <i class="bi bi-gift-fill text-gold-500" style="font-size: 8rem; opacity: 0.3"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Affiliate Disclosure -->
            <section class="py-3" style="background-color: rgba(255, 199, 39, 0.1); border-top: 2px solid var(--gold-500)">
                <div class="container">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-info-circle text-gold-500 fs-5"></i>
                        <div>
                            <span class="text-gold-500 fw-bold">Affiliate Disclosure:</span>
                            <span class="text-white ms-2">
                                We receive a commission when you sign up with our links, which is how we keep the show on the road!
                            </span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Filter Tabs -->
            <section class="py-4" style="background-color: var(--navy-800)">
                <div class="container">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button
                            @click="selectedType = 'all'"
                            :class="['btn', selectedType === 'all' ? 'btn-gold' : 'btn-outline-light']"
                        >
                            All Offers
                            <span class="badge bg-dark ms-2">{{ typeCounts.all }}</span>
                        </button>
                        <button
                            v-if="typeCounts.sportsbook > 0"
                            @click="selectedType = 'sportsbook'"
                            :class="['btn', selectedType === 'sportsbook' ? 'btn-gold' : 'btn-outline-light']"
                        >
                            <i class="bi bi-trophy me-2"></i>
                            Sportsbooks
                            <span class="badge bg-dark ms-2">{{ typeCounts.sportsbook }}</span>
                        </button>
                        <button
                            v-if="typeCounts.prediction > 0"
                            @click="selectedType = 'prediction'"
                            :class="['btn', selectedType === 'prediction' ? 'btn-gold' : 'btn-outline-light']"
                        >
                            <i class="bi bi-graph-up me-2"></i>
                            Prediction Markets
                            <span class="badge bg-dark ms-2">{{ typeCounts.prediction }}</span>
                        </button>
                        <button
                            v-if="typeCounts.casino > 0"
                            @click="selectedType = 'casino'"
                            :class="['btn', selectedType === 'casino' ? 'btn-gold' : 'btn-outline-light']"
                        >
                            <i class="bi bi-dice-5 me-2"></i>
                            Casinos
                            <span class="badge bg-dark ms-2">{{ typeCounts.casino }}</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Offers Grid -->
            <section class="py-5">
                <div class="container">
                    <PartnerOffersCard v-if="filteredOffers.length > 0" :offers="filteredOffers" variant="full" />

                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-inbox text-muted display-1"></i>
                        <h3 class="text-white mt-4">No offers available</h3>
                        <p class="text-gray-light">Check back soon for new partner offers.</p>
                    </div>
                </div>
            </section>

            <!-- Trust & Legal Section -->
            <section class="py-5" style="background-color: var(--navy-800)">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <h4 class="text-white mb-4">
                                <i class="bi bi-shield-check text-gold-500 me-2"></i>
                                Responsible Gaming
                            </h4>
                            <TrustBadges variant="horizontal" :showPaymentMethods="false" />
                        </div>
                        <div class="col-lg-6">
                            <div class="card" style="background-color: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1)">
                                <div class="card-body">
                                    <h5 class="text-white mb-3">
                                        <i class="bi bi-exclamation-triangle text-warning me-2"></i>
                                        Disclaimer
                                    </h5>
                                    <p class="text-gray-light small mb-0">
                                        This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be
                                        addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call
                                        1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.btn-gold {
    background: linear-gradient(135deg, var(--gold-500) 0%, var(--gold-600) 100%);
    border: none;
    color: #000;
    font-weight: 600;
}

.btn-gold:hover {
    background: linear-gradient(135deg, var(--gold-600) 0%, var(--gold-500) 100%);
    color: #000;
    transform: translateY(-2px);
}

.bg-gold-500 {
    background-color: var(--gold-500) !important;
}

.text-gold-500 {
    color: var(--gold-500) !important;
}
</style>
