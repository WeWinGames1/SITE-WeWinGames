<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';

interface Page {
    id: number;
    title: string;
    content: string;
    meta_title?: string;
    meta_description?: string;
    meta_keywords?: string;
}

interface Props {
    page: Page;
}

defineProps<Props>();
</script>

<template>
    <WelcomeLayout>
        <Head :title="page.meta_title || page.title">
            <meta v-if="page.meta_description" name="description" :content="page.meta_description" />
            <meta v-if="page.meta_keywords" name="keywords" :content="page.meta_keywords" />
        </Head>

        <div class="py-5" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%); min-height: 100vh">
            <article class="container-fluid px-4 px-lg-5" style="max-width: 1200px">
                <!-- Article Header -->
                <header class="mb-5">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        {{ page.title }}
                    </h1>
                </header>

                <!-- Article Content -->
                <div class="article-content mb-5" v-html="page.content"></div>
            </article>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.article-content {
    color: #e5e7eb;
    font-size: 1.125rem;
    line-height: 1.75;
}

.article-content :deep(h1),
.article-content :deep(h2),
.article-content :deep(h3),
.article-content :deep(h4),
.article-content :deep(h5),
.article-content :deep(h6) {
    color: #ffc107;
    font-weight: bold;
    margin-top: 2rem;
    margin-bottom: 1.5rem;
}

.article-content :deep(h2) {
    font-size: 2rem;
}

.article-content :deep(h3) {
    font-size: 1.5rem;
}

.article-content :deep(p) {
    margin-bottom: 1.5rem;
}

.article-content :deep(a) {
    color: #ffc107;
    text-decoration: none;
}

.article-content :deep(a:hover) {
    text-decoration: underline;
}

.article-content :deep(ul),
.article-content :deep(ol) {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.article-content :deep(li) {
    margin-bottom: 0.5rem;
}

.article-content :deep(strong) {
    color: #ffc107;
}
</style>
