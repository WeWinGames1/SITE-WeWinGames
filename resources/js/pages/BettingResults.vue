<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SubscriptionROIChart from '../components/SubscriptionROIChart.vue';
import SportProfitAndROIChart from '../components/SportProfitAndROIChart.vue';
import ProfitsByLevelTable from '../components/ProfitsByLevelTable.vue';
import ProfitsBySportTable from '../components/ProfitsBySportTable.vue';
import ProfitsByYearTable from '@/components/ProfitsByYearTable.vue';
import ProfitsByMonthTable from '@/components/ProfitsByMonthTable.vue';
import ProfitsByMonthChart from '@/components/ProfitsByMonthChart.vue';
import ProfitsByYearChart from '@/components/ProfitsByYearChart.vue';

const props = defineProps<{
    roiData: Record<string, number>,
    sportProfitRoiData: Array<{ sport: string, profit: number, roi: number, monthly?: number }>,
    lastYear?: number,
    lastYearProfit?: number,
    lastYearROI?: number,
    lastYearWinLoss?: number,
    lastMonthProfit?: number,
    lastMonthROI?: number,
    lastMonthWinLoss?: number,
    thisYear?: number,
    thisYearProfit?: number,
    thisYearROI?: number,
    thisYearWinLoss?: number,
    thisMonthProfit?: number,
    thisMonthROI?: number,
    thisMonthWinLoss?: number,
    monthlyProfit?: number,
    levelProfitRoiData?: Array<{ level: string, profit: number, roi: number }>,
    profitByYearData?: Array<{ year: number, profit: number }>,
    profitByMonthData?: Array<{ month: string, profit: number }>,
    sportProfitRoiDataLastYear?: Array<{ sport: string, profit: number, roi: number, monthly?: number }>,
    levelProfitRoiDataLastYear?: Array<{ level: string, profit: number, roi: number }>,
    roiDataLastYear?: Record<string, number>,
}>();


// Sort levels in the order: Bronze, Silver, Gold, Platinum
const sortedLevelProfitRoiData = computed(() => {
    const levelOrder = ['Bronze', 'Silver', 'Gold', 'Platinum'];
    return [...(props.levelProfitRoiData || [])].sort((a, b) => {
        return levelOrder.indexOf(a.level) - levelOrder.indexOf(b.level);
    });
});

// Sort levels for last year in the order: Silver, Gold, Platinum (no Bronze)
const sortedLevelProfitRoiDataLastYear = computed(() => {
    const levelOrder = ['Silver', 'Gold', 'Platinum'];
    return [...(props.levelProfitRoiDataLastYear || [])].sort((a: any, b: any) => {
        return levelOrder.indexOf(a.level) - levelOrder.indexOf(b.level);
    });
});

// Sort sports by ROI from lowest to highest
const sortedSportProfitRoiData = computed(() => {
    return [...(props.sportProfitRoiData || [])].sort((a, b) => {
        return a.roi - b.roi;
    });
});

// Sort sports by ROI from lowest to highest for last year
const sortedSportProfitRoiDataLastYear = computed(() => {
    return [...(props.sportProfitRoiDataLastYear || [])].sort((a, b) => {
        return a.roi - b.roi;
    });
});

// Sort years from oldest to newest (2023, 2024, 2025)
const sortedProfitByYearData = computed(() => {
    return [...(props.profitByYearData || [])].sort((a, b) => {
        return Number(a.year) - Number(b.year);
    });
});

// Sort months from oldest to newest (2024-01, 2024-02, ..., 2025-01, 2025-02)
const sortedProfitByMonthData = computed(() => {
    return [...(props.profitByMonthData || [])].sort((a, b) => {
        return a.month.localeCompare(b.month);
    });
});

function formatMoney(val: number | undefined) {
    return (Math.round(val ?? 0)).toLocaleString();
}
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games - Betting Results" />

        <div class="min-vh-100" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%);">
            <!-- Summary Cards -->
            <section class="py-5">
                <div class="container-fluid px-4 px-lg-5 pt-5 pb-4">
                    <h3 class="display-5 fw-bold text-white text-center">
                        Profits for $30 Bets Across All Our Picks
                    </h3>
                </div>
                <div class="container-fluid px-4 px-lg-5">
                    <div class="row g-4">
                        <!-- This Year -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card text-center h-100" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold text-white mb-3">This Year ({{ props.thisYear || new Date().getFullYear() }})</h3>
                                    <div class="h3 fw-bold text-primary mb-2">
                                        ${{ formatMoney(props.thisYearProfit) }}
                                    </div>
                                    <div class="small text-gray-light">
                                        ROI: <span class="fw-bold text-white">{{ Math.round(props.thisYearROI ?? 0,2) }}%</span>
                                    </div>
                                    <div class="small text-gray-light">
                                        Win/Loss: <span class="fw-bold text-white">{{ Math.round(props.thisYearWinLoss ?? 0,2) }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Last Year -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card text-center h-100" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold text-white mb-3">Last Year ({{ props.lastYear || (new Date().getFullYear() - 1) }})</h3>
                                    <div class="h3 fw-bold text-primary mb-2">
                                        ${{ formatMoney(props.lastYearProfit) }}
                                    </div>
                                    <div class="small text-gray-light">ROI: <span class="fw-bold text-white">{{ Math.round(props.lastYearROI ?? 0) }}%</span></div>
                                    <div class="small text-gray-light">Win/Loss: <span class="fw-bold text-white">{{ Math.round(props.lastYearWinLoss ?? 0) }}%</span></div>
                                </div>
                            </div>
                        </div>
                        <!-- This Month -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card text-center h-100" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold text-white mb-3">This Month</h3>
                                    <div class="h3 fw-bold text-primary mb-2">
                                        ${{ formatMoney(props.thisMonthProfit) }}
                                    </div>
                                    <div class="small text-gray-light">ROI: <span class="fw-bold text-white">{{Math.round(props.thisMonthROI ?? 0, 2) }}%</span></div>
                                    <div class="small text-gray-light">Win/Loss: <span class="fw-bold text-white">{{Math.round(props.thisMonthWinLoss ?? 0,2) }}%</span></div>
                                </div>
                            </div>
                        </div>
                        <!-- Last Month -->
                        <div class="col-12 col-md-6 col-lg-3">
                            <div class="card text-center h-100" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <div class="card-body p-4">
                                    <h3 class="h5 fw-bold text-white mb-3">Last Month</h3>
                                    <div class="h3 fw-bold text-primary mb-2">
                                        ${{ formatMoney(props.lastMonthProfit) }}
                                    </div>
                                    <div class="small text-gray-light">ROI: <span class="fw-bold text-white">{{ Math.round(props.lastMonthROI) }}%</span></div>
                                    <div class="small text-gray-light">Win/Loss: <span class="fw-bold text-white">{{ Math.round(props.lastMonthWinLoss ?? 0,2) }}%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Level Section: Table left, Chart right -->
            <section class="py-5" style="background-color: #0a1628;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Level YTD</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByLevelTable :data="sortedLevelProfitRoiData" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <SubscriptionROIChart :roi-data="props.roiData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Sport Section: Table left, Chart right -->
            <section class="py-5" style="background-color: #1a2332;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Sport YTD</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsBySportTable :data="sortedSportProfitRoiData" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <SportProfitAndROIChart :data="sortedSportProfitRoiData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Level Section: Table left, Chart right (Last Year) -->
            <section class="py-5" style="background-color: #0a1628;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Level (Last Year)</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByLevelTable :data="sortedLevelProfitRoiDataLastYear" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <SubscriptionROIChart :roi-data="props.roiDataLastYear || {}" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Profits by Year Section: Table left, Chart right -->
            <section class="py-5" style="background-color: #1a2332;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Sport (Last Year)</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsBySportTable :data="sortedSportProfitRoiDataLastYear" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <SportProfitAndROIChart :data="sortedSportProfitRoiDataLastYear" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Year Section: Table left, Chart right -->
            <section class="py-5" style="background-color: #0a1628;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Year</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByYearTable :data="sortedProfitByYearData" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByYearChart :data="sortedProfitByYearData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Month Section: Table left, Chart right -->
            <section class="py-5" style="background-color: #1a2332;">
                <div class="container-fluid px-4 px-lg-5">
                    <h2 class="display-6 fw-bold text-white text-center mb-5">Profits by Month (Last 24 Months)</h2>
                    <div class="row justify-content-center align-items-start g-4">
                        <!-- Table on the left -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByMonthTable :data="sortedProfitByMonthData" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="col-12 col-md-6 d-flex justify-content-center">
                            <div class="w-100" style="max-width: 500px;">
                                <ProfitsByMonthChart :data="sortedProfitByMonthData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </WelcomeLayout>
</template>