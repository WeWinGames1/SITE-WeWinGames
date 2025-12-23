<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { useElfsight } from '@/composables/useElfsight';

const props = defineProps<{ page: any }>();

// Process content for Elfsight widget shortcodes
const { processedContent } = useElfsight(props.page.content);
</script>
<template>
    <WelcomeLayout>
        <Head :title="props.page.title" />
        <div class="max-w-3xl mx-auto p-8">
            <h1 class="text-3xl font-bold mb-4">{{ props.page.title }}</h1>
            <img v-if="props.page.featured_image" :src="`${props.page.featured_image}`" class="mb-4 rounded" />
            <div class="prose prose-invert" v-html="processedContent"></div>
        </div>
    </WelcomeLayout>
</template>
