<script setup lang="ts">
import { useMetaBet } from '@/composables/useMetaBet';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';

// Initialize MetaBet widgets
useMetaBet();
</script>

<template>
    <WelcomeLayout>
        <div class="min-h-screen bg-dark text-white">
            <div class="container py-5">
                <h1 class="display-4 fw-bold mb-4">Futures</h1>
                <p class="text-muted mb-5">Championship odds and futures markets</p>

                <div class="row g-4">
                    <!-- NBA Championship Futures -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">NBA Championship</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-sideoddstile metabet-query-nba/championship"></div>
                            </div>
                        </div>
                    </div>

                    <!-- NFL Super Bowl Futures -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">Super Bowl</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-sideoddstile metabet-query-nfl/championship"></div>
                            </div>
                        </div>
                    </div>

                    <!-- MLB World Series Futures -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">World Series</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-sideoddstile metabet-query-mlb/championship"></div>
                            </div>
                        </div>
                    </div>

                    <!-- NHL Stanley Cup Futures -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">Stanley Cup</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-sideoddstile metabet-query-nhl/championship"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>
