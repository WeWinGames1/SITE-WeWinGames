<script setup lang="ts">
import SimpleBetCard from '@/components/SimpleBetCard.vue';
import TeaserBetCard from '@/components/TeaserBetCard.vue';
import TestimonialsCarousel from '@/components/TestimonialsCarousel.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import PricingCards from '../components/PricingCards.vue';
function formatMoney(val: number | undefined) {
    return Math.round(val ?? 0).toLocaleString();
}
const page = usePage();
const auth = page.props.auth || null; // Get the logged-in user
const bets = page.props.freeBets || []; // Get the daily bet picks
const totalBetsPerSport = page.props.totalBetsPerSport || {}; // Get total counts per sport
const props = defineProps<{
    roiData: Record<string, number>;
    levelProfitRoiData: Array<{ level: string; profit: number; roi: number }>;
    sportProfitRoiData: Array<{ sport: string; profit: number; roi: number }>;
    lastYearProfit?: number;
    lastYearROI?: number;
    lastYear?: number;
    thisYear?: number;
    thisYearProfit?: number;
    thisYearROI?: number;
    monthlyProfit?: number;
    thisMonthProfit?: number;
    thisMonthROI?: number;
    winRatio?: number;
    thisYearWinLoss?: number;
    lastYearWinLoss?: number;
    lastMonthProfit?: number;
    lastMonthWinLoss?: number;
    lastMonthROI?: number;
    thisMonthWinLoss?: number;
    golfWinners2026?: number;
    golfROI2026?: number;
    testimonials?: any[];
    enableDraftkingsCta?: boolean;
}>(); // Get ROI data by subscription level
// Get user's subscription type from auth.currentTier
const getUserSubscriptionType = () => {
    if (!auth?.user) return 'free';

    // Use currentTier from auth which already handles ambassador/gifted/override
    if (auth.currentTier) {
        return auth.currentTier.toLowerCase();
    }

    return 'free';
};

const userSubscriptionType = getUserSubscriptionType();
const isAdmin = auth?.isAdmin || false;

// Legacy variables for backward compatibility
const isGold = userSubscriptionType === 'gold';
const isPlatinum = userSubscriptionType === 'platinum';
const isDefault = userSubscriptionType === 'free';

// Helper to check if bet is Free tier (including legacy Bronze/Silver)
const isFreeTierBet = (bet) => {
    const level = bet.membership?.toLowerCase() || 'free';
    return level === 'free' || level === 'bronze' || level === 'silver';
};

const freeBets = bets.filter((bet) => isFreeTierBet(bet));

// Sports filter
const selectedSport = ref('all');
const availableSports = computed(() => {
    const sportsSet = new Set(bets.map((bet) => bet.sports || bet.sport || 'Football'));
    return ['all', ...Array.from(sportsSet)];
});

const sportIcons = {
    Football: 'bi-trophy-fill',
    Basketball: 'bi-dribbble',
    Hockey: 'bi-snow2',
    Baseball: 'bi-circle',
    Soccer: 'bi-globe',
    Golf: 'bi-flag',
    'Ultimate Fighting Championship': 'bi-person-arms-up',
    all: 'bi-grid-3x3-gap-fill',
};
// Use the dynamic Stripe prices and products from shared props
const stripePrices = page.props.stripePrices || {};
const stripeProducts = page.props.stripeProducts || {};
// Build checkout URL based on auth state - guests always go to quick checkout
const buildCheckoutLink = (tier: string, period: string, priceId: string): string => {
    // Logged in users always go to regular checkout
    if (auth?.user) {
        return route('subscription.checkout', {
            subscription_name: tier,
            subscription_price_id: priceId,
        });
    }

    // Guests go to quick checkout (registration disabled)
    return route('quick-checkout', { plan: tier, period: period });
};

// Helper function to get features for a specific tier and billing period
const getFeatures = (tier: string, billingPeriod: string = 'monthly') => {
    const products = stripeProducts[billingPeriod];
    if (!products || !products[tier.toLowerCase()]) {
        // Return default features if not found
        return getDefaultFeatures(tier);
    }
    return products[tier.toLowerCase()].features || getDefaultFeatures(tier);
};

// Default features as fallback
const getDefaultFeatures = (tier: string) => {
    switch (tier.toLowerCase()) {
        case 'free':
            return ['Access to Free picks daily', 'Basic straight bets', 'Email notifications', 'Community access', '24/7 support'];
        case 'gold':
            return [
                'All Free picks included',
                'Access to Gold-tier premium picks',
                'Priority email notifications',
                'Advanced analytics',
                'Early access to picks',
                'Cancel anytime',
            ];
        case 'platinum':
            return [
                'All Free & Gold picks included',
                'Access to ALL premium picks',
                'Instant notifications',
                'Premium analytics dashboard',
                'VIP support',
                'Personal betting consultant',
            ];
        default:
            return [];
    }
};

// Get prices from stripeProducts
const getPrice = (tier: string, billingPeriod: string = 'monthly') => {
    const products = stripeProducts[billingPeriod];
    if (!products || !products[tier.toLowerCase()]) {
        // Return default prices if not found
        return getDefaultPrice(tier, billingPeriod);
    }
    const amount = products[tier.toLowerCase()].amount;
    return billingPeriod === 'monthly' ? `$${amount}` : amount.toString();
};

// Default prices as fallback
const getDefaultPrice = (tier: string, billingPeriod: string) => {
    const defaults: Record<string, Record<string, string>> = {
        gold: { monthly: '$45', weekly: '15', daily: '5' },
        platinum: { monthly: '$90', weekly: '30', daily: '10' },
    };
    return defaults[tier.toLowerCase()]?.[billingPeriod] || '$0';
};

const plans = computed(() => [
    {
        name: 'Gold',
        price: getPrice('gold', 'monthly'),
        monthlyPrice: getPrice('gold', 'monthly'),
        duration: '30 days',
        features: getFeatures('gold', 'monthly'),
        monthlyFeatures: getFeatures('gold', 'monthly'),
        weeklyFeatures: getFeatures('gold', 'weekly'),
        dailyFeatures: getFeatures('gold', 'daily'),
        monthlyLink: buildCheckoutLink('gold', 'monthly', stripePrices.gold_monthly),
        weeklyLink: buildCheckoutLink('gold', 'weekly', stripePrices.gold_weekly),
        dailyLink: buildCheckoutLink('gold', 'daily', stripePrices.gold_daily),
        weeklyPrice: getPrice('gold', 'weekly'),
        dailyPrice: getPrice('gold', 'daily'),
        highlight: false,
    },
    {
        name: 'Platinum',
        price: getPrice('platinum', 'monthly'),
        monthlyPrice: getPrice('platinum', 'monthly'),
        duration: '30 days',
        features: getFeatures('platinum', 'monthly'),
        monthlyFeatures: getFeatures('platinum', 'monthly'),
        weeklyFeatures: getFeatures('platinum', 'weekly'),
        dailyFeatures: getFeatures('platinum', 'daily'),
        monthlyLink: buildCheckoutLink('platinum', 'monthly', stripePrices.platinum_monthly),
        weeklyLink: buildCheckoutLink('platinum', 'weekly', stripePrices.platinum_weekly),
        dailyLink: buildCheckoutLink('platinum', 'daily', stripePrices.platinum_daily),
        weeklyPrice: getPrice('platinum', 'weekly'),
        dailyPrice: getPrice('platinum', 'daily'),
        highlight: true,
    },
]);

// Determine which bets can be viewed based on subscription
// New tier structure: Free (formerly Bronze/Silver), Gold, Platinum
const canViewBet = (bet) => {
    if (isAdmin) return true;

    // Check both 'level' and 'membership' fields for compatibility
    const betLevel = (bet.level || bet.membership || 'free').toLowerCase();

    // Free tier includes legacy Bronze and Silver
    const isFreeLevel = betLevel === 'free' || betLevel === 'bronze' || betLevel === 'silver';

    switch (userSubscriptionType) {
        case 'free':
            // Free users can only view Free tier picks (and legacy Bronze/Silver)
            return isFreeLevel;
        case 'gold':
            // Gold users can view Free and Gold picks
            return isFreeLevel || betLevel === 'gold';
        case 'platinum':
            // Platinum users can view all picks
            return true;
        default:
            // Default to Free tier access
            return isFreeLevel;
    }
};

// Get viewable bets
const viewableBets = computed(() => {
    return bets.filter((bet) => canViewBet(bet));
});

// Get covered bets (ones that require upgrade)
const coveredBets = computed(() => {
    return bets.filter((bet) => !canViewBet(bet));
});

// Function to hide specific links on mount
onMounted(() => {
    const targetHref = 'https://www.jcompsolu.com';

    // Function to hide all matching links
    const hideTargetLinks = () => {
        const allLinks = document.getElementsByTagName('a');
        Array.from(allLinks).forEach((link) => {
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

// Helper function to create teaser cards for a sport
const createTeaserCards = (sport, visibleBetsCount, coveredBetsCount = null) => {
    // Calculate the actual number of covered/premium bets
    let remainingCount = 0;

    if (coveredBetsCount !== null) {
        // Use the provided covered bets count
        remainingCount = coveredBetsCount;
    } else {
        // Use the total count from backend minus visible bets
        const totalBetsForSport = totalBetsPerSport[sport] || 0;
        remainingCount = totalBetsForSport - visibleBetsCount;
    }

    // Create teaser card for guests and free users
    return [
        {
            id: `teaser-${sport}`,
            isTeaser: true,
            sport: sport,
            sports: sport,
            remainingCount: remainingCount,
            isGuest: !auth?.user,
        },
    ];
};

// Simplified home page picks: 2 picks + 1 teaser card (no sport headers)
const homePagePicks = computed(() => {
    const isGuest = !auth?.user;

    // Get the first 2 viewable bets (regardless of sport)
    const firstTwoPicks = viewableBets.value.slice(0, 2);

    // Calculate total premium picks count
    const totalPremiumCount = Object.values(totalBetsPerSport).reduce((sum: number, count: number) => sum + count, 0) - viewableBets.value.length;

    // Create a teaser card for the remaining picks
    const teaserCard = {
        id: 'home-teaser',
        isTeaser: true,
        sport: 'Premium',
        sports: 'Premium',
        remainingCount: Math.max(0, totalPremiumCount),
        isGuest: isGuest,
    };

    return {
        picks: firstTwoPicks,
        teaser: teaserCard,
    };
});

const allGroupedBets = computed(() => {
    const isGuest = !auth?.user;

    // Combine viewable and covered bets with proper marking
    const viewableWithFlag = viewableBets.value.map((bet) => ({ ...bet, isCovered: false }));
    const coveredWithFlag = coveredBets.value.map((bet) => ({ ...bet, isCovered: true }));

    // For guests: only use viewable bets (no covered cards shown)
    // For logged-in users: merge all bets
    const all = isGuest ? viewableWithFlag : [...viewableWithFlag, ...coveredWithFlag];

    // Filter by selected sport
    const filtered = selectedSport.value === 'all' ? all : all.filter((bet) => (bet.sports || bet.sport) === selectedSport.value);

    // Sort bets by priority
    const sorted = filtered.sort((a, b) => {
        const catA = categorizeBet(a);
        const catB = categorizeBet(b);

        // First sort by priority
        if (catA.priority !== catB.priority) {
            return catA.priority - catB.priority;
        }

        // Then by membership level (Free first, then Gold, then Platinum)
        const membershipOrder = { free: 1, bronze: 1, silver: 1, gold: 2, platinum: 3 };
        const memA = membershipOrder[a.membership?.toLowerCase()] || 4;
        const memB = membershipOrder[b.membership?.toLowerCase()] || 4;

        return memA - memB;
    });

    // Group by sport
    const grouped = sorted.reduce((acc, bet) => {
        const sport = bet.sports || bet.sport || 'Football';
        if (!acc[sport]) acc[sport] = [];
        acc[sport].push(bet);
        return acc;
    }, {});

    // Add teaser cards for guest/free users
    if (isGuest || isDefault) {
        if (isGuest) {
            // Guest users: Show limited free picks + one teaser card PER SPORT with premium picks
            // Don't show individual covered cards

            // For each sport with visible bets, add teaser card if there are premium picks
            Object.keys(grouped).forEach((sport) => {
                const sportBets = grouped[sport];
                const visibleBetsForSport = sportBets.filter((bet) => !bet.isCovered);
                const totalForSport = totalBetsPerSport[sport] || 0;
                const premiumCountForSport = Math.max(0, totalForSport - visibleBetsForSport.length);

                // Create teaser card if there are premium picks for this sport
                const teaserCards = premiumCountForSport > 0 ? createTeaserCards(sport, visibleBetsForSport.length, premiumCountForSport) : [];

                // Show visible bets + teaser card (no covered cards)
                grouped[sport] = [...visibleBetsForSport, ...teaserCards];
            });

            // Also add teaser cards for sports that have picks but no visible free bets
            Object.keys(totalBetsPerSport).forEach((sport) => {
                if (!grouped[sport] && totalBetsPerSport[sport] > 0) {
                    // This sport has only premium picks (no free picks visible)
                    if (selectedSport.value === 'all' || selectedSport.value === sport) {
                        grouped[sport] = createTeaserCards(sport, 0, totalBetsPerSport[sport]);
                    }
                }
            });
        } else {
            // Free users (logged in): Show free picks + covered cards with teasers
            Object.keys(grouped).forEach((sport) => {
                const sportBets = grouped[sport];
                const visibleBetsForSport = sportBets.filter((bet) => !bet.isCovered);
                const coveredBetsForSport = sportBets.filter((bet) => bet.isCovered);
                const totalForSport = totalBetsPerSport[sport] || 0;
                const remainingCount = totalForSport;

                const teaserCards = createTeaserCards(sport, visibleBetsForSport.length, remainingCount);

                grouped[sport] = [...visibleBetsForSport, ...teaserCards, ...coveredBetsForSport];
            });

            // Also check if any sports have bets but aren't shown due to filtering
            Object.keys(totalBetsPerSport).forEach((sport) => {
                if (!grouped[sport] && totalBetsPerSport[sport] > 0) {
                    if (selectedSport.value === 'all' || selectedSport.value === sport) {
                        grouped[sport] = createTeaserCards(sport, 0, totalBetsPerSport[sport]);
                    }
                }
            });
        }
    }

    return grouped;
});
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games">
            <!-- <link rel="stylesheet" href="https://rsms.me/inter/inter.css" /> -->
        </Head>

        <div class="min-vh-100">
            <!-- Hero Section -->
            <section
                class="position-relative overflow-hidden"
                style="background: url('/images/home-banner-bg.jpg') center/cover no-repeat; min-height: 480px"
            >
                <!-- Dark overlay for better text contrast -->
                <div
                    class="position-absolute top-0 start-0 w-100 h-100"
                    style="background: linear-gradient(90deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.3) 60%, transparent 100%)"
                ></div>

                <!-- Content -->
                <div class="container-fluid position-relative h-100 px-4 px-lg-5" style="padding-top: 5rem; padding-bottom: 3rem; min-height: 480px">
                    <div class="row align-items-center h-100">
                        <div class="col-lg-6">
                            <h1 class="display-3 fw-bold text-white mb-4" style="line-height: 1.1">We Win <span class="text-warning">Games</span></h1>
                            <p class="fs-5 text-white mb-4" style="opacity: 0.9">
                                The most transparent sports betting platform with consistent profits and expert picks.
                            </p>
                            <div class="mb-5">
                                <Link
                                    :href="route('quick-checkout', { plan: 'gold', period: 'daily' })"
                                    class="btn btn-warning btn-lg px-5 py-3 text-dark fw-bold"
                                >
                                    Start Winning Today
                                </Link>
                            </div>
                            <p class="text-white" style="opacity: 0.8; max-width: 500px">
                                We Make Sports Betting Easy—by doing all the hard work analyzing hundreds of betting sources to give you the best
                                picks.
                            </p>
                        </div>

                        <!-- Players Image -->
                        <div class="col-lg-6 position-relative d-none d-lg-block">
                            <img
                                src="/images/header-bg-players-v2.png"
                                alt="Sports Players"
                                class="img-fluid position-absolute"
                                style="right: -50px; bottom: -250px; max-height: 500px; width: auto"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <!-- Betting Results Section -->
            <section class="py-2 animate-section-fadein" style="background-color: var(--bs-gray-dark)">
                <!-- Page Header -->
                <div class="container-fluid text-center pt-5 pb-4">
                    <h3 class="display-4 fw-bold text-white">Proven Track Record</h3>
                    <p class="fs-5 text-gray-light mb-4">Profits calculated for $30 bets across all our picks</p>
                </div>
                <div class="container-fluid">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="card bg-dark text-white h-100 shadow">
                                <div class="card-body d-flex flex-column justify-content-center text-center p-5">
                                    <h3 class="h4 fw-bold mb-4">This Year ({{ props.thisYear || new Date().getFullYear() }})</h3>
                                    <div class="display-4 fw-bold text-primary mb-4">${{ formatMoney(props.thisYearProfit) }}</div>
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
                                    <h3 class="h5 fw-bold text-center mb-4">Our 5 Year Record</h3>
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
                                                <td>2025</td>
                                                <td>$58,559</td>
                                                <td>52%</td>
                                            </tr>
                                            <tr>
                                                <td>{{ props.thisYear || 2026 }} to date</td>
                                                <td>${{ formatMoney(props.thisYearProfit) }}</td>
                                                <td>{{ Math.round(props.thisYearROI ?? 0) }}%</td>
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
                                    <h3 class="h5 fw-bold text-center mb-4">Our 5 Year Golf Record</h3>
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
                                                <td>2025</td>
                                                <td>16</td>
                                                <td>134%</td>
                                            </tr>
                                            <tr>
                                                <td>2026 to date</td>
                                                <td>{{ props.golfWinners2026 || 0 }}</td>
                                                <td>{{ props.golfROI2026 || 0 }}%</td>
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
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-body-bg)">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <!-- Image on the left -->
                        <div class="col-md-6 text-center mb-4 mb-md-0">
                            <div class="position-relative">
                                <div
                                    class="position-absolute"
                                    style="
                                        top: -20px;
                                        left: -20px;
                                        width: 120%;
                                        height: 120%;
                                        background: radial-gradient(circle, rgba(124, 58, 237, 0.2) 0%, transparent 70%);
                                        filter: blur(40px);
                                    "
                                ></div>
                                <img
                                    src="/images/profit-picks.png"
                                    alt="Profit Picks"
                                    class="img-fluid rounded-3 shadow-lg animate-bounceY position-relative"
                                    style="max-width: 400px"
                                />
                            </div>
                        </div>

                        <!-- Content on the right -->
                        <div class="col-md-6">
                            <h2 class="display-5 fw-bold text-white mb-4">Why Choose WeWinGames?</h2>
                            <ul class="list-unstyled">
                                <li class="d-flex align-items-start mb-3">
                                    <svg
                                        class="text-warning me-2 flex-shrink-0"
                                        style="width: 24px; height: 24px; margin-top: 2px"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Best Returns in the Business:</span> ROI of 15% in 2024 across all sports,
                                        with 60% on premium Platinum picks.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg
                                        class="text-warning me-2 flex-shrink-0"
                                        style="width: 24px; height: 24px; margin-top: 2px"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Multiple Sports:</span> Football, Basketball, Hockey, Baseball, Soccer, Golf,
                                        UFC.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg
                                        class="text-warning me-2 flex-shrink-0"
                                        style="width: 24px; height: 24px; margin-top: 2px"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">10 to 20 Picks a Day:</span> Expert picks posted throughout the day.
                                    </span>
                                </li>
                                <li class="d-flex align-items-start mb-3">
                                    <svg
                                        class="text-warning me-2 flex-shrink-0"
                                        style="width: 24px; height: 24px; margin-top: 2px"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-secondary">
                                        <span class="fw-bold text-white">Transparent Results:</span> We analyze hundreds of betting sources to give
                                        you the best picks.
                                    </span>
                                </li>
                            </ul>
                            <!-- Prominent Google Sheet CTA -->
                            <div class="mt-4">
                                <a
                                    href="https://docs.google.com/spreadsheets/d/1dNj41tUxP2sdnMLWJ_Oz_K9zrn8kT6Kd1AeuSraC7xw/edit?gid=569762228#gid=569762228"
                                    target="_blank"
                                    rel="noopener"
                                    class="btn btn-warning btn-lg px-4 py-3 fw-bold"
                                >
                                    <i class="bi bi-file-earmark-spreadsheet me-2"></i>
                                    View Our Full Picks Record (20,000+ Picks)
                                    <i class="bi bi-box-arrow-up-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Testimonials Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-gray-dark)">
                <TestimonialsCarousel :testimonials="props.testimonials" />
            </section>

            <!-- Free Picks Section -->
            <section class="py-5 animate-section-fadein" style="background-color: var(--bs-body-bg)">
                <div class="container-fluid">
                    <div class="text-center mb-5">
                        <h2 class="display-4 fw-bold text-white mb-4">Today's Free Picks</h2>
                        <p class="fs-5 text-gray-light mb-5">Get a taste of our expert analysis - no credit card required</p>
                    </div>

                    <!-- Simplified 3-card display: 2 picks + 1 teaser -->
                    <div class="row g-3 justify-content-center">
                        <!-- First 2 picks -->
                        <div v-for="(bet, index) in homePagePicks.picks" :key="bet.id || `pick-${index}`" class="col-12 col-md-6 col-lg-4">
                            <SimpleBetCard :bet="bet" />
                        </div>
                        <!-- Teaser card for premium picks -->
                        <div class="col-12 col-md-6 col-lg-4">
                            <TeaserBetCard :bet="homePagePicks.teaser" />
                        </div>
                    </div>

                    <div class="text-center mt-5">
                        <Link href="/todays-bets" class="btn btn-primary btn-lg px-5 py-3">
                            <i class="bi bi-arrow-right me-2"></i>
                            View All Picks
                        </Link>
                    </div>
                </div>
            </section>

            <!-- Subscription Plans Section -->
            <section class="py-4 animate-section-fadein" id="pricing" style="background-color: var(--bs-body-bg)">
                <div class="container-fluid">
                    <div class="text-center mb-4">
                        <h2 class="display-5 fw-bold text-white mb-2">Choose Your Plan</h2>
                        <p class="fs-6 text-gray-light mb-4">Start with a free trial, upgrade anytime</p>
                    </div>

                    <PricingCards :plans="plans" default-period="daily" />

                    <!-- DraftKings CTA Section -->
                    <div v-if="props.enableDraftkingsCta !== false" class="mt-5">
                        <h3 class="h4 fw-bold text-white text-center mb-4">Subscription Plans</h3>
                        <div class="row justify-content-center">
                            <div class="col-lg-8">
                                <div
                                    class="card shadow-lg"
                                    style="background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); border: 3px solid #ff7900; overflow: hidden"
                                >
                                    <div class="card-body p-4 position-relative">
                                        <!-- Background DraftKings logo -->
                                        <div
                                            class="position-absolute top-0 end-0"
                                            style="opacity: 0.15; transform: rotate(-15deg) translate(20px, -20px); z-index: 0"
                                        >
                                            <img
                                                src="/images/DraftKings_FC_on_dark.svg"
                                                alt=""
                                                style="height: 200px; width: auto; filter: grayscale(100%) brightness(2)"
                                            />
                                        </div>

                                        <div class="row align-items-center position-relative">
                                            <div class="col-lg-8">
                                                <div class="d-flex align-items-start gap-3">
                                                    <img
                                                        src="/images/DraftKings_FC_on_dark.svg"
                                                        alt="DraftKings Sportsbook"
                                                        style="height: 50px; width: auto"
                                                        class="mt-2"
                                                    />
                                                    <div>
                                                        <div class="d-flex align-items-center gap-2 mb-2">
                                                            <h4 class="h5 text-white mb-0 fw-bold">DraftKings Sportsbook</h4>
                                                            <span class="badge bg-warning text-dark" style="font-size: 0.65rem">NEW CUSTOMERS</span>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                                <span class="display-6 fw-bold text-warning">BET $5</span>
                                                                <span class="h3 text-white">→</span>
                                                                <span class="display-6 fw-bold text-success">GET $200</span>
                                                            </div>
                                                            <p class="h5 text-white mb-2">Bonus Bets Instantly</p>
                                                            <p class="h6 text-white mb-0">
                                                                <span class="text-warning fw-bold">+ Over $200 Off</span> NFL Sunday Ticket
                                                            </p>
                                                            <p class="small text-muted mb-0" style="font-size: 0.75rem">From YouTube & YouTube TV</p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-4 text-center text-lg-end mt-3 mt-lg-0">
                                                <a
                                                    href="https://sportsbook.draftkings.com/acq-bet-and-get?pcid=420313&pcn=Promo1&pcrid=xx&pcrn=xx&pscid=xx&pscn=WeWinGames&psn=1967&referrer=singular_click_id%3d63c8a1b6-2dcc-42b9-928e-bdb91b06dee3&sl_id=tqhb&wpcid=420313&wpcn=Promo1&wpcrid=xx&wpcrn=xx&wpscid=xx&wpscn=WeWinGames&wpsrc=1967"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="btn btn-warning btn-lg px-5 py-3 fw-bold shadow"
                                                    style="
                                                        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
                                                        border: none;
                                                        transition: all 0.3s ease;
                                                    "
                                                    onmouseover="this.style.transform='scale(1.05)'"
                                                    onmouseout="this.style.transform='scale(1)'"
                                                >
                                                    <i class="bi bi-arrow-right-circle-fill me-2"></i>
                                                    CLAIM OFFER
                                                </a>
                                                <p class="small text-muted mt-2 mb-0">No Code Required</p>
                                            </div>
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
    animation: fadein-card 0.8s cubic-bezier(0.4, 0, 0.2, 1) forwards;
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
    animation: section-fadein 1s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}
@keyframes bounceY {
    0%,
    100% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    20% {
        transform: translateY(-10px);
        animation-timing-function: cubic-bezier(0.2, 0, 0.8, 1);
    }
    40% {
        transform: translateY(-20px);
        animation-timing-function: cubic-bezier(0.2, 0, 0.8, 1);
    }
    60% {
        transform: translateY(-10px);
        animation-timing-function: cubic-bezier(0.2, 0, 0.8, 1);
    }
    80% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
}
.animate-bounceY {
    animation: bounceY 2.5s cubic-bezier(0.4, 0, 0.2, 1) infinite;
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
