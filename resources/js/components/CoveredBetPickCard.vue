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

        <!-- Covered Tip -->
        <div class="w-full flex flex-col items-center mb-2">
            <Link
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded transition"
                :href="route('buy-our-picks')"
            >
                Unlock Picks
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    bet: {
        type: Object,
        required: true,
    },
});
</script>