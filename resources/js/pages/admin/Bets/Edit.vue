<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Sport {
    id: number;
    name: string;
}

interface Team {
    id: number;
    name: string;
}

interface Game {
    id: number;
    sport: Sport;
    homeTeam: Team;
    awayTeam: Team;
    game_at: string;
    game_date?: string;
}

interface Operator {
    id: number;
    name: string;
}

interface Bet {
    id: number;
    user_id: number;
    sport_id: number;
    game_id?: number;
    operator_id: number;
    selection: string;
    description?: string;
    bet_type: string;
    odds: number;
    stake: number;
    potential_win: number;
    game_at: string;
    status: string;
    actual_result?: string;
    profit?: number;
    is_featured: boolean;
    confidence?: number;
}

interface Props {
    bet: Bet;
    users: User[];
    sports: Sport[];
    games: Game[];
    operators: Operator[];
    betTypes: Record<string, string>;
}

const props = defineProps<Props>();

const form = useForm({
    user_id: props.bet.user_id,
    sport_id: props.bet.sport_id,
    game_id: props.bet.game_id || '',
    operator_id: props.bet.operator_id,
    selection: props.bet.selection,
    description: props.bet.description || '',
    bet_type: props.bet.bet_type,
    odds: props.bet.odds,
    stake: props.bet.stake,
    potential_win: props.bet.potential_win,
    game_at: props.bet.game_at ? new Date(props.bet.game_at).toISOString().slice(0, 16) : '',
    status: props.bet.status,
    actual_result: props.bet.actual_result || '',
    profit: props.bet.profit || 0,
    is_featured: props.bet.is_featured,
    confidence: props.bet.confidence || 5,
});

// Filtered games based on selected sport
const filteredGames = computed(() => {
    if (!form.sport_id) return [];
    return props.games.filter(game => game.sport.id === Number(form.sport_id));
});

// Watch for sport change to reset game
watch(() => form.sport_id, (newVal, oldVal) => {
    if (newVal !== oldVal && oldVal !== props.bet.sport_id) {
        form.game_id = '';
    }
});

// Calculate potential win when odds or stake change
watch([() => form.odds, () => form.stake], () => {
    calculatePotentialWin();
});

// Calculate profit when status changes
watch(() => form.status, () => {
    calculateProfit();
});

function calculatePotentialWin() {
    const odds = Number(form.odds);
    const stake = Number(form.stake);
    
    if (odds && stake) {
        if (odds > 0) {
            // Positive odds (underdog)
            form.potential_win = (stake * odds / 100);
        } else {
            // Negative odds (favorite)
            form.potential_win = (stake * 100 / Math.abs(odds));
        }
    } else {
        form.potential_win = 0;
    }
}

function calculateProfit() {
    if (form.status === 'won') {
        form.profit = form.potential_win;
    } else if (form.status === 'lost') {
        form.profit = -form.stake;
    } else if (form.status === 'push') {
        form.profit = 0;
    }
}

function submit() {
    form.put(route('admin.bets.update', props.bet.id));
}

function formatGameOption(game: Game): string {
    const gameDate = game.game_date || game.game_at;
    const dateStr = gameDate ? new Date(gameDate).toLocaleDateString() : 'No date';
    return `${game.awayTeam.name} @ ${game.homeTeam.name} - ${dateStr}`;
}
</script>

<template>
    <AdminLayout>
        <Head title="Edit Bet" />
        
        <div class="p-4">
            <!-- Header -->
            <div class="mb-4">
                <Link
                    :href="route('admin.bets.index')"
                    class="btn btn-link text-decoration-none p-0 mb-3"
                >
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Bets
                </Link>
                
                <h1 class="h2 fw-bold text-dark">Edit Bet</h1>
                <p class="text-muted small">
                    Update bet #{{ bet.id }} details
                </p>
            </div>

            <form @submit.prevent="submit">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 fw-medium text-dark mb-3">Basic Information</h2>
                        
                        <div class="row g-3">
                            <!-- User -->
                            <div class="col-md-6">
                                <label for="user_id" class="form-label text-dark fw-medium">
                                    User <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="user_id"
                                    v-model="form.user_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Select a user...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="form.errors.user_id" class="invalid-feedback d-block">
                                    {{ form.errors.user_id }}
                                </div>
                            </div>

                            <!-- Sport -->
                            <div class="col-md-6">
                                <label for="sport_id" class="form-label text-dark fw-medium">
                                    Sport <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="sport_id"
                                    v-model="form.sport_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Select a sport...</option>
                                    <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                        {{ sport.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.sport_id" class="invalid-feedback d-block">
                                    {{ form.errors.sport_id }}
                                </div>
                            </div>

                            <!-- Game -->
                            <div class="col-md-6">
                                <label for="game_id" class="form-label text-dark fw-medium">Game</label>
                                <select
                                    id="game_id"
                                    v-model="form.game_id"
                                    :disabled="!form.sport_id"
                                    class="form-select"
                                >
                                    <option value="">Select a game (optional)...</option>
                                    <option v-for="game in filteredGames" :key="game.id" :value="game.id">
                                        {{ formatGameOption(game) }}
                                    </option>
                                </select>
                                <div v-if="form.errors.game_id" class="invalid-feedback d-block">
                                    {{ form.errors.game_id }}
                                </div>
                            </div>

                            <!-- Operator -->
                            <div class="col-md-6">
                                <label for="operator_id" class="form-label text-dark fw-medium">
                                    Operator <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="operator_id"
                                    v-model="form.operator_id"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Select an operator...</option>
                                    <option v-for="operator in operators" :key="operator.id" :value="operator.id">
                                        {{ operator.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.operator_id" class="invalid-feedback d-block">
                                    {{ form.errors.operator_id }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bet Details -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 fw-medium text-dark mb-3">Bet Details</h2>
                        
                        <div class="row g-3">
                            <!-- Selection -->
                            <div class="col-md-6">
                                <label for="selection" class="form-label text-dark fw-medium">
                                    Selection <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="selection"
                                    v-model="form.selection"
                                    type="text"
                                    class="form-control"
                                    placeholder="e.g., Lakers -5.5"
                                    required
                                />
                                <div v-if="form.errors.selection" class="invalid-feedback d-block">
                                    {{ form.errors.selection }}
                                </div>
                            </div>

                            <!-- Bet Type -->
                            <div class="col-md-6">
                                <label for="bet_type" class="form-label text-dark fw-medium">
                                    Bet Type <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="bet_type"
                                    v-model="form.bet_type"
                                    class="form-select"
                                    required
                                >
                                    <option value="">Select bet type...</option>
                                    <option v-for="(label, value) in betTypes" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.bet_type" class="invalid-feedback d-block">
                                    {{ form.errors.bet_type }}
                                </div>
                            </div>

                            <!-- Odds -->
                            <div class="col-md-4">
                                <label for="odds" class="form-label text-dark fw-medium">
                                    Odds <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="odds"
                                    v-model.number="form.odds"
                                    type="number"
                                    step="any"
                                    class="form-control"
                                    placeholder="e.g., -110, +200"
                                    required
                                />
                                <div v-if="form.errors.odds" class="invalid-feedback d-block">
                                    {{ form.errors.odds }}
                                </div>
                            </div>

                            <!-- Stake -->
                            <div class="col-md-4">
                                <label for="stake" class="form-label text-dark fw-medium">
                                    Stake <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="stake"
                                    v-model.number="form.stake"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    placeholder="0.00"
                                    required
                                />
                                <div v-if="form.errors.stake" class="invalid-feedback d-block">
                                    {{ form.errors.stake }}
                                </div>
                            </div>

                            <!-- Potential Win -->
                            <div class="col-md-4">
                                <label for="potential_win" class="form-label text-dark fw-medium">
                                    Potential Win <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="potential_win"
                                    v-model.number="form.potential_win"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    placeholder="0.00"
                                    required
                                />
                                <div v-if="form.errors.potential_win" class="invalid-feedback d-block">
                                    {{ form.errors.potential_win }}
                                </div>
                            </div>

                            <!-- Game Date -->
                            <div class="col-md-6">
                                <label for="game_at" class="form-label text-dark fw-medium">
                                    Game Date/Time <span class="text-danger">*</span>
                                </label>
                                <input
                                    id="game_at"
                                    v-model="form.game_at"
                                    type="datetime-local"
                                    class="form-control"
                                    required
                                />
                                <div v-if="form.errors.game_at" class="invalid-feedback d-block">
                                    {{ form.errors.game_at }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-6">
                                <label for="status" class="form-label text-dark fw-medium">
                                    Status <span class="text-danger">*</span>
                                </label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="form-select"
                                    required
                                >
                                    <option value="pending">Pending</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                    <option value="push">Push</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                                <div v-if="form.errors.status" class="invalid-feedback d-block">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label text-dark fw-medium">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Additional details about this bet..."
                                ></textarea>
                                <div v-if="form.errors.description" class="invalid-feedback d-block">
                                    {{ form.errors.description }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Settings -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 fw-medium text-dark mb-3">Additional Settings</h2>
                        
                        <div class="row g-3">
                            <!-- Actual Result -->
                            <div class="col-md-6">
                                <label for="actual_result" class="form-label text-dark fw-medium">Actual Result</label>
                                <input
                                    id="actual_result"
                                    v-model="form.actual_result"
                                    type="text"
                                    class="form-control"
                                    placeholder="Final score or result..."
                                />
                                <div v-if="form.errors.actual_result" class="invalid-feedback d-block">
                                    {{ form.errors.actual_result }}
                                </div>
                            </div>

                            <!-- Profit -->
                            <div class="col-md-6">
                                <label for="profit" class="form-label text-dark fw-medium">Profit/Loss</label>
                                <input
                                    id="profit"
                                    v-model.number="form.profit"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    placeholder="0.00"
                                />
                                <div v-if="form.errors.profit" class="invalid-feedback d-block">
                                    {{ form.errors.profit }}
                                </div>
                            </div>

                            <!-- Confidence -->
                            <div class="col-md-6">
                                <label for="confidence" class="form-label text-dark fw-medium">Confidence (1-10)</label>
                                <input
                                    id="confidence"
                                    v-model.number="form.confidence"
                                    type="number"
                                    min="1"
                                    max="10"
                                    class="form-control"
                                    placeholder="5"
                                />
                                <div v-if="form.errors.confidence" class="invalid-feedback d-block">
                                    {{ form.errors.confidence }}
                                </div>
                            </div>

                            <!-- Featured -->
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input
                                        id="is_featured"
                                        v-model="form.is_featured"
                                        type="checkbox"
                                        class="form-check-input"
                                    />
                                    <label for="is_featured" class="form-check-label">
                                        Featured Bet
                                    </label>
                                </div>
                                <div v-if="form.errors.is_featured" class="invalid-feedback d-block">
                                    {{ form.errors.is_featured }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end gap-2">
                    <Link
                        :href="route('admin.bets.index')"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        {{ form.processing ? 'Updating...' : 'Update Bet' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>