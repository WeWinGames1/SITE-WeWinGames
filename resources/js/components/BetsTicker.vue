<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
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
    is_each_way?: boolean;
}

const bets = ref<Bet[]>([]);
const loading = ref(true);
const tickerContainer = ref<HTMLElement | null>(null);
const tickerWrapper = ref<HTMLElement | null>(null);
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
    if (!tickerContainer.value || !tickerWrapper.value) return;
    
    const animate = () => {
        position.value -= 0.8; // Animation speed
        
        // Get the width of one set of ticker items
        const firstTickerItems = tickerContainer.value?.querySelector('.ticker-items');
        const itemsWidth = firstTickerItems?.offsetWidth || 0;
        
        // Reset position when first set has completely scrolled out of view
        if (position.value <= -itemsWidth) {
            position.value = 0;
        }
        
        animationId.value = requestAnimationFrame(animate);
    };
    
    // Start from position 0
    position.value = 0;
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

// Resize handler
const handleResize = () => {
    // No need to restart animation on resize since container handles overflow
};

onMounted(() => {
    fetchBets();
    
    // Add resize listener
    window.addEventListener('resize', handleResize);
    
    setTimeout(() => {
        if (bets.value.length > 0) {
            startAnimation();
        }
    }, 1000);
});

onUnmounted(() => {
    stopAnimation();
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div ref="tickerWrapper" class="ticker-wrapper" v-if="!loading && bets.length > 0">
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
                        <span v-if="bet.is_each_way" class="badge badge-sm bg-success ms-1">EW</span>
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
                        <span v-if="bet.is_each_way" class="badge badge-sm bg-success ms-1">EW</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ticker-wrapper {
    position: relative;
    overflow: hidden; /* This creates the visible window */
    background-color: rgba(0, 0, 0, 0.2);
    padding: 5px 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    flex: 1; /* Take all available flex space */
    height: 30px; /* Fixed height */
}

.ticker-wrapper:hover {
    background-color: rgba(0, 0, 0, 0.3);
}

.ticker-container {
    overflow: hidden; /* Essential: hides overflow content */
    white-space: nowrap;
    width: 100%;
    height: 100%;
    position: relative;
}

.ticker-content {
    position: absolute; /* Key: absolutely positioned */
    top: 0;
    left: 0;
    display: flex;
    white-space: nowrap;
    will-change: transform;
    height: 100%;
    align-items: center;
}

.ticker-items {
    display: flex;
    white-space: nowrap;
    flex-shrink: 0;
    align-items: center;
    height: 100%;
}

.ticker-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-right: 20px;
    font-size: 12px;
    color: #fff;
    flex-shrink: 0;
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