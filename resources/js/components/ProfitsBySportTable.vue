<template>
    <div class="overflow-x-auto">
        <table class="min-w-full bg-gray-900 rounded-lg border border-gray-700">
            <thead>
                <tr>
                    <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">Sport</th>
                    <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">Profit</th>
                    <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">ROI</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in data" :key="row.sport" class="border-b border-gray-700 last:border-b-0">
                    <td class="px-4 py-2 border-r border-gray-700">{{ row.sport }}</td>
                    <td class="px-4 py-2 border-r border-gray-700">${{ formatMoney(row.profit) }}</td>
                    <td class="px-4 py-2">{{ Math.round(row.roi) }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup lang="ts">
defineProps<{
    data: Array<{ sport: string; profit: number; roi: number }>;
}>();

function formatMoney(val: number | undefined) {
    return Math.round(val ?? 0).toLocaleString();
}
</script>
