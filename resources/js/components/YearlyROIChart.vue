<template>
    <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">ROI by Year</h3>
        <canvas ref="yearlyRoiChart" class="w-full"></canvas>
    </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
    yearlyRoiData: {
        type: Object,
        required: true, // Example: { "2023": 15.2, "2024": 17.8 }
    },
});

const yearlyRoiChart = ref<HTMLCanvasElement | null>(null);

onMounted(() => {
    if (yearlyRoiChart.value) {
        const ctx = yearlyRoiChart.value.getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: Object.keys(props.yearlyRoiData), // Years (e.g., 2023, 2024)
                    datasets: [
                        {
                            label: 'ROI (%)',
                            data: Object.values(props.yearlyRoiData),
                            backgroundColor: [
                                'rgba(255, 255, 88, 0.7)',
                                'rgba(255, 255, 88, 0.7)',
                            ],
                            borderColor: [
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 99, 132, 1)',
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
                        },
                        tooltip: {
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
                            },
                        },
                    },
                },
            });
        }
    }
});
</script>