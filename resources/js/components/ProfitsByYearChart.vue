<template>
    <div class="p-6 bg-transparent dark:bg-transparent rounded-lg shadow">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">Profit by Year</h3>
        <canvas ref="chartRef" class="w-full"></canvas>
    </div>
</template>

<script setup lang="ts">
import Chart from 'chart.js/auto';
import { onMounted, ref, watch } from 'vue';

const props = defineProps<{
    data: Array<{ year: string | number; profit: number; roi?: number }>;
}>();

const chartRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const renderChart = () => {
    if (chartRef.value) {
        const years = props.data.map((item) => item.year);
        const profits = props.data.map((item) => item.profit);
        const rois = props.data.map((item) => item.roi ?? null);

        const ctx = chartRef.value.getContext('2d');
        if (ctx) {
            if (chartInstance) chartInstance.destroy();
            chartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: years,
                    datasets: [
                        {
                            type: 'bar',
                            label: 'Profit ($)',
                            data: profits,
                            backgroundColor: 'rgba(99, 102, 241, 0.7)',
                            borderColor: 'rgba(99, 102, 241, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        ...(rois.some((v) => v !== null)
                            ? [
                                  {
                                      type: 'line',
                                      label: 'ROI (%)',
                                      data: rois,
                                      borderColor: 'rgba(255, 206, 86, 1)',
                                      backgroundColor: 'rgba(255, 206, 86, 0.2)',
                                      borderWidth: 2,
                                      fill: false,
                                      yAxisID: 'y1',
                                      tension: 0.3,
                                      pointBackgroundColor: 'rgba(255, 206, 86, 1)',
                                      pointRadius: 4,
                                  },
                              ]
                            : []),
                    ],
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                color: '#fff', // <-- legend text color
                            },
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#222', // optional: dark tooltip background
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            callbacks: {
                                label: function (context) {
                                    if (context.dataset.label === 'ROI (%)') {
                                        return `ROI: ${Math.round(context.raw)}%`;
                                    }
                                    if (context.dataset.label === 'Profit ($)') {
                                        return `Profit: $${Math.round(context.raw).toLocaleString()}`;
                                    }
                                    return context.raw;
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: 'Profit ($)', color: '#fff' }, // axis title color
                            position: 'left',
                            ticks: { color: '#fff' }, // y axis labels
                            grid: { color: 'rgba(255,255,255,0.1)' },
                        },
                        y1: {
                            beginAtZero: true,
                            title: { display: true, text: 'ROI (%)', color: '#fff' },
                            position: 'right',
                            grid: { drawOnChartArea: false, color: 'rgba(255,255,255,0.1)' },
                            display: rois.some((v) => v !== null),
                            ticks: { color: '#fff' },
                        },
                        x: {
                            ticks: { color: '#fff' }, // x axis labels
                            grid: { color: 'rgba(255,255,255,0.1)' },
                        },
                    },
                },
            });
        }
    }
};

onMounted(renderChart);
watch(() => props.data, renderChart, { deep: true });
</script>
