<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', icon: 'bi-sun', label: 'Light' },
    { value: 'dark', icon: 'bi-moon', label: 'Dark' },
    { value: 'system', icon: 'bi-display', label: 'System' },
] as const;
</script>

<template>
    <div :class="['btn-group', containerClass]" role="group" aria-label="Appearance settings">
        <button
            v-for="{ value, icon, label } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="['btn', appearance === value ? 'btn-primary' : 'btn-outline-secondary']"
        >
            <i :class="[icon, 'me-2']"></i>
            {{ label }}
        </button>
    </div>
</template>
