<template>
  <div class="p-6 bg-white dark:bg-gray-800 rounded-lg shadow">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">Profit & ROI by Year</h3>
    <canvas ref="chartRef" class="w-full"></canvas>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps({
  yearlyProfitData: { type: Object, required: true }, // e.g. { "2023": 12000, "2024": 8000 }
  yearlyRoiData: { type: Object, required: true },    // e.g. { "2023": 15.2, "2024": 17.8 }
});

const chartRef = ref<HTMLCanvasElement | null>(null);

onMounted(() => {
  if (chartRef.value) {
    const years = Object.keys(props.yearlyProfitData);
    const profitData = years.map(year => props.yearlyProfitData[year] ?? 0);
    const roiData = years.map(year => props.yearlyRoiData[year] ?? 0);

    const ctx = chartRef.value.getContext('2d');
    if (ctx) {
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: years,
          datasets: [
            {
              type: 'bar',
              label: 'Profit ($)',
              data: profitData,
              backgroundColor: 'rgba(244, 210, 3 0.5)',
              borderColor: 'rgba(54, 162, 235, 1)',
              borderWidth: 1,
              yAxisID: 'y',
            },
            {
              type: 'line',
              label: 'ROI (%)',
              data: roiData,
              borderColor: 'rgba(255, 99, 132, 1)',
              backgroundColor: 'rgba(255, 99, 132, 0.2)',
              borderWidth: 2,
              fill: false,
              yAxisID: 'y1',
              tension: 0.3,
              pointBackgroundColor: 'rgba(255, 99, 132, 1)',
              pointRadius: 4,
            },
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
            },
          },
        },
      });
    }
  }
});
</script>