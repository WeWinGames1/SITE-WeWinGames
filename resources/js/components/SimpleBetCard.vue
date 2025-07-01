<template>
    <div class="bet-card position-relative" :style="{ backgroundColor: cardBgColor }">
        <!-- Odds Ribbon -->
        <div class="odds-ribbon">
            {{ getOdds(bet) }}
        </div>
        
        <!-- Header -->
        <div class="bet-header d-flex align-items-center gap-2 mb-2">
            <i class="bi bi-trophy-fill text-white"></i>
            <span class="text-white fw-semibold">{{ bet.sport || bet.sports || 'Football' }}</span>
            <div class="ms-auto me-5">
                <span class="text-white small">{{ bet.league || 'Premier League' }}</span>
            </div>
        </div>
        
        <!-- Date -->
        <div class="text-white small mb-3">Date: {{ formatDate(bet.betting_date) }}</div>
        
        <!-- Teams - Side by Side -->
        <div class="teams-section mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="team-box text-center">
                    <img 
                        :src="bet.team_one_logo || '/images/team-placeholder.svg'" 
                        :alt="bet.team_one"
                        class="team-logo mb-2"
                    />
                    <div class="text-white small">{{ bet.team_one }}</div>
                </div>
                
                <div class="text-white fw-bold">VS</div>
                
                <div class="team-box text-center">
                    <img 
                        :src="bet.team_two_logo || '/images/team-placeholder.svg'" 
                        :alt="bet.team_two"
                        class="team-logo mb-2"
                    />
                    <div class="text-white small">{{ bet.team_two }}</div>
                </div>
            </div>
        </div>
        
        <!-- Game Level -->
        <div class="text-center mb-3">
            <span class="badge text-uppercase" :style="{ backgroundColor: levelBgColor, color: levelTextColor }">
                Game Level: {{ bet.membership }}
            </span>
        </div>
        
        <!-- Betting Pick -->
        <button class="btn w-100 fw-bold" :style="{ backgroundColor: '#ffc107', color: '#000' }">
            {{ formatBetTip(bet) }}
        </button>
    </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps({
    bet: {
        type: Object,
        required: true
    }
});

const formatDate = (date: string) => {
    if (!date) return '21 May, 19:00';
    const d = new Date(date);
    return d.toLocaleDateString('en-US', { 
        day: 'numeric',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
    }).replace(',', ', ');
};

const formatBetTip = (bet: any) => {
    // If there's a specific tip text, use it
    if (bet.tips) {
        // Remove the odds from the tip text if it includes them
        const tipText = bet.tips.split(' - ')[0] || bet.tips;
        return tipText;
    }
    
    // Otherwise, create a default bet text
    const team = bet.bet_team || bet.team_one;
    const type = bet.bet_type || 'Moneyline';
    return `${team} ${type}`;
};

const getOdds = (bet: any) => {
    // Extract odds from various possible locations
    if (bet.wager_odds) {
        return formatOddsDisplay(bet.wager_odds);
    }
    
    // Try to extract from tips text
    if (bet.tips && bet.tips.includes(' - ')) {
        const parts = bet.tips.split(' - ');
        if (parts.length > 1) {
            return formatOddsDisplay(parts[1]);
        }
    }
    
    return '-110'; // Default odds
};

const formatOddsDisplay = (odds: any) => {
    // Ensure odds have proper sign
    const oddsStr = String(odds).trim();
    if (oddsStr.startsWith('-') || oddsStr.startsWith('+')) {
        return oddsStr;
    }
    // Assume positive odds if no sign
    return '+' + oddsStr;
};

const cardBgColor = computed(() => {
    const level = props.bet.membership?.toLowerCase();
    if (level === 'platinum') return '#2a2a2a';
    if (level === 'gold') return '#1a1a1a';
    return '#1e3a5f';
});

const levelBgColor = computed(() => {
    const level = props.bet.membership?.toLowerCase();
    if (level === 'platinum') return '#e3e3e3';
    if (level === 'gold') return '#ffc107';
    return '#6c757d';
});

const levelTextColor = computed(() => {
    const level = props.bet.membership?.toLowerCase();
    if (level === 'gold') return '#000';
    return '#000';
});
</script>

<style scoped>
.bet-card {
    padding: 20px;
    padding-right: 35px; /* Extra padding to account for ribbon */
    border-radius: 12px;
    height: 100%;
    overflow: hidden;
    position: relative;
}

.odds-ribbon {
    position: absolute;
    top: 10px;
    right: -30px;
    background-color: #dc3545;
    color: white;
    padding: 5px 40px;
    font-weight: bold;
    font-size: 14px;
    transform: rotate(45deg);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.team-box {
    flex: 1;
    max-width: 45%;
}

.team-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    background: white;
    border-radius: 50%;
    padding: 4px;
}

.badge {
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
}

.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
    transition: transform 0.2s;
    font-size: 14px;
}

.btn:hover {
    transform: scale(1.02);
}

.teams-section {
    padding: 10px 0;
}
</style>