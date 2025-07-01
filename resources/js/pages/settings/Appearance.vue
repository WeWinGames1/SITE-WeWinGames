<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
</script>

<template>
    <CustomerLayout>
        <Head title="Appearance Settings" />

        <div class="container py-4">
            <!-- Settings Navigation -->
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="mb-3">Settings</h2>
                    <nav class="nav nav-pills">
                        <Link :href="route('profile.edit')" class="nav-link">
                            <i class="bi bi-person me-2"></i>Profile
                        </Link>
                        <Link :href="route('billing.edit')" class="nav-link">
                            <i class="bi bi-credit-card me-2"></i>Billing
                        </Link>
                        <Link :href="route('password.edit')" class="nav-link">
                            <i class="bi bi-shield-lock me-2"></i>Security
                        </Link>
                        <Link :href="route('appearance')" class="nav-link active">
                            <i class="bi bi-palette me-2"></i>Appearance
                        </Link>
                    </nav>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Appearance Settings</h5>
                            <p class="text-muted mb-0 small">Update your account's appearance settings</p>
                        </div>
                        <div class="card-body">
                            <AppearanceTabs />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </CustomerLayout>
</template>