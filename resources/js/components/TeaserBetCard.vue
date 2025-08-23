<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps<{
    bet: {
        isTeaser: boolean;
        sport: string;
        remainingCount: number;
        isGuest: boolean;
    };
}>();
</script>

<template>
    <div class="teaser-card position-relative">
        <!-- Blurred background with fake bet content -->
        <div class="card bg-dark border-0 h-100 overflow-hidden">
            <div class="blurred-content">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-warning text-dark">Premium</span>
                        <div class="text-end">
                            <small class="text-white-50">Game Date</small>
                            <div class="text-white fw-bold">--/--</div>
                        </div>
                    </div>
                    
                    <h6 class="text-white mb-2">--- @ ---</h6>
                    <p class="text-white-50 mb-3">--------</p>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-white-50">Odds</small>
                            <div class="text-success fw-bold">+---</div>
                        </div>
                        <div class="text-end">
                            <small class="text-white-50">Confidence</small>
                            <div class="text-warning">
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                                <i class="bi bi-star-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Overlay content -->
            <div class="overlay-content position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center p-4">
                <div class="text-center">
                    <i class="bi bi-lock-fill text-warning display-4 mb-3"></i>
                    <h5 v-if="bet.remainingCount >= 2" class="text-white mb-3">
                        {{ bet.remainingCount }} More Picks Available
                    </h5>
                    <h5 v-else-if="bet.remainingCount === 1" class="text-white mb-3">
                        More Picks Available
                    </h5>
                    <h5 v-else class="text-white mb-3">
                        Unlock Premium Picks
                    </h5>
                    <p class="text-white-50 mb-4 small">
                        {{ bet.isGuest ? 'Create a free account' : 'Upgrade your membership' }} to access exclusive betting insights and premium picks
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <Link 
                            v-if="bet.isGuest" 
                            href="/register" 
                            class="btn btn-warning btn-sm"
                        >
                            <i class="bi bi-person-plus me-2"></i>
                            Register for Free
                        </Link>
                        <Link 
                            v-else 
                            href="/buy-our-picks" 
                            class="btn btn-warning btn-sm"
                        >
                            <i class="bi bi-arrow-up-circle me-2"></i>
                            Upgrade Now
                        </Link>
                        <Link 
                            v-if="bet.isGuest" 
                            href="/login" 
                            class="btn btn-outline-light btn-sm"
                        >
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.teaser-card {
    height: 100%;
    min-height: 280px;
}

.blurred-content {
    filter: blur(8px);
    opacity: 0.3;
}

.overlay-content {
    background: rgba(0, 0, 0, 0.85);
    backdrop-filter: blur(5px);
    z-index: 10;
}

.card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 2px solid transparent !important;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(255, 193, 7, 0.3);
    border-color: #ffc107 !important;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    border: none;
    font-weight: 600;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #ffca2c 0%, #ffa726 100%);
    transform: scale(1.05);
}
</style>