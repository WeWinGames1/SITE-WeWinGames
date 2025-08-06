<template>
    <table class="w-full text-left text-sm bg-transparent rounded-lg border border-gray-700 mb-2">
        <thead>
            <tr>
                <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Year</th>
                <th class="px-4 py-2 text-gray-300 border-b border-gray-700">Profits</th>
                <th class="px-4 py-2 text-gray-300 border-b border-gray-700">ROI</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="row in data" :key="row.year">
                <td class="px-4 py-2 border-r border-gray-700">{{ row.year }}</td>
                <td class="px-4 py-2 border-r border-gray-700">${{ Math.round(row.profit).toLocaleString() }}</td>
                <td class="px-4 py-2">{{ row.roi ? Math.round(row.roi) + '%' : '-' }}</td>
            </tr>
        </tbody>
    </table>
</template>

<script setup lang="ts">
defineProps<{ data: Array<{ year: string | number; profit: number; roi?: number }> }>();
</script>
