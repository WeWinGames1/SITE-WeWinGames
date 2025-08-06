<template>
    <div class="under-construction">
        <Head title="Under Construction" />

        <div class="container">
            <div class="row justify-content-center min-vh-100 align-items-center">
                <div class="col-12 col-md-8 col-lg-6 text-center">
                    <div class="construction-content">
                        <img src="/images/logo.png" alt="WeWinGames" class="logo mb-5" />

                        <h1 class="display-4 mb-4 text-white">Under Construction</h1>

                        <div class="message" v-html="blurb"></div>

                        <div class="mt-5">
                            <div class="construction-icon">
                                <i class="fas fa-hard-hat fa-3x text-warning mb-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Props {
    blurb: string;
}

defineProps<Props>();
</script>

<style scoped>
.under-construction {
    background: #0f1419;
    background-image:
        radial-gradient(ellipse at top, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
        radial-gradient(ellipse at bottom, rgba(139, 92, 246, 0.1) 0%, transparent 50%);
    min-height: 100vh;
    position: relative;
    overflow: hidden;
}

.under-construction::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(59, 130, 246, 0.03) 10px, rgba(59, 130, 246, 0.03) 20px);
    animation: stripe 20s linear infinite;
}

@keyframes stripe {
    0% {
        transform: translate(0, 0);
    }
    100% {
        transform: translate(50px, 50px);
    }
}

.construction-content {
    position: relative;
    z-index: 1;
    background: rgba(31, 41, 55, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(75, 85, 99, 0.3);
    padding: 3rem;
    border-radius: 15px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
}

.logo {
    max-width: 200px;
    height: auto;
    filter: brightness(1.1);
}

h1 {
    color: #f3f4f6;
    font-weight: 700;
}

.message {
    font-size: 1.2rem;
    color: #d1d5db;
    line-height: 1.6;
}

.message :deep(p) {
    margin-bottom: 1rem;
    color: #d1d5db;
}

.message :deep(p:last-child) {
    margin-bottom: 0;
}

.construction-icon {
    animation: bounce 2s ease-in-out infinite;
}

.construction-icon i {
    color: #fbbf24 !important;
}

@keyframes bounce {
    0%,
    20%,
    50%,
    80%,
    100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-20px);
    }
    60% {
        transform: translateY(-10px);
    }
}

@media (max-width: 768px) {
    .construction-content {
        padding: 2rem;
    }

    .logo {
        max-width: 150px;
    }

    h1 {
        font-size: 2rem;
    }

    .message {
        font-size: 1.1rem;
    }
}
</style>
