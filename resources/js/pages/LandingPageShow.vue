<script setup lang="ts">
import { useElfsight } from '@/composables/useElfsight';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ page: any }>();

// Process content for Elfsight widget shortcodes
const { processedContent } = useElfsight(props.page.content);

// "inertia_raw" renders the content without the site header/footer chrome
// (but still inside the Inertia shell, so site-wide GA/GTM load).
const isRaw = computed(() => props.page.render_mode === 'inertia_raw');
</script>
<template>
    <Head :title="props.page.title" />

    <!-- Raw (no chrome) mode -->
    <div v-if="isRaw" class="landing-raw-page" v-html="processedContent"></div>

    <!-- Normal mode with site header/footer -->
    <WelcomeLayout v-else>
        <div class="min-vh-100" style="background-color: #0a0e1a">
            <!-- Header Section -->
            <section class="py-5" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%)">
                <div class="container">
                    <div class="text-center">
                        <h1 class="display-4 fw-bold text-white">{{ props.page.title }}</h1>
                    </div>
                </div>
            </section>

            <!-- Content Section -->
            <section class="py-5">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057">
                                <div class="card-body p-5">
                                    <img v-if="props.page.featured_image" :src="props.page.featured_image" class="img-fluid rounded mb-4" />
                                    <div class="landing-content text-white" v-html="processedContent"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
/* Landing page content styling */
.landing-content :deep(h2) {
    color: #ffc107;
    font-size: 1.75rem;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.landing-content :deep(h3) {
    color: #fff;
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.landing-content :deep(p) {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.7;
    margin-bottom: 1rem;
}

.landing-content :deep(ul),
.landing-content :deep(ol) {
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 1rem;
    padding-left: 1.5rem;
}

.landing-content :deep(li) {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.landing-content :deep(strong) {
    color: #fff;
    font-weight: 600;
}

.landing-content :deep(a) {
    color: #ffc107;
    text-decoration: none;
}

.landing-content :deep(a:hover) {
    color: #ffca2c;
    text-decoration: underline;
}

.landing-content :deep(.lead) {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 300;
}
</style>
