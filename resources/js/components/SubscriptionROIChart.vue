<template>
    <div class="p-6 bg-transparent dark:bg-gray-800 rounded-lg shadow">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">ROI by Subscription Level</h3>
        <canvas ref="roiChart" class="w-full"></canvas>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    roiData: {
        type: Object,
        required: true,
    },
});

const roiChart = ref<HTMLCanvasElement | null>(null);

onMounted(() => {
    if (roiChart.value) {
        const ctx = roiChart.value.getContext('2d');

        if (ctx) {
            // Sort the data in the correct order
            // If Bronze is not in the data, it's Last Year data (Silver, Gold, Platinum only)
            const dataKeys = Object.keys(props.roiData);
            const hasBronze = dataKeys.includes('Bronze');
            const levelOrder = hasBronze 
                ? ['Bronze', 'Silver', 'Gold', 'Platinum'] 
                : ['Silver', 'Gold', 'Platinum'];
            
            const sortedKeys = dataKeys.sort((a, b) => {
                return levelOrder.indexOf(a) - levelOrder.indexOf(b);
            });
            const sortedValues = sortedKeys.map(key => props.roiData[key]);
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: sortedKeys,
                    datasets: [
                        {
                            label: 'ROI (%)',
                            data: sortedValues,
                            backgroundColor: [
                                'rgba(244, 210, 3, 0.9)',
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                            ],
                            borderWidth: 1,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#fff', // legend text color
                            },
                        },
                        tooltip: {
                            backgroundColor: '#222',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function (context) {
                                    return `${context.raw}%`;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'ROI (%)',
                                color: '#fff', // y axis title color
                            },
                            ticks: {
                                color: '#fff', // y axis labels
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.1)',
                            },
                        },
                        x: {
                            ticks: {
                                color: '#fff', // x axis labels
                            },
                            grid: {
                                color: 'rgba(255,255,255,0.1)',
                            },
                        },
                    },
                },
            });
        }
    }
});
</script>

<style scoped>
/* Add any specific styles for the chart container */
</style>