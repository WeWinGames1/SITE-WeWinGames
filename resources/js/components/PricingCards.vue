<script setup lang="ts">
defineProps<{
  plans: Array<{
    name: string,
    price: string,
    duration: string,
    features: string[],
    monthlyLink: string,
    weeklyLink?: string,
    dailyLink?: string,
    weeklyPrice?: string,
    dailyPrice?: string,
    highlight?: boolean,
  }>
}>();
</script>

<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5 justify-center mb-16">
    <div
      v-for="plan in plans"
      :key="plan.name"
      :class="[
        'bg-gray-800 rounded-xl shadow-lg p-8 flex flex-col items-center transition-transform hover:-translate-y-2',
        plan.highlight ? 'border-4 border-indigo-500 scale-105 z-10' : 'border border-gray-700'
      ]"
    >
      <h2 class="text-2xl font-bold mb-2 text-white">{{ plan.name }}</h2>
      <div class="text-4xl font-extrabold text-indigo-400 mb-2">{{ plan.price }}</div>
      <div class="text-sm text-gray-400 mb-4">for {{ plan.duration }}</div>
      <ul class="mb-6 space-y-2 text-gray-200 text-left w-full max-w-xs mx-auto">
        <li v-for="feature in plan.features" :key="feature" class="flex items-center">
          <svg class="w-5 h-5 text-indigo-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
          </svg>
          <span>{{ feature }}</span>
        </li>
      </ul>
      <a
        :href="plan.monthlyLink"
        target="_blank"
        class="w-full text-center py-2 px-4 rounded font-bold transition-colors"
        :class="plan.highlight ? 'bg-indigo-600 hover:bg-indigo-700 text-white' : 'bg-gray-700 hover:bg-gray-600 text-indigo-200'"
      >
        Sign Up
      </a>
      <a
        v-if="plan.weeklyLink"
        :href="plan.weeklyLink"
        target="_blank"
        class="mt-4 w-full text-center py-2 px-4 rounded font-bold transition-colors bg-gray-700 hover:bg-gray-600 text-indigo-200">
        Sign Up Weekly ${{ plan.weeklyPrice }}
      </a>
      <a
        v-if="plan.dailyLink"
        :href="plan.dailyLink"
        target="_blank"
        class="mt-4 w-full text-center py-2 px-4 rounded font-bold transition-colors bg-gray-700 hover:bg-gray-600 text-indigo-200">
        Sign Up Daily ${{ plan.dailyPrice }}
      </a>
    </div>
  </div>
</template>