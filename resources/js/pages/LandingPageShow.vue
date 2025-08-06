<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps<{ page: any }>();
</script>
<template>
    <WelcomeLayout>
        <Head :title="props.page.title" />
        <div class="max-w-3xl mx-auto p-8">
            <h1 class="text-3xl font-bold mb-4">{{ props.page.title }}</h1>
            <img v-if="props.page.featured_image" :src="`${props.page.featured_image}`" class="mb-4 rounded" />
            <div class="prose prose-invert" v-html="props.page.content"></div>
        </div>
    </WelcomeLayout>
</template>
