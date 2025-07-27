<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

const file = ref<File|null>(null);
const loading = ref(false);

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    file.value = target.files[0];
  }
};

const uploadCSV = async () => {
  if (!file.value) return;
  loading.value = true;
  const formData = new FormData();
  formData.append('csv', file.value);

  try {
    await axios.post('/admin/bets/import-csv', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });
    alert('CSV uploaded and imported!');
    //window.location.reload();
  } catch {
    alert('Upload failed');
  } finally {
    loading.value = false;
  }
};
</script>

<template>
    <div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
      <h2 class="text-xl font-bold text-white mb-4">Import Bets CSV</h2>
      <input type="file" accept=".csv" @change="handleFileChange" class="mb-4 w-full text-gray-200" />
      <button
        @click="uploadCSV"
        :disabled="loading"
        class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded w-full font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="loading">Uploading...</span>
        <span v-else>Upload CSV</span>
      </button>
    </div>
</template>