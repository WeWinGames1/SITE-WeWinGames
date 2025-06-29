<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { 
    ArrowLeftIcon,
    CalendarIcon,
    CurrencyDollarIcon,
    ChartBarIcon,
    TrophyIcon
} from '@heroicons/vue/24/outline';

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
    game_at: new Date(props.bet.game_at).toISOString().slice(0, 16),
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
    return `${game.awayTeam.name} @ ${game.homeTeam.name} - ${new Date(game.game_at).toLocaleDateString()}`;
}
</script>

<template>
    <AdminLayout>
        <Head title="Edit Bet" />
        
        <div class="p-6">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="route('admin.bets.index')"
                    class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                >
                    <ArrowLeftIcon class="h-4 w-4 mr-1" />
                    Back to Bets
                </Link>
                
                <h1 class="text-3xl font-bold text-gray-900">Edit Bet</h1>
                <p class="mt-2 text-sm text-gray-700">
                    Update bet #{{ bet.id }} details
                </p>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">Basic Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- User -->
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                                User <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="user_id"
                                v-model="form.user_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="">Select a user...</option>
                                <option v-for="user in users" :key="user.id" :value="user.id">
                                    {{ user.name }} ({{ user.email }})
                                </option>
                            </select>
                            <p v-if="form.errors.user_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.user_id }}
                            </p>
                        </div>

                        <!-- Sport -->
                        <div>
                            <label for="sport_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Sport <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="sport_id"
                                v-model="form.sport_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="">Select a sport...</option>
                                <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                    {{ sport.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.sport_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.sport_id }}
                            </p>
                        </div>

                        <!-- Game -->
                        <div>
                            <label for="game_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Game
                            </label>
                            <select
                                id="game_id"
                                v-model="form.game_id"
                                :disabled="!form.sport_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-50"
                            >
                                <option value="">Select a game (optional)...</option>
                                <option v-for="game in filteredGames" :key="game.id" :value="game.id">
                                    {{ formatGameOption(game) }}
                                </option>
                            </select>
                            <p v-if="form.errors.game_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.game_id }}
                            </p>
                        </div>

                        <!-- Operator -->
                        <div>
                            <label for="operator_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Operator <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="operator_id"
                                v-model="form.operator_id"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="">Select an operator...</option>
                                <option v-for="operator in operators" :key="operator.id" :value="operator.id">
                                    {{ operator.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.operator_id" class="mt-1 text-sm text-red-600">
                                {{ form.errors.operator_id }}
                            </p>
                        </div>

                        <!-- Bet Type -->
                        <div>
                            <label for="bet_type" class="block text-sm font-medium text-gray-700 mb-1">
                                Bet Type <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="bet_type"
                                v-model="form.bet_type"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option v-for="(label, value) in betTypes" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                            <p v-if="form.errors.bet_type" class="mt-1 text-sm text-red-600">
                                {{ form.errors.bet_type }}
                            </p>
                        </div>

                        <!-- Game Date/Time -->
                        <div>
                            <label for="game_at" class="block text-sm font-medium text-gray-700 mb-1">
                                Game Date/Time <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="game_at"
                                    v-model="form.game_at"
                                    type="datetime-local"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    required
                                />
                                <CalendarIcon class="absolute right-3 top-2.5 h-5 w-5 text-gray-400 pointer-events-none" />
                            </div>
                            <p v-if="form.errors.game_at" class="mt-1 text-sm text-red-600">
                                {{ form.errors.game_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">Bet Details</h2>
                    
                    <div class="space-y-6">
                        <!-- Selection -->
                        <div>
                            <label for="selection" class="block text-sm font-medium text-gray-700 mb-1">
                                Selection <span class="text-red-500">*</span>
                            </label>
                            <input
                                id="selection"
                                v-model="form.selection"
                                type="text"
                                placeholder="e.g., Team A -3.5, Over 48.5, Player to score"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            />
                            <p v-if="form.errors.selection" class="mt-1 text-sm text-red-600">
                                {{ form.errors.selection }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Additional details about this bet..."
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                                {{ form.errors.description }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Odds -->
                            <div>
                                <label for="odds" class="block text-sm font-medium text-gray-700 mb-1">
                                    Odds <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="odds"
                                        v-model.number="form.odds"
                                        type="number"
                                        step="1"
                                        placeholder="-110"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                </div>
                                <p class="mt-1 text-xs text-gray-500">American odds format</p>
                                <p v-if="form.errors.odds" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.odds }}
                                </p>
                            </div>

                            <!-- Stake -->
                            <div>
                                <label for="stake" class="block text-sm font-medium text-gray-700 mb-1">
                                    Stake <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <CurrencyDollarIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                    <input
                                        id="stake"
                                        v-model.number="form.stake"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="100"
                                        class="block w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.stake" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.stake }}
                                </p>
                            </div>

                            <!-- Potential Win -->
                            <div>
                                <label for="potential_win" class="block text-sm font-medium text-gray-700 mb-1">
                                    Potential Win
                                </label>
                                <div class="relative">
                                    <CurrencyDollarIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                    <input
                                        id="potential_win"
                                        :value="form.potential_win.toFixed(2)"
                                        type="text"
                                        class="block w-full pl-10 rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm"
                                        readonly
                                    />
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Calculated automatically</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-6">Result & Status</h2>
                    
                    <div class="space-y-6">
                        <!-- Status -->
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">
                                Status
                            </label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="pending">Pending</option>
                                <option value="won">Won</option>
                                <option value="lost">Lost</option>
                                <option value="push">Push</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                            <p v-if="form.errors.status" class="mt-1 text-sm text-red-600">
                                {{ form.errors.status }}
                            </p>
                        </div>

                        <!-- Actual Result -->
                        <div>
                            <label for="actual_result" class="block text-sm font-medium text-gray-700 mb-1">
                                Actual Result
                            </label>
                            <input
                                id="actual_result"
                                v-model="form.actual_result"
                                type="text"
                                placeholder="e.g., Team A won 27-24"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                            <p v-if="form.errors.actual_result" class="mt-1 text-sm text-red-600">
                                {{ form.errors.actual_result }}
                            </p>
                        </div>

                        <!-- Profit -->
                        <div>
                            <label for="profit" class="block text-sm font-medium text-gray-700 mb-1">
                                Profit/Loss
                            </label>
                            <div class="relative">
                                <CurrencyDollarIcon class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" />
                                <input
                                    id="profit"
                                    :value="form.profit.toFixed(2)"
                                    type="text"
                                    :class="[
                                        'block w-full pl-10 rounded-md border-gray-300 bg-gray-50 shadow-sm sm:text-sm',
                                        form.profit > 0 ? 'text-green-600' : form.profit < 0 ? 'text-red-600' : 'text-gray-900'
                                    ]"
                                    readonly
                                />
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Calculated based on status</p>
                        </div>

                        <!-- Confidence -->
                        <div>
                            <label for="confidence" class="block text-sm font-medium text-gray-700 mb-1">
                                Confidence Level
                            </label>
                            <div class="flex items-center space-x-4">
                                <input
                                    id="confidence"
                                    v-model.number="form.confidence"
                                    type="range"
                                    min="1"
                                    max="10"
                                    class="flex-1"
                                />
                                <div class="flex items-center">
                                    <ChartBarIcon class="h-5 w-5 text-gray-400 mr-2" />
                                    <span class="text-sm font-medium text-gray-700 w-8">{{ form.confidence }}/10</span>
                                </div>
                            </div>
                            <p v-if="form.errors.confidence" class="mt-1 text-sm text-red-600">
                                {{ form.errors.confidence }}
                            </p>
                        </div>

                        <!-- Featured -->
                        <div class="flex items-center">
                            <input
                                id="is_featured"
                                v-model="form.is_featured"
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                            />
                            <label for="is_featured" class="ml-3 flex items-center">
                                <TrophyIcon class="h-5 w-5 text-yellow-500 mr-2" />
                                <span class="text-sm font-medium text-gray-700">Feature this bet</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end space-x-3">
                    <Link
                        :href="route('admin.bets.index')"
                        class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>