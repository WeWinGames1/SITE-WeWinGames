<template>
    <div class="card h-100 position-relative overflow-hidden" style="background-color: #1a2332; border: 2px solid #2a3441; border-radius: 12px;" @mouseenter="showDetails = true" @mouseleave="showDetails = false">
        <!-- Header -->
        <div class="px-4 py-3" style="background-color: #0a1628; border-bottom: 1px solid #2a3441;">
            <div class="d-flex justify-content-between align-items-center">
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
        
        <div class="card-body p-4">

            <!-- Teams Section -->
            <div class="text-center mb-4">
                <div class="d-flex align-items-center justify-content-center gap-4">
                    <div class="text-center">
                        <img
                            :src="bet.team_one_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_one"
                            class="mb-2"
                            style="width: 64px; height: 64px; object-fit: contain;"
                        />
                        <p class="fw-bold text-white mb-0">{{ bet.team_one }}</p>
                    </div>
                    <div class="text-white fs-4 fw-bold">VS</div>
                    <div class="text-center">
                        <img
                            :src="bet.team_two_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_two"
                            class="mb-2"
                            style="width: 64px; height: 64px; object-fit: contain;"
                        />
                        <p class="fw-bold text-white mb-0">{{ bet.team_two }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Game Level Badge -->
            <div class="text-center mb-4">
                <span class="badge px-4 py-2" :class="getMembershipBadgeClass()" style="font-size: 14px; border-radius: 20px;">
                    Game Level: {{ bet.membership.toUpperCase() }}
                </span>
            </div>

            <!-- Betting Button -->
            <div class="text-center">
                <button class="btn btn-warning btn-lg w-100 fw-bold text-dark" style="border-radius: 8px; padding: 12px;">
                    {{ bet.tips || 'Ohio Moneyline' }} - {{ bet.wager_odds || '110' }}
                </button>
            </div>
            
            <!-- Additional Info (hidden by default, shown on hover) -->
            <div class="position-absolute bottom-0 start-0 end-0 p-3 bg-dark" style="background-color: rgba(10, 22, 40, 0.95) !important; transform: translateY(100%); transition: transform 0.3s ease;" :style="showDetails ? 'transform: translateY(0);' : ''">
                <div class="small">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-gray-light">Market:</span>
                        <span class="text-white">{{ bet.markets || 'Moneyline' }}</span>
                    </div>
                    <div v-if="bet.place_fraction" class="d-flex justify-content-between">
                        <span class="text-gray-light">Place Fraction:</span>
                        <span class="text-info">{{ bet.place_fraction }}</span>
                    </div>
                </div>
            </div>

            <!-- Result Badge (if applicable) -->
            <div v-if="bet.status && bet.status !== 'Pending'" class="text-center">
                <span class="badge" :class="getStatusBadgeClass()">
                    <i :class="getStatusIcon()" class="me-1"></i>
                    {{ bet.status }}
                </span>
            </div>

            <!-- Admin Actions -->
            <div v-if="isAdmin" class="mt-4 pt-3 border-top" style="border-color: var(--bs-gray-medium) !important;">
                <h6 class="text-white mb-3">Admin Controls</h6>
                
                <div class="row g-3">
                    <div class="col-6">
                        <label class="form-label text-gray-light small">Status</label>
                        <select v-model="updatedStatus" class="form-select form-select-sm">
                            <option value="Pending">Pending</option>
                            <option value="Won">Win</option>
                            <option value="Lost">Loss</option>
                            <option value="Push">Push</option>
                        </select>
                    </div>
                    
                    <div class="col-6">
                        <label class="form-label text-gray-light small">Date</label>
                        <input type="date" v-model="updatedDate" class="form-control form-control-sm" />
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-gray-light small">Referrer</label>
                        <input type="text" v-model="updatedReferrer" class="form-control form-control-sm" placeholder="Optional" />
                    </div>
                    
                    <div class="col-12">
                        <label class="form-label text-gray-light small">Place Fraction</label>
                        <input type="number" v-model="updatedPlaceFraction" class="form-control form-control-sm" step="0.01" min="0" max="1" />
                    </div>
                </div>
                
                <div class="d-flex gap-2 mt-3">
                    <button @click="updateBet" class="btn btn-primary btn-sm flex-fill">
                        <i class="bi bi-check-circle me-1"></i> Update
                    </button>
                    <button @click="deleteBet" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, defineEmits } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    bet: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits();
const { props: pageProps } = usePage();
const isAdmin = pageProps.auth?.isAdmin || false;

const updatedStatus = ref(props.bet.status);
const updatedDate = ref(props.bet.betting_date || '');
const updatedReferrer = ref(props.bet.referrer || '');
const updatedPlaceFraction = ref(props.bet.place_fraction || '');
const showDetails = ref(false);

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

const getStatusBadgeClass = () => {
    switch (props.bet.status) {
        case 'Won': return 'bg-success';
        case 'Lost': return 'bg-danger';
        case 'Push': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
};

const getStatusIcon = () => {
    switch (props.bet.status) {
        case 'Won': return 'bi bi-check-circle-fill';
        case 'Lost': return 'bi bi-x-circle-fill';
        case 'Push': return 'bi bi-dash-circle-fill';
        default: return '';
    }
};

const getBetCardClass = () => {
    const membership = props.bet.membership.toUpperCase();
    if (membership === 'PLATINUM') return 'border-purple';
    if (membership === 'GOLD') return 'border-warning';
    return '';
};

const updateBet = async () => {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('status', updatedStatus.value);
    formData.append('betting_date', updatedDate.value);
    formData.append('referrer', updatedReferrer.value);
    formData.append('place_fraction', updatedPlaceFraction.value);
    
    try {
        const response = await axios.post(`/api/bets/${props.bet.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        alert('Bet updated successfully!');
        emit('bet-updated', response.data);
    } catch (error) {
        alert('Failed to update bet. Please try again.');
    }
};

const deleteBet = async () => {
    if (confirm('Are you sure you want to delete this bet?')) {
        try {
            await axios.delete(`/api/bets/${props.bet.id}`);
            alert('Bet deleted successfully!');
            emit('bet-deleted', props.bet.id);
        } catch (error) {
            alert('Failed to delete bet. Please try again.');
        }
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

.border-purple {
    border: 2px solid var(--bs-purple) !important;
}

.border-warning {
    border: 2px solid var(--bs-warning) !important;
}
</style>