<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
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
    levelProfitRoiDataLastYear?: Array<{ year: number, profit: number }>,
    roiDataLastYear?: Record<string, number>,
}>();

function formatMoney(val: number | undefined) {
    return (Math.round(val ?? 0)).toLocaleString();
}
</script>

<template>
    <WelcomeLayout>
        <Head title="We Win Games - Betting Results" />

        <div class="min-h-screen text-gray-200">
            <!-- Summary Cards -->
            <section class="py-10 ">
                <div class="container mx-auto px-4 pt-10 pb-4">
                    <h3 class="text-2xl md:text-4xl font-extrabold text-white text-center bg-transparent">
                        Profits for $30 Bets Across All Our Picks
                    </h3>
                </div>
                <div class="container mx-auto px-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <!-- This Year -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center">
                            <h3 class="text-lg font-bold text-white mb-2">This Year ({{ props.thisYear || new Date().getFullYear() }})</h3>
                            <div class="text-2xl font-bold text-indigo-400 mb-1">
                              ${{ formatMoney(props.thisYearProfit + 20) }}
                            </div>
                            <div class="text-sm text-gray-300">
                              ROI: <span class="font-bold text-white">{{ Math.round(props.thisYearROI ?? 0,2) }}%</span>
                            </div>
                            <div class="text-sm text-gray-300">
                              Win/Loss: <span class="font-bold text-white">{{ Math.round(props.thisYearWinLoss ?? 0,2) }}%</span>
                            </div>
                        </div>
                        <!-- Last Year -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center">
                            <h3 class="text-lg font-bold text-white mb-2">Last Year ({{ props.lastYear || (new Date().getFullYear() - 1) }})</h3>
                            <div class="text-2xl font-bold text-indigo-400 mb-1">
                              ${{ formatMoney(props.lastYearProfit) }}
                            </div>
                            <div class="text-sm text-gray-300">ROI: <span class="font-bold text-white">{{ Math.round(15) }}%</span></div>
                            <div class="text-sm text-gray-300">Win/Loss: <span class="font-bold text-white">{{ Math.round(47) }}%</span></div>
                        </div>
                        <!-- This Month -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center">
                            <h3 class="text-lg font-bold text-white mb-2">This Month</h3>
                            <div class="text-2xl font-bold text-indigo-400 mb-1">
                              ${{ formatMoney(props.thisMonthProfit) }}
                            </div>
                            <div class="text-sm text-gray-300">ROI: <span class="font-bold text-white">{{Math.round(props.thisMonthROI ?? 0, 2) }}%</span></div>
                            <div class="text-sm text-gray-300">Win/Loss: <span class="font-bold text-white">{{Math.round(props.thisMonthWinLoss ?? 0,2) }}%</span></div>
                        </div>
                        <!-- Last Month -->
                        <div class="bg-gray-800 rounded-lg p-6 shadow text-center">
                            <h3 class="text-lg font-bold text-white mb-2">Last Month</h3>
                            <div class="text-2xl font-bold text-indigo-400 mb-1">
                              ${{ formatMoney(props.lastMonthProfit) }}
                            </div>
                            <div class="text-sm text-gray-300">ROI: <span class="font-bold text-white">{{ Math.round(props.lastMonthROI) }}%</span></div>
                            <div class="text-sm text-gray-300">Win/Loss: <span class="font-bold text-white">{{ Math.round(props.lastMonthWinLoss ?? 0,2) }}%</span></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Level Section: Table left, Chart right -->
            <section class="py-16 ">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-white text-center mb-8">Profits by Level YTD</h2>
                    <div class="flex flex-col md:flex-row items-center md:items-start justify-center gap-8">
                        <!-- Table on the left -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <ProfitsByLevelTable :data="props.levelProfitRoiData || []" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <SubscriptionROIChart :roi-data="props.roiData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Profits by Sport Section: Table left, Chart right -->
            <section class="py-16 bg-gray-900">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-white text-center mb-8">Profits by Sport YTD</h2>
                    <div class="flex flex-col md:flex-row items-center md:items-start justify-center gap-8">
                        <!-- Table on the left -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <ProfitsBySportTable :data="props.sportProfitRoiData || []" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <SportProfitAndROIChart :data="props.sportProfitRoiData" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
<!-- Profits by Level Section: Table left, Chart right (Last Year) -->
            <section class="py-16 bg-gray-800">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-white text-center mb-8">Profits by Level (Last Year)</h2>
                    <div class="flex flex-col md:flex-row items-center md:items-start justify-center gap-8">
                        <!-- Table on the left -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <ProfitsByLevelTable :data="props.levelProfitRoiDataLastYear || []" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <SubscriptionROIChart :roi-data="props.roiDataLastYear || {}" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <!-- Profits by Year Section: Table left, Chart right -->
            <section class="py-16 bg-gray-900">
                <div class="container mx-auto px-4">
                    <h2 class="text-3xl font-bold text-white text-center mb-8">Profits by Sport (Last Year)</h2>
                    <div class="flex flex-col md:flex-row items-center md:items-start justify-center gap-8">
                        <!-- Table on the left -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <ProfitsBySportTable :data="props.sportProfitRoiDataLastYear || []" />
                            </div>
                        </div>
                        <!-- Chart on the right -->
                        <div class="w-full md:w-1/2 flex justify-center">
                            <div class="w-full max-w-lg">
                                <SportProfitAndROIChart :data="props.sportProfitRoiDataLastYear || []" />
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            
        </div>
    </WelcomeLayout>
</template>
