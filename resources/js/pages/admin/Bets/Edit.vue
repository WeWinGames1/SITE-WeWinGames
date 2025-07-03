<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Sport {
    id: number;
    name: string;
}

interface Operator {
    id: number;
    name: string;
}

interface Bet {
    id: number;
    sports: string;
    league?: string;
    month?: string;
    matches?: string;
    markets?: string;
    wager_type?: string;
    team_one?: string;
    team_one_logo?: string;
    team_two?: string;
    team_two_logo?: string;
    tips?: string;
    betting_date: string;
    wager_odds: number | string;
    membership: string;
    level?: string;
    code?: string;
    roi?: number;
    wager_amount: number;
    winning_amount?: number;
    profit_amount?: number;
    status: string;
    referrer?: string;
    place_fraction?: number;
    user_id?: number;
}

interface Props {
    bet: Bet;
    users: User[];
    sports: Sport[];
    operators: Operator[];
}

const props = defineProps<Props>();

// Debug: log the bet data to see what's coming from the backend
console.log('Bet data from backend:', props.bet);
console.log('Status value:', props.bet.status);
console.log('Wager type value:', props.bet.wager_type);
console.log('Month value:', props.bet.month);

const form = useForm({
    sports: props.bet.sports || '',
    league: props.bet.league || '',
    month: props.bet.month || '',
    matches: props.bet.matches || '',
    markets: props.bet.markets || '',
    wager_type: props.bet.wager_type ? String(props.bet.wager_type).toLowerCase() : null,
    team_one: props.bet.team_one || '',
    team_two: props.bet.team_two || '',
    tips: props.bet.tips || '',
    betting_date: props.bet.betting_date ? new Date(props.bet.betting_date).toISOString().slice(0, 16) : '',
    wager_odds: props.bet.wager_odds || '',
    membership: String(props.bet.membership || 'bronze').toLowerCase(),
    level: props.bet.level || '',
    code: props.bet.code || '',
    roi: props.bet.roi || 0,
    wager_amount: props.bet.wager_amount || 0,
    winning_amount: props.bet.winning_amount || 0,
    profit_amount: props.bet.profit_amount || 0,
    status: String(props.bet.status || 'pending').toLowerCase(),
    referrer: props.bet.referrer || '',
    place_fraction: props.bet.place_fraction || 0,
    user_id: props.bet.user_id || null,
});

// Calculate potential win when odds or stake change
watch([() => form.wager_odds, () => form.wager_amount], () => {
    calculatePotentialWin();
});

// Calculate profit when status changes
watch(() => form.status, () => {
    calculateProfit();
});

function calculatePotentialWin() {
    const odds = typeof form.wager_odds === 'string' ? parseFloat(form.wager_odds) : form.wager_odds;
    const stake = Number(form.wager_amount);
    
    if (odds && stake) {
        if (odds > 0) {
            // Positive American odds
            form.winning_amount = stake + (stake * odds / 100);
        } else {
            // Negative American odds
            form.winning_amount = stake + (stake * 100 / Math.abs(odds));
        }
    } else {
        form.winning_amount = 0;
    }
}

function calculateProfit() {
    const stake = Number(form.wager_amount);
    const winning = Number(form.winning_amount);
    
    if (form.status === 'won') {
        form.profit_amount = winning - stake;
    } else if (form.status === 'lost') {
        form.profit_amount = -stake;
    } else if (form.status === 'push' || form.status === 'void') {
        form.profit_amount = 0;
    }
}

function submit() {
    form.put(route('admin.bets.update', props.bet.id));
}
</script>

<template>
    <AdminLayout>
        <Head title="Edit Bet" />
        
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <Link :href="route('admin.dashboard')">Dashboard</Link>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <Link :href="route('admin.bets.index')">Bets</Link>
                                    </li>
                                    <li class="breadcrumb-item active">Edit</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 text-dark">Edit Bet #{{ bet.id }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Basic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Sport -->
                            <div class="col-md-4">
                                <label for="sports" class="form-label">Sport <span class="text-danger">*</span></label>
                                <input
                                    id="sports"
                                    v-model="form.sports"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.sports }"
                                    required
                                />
                                <div v-if="form.errors.sports" class="invalid-feedback">
                                    {{ form.errors.sports }}
                                </div>
                            </div>

                            <!-- League -->
                            <div class="col-md-4">
                                <label for="league" class="form-label">League <span class="text-danger">*</span></label>
                                <input
                                    id="league"
                                    v-model="form.league"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.league }"
                                    placeholder="e.g., NFL, NBA, MLB"
                                />
                                <div v-if="form.errors.league" class="invalid-feedback">
                                    {{ form.errors.league }}
                                </div>
                            </div>

                            <!-- Month -->
                            <div class="col-md-4">
                                <label for="month" class="form-label">Month</label>
                                <input
                                    id="month"
                                    v-model="form.month"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.month }"
                                    placeholder="e.g., January, February"
                                />
                                <div v-if="form.errors.month" class="invalid-feedback">
                                    {{ form.errors.month }}
                                </div>
                            </div>

                            <!-- Team One -->
                            <div class="col-md-6">
                                <label for="team_one" class="form-label">Team One / Player</label>
                                <input
                                    id="team_one"
                                    v-model="form.team_one"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.team_one }"
                                    placeholder="Home team or player name"
                                />
                                <div v-if="form.errors.team_one" class="invalid-feedback">
                                    {{ form.errors.team_one }}
                                </div>
                            </div>

                            <!-- Team Two -->
                            <div class="col-md-6">
                                <label for="team_two" class="form-label">Team Two</label>
                                <input
                                    id="team_two"
                                    v-model="form.team_two"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.team_two }"
                                    placeholder="Away team (optional for individual sports)"
                                />
                                <div v-if="form.errors.team_two" class="invalid-feedback">
                                    {{ form.errors.team_two }}
                                </div>
                            </div>

                            <!-- User -->
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">User</label>
                                <select
                                    id="user_id"
                                    v-model="form.user_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.user_id }"
                                >
                                    <option :value="null">Select a user...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="form.errors.user_id" class="invalid-feedback">
                                    {{ form.errors.user_id }}
                                </div>
                            </div>

                            <!-- Betting Date -->
                            <div class="col-md-6">
                                <label for="betting_date" class="form-label">Betting Date <span class="text-danger">*</span></label>
                                <input
                                    id="betting_date"
                                    v-model="form.betting_date"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.betting_date }"
                                    required
                                />
                                <div v-if="form.errors.betting_date" class="invalid-feedback">
                                    {{ form.errors.betting_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bet Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Bet Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Markets -->
                            <div class="col-md-6">
                                <label for="markets" class="form-label">Markets</label>
                                <input
                                    id="markets"
                                    v-model="form.markets"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.markets }"
                                    placeholder="e.g., Spread, Over/Under, Moneyline"
                                />
                                <div v-if="form.errors.markets" class="invalid-feedback">
                                    {{ form.errors.markets }}
                                </div>
                            </div>

                            <!-- Wager Type -->
                            <div class="col-md-6">
                                <label for="wager_type" class="form-label">Wager Type</label>
                                <select
                                    id="wager_type"
                                    v-model="form.wager_type"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.wager_type }"
                                >
                                    <option :value="null">Select wager type...</option>
                                    <option value="straight">Straight</option>
                                    <option value="parlay">Parlay</option>
                                    <option value="teaser">Teaser</option>
                                    <option value="round robin">Round Robin</option>
                                    <option value="each way">Each Way</option>
                                    <option value="if bet">If Bet</option>
                                    <option value="reverse">Reverse</option>
                                    <option value="future">Future</option>
                                    <option value="prop">Prop</option>
                                </select>
                                <div v-if="form.errors.wager_type" class="invalid-feedback">
                                    {{ form.errors.wager_type }}
                                </div>
                            </div>

                            <!-- Tips -->
                            <div class="col-md-12">
                                <label for="tips" class="form-label">Tips / Selection</label>
                                <input
                                    id="tips"
                                    v-model="form.tips"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.tips }"
                                    placeholder="e.g., Lakers -5.5, Over 220.5"
                                />
                                <div v-if="form.errors.tips" class="invalid-feedback">
                                    {{ form.errors.tips }}
                                </div>
                            </div>

                            <!-- Odds -->
                            <div class="col-md-4">
                                <label for="wager_odds" class="form-label">Odds <span class="text-danger">*</span></label>
                                <input
                                    id="wager_odds"
                                    v-model="form.wager_odds"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.wager_odds }"
                                    placeholder="e.g., -110, +150"
                                    required
                                />
                                <div v-if="form.errors.wager_odds" class="invalid-feedback">
                                    {{ form.errors.wager_odds }}
                                </div>
                            </div>

                            <!-- Wager Amount -->
                            <div class="col-md-4">
                                <label for="wager_amount" class="form-label">Wager Amount <span class="text-danger">*</span></label>
                                <input
                                    id="wager_amount"
                                    v-model.number="form.wager_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.wager_amount }"
                                    required
                                />
                                <div v-if="form.errors.wager_amount" class="invalid-feedback">
                                    {{ form.errors.wager_amount }}
                                </div>
                            </div>

                            <!-- Winning Amount -->
                            <div class="col-md-4">
                                <label for="winning_amount" class="form-label">Winning Amount</label>
                                <input
                                    id="winning_amount"
                                    v-model.number="form.winning_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.winning_amount }"
                                    readonly
                                />
                                <div v-if="form.errors.winning_amount" class="invalid-feedback">
                                    {{ form.errors.winning_amount }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.status }"
                                    required
                                >
                                    <option value="">Select status...</option>
                                    <option value="pending">Pending</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
                                    <option value="push">Push</option>
                                    <option value="void">Void</option>
                                </select>
                                <div v-if="form.errors.status" class="invalid-feedback">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <!-- Profit Amount -->
                            <div class="col-md-4">
                                <label for="profit_amount" class="form-label">Profit Amount</label>
                                <input
                                    id="profit_amount"
                                    v-model.number="form.profit_amount"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.profit_amount }"
                                    readonly
                                />
                                <div v-if="form.errors.profit_amount" class="invalid-feedback">
                                    {{ form.errors.profit_amount }}
                                </div>
                            </div>

                            <!-- ROI -->
                            <div class="col-md-4">
                                <label for="roi" class="form-label">ROI (%)</label>
                                <input
                                    id="roi"
                                    v-model.number="form.roi"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.roi }"
                                />
                                <div v-if="form.errors.roi" class="invalid-feedback">
                                    {{ form.errors.roi }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Additional Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Membership -->
                            <div class="col-md-4">
                                <label for="membership" class="form-label">Membership <span class="text-danger">*</span></label>
                                <select
                                    id="membership"
                                    v-model="form.membership"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.membership }"
                                    :key="`membership-${form.membership}`"
                                    required
                                >
                                    <option v-if="!form.membership" value="" selected>Select membership...</option>
                                    <option value="bronze">Bronze</option>
                                    <option value="silver">Silver</option>
                                    <option value="gold">Gold</option>
                                    <option value="platinum">Platinum</option>
                                </select>
                                <div v-if="form.errors.membership" class="invalid-feedback">
                                    {{ form.errors.membership }}
                                </div>
                            </div>

                            <!-- Level -->
                            <div class="col-md-4">
                                <label for="level" class="form-label">Level</label>
                                <input
                                    id="level"
                                    v-model="form.level"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.level }"
                                    placeholder="e.g., 1, 2, 3"
                                />
                                <div v-if="form.errors.level" class="invalid-feedback">
                                    {{ form.errors.level }}
                                </div>
                            </div>

                            <!-- Code -->
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code</label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.code }"
                                    placeholder="Reference code"
                                />
                                <div v-if="form.errors.code" class="invalid-feedback">
                                    {{ form.errors.code }}
                                </div>
                            </div>

                            <!-- Referrer -->
                            <div class="col-md-6">
                                <label for="referrer" class="form-label">Referrer</label>
                                <input
                                    id="referrer"
                                    v-model="form.referrer"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.referrer }"
                                    placeholder="Referral source"
                                />
                                <div v-if="form.errors.referrer" class="invalid-feedback">
                                    {{ form.errors.referrer }}
                                </div>
                            </div>

                            <!-- Place Fraction -->
                            <div class="col-md-6">
                                <label for="place_fraction" class="form-label">Place Fraction (Each Way)</label>
                                <input
                                    id="place_fraction"
                                    v-model.number="form.place_fraction"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.place_fraction }"
                                    placeholder="0.25"
                                />
                                <div v-if="form.errors.place_fraction" class="invalid-feedback">
                                    {{ form.errors.place_fraction }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
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