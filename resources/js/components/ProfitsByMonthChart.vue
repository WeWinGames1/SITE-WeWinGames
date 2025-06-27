<template>
  <div class="p-6 bg-transparent dark:bg-transparent rounded-lg shadow">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">Profit by Month</h3>
    <canvas ref="chartRef" class="w-full"></canvas>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps<{
  data: Array<{ month: string, profit: number, roi?: number }>
}>();

const chartRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const renderChart = () => {
  if (chartRef.value) {
    // Sort months chronologically if needed (assumes YYYY-MM or Month name order)
    const sortedData = [...props.data]; // sort if needed

    const months = sortedData.map(item => item.month);
    const profits = sortedData.map(item => item.profit);
    const rois = sortedData.map(item => item.roi ?? null);

    const ctx = chartRef.value.getContext('2d');
    if (ctx) {
      if (chartInstance) chartInstance.destroy();
      chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: months,
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
            ...(rois.some(v => v !== null) ? [{
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
            }] : [])
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top' },
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                label: function(context) {
                  if (context.dataset.label === 'ROI (%)') {
                    return `ROI: ${context.raw}%`;
                  }
                  if (context.dataset.label === 'Profit ($)') {
                    return `Profit: $${context.raw}`;
                  }
                  return context.raw;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              title: { display: true, text: 'Profit ($)' },
              position: 'left',
            },
            y1: {
              beginAtZero: true,
              title: { display: true, text: 'ROI (%)' },
              position: 'right',
              grid: { drawOnChartArea: false },
              display: rois.some(v => v !== null),
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