<template>
  <div class="p-6 bg-transparent dark:bg-transparent rounded-lg shadow">
    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 text-center mb-4">Profit & ROI by Sport</h3>
    <canvas ref="chartRef" class="w-full"></canvas>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import Chart from 'chart.js/auto';

const props = defineProps<{
  data: Array<{ sport: string, profit: number, roi: number }>
}>();

const chartRef = ref<HTMLCanvasElement | null>(null);
let chartInstance: Chart | null = null;

const renderChart = () => {
  if (chartRef.value) {
    // Sort data by ROI ascending (lowest to highest)
    const sortedData = [...props.data].sort((a, b) => a.roi - b.roi);

    // Calculate totals
    const totalProfit = sortedData.reduce((sum, item) => sum + item.profit, 0);
    const totalROI = sortedData.length
      ? sortedData.reduce((sum, item) => sum + item.roi, 0) / sortedData.length
      : 0;

    // Prepare data with "Total" appended
    const sports = sortedData.map(item => item.sport).concat('Total');
    const profits = sortedData.map(item => item.profit).concat(Number(totalProfit.toFixed(2)));
    const rois = sortedData.map(item => item.roi).concat(Number(totalROI.toFixed(2)));

    const ctx = chartRef.value.getContext('2d');
    if (ctx) {
      if (chartInstance) chartInstance.destroy();
      chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: sports,
          datasets: [
            {
              type: 'bar',
              label: 'Profit ($)',
              data: profits,
              backgroundColor: sports.map(sport =>
                sport === 'Total' ? 'rgba(67, 45, 215, 1)' : 'rgba(244, 210, 3, 0.9)'
              ),
              borderColor: sports.map(sport =>
                sport === 'Total' ? 'rgba(255, 206, 86, 1)' : 'rgba(54, 162, 235, 1)'
              ),
              borderWidth: 1,
              yAxisID: 'y',
            },
            {
              type: 'line',
              label: 'ROI (%)',
              data: rois,
              borderColor: 'rgba(88, 93, 105,  1)',
              backgroundColor: 'rgba(88, 93, 105, 0.2)',
              borderWidth: 2,
              fill: false,
              yAxisID: 'y1',
              tension: 0.3,
              pointBackgroundColor: sports.map(sport =>
                sport === 'Total' ? 'rgba(255, 206, 86, 1)' : 'rgba(255, 99, 132, 1)'
              ),
              pointRadius: 4,
            },
          ],
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top',
              labels: {
                color: '#fff' // legend text color
              }
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              backgroundColor: '#222',
              titleColor: '#fff',
              bodyColor: '#fff',
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
              title: { display: true, text: 'Profit ($)', color: '#fff' },
              position: 'left',
              ticks: { color: '#fff' },
              grid: { color: 'rgba(255,255,255,0.1)' }
            },
            y1: {
              beginAtZero: true,
              title: { display: true, text: 'ROI (%)', color: '#fff' },
              position: 'right',
              grid: { drawOnChartArea: false, color: 'rgba(255,255,255,0.1)' },
              ticks: { color: '#fff' }
            },
            x: {
              ticks: { color: '#fff' },
              grid: { color: 'rgba(255,255,255,0.1)' }
            }
          },
        },
      });
    }
  }
};

onMounted(renderChart);
watch(() => props.data, renderChart, { deep: true });
</script>