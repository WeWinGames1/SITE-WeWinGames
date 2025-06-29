<template>
    <div class="card h-100" :class="getBetCardClass()" style="background-color: var(--bs-card-bg); border: 1px solid var(--bs-card-border);">
        <div class="card-body p-4">
            <!-- Header with Date and League -->
            <div class="d-flex justify-content-between align-items-start mb-3">
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
            <div class="text-center mb-4">
                <div class="d-flex align-items-center justify-content-center gap-3">
                    <div class="text-center">
                        <img
                            :src="bet.team_one_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_one"
                            class="rounded-circle mb-2"
                            style="width: 48px; height: 48px; object-fit: cover; background: white;"
                        />
                        <p class="fw-semibold text-white small mb-0">{{ bet.team_one }}</p>
                    </div>
                    <div class="text-gray-light fs-5 fw-bold">VS</div>
                    <div class="text-center">
                        <img
                            :src="bet.team_two_logo || '/images/placeholder-team-logo.png'"
                            :alt="bet.team_two"
                            class="rounded-circle mb-2"
                            style="width: 48px; height: 48px; object-fit: cover; background: white;"
                        />
                        <p class="fw-semibold text-white small mb-0">{{ bet.team_two }}</p>
                    </div>
                </div>
            </div>

            <!-- Betting Info -->
            <div class="bg-dark rounded-3 p-3 mb-3" style="background-color: rgba(0,0,0,0.3) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-light">Pick:</span>
                    <span class="text-white fw-semibold">{{ bet.tips || 'N/A' }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-gray-light">Market:</span>
                    <span class="text-white">{{ bet.markets || 'N/A' }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="text-gray-light">Odds:</span>
                    <span class="text-success fw-bold">{{ bet.wager_odds || 'N/A' }}</span>
                </div>
                <div v-if="bet.place_fraction" class="d-flex align-items-center justify-content-between mt-2">
                    <span class="text-gray-light">Place Fraction:</span>
                    <span class="text-info">{{ bet.place_fraction }}</span>
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