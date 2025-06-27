<template>
    <section class="py-10 bg-gray-800">
        <div class="container mx-auto px-4">
            <h2 class="text-2xl font-bold text-white mb-6 text-center">Profits by Level</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full bg-gray-900 rounded-lg border border-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">Level</th>
                            <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">Profit</th>
                            <th class="px-4 py-2 text-left text-gray-300 border-b border-gray-700">ROI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in data" :key="row.level" class="border-b border-gray-700 last:border-b-0">
                            <td class="px-4 py-2 border-r border-gray-700">{{ row.level }}</td>
                            <td class="px-4 py-2 border-r border-gray-700">${{ formatMoney(row.profit) }}</td>
                            <td class="px-4 py-2">{{ Math.round(row.roi, 2) }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script setup lang="ts">
const props = defineProps<{
    data: Array<{ level: string, profit: number, roi: number }>
}>();
console.log('ProfitsByLevelTable props:', props.data);
function formatMoney(val: number | undefined) {
    return (Math.round(val ?? 0)).toLocaleString();
}
</script>