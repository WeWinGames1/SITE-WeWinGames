<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';

const page = usePage();
const user = page.props.auth?.user?.data;
const isImpersonating = page.props.impersonation?.isImpersonating || false;

// Mobile menu state
const mobileMenuOpen = ref(false);

// Initialize Bootstrap tooltips
onMounted(() => {
    // Initialize Bootstrap components if needed
    if (typeof window !== 'undefined' && window.bootstrap) {
        const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        [...tooltipTriggerList].map(tooltipTriggerEl => new window.bootstrap.Tooltip(tooltipTriggerEl));
    }
});

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div>
        <!-- Impersonation Banner -->
        <ImpersonationBanner v-if="isImpersonating" />
        
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <Link href="/" class="navbar-brand fw-bold">
                    WeWinGames
                </Link>
                
                <button 
                    class="navbar-toggler" 
                    type="button" 
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    :aria-expanded="mobileMenuOpen"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" :class="{ show: mobileMenuOpen }">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <Link href="/dashboard" class="nav-link" :class="{ active: route().current('dashboard') }">
                                <i class="bi bi-speedometer2 me-1"></i> Dashboard
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link href="/picks" class="nav-link" :class="{ active: route().current('picks') }">
                                <i class="bi bi-trophy me-1"></i> Picks
                            </Link>
                        </li>
                        <li class="nav-item">
                            <Link href="/pricing" class="nav-link" :class="{ active: route().current('pricing') }">
                                <i class="bi bi-tag me-1"></i> Pricing
                            </Link>
                        </li>
                        <li class="nav-item dropdown">
                            <a 
                                class="nav-link dropdown-toggle d-flex align-items-center" 
                                href="#" 
                                role="button" 
                                data-bs-toggle="dropdown"
                            >
                                <i class="bi bi-person-circle me-2"></i>
                                {{ user?.name || 'Account' }}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <Link href="/settings/profile" class="dropdown-item">
                                        <i class="bi bi-person me-2"></i> Profile
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/settings/billing" class="dropdown-item">
                                        <i class="bi bi-credit-card me-2"></i> Billing
                                    </Link>
                                </li>
                                <li>
                                    <Link href="/support" class="dropdown-item">
                                        <i class="bi bi-headset me-2"></i> Support
                                    </Link>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button @click="logout" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </button>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="py-4">
            <slot />
        </main>
        
        <!-- Footer -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container text-center">
                <p class="mb-0">&copy; {{ new Date().getFullYear() }} WeWinGames. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>