<template>
    <div
        class="relative w-full max-w-full p-4 bg-white dark:bg-gray-800 rounded-lg shadow-md flex flex-col items-center group border-4"
        style="border-color: gold;"
    >
        <!-- Date -->
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            <strong>Date:</strong> {{ bet.betting_date }}
        </p>

        <!-- League -->
        <p class="text-sm text-indigo-600 dark:text-indigo-300 font-semibold mb-1">
            <strong>League:</strong> {{ bet.league || 'N/A' }}
        </p>

        <!-- Teams -->
        <div class="flex items-center justify-center mb-4">
            <div class="flex flex-col items-center">
                <img
                    :src="bet.team_one_logo || '/placeholder-team-logo.png'"
                    alt="Team One Logo"
                    class="h-12 w-12 object-contain mb-2"
                />
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ bet.team_one }}</p>
            </div>
            <p class="mx-4 text-lg font-bold text-gray-800 dark:text-gray-200">VS</p>
            <div class="flex flex-col items-center">
                <img
                    :src="bet.team_two_logo || '/placeholder-team-logo.png'"
                    alt="Team Two Logo"
                    class="h-12 w-12 object-contain mb-2"
                />
                <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ bet.team_two }}</p>
            </div>
        </div>

        <!-- Membership Level -->
        <p
            class="px-4 py-1 text-sm font-semibold rounded-full mb-2"
            :class="{
                'bg-red-500 text-white': bet.membership.toUpperCase() === 'BRONZE',
                'bg-yellow-500 text-white': bet.membership.toUpperCase() === 'GOLD',
                'bg-gray-500 text-white': bet.membership.toUpperCase() === 'SILVER',
                'bg-indigo-600 text-white': bet.membership.toUpperCase() === 'PLATINUM',
            }"
        >
            GAME LEVEL: {{ bet.membership.toUpperCase() }}
        </p>

       

        <!-- Tips and Wager Odds -->
        <div class="w-full flex flex-col items-center mb-2">
            <p class="text-sm text-green-600 dark:text-green-400 font-semibold">
                <strong>Tips:</strong> {{ bet.markets || 'N/A' }} {{ bet.tips }} {{ bet.wager_odds }}
            </p>
        </div>

        <!-- Place Fraction -->
        <p v-if="bet.place_fraction" class="text-sm text-blue-600 dark:text-blue-400 font-semibold">
            <strong>Place Fraction:</strong> {{ bet.place_fraction }}
        </p>

        <!-- Admin Actions -->
        <div v-if="isAdmin" class="mt-4 flex flex-col space-y-2 w-full">
            <!-- Edit Form -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                <select
                    id="status"
                    v-model="updatedStatus"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="Pending">Pending</option>
                    <option value="Won">Win</option>
                    <option value="Lost">Loss</option>
                    <option value="Push">Push</option>
                </select>
            </div>

            <!-- Edit Date -->
            <div>
                <label for="betting_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date</label>
                <input
                    type="date"
                    id="betting_date"
                    v-model="updatedDate"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>

            <!-- Edit Team One Logo -->
            <div>
                <label for="team_one_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team One Logo</label>
                <input
                    type="file"
                    id="team_one_logo"
                    @change="handleFileUpload('team_one_logo', $event)"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                />
            </div>

            <!-- Edit Team Two Logo -->
            <div>
                <label for="team_two_logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Team Two Logo</label>
                <input
                    type="file"
                    id="team_two_logo"
                    @change="handleFileUpload('team_two_logo', $event)"
                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                />
            </div>

            <!-- Edit Referrer -->
            <div>
                <label for="referrer" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Referrer (optional)</label>
                <input
                    type="text"
                    id="referrer"
                    v-model="updatedReferrer"
                    placeholder="Enter referrer name or code"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>

            <!-- Edit Place Fraction -->
            <div>
                <label for="place_fraction" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Place Fraction</label>
                <input
                    type="number"
                    id="place_fraction"
                    v-model="updatedPlaceFraction"
                    step="0.01"
                    min="0"
                    max="1"
                    placeholder="e.g., 0.25"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>

            <!-- Update Button -->
            <button
                @click="updateBet"
                class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
                Update Bet
            </button>

            <!-- Delete Button -->
            <button
                @click="deleteBet"
                class="px-4 py-2 bg-red-600 text-white rounded-md shadow hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
            >
                Delete Bet
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, defineEmits } from 'vue';
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
// console.log('Updated Status:', updatedStatus.value);
const updatedDate = ref(props.bet.betting_date || '');
const updatedReferrer = ref(props.bet.referrer || '');
const updatedPlaceFraction = ref(props.bet.place_fraction || '');
const teamOneLogo = ref(null);
const teamTwoLogo = ref(null);

watch(updatedDate, (newValue) => {
    // console.log('Updated Date:', newValue);
});

const handleFileUpload = (field, event) => {
    if (field === 'team_one_logo') {
        teamOneLogo.value = event.target.files[0];
    } else if (field === 'team_two_logo') {
        teamTwoLogo.value = event.target.files[0];
    }
}; 

const updateBet = async () => {
    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('status', updatedStatus.value);
    formData.append('betting_date', updatedDate.value);
    formData.append('referrer', updatedReferrer.value);
    formData.append('place_fraction', updatedPlaceFraction.value);
    if (teamOneLogo.value) {
        formData.append('team_one_logo', teamOneLogo.value);
    }
    if (teamTwoLogo.value) {
        formData.append('team_two_logo', teamTwoLogo.value);
    }
    
    try {
        const response = await axios.post(`/api/bets/${props.bet.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        alert('Bet updated successfully!');
        emit('bet-updated', response.data);
       // location.reload(); // Reload the page to reflect changes
    } catch (error) {
        // console.error('Error updating bet:', error);
        alert('Failed to update bet. Please try again.');
    }
};

const deleteBet = async () => {
    if (confirm('Are you sure you want to delete this bet?')) {
        try {
            await axios.delete(`/api/bets/${props.bet.id}`);
            alert('Bet deleted successfully!');
            //location.reload(); // Reload the page to reflect changes
        } catch (error) {
            // console.error('Error deleting bet:', error);
            alert('Failed to delete bet. Please try again.');
        }
    }
};
</script>

<style scoped>
/* Optional: Enhance gold border appearance */
.group {
    box-shadow: 0 0 0 1px gold, 0 1px 2px rgba(0,0,0,0.08);
}
</style>