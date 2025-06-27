<template>
    <div class="max-w-3xl mx-auto p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Manual Bet Upload</h2>
        <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Sports -->
                <div>
                    <label for="sports"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Sports</label>
                    <select id="sports" v-model="form.sports"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="BASEBALL">Baseball</option>
                        <option value="BASKETBALL">Basketball</option>
                        <option value="SOCCER">Soccer</option>
                        <option value="GOLF">Golf</option>
                        <option value="HOCKEY">Hockey</option>
                        <option value="MMA">MMA</option>
                        <option value="OTHERSPORTS">Other Sports</option>
                    </select>
                </div>
                <!-- League -->
                <div>
                    <label for="league"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">League</label>
                    <input type="text" id="league" v-model="form.league" placeholder="e.g., MLB"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Matches -->
                <div>
                    <label for="matches"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Matches</label>
                    <input type="text" id="matches" v-model="form.matches"
                        placeholder="e.g., Detroit Tigers @ Houston Astros"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Markets -->
                <div>
                    <label for="markets"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Markets</label>
                    <input type="text" id="markets" v-model="form.markets" placeholder="e.g., Moneyline"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Team One -->
                <div>
                    <label for="team_one" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team
                        One</label>
                    <input type="text" id="team_one" v-model="form.team_one" placeholder="e.g., Detroit Tigers"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Team One Logo -->
                <div>
                    <label for="team_one_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team One Logo</label>
                    <input type="file" id="team_one_logo" @change="handleFileUpload('team_one_logo', $event)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                </div>
                <!-- Team Two -->
                <div>
                    <label for="team_two" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team
                        Two</label>
                    <input type="text" id="team_two" v-model="form.team_two" placeholder="e.g., Houston Astros"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Team Two Logo -->
                <div>
                    <label for="team_two_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team Two Logo</label>
                    <input type="file" id="team_two_logo" @change="handleFileUpload('team_two_logo', $event)"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                </div>
                <!-- Wager Amount -->
                <div>
                    <label for="wager_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wager
                        Amount</label>
                    <input type="number" id="wager_amount" v-model="form.wager_amount"
                        placeholder="Enter amount without $"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Betting Date -->
                <div>
                    <label for="betting_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Betting
                        Date</label>
                    <input type="date" id="betting_date" v-model="form.betting_date"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Tips -->
                <div>
                    <label for="tips" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tips</label>
                    <input type="text" id="tips" v-model="form.tips" placeholder="e.g., Astros to win"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Wager Odds -->
                <div>
                    <label for="wager_odds" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Wager
                        Odds</label>
                    <input type="text" id="wager_odds" v-model="form.wager_odds" placeholder="e.g., -110"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Status -->
                <div>
                    <label for="status"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <select id="status" v-model="form.status"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Pending">Pending</option>
                        <option value="Won">Win</option>
                        <option value="Lost">Loss</option>
                        <option value="Push">Push</option>
                    </select>
                </div>
                <!-- Membership -->
                <div>
                    <label for="membership"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Membership</label>
                    <select id="membership" v-model="form.membership"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Bronze">Bronze</option>
                        <option value="Silver">Silver</option>
                        <option value="Gold">Gold</option>
                        <option value="Platinum">Platinum</option>
                    </select>
                </div>
                <!-- Referrer -->
                <div>
                    <label for="referrer"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Referrer (optional)</label>
                    <input type="text" id="referrer" v-model="form.referrer" placeholder="Enter referrer name or code"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Place Fraction -->
                <div>
                    <label for="place_fraction" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Place Fraction (optional)</label>
                    <input
                        type="number"
                        id="place_fraction"
                        v-model="form.place_fraction"
                        step="0.01"
                        min="0"
                        max="1"
                        placeholder="e.g., 0.25 for 1/4 odds"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    />
                </div>
            </div>
            <!-- Submit Button -->
            <div class="mt-6">
                <button type="submit"
                    class="w-full px-4 py-2 text-white bg-indigo-600 rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    Upload Manual Bet
                </button>
            </div>
        </form>
    </div>
</template>
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
const form = useForm({
    sports: '',
    league: '',
    matches: '',
    markets: '',
    team_one: '',
    team_two: '',
    team_one_logo: null,
    team_two_logo: null,
    wager_amount: '',
    roi: '',
    betting_date: '',
    tips: '',
    wager_odds: '',
    status: 'Pending',
    membership: '',
    referrer: '', // Added referrer field
    place_fraction: '',
});
const handleFileUpload = (field, event) => {
    form[field] = event.target.files[0];
};
const submitForm = () => {
    form.post('/api/bets', {
        onSuccess: () => {
            alert('Bet uploaded successfully!');
        },
        onError: (errors) => {
            console.error('Error uploading bet:', errors);
            alert('Failed to upload bet. Please try again.');
        },
    });
};
</script>
<style scoped>
/* Add any additional styles if needed */
</style>