<script setup lang="ts">
import PricingCards from '@/components/PricingCards.vue';
import SimpleBetCard from '@/components/SimpleBetCard.vue';
import TestimonialsCarousel from '@/components/TestimonialsCarousel.vue';
import { useHomePlans } from '@/composables/useHomePlans';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

interface Segment {
    type: 'html' | 'widget';
    value?: string;
    name?: string;
}

const props = defineProps<{
    title: string;
    segments: Segment[];
    testimonials?: any[];
    freeBets?: any[];
}>();

const { plans } = useHomePlans();

const elfsightPattern = /\{elfsight\s+([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\}/gi;

// Pre-process HTML segments: convert {elfsight UUID} shortcodes to widget divs.
const renderedSegments = computed(() =>
    props.segments.map((segment) => {
        if (segment.type !== 'html') return segment;
        return {
            ...segment,
            value: (segment.value || '').replace(elfsightPattern, (_m, uuid) => `<div class="elfsight-app-${uuid}" data-elfsight-app-lazy></div>`),
        };
    }),
);

onMounted(() => {
    const hasElfsight = props.segments.some((s) => s.type === 'html' && /\{elfsight\s+[a-f0-9-]+\}/i.test(s.value || ''));
    if (!hasElfsight) return;
    if (document.querySelector('script[src*="elfsightcdn.com/platform.js"], script[src*="static.elfsight.com/platform/platform.js"]')) return;

    const script = document.createElement('script');
    script.src = 'https://static.elfsight.com/platform/platform.js';
    script.async = true;
    document.head.appendChild(script);
});
</script>

<template>
    <WelcomeLayout>
        <Head :title="props.title" />

        <template v-for="(segment, index) in renderedSegments" :key="index">
            <!-- Editable HTML fragment -->
            <div v-if="segment.type === 'html'" v-html="segment.value"></div>

            <!-- Live widgets -->
            <PricingCards v-else-if="segment.name === 'pricing'" :plans="plans" default-period="daily" />

            <TestimonialsCarousel v-else-if="segment.name === 'testimonials'" :testimonials="props.testimonials" />

            <section v-else-if="segment.name === 'todays-bets'" class="py-5">
                <div class="container">
                    <div class="row g-4">
                        <div v-for="bet in props.freeBets || []" :key="bet.id" class="col-md-6 col-lg-4">
                            <SimpleBetCard :bet="bet" />
                        </div>
                        <div v-if="!(props.freeBets && props.freeBets.length)" class="col-12 text-center text-muted">
                            No picks available right now — check back soon.
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </WelcomeLayout>
</template>
