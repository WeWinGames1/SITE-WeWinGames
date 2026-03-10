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
                <h1 class="display-4 fw-bold mb-4">Live Odds</h1>
                <p class="text-muted mb-5">Live betting odds powered by MetaBet</p>

                <div class="row g-4">
                    <!-- NBA Odds -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">NBA</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-gametile metabet-query-nba metabet-size-336x280 metabet-style-modern"></div>
                            </div>
                        </div>
                    </div>

                    <!-- NFL Odds -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">NFL</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-gametile metabet-query-nfl metabet-size-336x280 metabet-style-modern"></div>
                            </div>
                        </div>
                    </div>

                    <!-- MLB Odds -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">MLB</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-gametile metabet-query-mlb metabet-size-336x280 metabet-style-modern"></div>
                            </div>
                        </div>
                    </div>

                    <!-- NHL Odds -->
                    <div class="col-md-6">
                        <div class="card bg-dark border-secondary">
                            <div class="card-header border-secondary">
                                <h5 class="mb-0 text-white">NHL</h5>
                            </div>
                            <div class="card-body">
                                <div class="metabet-gametile metabet-query-nhl metabet-size-336x280 metabet-style-modern"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>
