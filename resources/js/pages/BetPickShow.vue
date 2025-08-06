<script setup lang="ts">
import BetPickCard from '@/components/BetPickCard.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    bet: Record<string, any>;
}>();
</script>

<template>
    <WelcomeLayout>
        <Head :title="`Bet Pick #${props.bet.id}`" />
        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200 flex justify-center items-center">
            <BetPickCard :bet="props.bet" class="max-w-xl w-full" />
        </div>
    </WelcomeLayout>
</template>
