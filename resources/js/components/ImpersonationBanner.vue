<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isImpersonating = computed(() => page.props.impersonation?.isImpersonating || false);
const stopImpersonateUrl = computed(() => route('admin.impersonate.stop'));
</script>

<template>
    <div v-if="isImpersonating" class="bg-warning text-dark">
        <div class="container-fluid py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span class="fw-medium"> You are currently impersonating {{ page.props.auth.user.data.name }} </span>
                </div>
                <Link :href="stopImpersonateUrl" class="btn btn-sm btn-dark">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Stop Impersonating
                </Link>
            </div>
        </div>
    </div>
</template>
