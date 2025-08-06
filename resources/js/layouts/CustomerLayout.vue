<script setup lang="ts">
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const isImpersonating = page.props.impersonation?.isImpersonating || false;

// Simply use WelcomeLayout and pass through the slot
</script>

<template>
    <WelcomeLayout>
        <!-- Impersonation Banner -->
        <ImpersonationBanner v-if="isImpersonating" />

        <!-- Main Content -->
        <slot />
    </WelcomeLayout>
</template>
