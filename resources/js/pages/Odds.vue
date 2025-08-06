<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
</script>

<template>
    <WelcomeLayout>
        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200">
            <div class="container mx-auto px-4 py-16 max-w-3xl">
                <h1 class="text-4xl font-bold mb-8 text-white">Odds</h1>
                <div class="metabet-odds-board"></div>
            </div>
        </div>
    </WelcomeLayout>
</template>
