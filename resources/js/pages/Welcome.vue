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
      'Best tipsters & ROI',
      'Cancel anytime',
      '24/7 support',
    ],
    monthlyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_monthly }),
    weeklyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_weekly }),
    dailyLink: route('subscription.checkout', { subscription_name: 'platinum', subscription_price_id: platinum_daily }),
    weeklyPrice: '55',
    dailyPrice: '15',
    highlight: false,
  },
];
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
onMounted(() => {
    const targetHref = 'https://elfsight.com/google-reviews-widget/?utm_source=websites&utm_medium=clients&utm_content=google-reviews&utm_term=wewingames.test&utm_campaign=free-widget';

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
  const all = [...viewableBets, ...coveredBets].filter(
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

        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-purple text-gray-200">
            <!-- Hero Section -->
            <section class="relative bg-cover bg-center h-screen animate-section-fadein" style="background-image: url('/images/hero-3.svg');">
                <!-- Overlay -->
                <div class="absolute inset-1 bg-black opacity-50 z-2"></div>
                <!-- Content -->
                <div
                    class="container mx-auto px-4 h-full flex flex-col justify-center items-start text-left relative z-10
                    opacity-0 animate-fadein"
                >
                    <h1 class="text-5xl font-extrabold" style="color: #C8B5F3;">
                        <strong>We Win Games</strong>
                    </h1>
                    <p class="mt-4 text-lg text-white drop-shadow-lg">
                        The most transparent sports betting platform with consistent profits and expert picks.
                    </p>
                    <Link href="/register" class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg shadow-lg text-lg">
                        Start Winning Today
                    </Link>

                    <p class="mt-4 text-lg text-white drop-shadow-lg break-words max-w-xl">
                        We Make Sports Betting Easy—by doing all the hard work analyzing hundreds of betting sources to give you the best picks.
                    </p>
                </div>
            </section>

            <!-- Betting Results Section -->
            <section class="py-10 bg-transparent animate-section-fadein">
                <!-- Page Header -->
                <div class="container mx-auto px-4 pt-10 pb-4">
                    <h3 class="text-2xl md:text-4xl font-extrabold text-white text-center bg-transparent">
                        Profits for $30 Bets Across All Our Picks
                    </h3>
                </div>
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                       <div class="bg-gray-800 rounded-lg p-8 shadow text-center flex flex-col justify-center h-full">
    <h3 class="text-2xl font-extrabold text-white mb-4 mt-2">This Year ({{ props.thisYear || new Date().getFullYear() }})</h3>
    <div class="text-4xl font-extrabold text-indigo-400 mb-4 mt-2">
      ${{ formatMoney(props.thisYearProfit + 20) }}
    </div>
    <div class="text-lg text-gray-300 mb-2">
      ROI: <span class="font-bold text-white">{{ Math.round(props.thisYearROI ?? 0, 2) }}%</span>
    </div>
    <div class="text-lg text-gray-300">
      Win/Loss: <span class="font-bold text-white">{{ Math.round(props.thisYearWinLoss ?? 0) }}%</span>
    </div>
</div>
                        <!-- Last Year -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center flex flex-col items-center animate-section-fadein">
                            <h3 class="text-lg font-bold text-white mb-4">Our 4 Year Record</h3>
                            <table class="w-full text-left text-sm bg-transparent rounded-lg border border-transparent mb-2">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Year</th>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Profits</th>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">ROI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2022</td>
                                        <td class="px-4 py-2 border-r border-gray-700">$15,769</td>
                                        <td class="px-4 py-2">19%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2023</td>
                                        <td class="px-4 py-2 border-r border-gray-700">$21,678</td>
                                        <td class="px-4 py-2">16%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2024</td>
                                        <td class="px-4 py-2 border-r border-gray-700">$13,509</td>
                                        <td class="px-4 py-2">15%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2025 to date</td>
                                        <td class="px-4 py-2 border-r border-gray-700">$12,600</td>
                                        <td class="px-4 py-2">40%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Last Month -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center flex flex-col items-center animate-section-fadein">
                            <h3 class="text-lg font-bold text-white mb-4">Our 4 Year Golf Record</h3>
                            <table class="w-full text-left text-sm bg-transparent rounded-lg border border-transparent mb-2">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Year</th>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Winners</th>
                                        <th class="px-4 py-2 text-gray-300 border-b border-gray-700">ROI</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2022</td>
                                        <td class="px-4 py-2 border-r border-gray-700">12</td>
                                        <td class="px-4 py-2">61%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2023</td>
                                        <td class="px-4 py-2 border-r border-gray-700">18</td>
                                        <td class="px-4 py-2">83%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2024</td>
                                        <td class="px-4 py-2 border-r border-gray-700">14</td>
                                        <td class="px-4 py-2">69%</td>
                                    </tr>
                                    <tr>
                                        <td class="px-4 py-2 border-r border-gray-700">2025 to date</td>
                                        <td class="px-4 py-2 border-r border-gray-700">6</td>
                                        <td class="px-4 py-2">93%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Why Choose Us Section -->
            <section class="py-16 bg-gradient-to-b from-gray-900 to-black animate-section-fadein">
                <div class="container mx-auto px-4 flex flex-col md:flex-row items-center text-center md:text-left">
                    <!-- Image on the left -->
                    <div class="md:w-1/2 flex justify-center mb-8 md:mb-0">
    <img
        src="/images/profit-picks.png"
        alt="Profit Picks"
        class="max-w-xs w-full rounded-lg shadow-lg animate-bounceY"
    />
</div>

                    <!-- Content on the right -->
                    <div class="md:w-1/2 md:pl-12">
                        <h2 class="text-3xl font-bold text-white">Why Choose Us?</h2>
                        <ul class="mt-4 space-y-3">
                            <li class="flex items-start gap-2 text-gray-400">
                                <svg class="w-6 h-6 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    <span class="font-bold text-white">Best Returns in the Business:</span> ROI of 15% in 2024 across all sports, with 60% on premium Platinum picks.
                                </span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <svg class="w-6 h-6 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    <span class="font-bold text-white">Multiple Sports:</span> Football, Basketball, Hockey, Baseball, Soccer, Golf, UFC.
                                </span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <svg class="w-6 h-6 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    <span class="font-bold text-white">10 to 20 Picks a Day:</span> Expert picks posted throughout the day.
                                </span>
                            </li>
                            <li class="flex items-start gap-2 text-gray-400">
                                <svg class="w-6 h-6 text-yellow-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>
                                    <span class="font-bold text-white">Transparent Results:</span> We analyze hundreds of betting sources to give you the best picks. See your <a href="https://docs.google.com/spreadsheets/d/1dNj41tUxP2sdnMLWJ_Oz_K9zrn8kT6Kd1AeuSraC7xw/edit?gid=569762228#gid=569762228">Google Sheet</a> with 12000+ picks!
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- Google Reviews Section -->
            <GoogleReviews class="animate-section-fadein" />

            <!-- Free Picks Section -->
            <section class="py-16 bg-transparent animate-section-fadein">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold text-white">Today's Free Picks</h2>
                    <p class="mt-4 text-gray-400">Get a taste of our expert picks for free!</p>
                    <div class="container mx-auto px-4 text-center">
                    <div class="mt-8">
                        <GroupedBetCards :grouped-bets="allGroupedBets"  />
                    </div>
                </div>
                    <Link
                        href="/todays-tips"
                        class="mt-8 inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg shadow-lg text-lg font-semibold transition"
                    >
                        See More Picks
                    </Link>
                </div>
            </section>

            <!-- Subscription Plans Section -->
            <section class="py-16 bg-transparent animate-section-fadein">
                <div class="container mx-auto px-4 text-center">
                    <h2 class="text-3xl font-bold text-white">Subscription Plans</h2>
                    
                    <!-- Affiliate Links Component -->
                    <AffiliateLinks
                        :affiliates="[
                            {
                                logo: '/images/draft_kings_logo_2.jpeg',
                                text: 'Draftkings Sportsbook',
                                url: 'https://sportsbook.draftkings.com/acq-bet-and-get?pcid=420313&pcn=Promo1&pcrid=xx&pcrn=xx&pscid=xx&pscn=WeWinGames&psn=1967&referrer=singular_click_id%3d63c8a1b6-2dcc-42b9-928e-bdb91b06dee3&sl_id=tqhb&wpcid=420313&wpcn=Promo1&wpcrid=xx&wpcrn=xx&wpscid=xx&wpscn=WeWinGames&wpsrc=1967',
                                description: 'Bet $10, get $300 in free bets if your bet wins.'
                            }
                        ]"
                        class="mb-8"
                    />

                    <p class="mt-4 mb-6 text-gray-400">Choose the plan that fits your betting style and start winning today.</p>
                    <PricingCards :plans="plans" />
                    <!-- Affiliate Links Component -->
                    <AffiliateLinks
                        :affiliates="[
                            {
                                logo: '/images/draftkings_logo_1.png',
                                text: 'Draftkings Casino',
                                url: 'https://bit.ly/42HzBOi?r=lp&m=Mp1umLbk8Wo',
                                description: 'Bet $10 and get 350 free spins + up to $1000 back in casino credits for any day 1 losses'
                            }
                        ]"
                        class="mb-8"
                    />
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
</style>
