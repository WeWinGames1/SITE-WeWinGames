<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    queryId: string | null | undefined;
    market?: 'moneyLine' | 'spread' | 'total' | 'futures';
    team?: 'home' | 'away';
    style?: 'classic' | 'modern' | 'decimal';
    showLabel?: boolean;
    className?: string;
}

const props = withDefaults(defineProps<Props>(), {
    market: 'moneyLine',
    team: 'home',
    style: 'modern',
    showLabel: true,
    className: '',
});

// Construct the MetaBet CSS classes based on props
const metabetClasses = computed(() => {
    if (!props.queryId) return '';

    const classes = [
        'metabet-odds',
        `metabet-market-${props.market}-${props.team}`,
        `metabet-query-${props.queryId}`,
        `metabet-style-${props.style}`,
    ];

    return classes.join(' ');
});

// Show the component only if queryId is provided
const shouldShow = computed(() => !!props.queryId);
</script>

<template>
    <div v-if="shouldShow" class="metabet-odds-wrapper" :class="className">
        <div class="d-flex align-items-center gap-2">
            <span v-if="showLabel" class="text-muted small">Live Odds:</span>
            <span :class="metabetClasses" class="fw-bold"> Loading... </span>
        </div>
    </div>
</template>

<style scoped>
.metabet-odds-wrapper {
    display: inline-flex;
    align-items: center;
}

.metabet-odds {
    min-width: 60px;
    text-align: center;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    background-color: rgba(13, 110, 253, 0.1);
    color: var(--bs-primary);
    transition: all 0.3s ease;
}

.metabet-odds:not(:empty) {
    background-color: rgba(25, 135, 84, 0.1);
    color: var(--bs-success);
}

/* Dark theme support */
:global(.dark) .metabet-odds {
    background-color: rgba(13, 110, 253, 0.2);
}

:global(.dark) .metabet-odds:not(:empty) {
    background-color: rgba(25, 135, 84, 0.2);
}
</style>
