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

        <div class="min-h-screen text-gray-200">
            <!-- Free Picks Section -->
            <section class="py-16">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold text-white">Today's Picks</h2>
                    <p class="mt-4 text-gray-400">Get a taste of our expert picks for free!</p>
                    <div class="mt-8">
                        <GroupedBetCards :grouped-bets="allGroupedBets" />
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
