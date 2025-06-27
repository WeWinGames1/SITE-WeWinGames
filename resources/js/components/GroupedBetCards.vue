<script setup lang="ts">
import BetPickCard from './BetPickCard.vue';
import CoveredBetPickCard from './CoveredBetPickCard.vue';

const props = defineProps<{
  groupedBets: Record<string, Array<any>>
}>();
</script>

<template>
  <div>
    <div v-for="(bets, sport) in groupedBets" :key="sport" class="mb-12">
      <div class="bg-indigo-600 bg-opacity-25 rounded-lg shadow-lg p-8 mb-6">
        <h2 class="text-2xl font-bold text-white mb-6 capitalize text-center tracking-wide">{{ sport }}</h2>
        <div class="w-full grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
          <component
            v-for="(bet, index) in bets"
            :is="bet.isCovered ? CoveredBetPickCard : BetPickCard"
            :key="bet.id"
            :bet="bet"
            class="transition-transform transform hover:scale-105"
          />
        </div>
      </div>
    </div>
  </div>
</template>