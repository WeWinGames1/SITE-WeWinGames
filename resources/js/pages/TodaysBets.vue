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

// Get user's subscription type - handle ambassador/gifted/override users
const getUserSubscriptionType = () => {
    if (!auth?.user?.data) return 'free';
    const user = auth.user.data;
    
    // Check for override fields first (ambassador, gifted, override)
    if (user.ambassador || user.gifted || user.override) {
        return 'platinum'; // These users get platinum access
    }
    
    // Check active subscription
    const activeSubscription = user.subscriptions?.find(sub => sub.stripe_status === 'active');
    if (activeSubscription?.type) {
        return activeSubscription.type.toLowerCase();
    }
    
    return 'free';
};

const userSubscriptionType = getUserSubscriptionType();
const isAdmin = auth?.isAdmin || false;

// Determine which bets can be viewed based on subscription
const canViewBet = (bet) => {
    if (isAdmin) return true;
    
    const betLevel = bet.membership?.toLowerCase() || 'bronze';
    
    switch (userSubscriptionType) {
        case 'free':
            return betLevel === 'bronze';
        case 'silver':
            return betLevel === 'bronze' || betLevel === 'silver';
        case 'gold':
            return betLevel === 'bronze' || betLevel === 'silver' || betLevel === 'gold';
        case 'platinum':
            return true; // Can view all bets
        default:
            return betLevel === 'bronze';
    }
};

// Split bets into viewable and covered
const viewableBets = bets
    .filter(bet => canViewBet(bet))
    .map(bet => ({ ...bet, isCovered: false }));

const coveredBets = bets
    .filter(bet => !canViewBet(bet))
    .map(bet => ({ ...bet, isCovered: true }));
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

// Helper function to categorize bets by date
const categorizeBet = (bet) => {
    // Use game_date if available, otherwise fall back to betting_date
    const betDate = new Date(bet.game_date || bet.betting_date || bet.game_at);
    const today = new Date();
    const endOfWeek = new Date();
    endOfWeek.setDate(today.getDate() + (7 - today.getDay())); // Next Sunday
    
    // Check if it's golf and within this week
    const isGolf = (bet.sports || '').toLowerCase().includes('golf');
    
    if (betDate.toDateString() === today.toDateString()) {
        return { category: 'daily', priority: 1 };
    } else if (isGolf && betDate <= endOfWeek) {
        return { category: 'weekly_golf', priority: 2 };
    } else {
        return { category: 'futures', priority: 3 };
    }
};

// Combine all bets for grouping
const allGroupedBets = computed(() => {
  // Merge all bets
  const all = [...viewableBets, ...coveredBets].filter(
    (bet, idx, arr) => arr.findIndex(b => b.id === bet.id) === idx
  );
  
  // Filter by selected sport
  const filtered = selectedSport.value === 'all' 
    ? all 
    : all.filter(bet => bet.sports === selectedSport.value);
  
  // Sort bets by priority
  const sorted = filtered.sort((a, b) => {
    const catA = categorizeBet(a);
    const catB = categorizeBet(b);
    
    // First sort by priority
    if (catA.priority !== catB.priority) {
        return catA.priority - catB.priority;
    }
    
    // Then by membership level (bronze first for free picks)
    const membershipOrder = { 'bronze': 1, 'silver': 2, 'gold': 3, 'platinum': 4 };
    const memA = membershipOrder[a.membership?.toLowerCase()] || 5;
    const memB = membershipOrder[b.membership?.toLowerCase()] || 5;
    
    return memA - memB;
  });
  
  // Group by sport
  return sorted.reduce((acc, bet) => {
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
        month: 'short'
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
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-4">Today's Picks</h2>
                        <p class="fs-5 text-gray-light mb-5">Expert analysis and betting recommendations</p>
                    </div>
                    
                    <!-- Use the same GroupedBetCards component as home page -->
                    <GroupedBetCards :grouped-bets="allGroupedBets" />
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
