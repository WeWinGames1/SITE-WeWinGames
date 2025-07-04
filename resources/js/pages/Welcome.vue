<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import GroupedBetCards from '@/components/GroupedBetCards.vue';
import BetPickCard from '@/components/BetPickCard.vue';
import CoveredBetPickCard from '@/components/CoveredBetPickCard.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import PricingCards from '../components/PricingCards.vue';
import ProfitsByLevelTable from '@/components/ProfitsByLevelTable.vue';
import ProfitsBySportTable from '@/components/ProfitsBySportTable.vue';
import GoogleReviews from '@/components/GoogleReviews.vue'; // <-- Import your new component
import AffiliateLinks from '@/components/AffiliateLinks.vue';
import { onMounted, onBeforeUnmount, computed, ref } from 'vue';
function formatMoney(val: number | undefined) {
    return (Math.round(val ?? 0)).toLocaleString();
}
const page = usePage();
const auth = page.props.auth || null; // Get the logged-in user
const bets = page.props.freeBets || []; // Get the daily bet picks
const props = defineProps<{
    roiData: Record<string, number>,
    levelProfitRoiData: Array<{ level: string, profit: number, roi: number }>,
    sportProfitRoiData: Array<{ sport: string, profit: number, roi: number }>,
    lastYearProfit?: number,
    lastYearROI?: number,
    lastYear?: number,
    thisYear?: number,
    thisYearProfit?: number,
    thisYearROI?: number,
    monthlyProfit?: number,
    thisMonthProfit?: number,
    thisMonthROI?: number,
    winRatio?: number,
    thisYearWinLoss?: number,
    lastYearWinLoss?: number,
    lastMonthProfit?: number,
    lastMonthWinLoss?: number,
    lastMonthROI?: number,
    thisMonthWinLoss?: number,
    testimonials?: any[],
}>(); // Get ROI data by subscription level
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

// Legacy variables for backward compatibility
const isGold = userSubscriptionType === 'gold';
const isSilver = userSubscriptionType === 'silver';
const isPlatinum = userSubscriptionType === 'platinum';
const isDefault = userSubscriptionType === 'free';

const bronzeBets = bets.filter((bet) => bet.membership === 'bronze');

// Sports filter
const selectedSport = ref('all');
const availableSports = computed(() => {
    const sportsSet = new Set(bets.map(bet => bet.sports || bet.sport || 'Football'));
    return ['all', ...Array.from(sportsSet)];
});

const sportIcons = {
    'Football': 'bi-trophy-fill',
    'Basketball': 'bi-dribbble',
    'Hockey': 'bi-snow2',
    'Baseball': 'bi-circle',
    'Soccer': 'bi-globe',
    'Golf': 'bi-flag',
    'Ultimate Fighting Championship': 'bi-person-arms-up',
    'all': 'bi-grid-3x3-gap-fill'
};
// Use the dynamic Stripe prices from shared props
const stripePrices = page.props.stripePrices || {};
const silver_monthly = stripePrices.silver_monthly;
const silver_weekly = stripePrices.silver_weekly;
const silver_daily = stripePrices.silver_daily;
const gold_monthly = stripePrices.gold_monthly;
const gold_weekly = stripePrices.gold_weekly;
const gold_daily = stripePrices.gold_daily;
const platinum_monthly = stripePrices.platinum_monthly;
const platinum_weekly = stripePrices.platinum_weekly;
const platinum_daily = stripePrices.platinum_daily;
const plans = [
  {
    name: 'Silver',
    price: '$45',
    monthlyPrice: '$45',
    duration: '30 days',
    features: [
      'Over 5 picks a day',
      'Straight bets',
      'Favorite picks',
      'Avg odds -120',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'silver', subscription_price_id: silver_daily }),
    weeklyPrice: '17',
    dailyPrice: '5',
    highlight: false,
  },
  {
    name: 'Gold',
    price: '$65',
    monthlyPrice: '$65',
    duration: '30 days',
    features: [
      'All Silver features +',
      '> 5 gold picks daily',
      'Best Value Bets',
      'Avg odds > +100',
      'Cancel anytime',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'gold', subscription_price_id: gold_daily }),
    weeklyPrice: '29',
    dailyPrice: '8',
    highlight: true,
  },
  {
    name: 'Platinum',
    price: '$80',
    monthlyPrice: '$80',
    duration: '30 days',
    features: [
      'All Silver & Gold features +',
      '5 platinum picks daily',
      'Parlay & prop bets',
      'Highest Value',
      'Avg odds > +170',
      'Cancel anytime',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_daily }),
    weeklyPrice: '49',
    dailyPrice: '12',
    highlight: false,
  },
];

const silverBets = bets.filter((bet) => bet.membership === 'silver');

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

// Get viewable bets
const viewableBets = computed(() => {
    return bets.filter(bet => canViewBet(bet));
});

// Get covered bets (ones that require upgrade)
const coveredBets = computed(() => {
    return bets.filter(bet => !canViewBet(bet));
});

// Function to hide specific links on mount
onMounted(() => {
    const targetHref = 'https://www.jcompsolu.com';

    // Function to hide all matching links
    const hideTargetLinks = () => {
        const allLinks = document.getElementsByTagName('a');
        Array.from(allLinks).forEach(link => {
            if (link.href === targetHref) {
                link.style.display = 'none';
            }
        });
    };

    // Initial check
    hideTargetLinks();

    // Set up MutationObserver to watch for new links
    const observer = new MutationObserver(() => {
        hideTargetLinks();
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Clean up observer when component is unmounted
    onBeforeUnmount(() => {
        observer.disconnect();
    });
});
function seeMorePicks() {
    // You can implement navigation, modal, or load more logic here
    // For example, navigate to a picks page:
    window.location.href = '/picks';
}
// Helper function to categorize bets by date
const categorizeBet = (bet) => {
    const betDate = new Date(bet.betting_date || bet.game_at);
    const today = new Date();
    const endOfWeek = new Date();
    endOfWeek.setDate(today.getDate() + (7 - today.getDay())); // Next Sunday
    
    // Check if it's golf and within this week
    const isGolf = (bet.sports || bet.sport || '').toLowerCase().includes('golf');
    
    if (betDate.toDateString() === today.toDateString()) {
        return { category: 'daily', priority: 1 };
    } else if (isGolf && betDate <= endOfWeek) {
        return { category: 'weekly_golf', priority: 2 };
    } else {
        return { category: 'futures', priority: 3 };
    }
};

const allGroupedBets = computed(() => {
  // Combine viewable and covered bets with proper marking
  const viewableWithFlag = viewableBets.value.map(bet => ({ ...bet, isCovered: false }));
  const coveredWithFlag = coveredBets.value.map(bet => ({ ...bet, isCovered: true }));
  
  // Merge all bets
  const all = [...viewableWithFlag, ...coveredWithFlag];
  
  // Filter by selected sport
  const filtered = selectedSport.value === 'all' 
    ? all 
    : all.filter(bet => (bet.sports || bet.sport) === selectedSport.value);
  
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
    const sport = bet.sports || bet.sport || 'Football';
    if (!acc[sport]) acc[sport] = [];
    acc[sport].push(bet);
    return acc;
  }, {});
});
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games">
            <!-- <link rel="stylesheet" href="https://rsms.me/inter/inter.css" /> -->
        </Head>

        <div class="min-vh-100">
            <!-- Hero Section -->
            <section class="position-relative overflow-hidden" style="background: url('/images/home-banner-bg.jpg') center/cover no-repeat; min-height: 480px;">
                <!-- Dark overlay for better text contrast -->
                <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(90deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 60%, transparent 100%);"></div>
                
                <!-- Content -->
                <div class="container-fluid position-relative h-100 px-4 px-lg-5" style="padding-top: 5rem; padding-bottom: 3rem; min-height: 480px;">
                    <div class="row align-items-center h-100">
                        <div class="col-lg-6">
                            <h1 class="display-3 fw-bold text-white mb-4" style="line-height: 1.1;">
                                We Win <span class="text-warning">Games</span>
                            </h1>
                            <p class="fs-5 text-white mb-4" style="opacity: 0.9;">
                                The most transparent sports betting platform with consistent profits and expert picks.
                            </p>
                            <div class="mb-5">
                                <Link href="/register" class="btn btn-warning btn-lg px-5 py-3 text-dark fw-bold">
                                    Start Winning Today
                                </Link>
                            </div>
                            <p class="text-white" style="opacity: 0.8; max-width: 500px;">
                                We Make Sports Betting Easy—by doing all the hard work analyzing hundreds of betting sources to give you the best picks.
                            </p>
                        </div>
                        
                        <!-- Players Image -->
                        <div class="col-lg-6 position-relative d-none d-lg-block">
                            <img src="/images/header-bg-players.png" alt="Sports Players" class="img-fluid position-absolute" style="right: -100px; bottom: -250px; max-height: 500px; width: auto;" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Betting Results Section -->
            <section class="py-2 animate-section-fadein" style="background-color: var(--bs-gray-dark);">
                <!-- Page Header -->
                <div class="container-fluid text-center pt-5 pb-4">
                    <h3 class="display-4 fw-bold text-white">
                        Proven Track Record
                    </h3>
                    <p class="fs-5 text-gray-light mb-4">Profits calculated for $30 bets across all our picks</p>
                </div>
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card bg-dark text-white h-100 shadow text-center">
                                <div class="card-body d-flex flex-column justify-content-center p-5">
                                    <h3 class="h4 fw-bold mb-4">This Year ({{ props.thisYear || new Date().getFullYear() }})</h3>
                                    <div class="display-4 fw-bold text-primary mb-4">
                                        ${{ formatMoney(props.thisYearProfit + 20) }}
                                    </div>
                                    <div class="fs-5 text-secondary mb-2">
                                        ROI: <span class="fw-bold text-white">{{ Math.round(props.thisYearROI ?? 0) }}%</span>
                                    </div>
                                    <div class="fs-5 text-secondary">
                                        Win/Loss: <span class="fw-bold text-white">{{ Math.round(props.thisYearWinLoss ?? 0) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Last Year -->
                        <div class="col-lg-4">
                            <div class="card bg-dark text-white h-100 shadow">
                                <div class="card-body">
                                    <h3 class="h5 fw-bold text-center mb-4">Our 4 Year Record</h3>
                                    <table class="table table-dark table-sm">
                                        <thead>
                                            <tr>
                                                <th class="text-secondary">Year</th>
                                                <th class="text-secondary">Profits</th>
                                                <th class="text-secondary">ROI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2022</td>
                                                <td>$15,769</td>
                                                <td>19%</td>
                                            </tr>
                                            <tr>
                                                <td>2023</td>
                                                <td>$21,678</td>
                                                <td>16%</td>
                                            </tr>
                                            <tr>
                                                <td>2024</td>
                                                <td>$13,509</td>
                                                <td>15%</td>
                                            </tr>
                                            <tr>
                                                <td>2025 to date</td>
                                                <td>$12,600</td>
                                                <td>40%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- Golf Record -->
                        <div class="col-lg-4">
                            <div class="card bg-dark text-white h-100 shadow">
                                <div class="card-body">
                                    <h3 class="h5 fw-bold text-center mb-4">Our 4 Year Golf Record</h3>
                                    <table class="table table-dark table-sm">
                                        <thead>
                                            <tr>
                                                <th class="text-secondary">Year</th>
                                                <th class="text-secondary">Winners</th>
                                                <th class="text-secondary">ROI</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2022</td>
                                                <td>12</td>
                                                <td>61%</td>
                                            </tr>
                                            <tr>
                                                <td>2023</td>
                                                <td>18</td>
                                                <td>83%</td>
                                            </tr>
                                            <tr>
                                                <td>2024</td>
                                                <td>14</td>
                                                <td>69%</td>
                                            </tr>
                                            <tr>
                                                <td>2025 to date</td>
                                                <td>6</td>
                                                <td>93%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-body-bg);">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <!-- Image on the left -->
                        <div class="col-md-6 text-center mb-4 mb-md-0">
                            <div class="position-relative">
                                <div class="position-absolute" style="top: -20px; left: -20px; width: 120%; height: 120%; background: radial-gradient(circle, rgba(124, 58, 237, 0.2) 0%, transparent 70%); filter: blur(40px);"></div>
                                <img
                                    src="/images/profit-picks.png"
                                    alt="Profit Picks"
                                    class="img-fluid rounded-3 shadow-lg animate-bounceY position-relative"
                                    style="max-width: 400px;"
                                />
                            </div>
                        </div>

                        <!-- Content on the right -->
                        <div class="col-md-6">
                            <h2 class="display-5 fw-bold text-white mb-4">Why Choose WeWinGames?</h2>
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-start mb-3">
                                    <svg class="text-warning me-2 flex-shrink-0" style="width: 24px; height: 24px; margin-top: 2px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Best Returns in the Business:</span> ROI of 15% in 2024 across all sports, with 60% on premium Platinum picks.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg class="text-warning me-2 flex-shrink-0" style="width: 24px; height: 24px; margin-top: 2px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Multiple Sports:</span> Football, Basketball, Hockey, Baseball, Soccer, Golf, UFC.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg class="text-warning me-2 flex-shrink-0" style="width: 24px; height: 24px; margin-top: 2px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">10 to 20 Picks a Day:</span> Expert picks posted throughout the day.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg class="text-warning me-2 flex-shrink-0" style="width: 24px; height: 24px; margin-top: 2px;" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Transparent Results:</span> We analyze hundreds of betting sources to give you the best picks. See your <a href="https://docs.google.com/spreadsheets/d/1dNj41tUxP2sdnMLWJ_Oz_K9zrn8kT6Kd1AeuSraC7xw/edit?gid=569762228#gid=569762228" class="text-primary">Google Sheet</a> with 12000+ picks!
                                    </span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Google Reviews Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-gray-dark);">
                <GoogleReviews :testimonials="props.testimonials" />
            </section>

            <!-- Free Picks Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-body-bg);">
                <div class="container-fluid">
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-4">Today's Free Picks</h2>
                        <p class="fs-5 text-gray-light mb-5">Get a taste of our expert analysis - no credit card required</p>
                    </div>
                    
                    <!-- Sports Filter Bar -->
                    <div class="sports-filter-bar mb-4 p-3 rounded" style="background: linear-gradient(90deg, #2e4057 0%, #1a2332 50%, #2e4057 100%);">
                        <div class="d-flex align-items-center gap-3 overflow-auto pb-2" style="scrollbar-width: thin;">
                            <button 
                                v-for="sport in availableSports" 
                                :key="sport"
                                @click="selectedSport = sport"
                                class="btn btn-sm px-4 py-2 text-nowrap d-flex align-items-center gap-2"
                                :class="selectedSport === sport ? 'btn-warning text-dark' : 'btn-outline-light'"
                            >
                                <i :class="sportIcons[sport] || 'bi-star'"></i>
                                {{ sport === 'all' ? 'All Sports' : sport }}
                            </button>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <GroupedBetCards :grouped-bets="allGroupedBets" />
                    </div>
                    <div class="text-center mt-5">
                        <Link
                            href="/todays-bets"
                            class="btn btn-primary btn-lg px-5 py-3"
                        >
                            <i class="bi bi-arrow-right me-2"></i>
                            View All Picks
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Subscription Plans Section -->
            <section class="py-4 animate-section-fadein" id="pricing" style="background-color: var(--bs-body-bg);">
                <div class="container-fluid">
                    <div class="text-center mb-4">
                        <h2 class="display-5 fw-bold text-white mb-2">Choose Your Plan</h2>
                        <p class="fs-6 text-gray-light mb-4">Start with a free trial, upgrade anytime</p>
                    </div>
                    
                    <PricingCards :plans="plans" />
                    
                    <!-- Subscription Plans Section -->
                    <div class="mt-5">
                        <h3 class="h4 fw-bold text-white text-center mb-4">Subscription Plans</h3>
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div class="card" style="background-color: var(--bs-card-bg); border: 2px solid #ff7900;">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="/images/draftkings_logo_1.png" alt="DraftKings Sportsbook" style="height: 40px; width: auto;">
                                                <div class="text-start">
                                                    <h4 class="h6 text-white mb-1">DraftKings Sportsbook</h4>
                                                    <p class="text-gray-light mb-0 small">Bet $10, get $300 in free bets if your bet wins.</p>
                                                </div>
                                            </div>
                                            <a href="https://sportsbook.draftkings.com/acq-bet-and-get?pcid=420313&pcn=Promo1&pcrid=xx&pcrn=xx&pscid=xx&pscn=WeWinGames&psn=1967&referrer=singular_click_id%3d63c8a1b6-2dcc-42b9-928e-bdb91b06dee3&sl_id=tqhb&wpcid=420313&wpcn=Promo1&wpcrid=xx&wpcrn=xx&wpscid=xx&wpscn=WeWinGames&wpsrc=1967" target="_blank" rel="noopener" class="btn btn-primary btn-sm px-4 mt-2 mt-md-0">
                                                <i class="bi bi-box-arrow-up-right me-2"></i>
                                                Claim Offer
                                            </a>
                                        </div>
                                    </div>
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
@keyframes fadein {
  to {
    opacity: 1;
    transform: none;
  }
}
.animate-fadein {
  animation: fadein 1.2s ease 0.2s forwards;
  transform: translateY(30px);
}
@keyframes fadein-card {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
.animate-fadein-card {
  opacity: 0;
  animation: fadein-card 0.8s cubic-bezier(0.4,0,0.2,1) forwards;
}
@keyframes section-fadein {
  from {
    opacity: 0;
    transform: translateY(40px);
  }
  to {
    opacity: 1;
    transform: none;
  }
}
.animate-section-fadein {
  opacity: 0;
  animation: section-fadein 1s cubic-bezier(0.4,0,0.2,1) forwards;
}
@keyframes bounceY {
  0%, 100% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0.8,0,1,1);
  }
  20% {
    transform: translateY(-10px);
    animation-timing-function: cubic-bezier(0.2,0,0.8,1);
  }
  40% {
    transform: translateY(-20px);
    animation-timing-function: cubic-bezier(0.2,0,0.8,1);
  }
  60% {
    transform: translateY(-10px);
    animation-timing-function: cubic-bezier(0.2,0,0.8,1);
  }
  80% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0.8,0,1,1);
  }
}
.animate-bounceY {
  animation: bounceY 2.5s cubic-bezier(0.4,0,0.2,1) infinite;
  will-change: transform;
}

.text-shadow {
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
}

/* Custom scrollbar for sports filter */
.sports-filter-bar .overflow-auto::-webkit-scrollbar {
    height: 6px;
}

.sports-filter-bar .overflow-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.sports-filter-bar .overflow-auto::-webkit-scrollbar-thumb {
    background: #ffc107;
    border-radius: 3px;
}

.sports-filter-bar .overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #ffca2c;
}
</style>