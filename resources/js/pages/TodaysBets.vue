<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import SubscriptionROIChart from '@/components/SubscriptionROIChart.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { computed } from 'vue';
import GroupedBetCards from '@/components/GroupedBetCards.vue';

const page = usePage();
const auth = page.props.auth || null;
const bets = page.props.freeBets || [];

const isGold = auth?.user?.data?.subscriptions[0]?.type === 'gold';
const isSilver = auth?.user?.data?.subscriptions[0]?.type === 'silver';
const isPlatinum = auth?.user?.data?.subscriptions[0]?.type === 'platinum';
const isDefault = auth?.user?.data?.subscriptions[0]?.type === 'default';
const roiData = page.props.roiData || {};
const bronzeBets = bets.filter((bet) => bet.membership === 'bronze' || auth.isAdmin);

const coveredBets = bets
  .filter(bet => { 
    if (auth.isAdmin) return true;
    if (bet.membership.toLowerCase() === 'bronze') return false;
    if (bet.membership.toLowerCase() === 'gold') return !isGold && !isPlatinum && !isDefault ;
    if (bet.membership.toLowerCase() === 'silver') return !isSilver && !isGold && !isPlatinum && !isDefault;
    if (bet.membership.toLowerCase() === 'platinum') return !isPlatinum && !isDefault;
  })
  .map(bet => ({ ...bet, isCovered: true }));

const viewableBets = bets
  .filter(bet => { 
    if (auth.isAdmin) return true;
    if (bet.membership.toLowerCase() === 'bronze') return true;
    if (bet.membership.toLowerCase() === 'gold') return isGold || isPlatinum || isDefault;
    if (bet.membership.toLowerCase() === 'silver') return isSilver || isGold || isPlatinum || isDefault;
    if (bet.membership.toLowerCase() === 'platinum') return isPlatinum || isDefault;
  })
  .map(bet => ({ ...bet, isCovered: false }));
// Group bets by sport
const groupedBets = computed(() => {
  return viewableBets.reduce((acc, bet) => {
    if (!acc[bet.sports]) acc[bet.sports] = [];
    acc[bet.sports].push(bet);
    return acc;
  }, {});
});

const coveredGroupedBets = computed(() => {
  return coveredBets.reduce((acc, bet) => {
    if (!acc[bet.sports]) acc[bet.sports] = [];
    acc[bet.sports].push(bet);
    return acc;
  }, {});
});

// Combine all bets for grouping
const allGroupedBets = computed(() => {
  // Get all non-bronze bets first, then bronze bets last
  
  // Merge, bronze last so it overwrites by id
  const all = [...viewableBets, ...coveredBets].filter(
    (bet, idx, arr) => arr.findIndex(b => b.id === bet.id) === idx
  );
  return all.reduce((acc, bet) => {
    if (!acc[bet.sports]) acc[bet.sports] = [];
    acc[bet.sports].push(bet);
    return acc;
  }, {});
});
// console.log('All Grouped Bets:', allGroupedBets.value);
// console.log('Viewable Bets:', viewableBets);
// console.log('Covered Bets:', coveredBets);
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games">
            <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        </Head>

        <div class="min-vh-100" style="background-color: var(--bs-body-bg);">
            <!-- Hero Section -->
            <section class="py-5" style="background: linear-gradient(135deg, #7C3AED 0%, #111827 100%);">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-8">
                            <h1 class="display-4 fw-bold text-white mb-3">Today's Expert Picks</h1>
                            <p class="fs-5 text-gray-light mb-4">Get access to our premium betting analysis and picks</p>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-white">Updated Daily</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-white">Expert Analysis</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                    <span class="text-white">Proven ROI</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                            <div class="bg-dark rounded-3 p-4 d-inline-block" style="border: 1px solid var(--bs-gray-medium);">
                                <h3 class="h5 text-white mb-2">Today's Win Rate</h3>
                                <div class="display-3 fw-bold text-success">68%</div>
                                <p class="text-gray-light mb-0">{{ Object.keys(allGroupedBets).reduce((sum, sport) => sum + allGroupedBets[sport].length, 0) }} picks available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Subscription Status -->
            <section class="py-4 border-bottom" style="background-color: var(--bs-gray-dark); border-color: var(--bs-gray-medium) !important;">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-person-circle text-purple fs-3 me-3"></i>
                                <div>
                                    <h5 class="mb-0 text-white">{{ auth?.user?.data?.name || 'Guest User' }}</h5>
                                    <p class="mb-0 text-gray-light">
                                        <span v-if="isPlatinum" class="badge bg-purple me-2">Platinum</span>
                                        <span v-else-if="isGold" class="badge bg-warning text-dark me-2">Gold</span>
                                        <span v-else-if="isSilver" class="badge bg-secondary me-2">Silver</span>
                                        <span v-else class="badge bg-dark border me-2">Free</span>
                                        <span v-if="auth?.user">Member since {{ new Date(auth.user.data.created_at).toLocaleDateString() }}</span>
                                        <span v-else>Sign up to access premium picks</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-3 mt-md-0">
                            <Link v-if="!auth?.user || isDefault" href="/register" class="btn btn-primary">
                                <i class="bi bi-rocket-takeoff me-2"></i>
                                Upgrade to Premium
                            </Link>
                            <Link v-else-if="!isPlatinum" href="/settings/billing" class="btn btn-outline-primary">
                                <i class="bi bi-arrow-up-circle me-2"></i>
                                Upgrade Plan
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Picks Section -->
            <section class="py-5">
                <div class="container">
                    <div class="mb-5">
                        <h2 class="h3 fw-bold text-white mb-3">Available Picks</h2>
                        <p class="text-gray-light">Click on any pick to view detailed analysis and betting recommendations</p>
                    </div>
                    
                    <div class="mt-4">
                        <GroupedBetCards :grouped-bets="allGroupedBets" />
                    </div>
                    
                    <!-- CTA for non-subscribers -->
                    <div v-if="!auth?.user || isDefault" class="text-center mt-5 py-5">
                        <div class="bg-gradient-primary rounded-3 p-5">
                            <h3 class="h2 fw-bold text-white mb-3">Unlock All Premium Picks</h3>
                            <p class="fs-5 text-white mb-4">Join thousands of winning bettors with our expert analysis</p>
                            <Link href="/register" class="btn btn-light btn-lg px-5">
                                <span class="text-dark fw-semibold">Start Free Trial</span>
                                <i class="bi bi-arrow-right ms-2 text-dark"></i>
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.8s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
.fade-enter-to, .fade-leave-from {
  opacity: 1;
}
</style>
