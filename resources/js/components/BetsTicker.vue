<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

interface Bet {
    id: number;
    sport: string;
    game: string;
    wager_name: string;
    odds: number;
    level: string;
    game_date: string;
}

const bets = ref<Bet[]>([]);
const loading = ref(true);
const tickerContainer = ref<HTMLElement | null>(null);
const animationId = ref<number | null>(null);
const position = ref(0);

// Fetch last 10 bets
const fetchBets = async () => {
    try {
        const response = await axios.get('/api/ticker-bets');
        bets.value = response.data.bets || [];
    } catch (error) {
        console.error('Failed to fetch ticker bets:', error);
    } finally {
        loading.value = false;
    }
};

// Format odds display
const formatOdds = (odds: number) => {
    return odds > 0 ? `+${odds}` : odds.toString();
};

// Get level badge class
const getLevelClass = (level: string) => {
    const levelLower = level.toLowerCase();
    if (levelLower.includes('platinum')) return 'bg-dark text-white';
    if (levelLower.includes('gold')) return 'bg-warning text-dark';
    if (levelLower.includes('silver')) return 'bg-secondary text-white';
    return 'bg-info text-white'; // Bronze
};

// Animation logic
const startAnimation = () => {
    if (!tickerContainer.value) return;
    
    const animate = () => {
        position.value -= 1;
        const tickerWidth = tickerContainer.value?.scrollWidth || 0;
        const containerWidth = tickerContainer.value?.offsetWidth || 0;
        
        // Reset position when ticker has scrolled completely
        if (Math.abs(position.value) >= tickerWidth / 2) {
            position.value = 0;
        }
        
        animationId.value = requestAnimationFrame(animate);
    };
    
    animate();
};

const stopAnimation = () => {
    if (animationId.value) {
        cancelAnimationFrame(animationId.value);
        animationId.value = null;
    }
};

// Navigate to picks page
const viewAllPicks = () => {
    router.visit('/todays-bets');
};

onMounted(() => {
    fetchBets();
    setTimeout(() => {
        if (bets.value.length > 0) {
            startAnimation();
        }
    }, 1000);
});

onUnmounted(() => {
    stopAnimation();
});
</script>

<template>
    <div class="ticker-wrapper" v-if="!loading && bets.length > 0">
        <div class="ticker-container" @click="viewAllPicks">
            <div 
                ref="tickerContainer"
                class="ticker-content"
                :style="{ transform: `translateX(${position}px)` }"
            >
                <!-- Duplicate content for seamless loop -->
                <div class="ticker-items">
                    <div v-for="bet in bets" :key="`bet-1-${bet.id}`" class="ticker-item">
                        <span :class="['badge', getLevelClass(bet.level)]">{{ bet.level }}</span>
                        <span class="sport">{{ bet.sport }}</span>
                        <span class="separator">•</span>
                        <span class="game">{{ bet.game }}</span>
                        <span class="separator">•</span>
                        <span class="pick">{{ bet.wager_name }}</span>
                        <span class="odds">({{ formatOdds(bet.odds) }})</span>
                    </div>
                </div>
                <!-- Duplicate for seamless scrolling -->
                <div class="ticker-items">
                    <div v-for="bet in bets" :key="`bet-2-${bet.id}`" class="ticker-item">
                        <span :class="['badge', getLevelClass(bet.level)]">{{ bet.level }}</span>
                        <span class="sport">{{ bet.sport }}</span>
                        <span class="separator">•</span>
                        <span class="game">{{ bet.game }}</span>
                        <span class="separator">•</span>
                        <span class="pick">{{ bet.wager_name }}</span>
                        <span class="odds">({{ formatOdds(bet.odds) }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ticker-wrapper {
    position: relative;
    overflow: hidden;
    background-color: rgba(0, 0, 0, 0.2);
    padding: 5px 0;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.ticker-wrapper:hover {
    background-color: rgba(0, 0, 0, 0.3);
}

.ticker-container {
    overflow: hidden;
    white-space: nowrap;
}

.ticker-content {
    display: inline-flex;
    padding-left: 100%;
}

.ticker-items {
    display: inline-flex;
    padding-right: 50px;
}

.ticker-item {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-right: 40px;
    font-size: 13px;
    color: #fff;
}

.ticker-item .badge {
    font-size: 10px;
    padding: 2px 8px;
    text-transform: uppercase;
    font-weight: 600;
}

.ticker-item .sport {
    font-weight: 600;
    color: #ffc107;
}

.ticker-item .separator {
    color: rgba(255, 255, 255, 0.5);
}

.ticker-item .game {
    color: rgba(255, 255, 255, 0.9);
}

.ticker-item .pick {
    font-weight: 500;
}

.ticker-item .odds {
    color: #28a745;
    font-weight: 600;
}
</style>