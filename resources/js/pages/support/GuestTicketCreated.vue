<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    ticketNumber: string;
    email: string;
}>();
</script>

<template>
    <WelcomeLayout>
        <Head title="Ticket Submitted" />

        <div class="min-vh-100" style="background-color: #0a0e1a">
            <div class="container py-5">
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card" style="background-color: #1a2332; border: 1px solid #2e4057">
                            <div class="card-body p-5 text-center">
                                <div class="mb-4">
                                    <div
                                        class="rounded-circle bg-success d-inline-flex align-items-center justify-content-center"
                                        style="width: 80px; height: 80px"
                                    >
                                        <i class="bi bi-check-lg text-white fs-1"></i>
                                    </div>
                                </div>

                                <h2 class="h3 fw-bold text-white mb-3">Ticket Submitted Successfully!</h2>

                                <p class="text-gray-light mb-4">
                                    Thank you for contacting us. Your support ticket has been created and our team will respond within 24-48 hours.
                                </p>

                                <div class="p-4 rounded mb-4" style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid #ffc107">
                                    <h5 class="text-warning mb-2">Your Ticket Number</h5>
                                    <p class="fs-4 fw-bold text-white mb-0">{{ ticketNumber }}</p>
                                </div>

                                <p class="text-gray-light mb-4">
                                    We've sent a confirmation email to <strong class="text-white">{{ email }}</strong> with your ticket details.
                                </p>

                                <div class="alert alert-info" style="background-color: rgba(13, 202, 240, 0.1); border-color: #0dcaf0">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Tip:</strong> Create an account to track your tickets and get faster support in the future.
                                </div>

                                <div class="d-grid gap-2">
                                    <a href="/register" class="btn btn-warning btn-lg text-dark fw-bold"> Create Account </a>
                                    <a href="/" class="btn btn-outline-light btn-lg"> Return to Home </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>
