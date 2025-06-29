<script setup lang="ts">
import BetPickCard from './BetPickCard.vue';
import CoveredBetPickCard from './CoveredBetPickCard.vue';

const props = defineProps<{
  groupedBets: Record<string, Array<any>>
}>();

// Sport icons mapping
const sportIcons: Record<string, string> = {
  'football': 'bi-trophy',
  'basketball': 'bi-dribbble',
  'baseball': 'bi-circle',
  'hockey': 'bi-snow2',
  'soccer': 'bi-globe',
  'golf': 'bi-flag',
  'ufc': 'bi-person-arms-up',
  'default': 'bi-star'
};

const getSportIcon = (sport: string) => {
  const sportLower = sport.toLowerCase();
  return sportIcons[sportLower] || sportIcons['default'];
};
</script>

<template>
  <div>
    <div v-for="(bets, sport) in groupedBets" :key="sport" class="mb-5">
      <div class="rounded-3 p-4" style="background-color: var(--bs-gray-dark); border: 1px solid var(--bs-gray-medium);">
        <!-- Sport Header -->
        <div class="d-flex align-items-center justify-content-between mb-4">
          <div class="d-flex align-items-center">
            <div class="rounded-circle bg-purple d-flex align-items-center justify-content-center me-3" style="width: 48px; height: 48px;">
              <i :class="getSportIcon(sport)" class="bi text-white fs-4"></i>
            </div>
            <div>
              <h3 class="h4 fw-bold text-white mb-0 text-capitalize">{{ sport }}</h3>
              <p class="text-gray-light small mb-0">{{ bets.length }} {{ bets.length === 1 ? 'pick' : 'picks' }} available</p>
            </div>
          </div>
          <div class="text-end">
            <span class="badge bg-success">
              <i class="bi bi-graph-up-arrow me-1"></i>
              Live
            </span>
          </div>
        </div>
        
        <!-- Bet Cards Grid -->
        <div class="row g-4">
          <div
            v-for="(bet, index) in bets"
            :key="bet.id"
            class="col-12 col-sm-6 col-lg-4 col-xl-3"
          >
            <component
              :is="bet.isCovered ? CoveredBetPickCard : BetPickCard"
              :bet="bet"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty State -->
    <div v-if="Object.keys(groupedBets).length === 0" class="text-center py-5">
      <i class="bi bi-inbox text-gray-light display-1 mb-3"></i>
      <h5 class="text-white">No picks available</h5>
      <p class="text-gray-light">Check back later for new betting picks</p>
    </div>
  </div>
</template>

<style scoped>
/* Additional hover effects for the sport sections */
.rounded-3 {
  transition: all 0.3s ease;
}

.rounded-3:hover {
  box-shadow: 0 0.5rem 1rem rgba(124, 58, 237, 0.1) !important;
  border-color: rgba(124, 58, 237, 0.3) !important;
}
</style>