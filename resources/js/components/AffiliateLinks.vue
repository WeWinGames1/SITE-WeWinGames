<template>
  <div class="flex flex-col gap-4 mb-8">
    <div
      v-for="(affiliate, idx) in affiliates"
      :key="idx"
      class="flex items-center bg-gray-800 rounded-lg shadow p-4 hover:bg-gray-700 transition"
    >
      <img
        :src="affiliate.logo"
        :alt="affiliate.text"
        class="w-16 h-16 object-contain rounded mr-4 bg-white p-2"
      />
      <div class="flex-1">
        <a
          :href="affiliate.url"
          target="_blank"
          rel="noopener"
          class="text-lg font-semibold text-indigo-400 hover:underline"
        >
          {{ affiliate.text }}
        </a>
        <div v-if="affiliate.description" class="text-gray-300 text-sm mt-1">
          {{ affiliate.description }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  affiliates: Array<{
    logo: string,
    text: string,
    url: string,
    description?: string
  }>
}>();
</script>