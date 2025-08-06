<script setup lang="ts">
import CoveredBetPickCard from './CoveredBetPickCard.vue';
import SimpleBetCard from './SimpleBetCard.vue';

defineProps<{
    groupedBets: Record<string, Array<any>>;
}>();

// Sport icons mapping
const sportIcons: Record<string, string> = {
    football: 'bi-trophy',
    basketball: 'bi-dribbble',
    baseball: 'bi-circle',
    hockey: 'bi-snow2',
    soccer: 'bi-globe',
    golf: 'bi-flag',
    ufc: 'bi-person-arms-up',
    default: 'bi-star',
};

const getSportIcon = (sport: string) => {
    const sportLower = sport.toLowerCase();
    return sportIcons[sportLower] || sportIcons['default'];
};
</script>

<template>
    <div>
        <div v-for="(bets, sport) in groupedBets" :key="sport" class="mb-4">
            <!-- Sport Header -->
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px">
                        <i :class="getSportIcon(sport)" class="bi text-white fs-5"></i>
                    </div>
                    <h3 class="h5 fw-bold text-white mb-0 text-capitalize">{{ sport }}</h3>
                </div>
                <span class="badge bg-success">
                    <i class="bi bi-circle-fill me-1" style="font-size: 8px"></i>
                    {{ bets.length }} {{ bets.length === 1 ? 'Pick' : 'Picks' }}
                </span>
            </div>

            <!-- Bet Cards Grid -->
            <div class="row g-3">
                <div v-for="bet in bets" :key="bet.id" class="col-12 col-md-6 col-lg-3">
                    <SimpleBetCard v-if="!bet.isCovered" :bet="bet" />
                    <CoveredBetPickCard v-else :bet="bet" />
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
/* Clean, minimal styling */
</style>
