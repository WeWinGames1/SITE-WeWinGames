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
import { onMounted, onBeforeUnmount, computed } from 'vue';
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
}>(); // Get ROI data by subscription level
const isGold = auth?.user?.data?.subscriptions[0]?.type === 'gold'; // Check if user is subscribed to Gold level
const isSilver = auth?.user?.data?.subscriptions[0]?.type === 'silver';
const isPlatinum = auth?.user?.data?.subscriptions[0]?.type === 'platinum';
const isDefault = auth?.user?.data?.subscriptions[0]?.type === 'default';

const bronzeBets = bets.filter((bet) => bet.membership === 'bronze');
const silver_monthly = page.props.env.SILVER_MONTHLY;
const silver_weekly = page.props.env.SILVER_WEEKLY;
const gold_weekly = page.props.env.GOLD_WEEKLY;
const platinum_weekly = page.props.env.PLATINUM_WEEKLY;
const silver_daily = page.props.env.SILVER_DAILY;
const gold_daily = page.props.env.GOLD_DAILY;
const platinum_daily = page.props.env.PLATINUM_DAILY;
const gold_monthly = page.props.env.GOLD_MONTHLY;
const platinum_monthly = page.props.env.PLATINUM_MONTHLY;
const plans = [
  {
    name: 'Silver',
    price: '$60',
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
    weeklyPrice: '20',
    dailyPrice: '5',
    highlight: false,
  },
  {
    name: 'Gold',
    price: '$110',
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
    weeklyPrice: '39',
    dailyPrice: '10',
    highlight: true,
  },
  {
    name: 'Platinum',
    price: '$149',
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

// Get covered bets (for non-authenticated or free users)
const coveredBets = computed(() => {
  // Non-bronze bets (silver, gold, platinum)
  const nonBronzeBets = bets.filter(bet => bet.membership !== 'bronze');
  
  // If user is not authenticated, all non-bronze bets are covered
  if (!auth.user) return nonBronzeBets;
  
  // If user is default/bronze, all non-bronze bets are covered
  if (isDefault) return nonBronzeBets;
  
  // If user is silver, gold and platinum bets are covered
  if (isSilver) return nonBronzeBets.filter(bet => bet.membership === 'gold' || bet.membership === 'platinum');
  
  // If user is gold, only platinum bets are covered
  if (isGold) return nonBronzeBets.filter(bet => bet.membership === 'platinum');
  
  // If user is platinum, no bets are covered
  if (isPlatinum) return [];
  
  // Default case: all non-bronze bets are covered
  return nonBronzeBets;
});

// Get viewable bets
const viewableBets = computed(() => {
  // If not authenticated, only show bronze bets
  if (!auth.user) return bronzeBets;
  
  // If default tier, only show bronze bets
  if (isDefault) return bronzeBets;
  
  // If silver, show bronze and silver bets
  if (isSilver) return bets.filter(bet => bet.membership === 'bronze' || bet.membership === 'silver');
  
  // If gold, show bronze, silver, and gold bets
  if (isGold) return bets.filter(bet => bet.membership !== 'platinum');
  
  // If platinum, show all bets
  if (isPlatinum) return bets;
  
  // Default case: only show bronze bets
  return bronzeBets;
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
const allGroupedBets = computed(() => {
  // Get all non-bronze bets first, then bronze bets last
  
  // Merge, bronze last so it overwrites by id
  const all = [...(viewableBets.value || []), ...(coveredBets.value || [])].filter(
    (bet, idx, arr) => arr.findIndex(b => b.id === bet.id) === idx
  );
  return all.reduce((acc, bet) => {
    if (!acc[bet.sports]) acc[bet.sports] = [];
    acc[bet.sports].push(bet);
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
                                        ROI: <span class="fw-bold text-white">{{ Math.round(props.thisYearROI ?? 0, 2) }}%</span>
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
                <GoogleReviews />
            </section>

            <!-- Free Picks Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-body-bg);">
                <div class="container-fluid">
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-4">Today's Free Picks</h2>
                        <p class="fs-5 text-gray-light mb-5">Get a taste of our expert analysis - no credit card required</p>
                    </div>
                    <div class="mt-4">
                        <GroupedBetCards :grouped-bets="allGroupedBets" />
                    </div>
                    <div class="text-center mt-5">
                        <Link
                            href="/todays-tips"
                            class="btn btn-primary btn-lg px-5 py-3"
                        >
                            <i class="bi bi-arrow-right me-2"></i>
                            View All Picks
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Subscription Plans Section -->
            <section class="py-5 animate-section-fadein" id="pricing" style="background-color: var(--bs-body-bg);">
                <div class="container-fluid">
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-4">Choose Your Plan</h2>
                        <p class="fs-5 text-gray-light mb-5">Start with a free trial, upgrade anytime</p>
                    </div>
                    
                    <PricingCards :plans="plans" />
                    
                    <!-- DraftKings Casino Affiliate -->
                    <div class="row justify-content-center mt-5">
                        <div class="col-lg-8">
                            <div class="card text-center" style="background-color: var(--bs-card-bg); border: 2px solid var(--bs-purple);">
                                <div class="card-body p-5">
                                    <img src="/images/draftkings_logo_1.png" alt="DraftKings Casino" class="mb-4" style="height: 60px; width: auto;">
                                    <h3 class="h4 text-white mb-3">DraftKings Casino</h3>
                                    <p class="text-gray-light mb-4">Bet $10 and get 350 free spins + up to $1000 back in casino credits for any day 1 losses</p>
                                    <a href="https://bit.ly/42HzBOi?r=lp&m=Mp1umLbk8Wo" target="_blank" rel="noopener" class="btn btn-lg btn-primary px-5">
                                        <i class="bi bi-box-arrow-up-right me-2"></i>
                                        Claim Offer
                                    </a>
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
</style>