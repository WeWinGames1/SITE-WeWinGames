<template>
  <div class="my-4">
    <button
      @click="exportCSV"
      class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded font-semibold transition"
      :disabled="loading"
    >
      <span v-if="loading">Exporting...</span>
      <span v-else>Export Bets as CSV</span>
    </button>
    <span v-if="error" class="text-red-500 ml-4">{{ error }}</span>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';

const loading = ref(false);
const error = ref('');

function exportCSV() {
  loading.value = true;
  error.value = '';
  fetch('/admin/bets/export-csv', {
    credentials: 'include',
  })
    .then(response => {
      if (!response.ok) throw new Error('Failed to export CSV');
      return response.blob();
    })
    .then(blob => {
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'bets_export.csv';
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);
    })
    .catch(e => {
      error.value = e.message || 'Export failed';
    })
    .finally(() => {
      loading.value = false;
    });
}
</script>