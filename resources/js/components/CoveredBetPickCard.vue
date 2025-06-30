<template>
    <div class="card h-100 position-relative overflow-hidden" style="background-color: #1a2332; border: 2px solid #2a3441; border-radius: 12px;">
        <!-- Blur Overlay -->
        <div class="position-absolute top-0 start-0 w-100 h-100" style="backdrop-filter: blur(8px); background: rgba(0, 0, 0, 0.7); z-index: 1;"></div>
        
        <!-- Header -->
        <div class="px-4 py-3 position-relative" style="background-color: #0a1628; border-bottom: 1px solid #2a3441; z-index: 2;">
            <div class="d-flex justify-content-between align-items-center opacity-50">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <i class="bi bi-trophy text-white" style="font-size: 16px;"></i>
                    </div>
                    <h6 class="text-white mb-0 fw-bold">{{ bet.sport || 'Football' }}</h6>
                </div>
                <div class="text-end">
                    <p class="text-white small mb-0">{{ bet.league || 'Premier League' }}</p>
                    <p class="text-gray-light small mb-0">Date: {{ formatDate(bet.betting_date) }}</p>
                </div>
            </div>
        </div>
        
        <div class="card-body p-4 position-relative" style="z-index: 2;">

            <!-- Teams Section -->
            <div class="text-center mb-4 opacity-50">
                <div class="d-flex align-items-center justify-content-center gap-4">
                    <div class="text-center">
                        <img
                            :src="bet.team_one_logo || '/images/team-placeholder.svg'"
                            :alt="bet.team_one"
                            class="mb-2"
                            style="width: 64px; height: 64px; object-fit: contain; filter: blur(2px);"
                        />
                        <p class="fw-bold text-white mb-0">{{ bet.team_one }}</p>
                    </div>
                    <div class="text-white fs-4 fw-bold">VS</div>
                    <div class="text-center">
                        <img
                            :src="bet.team_two_logo || '/images/team-placeholder.svg'"
                            :alt="bet.team_two"
                            class="mb-2"
                            style="width: 64px; height: 64px; object-fit: contain; filter: blur(2px);"
                        />
                        <p class="fw-bold text-white mb-0">{{ bet.team_two }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Game Level Badge -->
            <div class="text-center mb-4">
                <span class="badge px-4 py-2 opacity-50" :class="getMembershipBadgeClass()" style="font-size: 14px; border-radius: 20px;">
                    Game Level: {{ bet.membership.toUpperCase() }}
                </span>
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