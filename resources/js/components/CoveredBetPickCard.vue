<template>
    <div class="card h-100 position-relative overflow-hidden" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
        <!-- Blur Overlay -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="backdrop-filter: blur(8px); background: rgba(0, 0, 0, 0.7); z-index: 1;"></div>
        
        <div class="card-body p-4 position-relative" style="z-index: 2;">
            <!-- Header with Date and League -->
            <div class="d-flex justify-content-between align-items-start mb-3 opacity-50">
                <div>
                    <span class="badge" :class="getMembershipBadgeClass()">
                        {{ bet.membership.toUpperCase() }}
                    </span>
                    <p class="text-gray-light small mb-0 mt-2">{{ formatDate(bet.betting_date) }}</p>
                </div>
                <div class="text-end">
                    <p class="text-purple fw-semibold mb-0">{{ bet.league || 'N/A' }}</p>
                </div>
            </div>

            <!-- Teams Section -->
            <div class="text-center mb-4 opacity-50">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="text-center">
                        <img
                            :src="bet.team_one_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_one"
                            class="rounded-circle mb-2"
                            style="width: 48px; height: 48px; object-fit: cover; background: white; filter: blur(2px);"
                        />
                        <p class="fw-semibold text-white small mb-0">{{ bet.team_one }}</p>
                    </div>
                    <div class="text-gray-light fs-5 fw-bold">VS</div>
                    <div class="text-center">
                        <img
                            :src="bet.team_two_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_two"
                            class="rounded-circle mb-2"
                            style="width: 48px; height: 48px; object-fit: cover; background: white; filter: blur(2px);"
                        />
                        <p class="fw-semibold text-white small mb-0">{{ bet.team_two }}</p>
                    </div>
                </div>
            </div>

            <!-- Locked Content Overlay -->
            <div class="text-center py-4">
                <i class="bi bi-lock-fill text-white display-4 mb-3"></i>
                <h5 class="text-white fw-bold mb-3">Premium Pick</h5>
                <p class="text-gray-light mb-4">Unlock this {{ bet.membership }} pick and get access to:</p>
                <ul class="list-unstyled text-gray-light mb-4">
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Expert Analysis</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Betting Recommendations</li>
                    <li class="mb-2"><i class="bi bi-check-circle text-success me-2"></i>Live Updates</li>
                </ul>
                <Link
                    :href="route('buy-our-picks')"
                    class="btn btn-primary btn-lg px-5"
                >
                    <i class="bi bi-unlock me-2"></i>
                    Unlock All Picks
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    bet: {
        type: Object,
        required: true,
    },
});

const formatDate = (date: string) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
    });
};

const getMembershipBadgeClass = () => {
    const membership = props.bet.membership.toUpperCase();
    switch (membership) {
        case 'BRONZE': return 'bg-danger';
        case 'SILVER': return 'bg-secondary';
        case 'GOLD': return 'bg-warning text-dark';
        case 'PLATINUM': return 'bg-purple';
        default: return 'bg-dark';
    }
};
</script>

<style scoped>
.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.3) !important;
}
</style>