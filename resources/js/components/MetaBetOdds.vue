<script setup lang="ts">
import { useMetaBet } from '@/composables/useMetaBet';
import { computed } from 'vue';

interface Props {
    queryId: string | null | undefined;
    size?: '320x50' | '336x280' | '728x90' | '300x250';
    style?: 'classic' | 'modern';
    showLabel?: boolean;
    className?: string;
    type?: 'game' | 'prop';
}

const props = withDefaults(defineProps<Props>(), {
    size: '320x50',
    style: 'modern',
    showLabel: true,
    className: '',
    type: 'game',
});

// Initialize MetaBet widgets
useMetaBet();

// Construct the MetaBet CSS classes based on props
const metabetClasses = computed(() => {
    if (!props.queryId) return '';

    // Use correct MetaBet class names per documentation
    const baseClass = props.type === 'prop' ? 'metabet-sideoddstile' : 'metabet-gametile';

    const classes = [baseClass, `metabet-query-${props.queryId}`];

    // Only add size for game tiles
    if (props.type === 'game') {
        classes.push(`metabet-size-${props.size}`);
    }

    // Add style modifier if not default
    if (props.style !== 'classic') {
        classes.push(`metabet-style-${props.style}`);
    }

    return classes.join(' ');
});

// Show the component only if queryId is provided
const shouldShow = computed(() => !!props.queryId);
</script>

<template>
    <div v-if="shouldShow" class="metabet-odds-wrapper" :class="className">
        <div v-if="showLabel" class="text-muted small mb-1">Live Odds:</div>
        <div :class="metabetClasses"></div>
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
