<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import SubscriptionROIChart from '@/components/SubscriptionROIChart.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { computed, ref } from 'vue';
import GroupedBetCards from '@/components/GroupedBetCards.vue';

const page = usePage();
const auth = page.props.auth || null;
const bets = page.props.freeBets || [];

// Sports filter
const selectedSport = ref('all');
const sports = computed(() => {
    const sportSet = new Set(bets.map(bet => bet.sports));
    return Array.from(sportSet);
});

const sportIcons = {
    'Football': '⚽',
    'Basketball': '🏀',
    'Hockey': '🏒',
    'Baseball': '⚾',
    'Soccer': '⚽',
    'Golf': '⛳',
    'Ultimate Fighting Championship': '🥊'
};

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
  
  // Filter by selected sport
  const filtered = selectedSport.value === 'all' 
    ? all 
    : all.filter(bet => bet.sports === selectedSport.value);
  
  return filtered.reduce((acc, bet) => {
    if (!acc[bet.sports]) acc[bet.sports] = [];
    acc[bet.sports].push(bet);
    return acc;
  }, {});
});

// Get all bets for display (not grouped)
const displayBets = computed(() => {
  const all = [...viewableBets, ...coveredBets].filter(
    (bet, idx, arr) => arr.findIndex(b => b.id === bet.id) === idx
  );
  
  // Filter by selected sport
  return selectedSport.value === 'all' 
    ? all 
    : all.filter(bet => bet.sports === selectedSport.value);
});
// console.log('All Grouped Bets:', allGroupedBets.value);
// console.log('Viewable Bets:', viewableBets);
// console.log('Covered Bets:', coveredBets);

// Helper functions
const formatBetDate = (date: string) => {
    if (!date) return 'TBD';
    return new Date(date).toLocaleDateString('en-US', { 
        day: 'numeric',
        month: 'short',
        hour: 'numeric',
        minute: '2-digit'
    });
};

const getMembershipBadgeStyle = (membership: string) => {
    switch (membership.toLowerCase()) {
        case 'silver':
            return 'bg-secondary text-white';
        case 'gold':
            return 'bg-warning text-dark';
        case 'platinum':
            return 'bg-info text-dark';
        default:
            return 'bg-secondary text-white';
    }
};
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games">
            <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
        </Head>

        <div class="min-vh-100" style="background-color: #0a0e1a;">
            <!-- Sports Filter Bar -->
            <section class="py-3" style="background: linear-gradient(90deg, #2e4057 0%, #1a2332 50%, #2e4057 100%);">
                <div class="container">
                    <div class="d-flex align-items-center gap-4 overflow-auto pb-2" style="scrollbar-width: thin;">
                        <button 
                            @click="selectedSport = 'all'"
                            class="btn btn-sm px-4 py-2 text-nowrap"
                            :class="selectedSport === 'all' ? 'btn-warning text-dark' : 'btn-outline-light'"
                        >
                            All Sports
                        </button>
                        <button 
                            v-for="sport in sports" 
                            :key="sport"
                            @click="selectedSport = sport"
                            class="btn btn-sm px-4 py-2 text-nowrap"
                            :class="selectedSport === sport ? 'btn-warning text-dark' : 'btn-outline-light'"
                        >
                            <span class="me-2">{{ sportIcons[sport] || '🏆' }}</span>
                            {{ sport }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Picks Grid Section -->
            <section class="py-4">
                <div class="container">
                    <!-- Picks Grid -->
                    <div class="row g-3">
                        <div 
                            v-for="bet in displayBets" 
                            :key="bet.id"
                            class="col-12 col-md-6 col-lg-4"
                        >
                            <div class="card h-100" style="background-color: #1a2332; border: 2px solid #2e4057;">
                                <!-- Card Header -->
                                <div class="card-header d-flex align-items-center justify-content-between py-3" style="background-color: #0d1829; border-bottom: 1px solid #2e4057;">
                                    <div class="d-flex align-items-center gap-2">
                                        <span v-if="bet.sports === 'Football'" class="fs-5">🏈</span>
                                        <span class="text-white fw-semibold">{{ bet.sports }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3 text-secondary small">
                                        <span>{{ bet.league || 'Premier League' }}</span>
                                        <span>Date: {{ formatBetDate(bet.betting_date) }}</span>
                                    </div>
                                </div>
                                
                                <!-- Card Body -->
                                <div class="card-body">
                                    <!-- Teams -->
                                    <div class="text-center py-4">
                                        <div class="d-flex align-items-center justify-content-center gap-4">
                                            <div class="text-center">
                                                <img 
                                                    :src="bet.team_one_logo || '/images/team-placeholder.svg'"
                                                    :alt="bet.team_one"
                                                    class="mb-2"
                                                    style="height: 60px; width: auto;"
                                                    onerror="this.src='/images/team-placeholder.svg'"
                                                >
                                                <div class="text-white fw-medium">{{ bet.team_one }}</div>
                                            </div>
                                            <div class="text-white fs-4 fw-bold">VS</div>
                                            <div class="text-center">
                                                <img 
                                                    :src="bet.team_two_logo || '/images/team-placeholder.svg'"
                                                    :alt="bet.team_two"
                                                    class="mb-2"
                                                    style="height: 60px; width: auto;"
                                                    onerror="this.src='/images/team-placeholder.svg'"
                                                >
                                                <div class="text-white fw-medium">{{ bet.team_two }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Game Level Badge -->
                                    <div class="text-center mb-3">
                                        <span 
                                            class="badge px-4 py-2"
                                            :class="getMembershipBadgeStyle(bet.membership)"
                                        >
                                            Game Level: {{ bet.membership.toUpperCase() }}
                                        </span>
                                    </div>
                                    
                                    <!-- Betting Pick -->
                                    <div v-if="!bet.isCovered" class="text-center">
                                        <button class="btn btn-warning btn-lg w-100 fw-bold text-dark">
                                            {{ bet.tips || 'View Pick' }}
                                        </button>
                                    </div>
                                    <div v-else class="text-center">
                                        <button class="btn btn-secondary btn-lg w-100" disabled>
                                            <i class="bi bi-lock-fill me-2"></i>
                                            Upgrade to View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center align-items-center mt-5">
                        <button class="btn btn-outline-light me-3">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="text-white mx-3">1 / 6</span>
                        <button class="btn btn-outline-light ms-3">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-if="displayBets.length === 0" class="text-center py-5">
                        <i class="bi bi-inbox text-secondary display-1 mb-3"></i>
                        <h5 class="text-white">No picks available</h5>
                        <p class="text-secondary">Check back later for new betting picks</p>
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

/* Custom scrollbar for sports filter */
.overflow-auto::-webkit-scrollbar {
    height: 6px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #ffc107;
    border-radius: 3px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #ffca2c;
}

/* Card hover effects */
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    border-color: #ffc107 !important;
}

/* Button hover effects */
.btn-warning:hover {
    background-color: #ffca2c;
    border-color: #ffca2c;
    transform: scale(1.02);
}

.btn-outline-light:hover {
    background-color: rgba(255, 255, 255, 0.1);
    border-color: #ffc107;
    color: #ffc107 !important;
}
</style>
