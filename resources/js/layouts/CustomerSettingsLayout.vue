<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ApplicationLogo from '@/components/AppLogo.vue';

const page = usePage();
const user = page.props.auth?.user?.data;
const sidebarOpen = ref(false);

function logout() {
    router.post(route('logout'));
}

// Settings navigation items
const settingsNav = [
    { name: 'Profile', href: '/settings/profile', icon: 'bi-person' },
    { name: 'Billing', href: '/settings/billing', icon: 'bi-credit-card' },
    { name: 'Notifications', href: '/settings/notifications', icon: 'bi-bell' },
    { name: 'Security', href: '/settings/password', icon: 'bi-shield-lock' },
];

function isActive(href: string): boolean {
    return route().current() === href.replace('/settings/', 'settings.');
}
</script>

<template>
    <div class="d-flex min-vh-100">
        <!-- Mobile Sidebar Overlay -->
        <div 
            v-show="sidebarOpen" 
            class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-75 d-lg-none" 
            style="z-index: 1040;"
            @click="sidebarOpen = false"
        ></div>

        <!-- Sidebar -->
        <nav 
            class="settings-sidebar position-fixed h-100 overflow-auto bg-white border-end" 
            :class="{ 'show': sidebarOpen }"
            style="width: 260px; z-index: 1050;"
        >
            <div class="p-3 border-bottom">
                <Link href="/" class="d-flex align-items-center text-decoration-none">
                    <ApplicationLogo style="height: 36px; width: 36px;" class="me-2" />
                    <span class="fs-5 fw-semibold text-dark">WeWinGames</span>
                </Link>
            </div>

            <div class="p-3">
                <div class="mb-4">
                    <Link href="/todays-tips" class="btn btn-sm btn-outline-primary w-100">
                        <i class="bi bi-arrow-left me-2"></i>Back to Site
                    </Link>
                </div>

                <h6 class="text-muted text-uppercase small mb-3">Account Settings</h6>
                <ul class="nav flex-column">
                    <li v-for="item in settingsNav" :key="item.name" class="nav-item mb-1">
                        <Link
                            :href="item.href"
                            :class="[
                                'nav-link d-flex align-items-center rounded px-3 py-2',
                                isActive(item.href) ? 'active bg-primary text-white' : 'text-dark'
                            ]"
                            @click="sidebarOpen = false"
                        >
                            <i :class="[item.icon, 'me-3']"></i>
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>

                <hr class="my-4">

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <Link href="/support/tickets" class="nav-link text-dark d-flex align-items-center rounded px-3 py-2">
                            <i class="bi bi-headset me-3"></i>
                            Support Center
                        </Link>
                    </li>
                    <li class="nav-item">
                        <button
                            @click="logout"
                            class="nav-link text-danger d-flex align-items-center rounded px-3 py-2 w-100 border-0 bg-transparent text-start"
                        >
                            <i class="bi bi-box-arrow-right me-3"></i>
                            Sign out
                        </button>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1" style="margin-left: 260px;">
            <!-- Top Bar -->
            <nav class="navbar navbar-expand navbar-light bg-white border-bottom sticky-top">
                <div class="container-fluid">
                    <!-- Mobile menu toggle -->
                    <button 
                        class="btn btn-link text-dark d-lg-none p-0 me-3"
                        @click="sidebarOpen = true"
                    >
                        <i class="bi bi-list fs-4"></i>
                    </button>

                    <h5 class="mb-0 text-dark">Account Settings</h5>

                    <!-- User info -->
                    <div class="ms-auto d-flex align-items-center">
                        <img
                            class="rounded-circle me-2"
                            :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(user?.name || '')}&color=7F9CF5&background=EBF4FF`"
                            alt="User avatar"
                            style="width: 36px; height: 36px;"
                        />
                        <div class="d-none d-md-block">
                            <div class="fw-medium text-dark">{{ user?.name }}</div>
                            <div class="small text-muted">{{ user?.email }}</div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page content -->
            <main class="bg-light min-vh-100">
                <div class="container-fluid py-4">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Sidebar styles */
.settings-sidebar {
    transition: transform 0.3s ease-in-out;
}

.settings-sidebar .nav-link {
    transition: all 0.2s ease;
}

.settings-sidebar .nav-link:hover:not(.active) {
    background-color: #f8f9fa;
}

.settings-sidebar .nav-link.active {
    font-weight: 500;
}

/* Mobile sidebar styles */
@media (max-width: 991px) {
    .settings-sidebar {
        transform: translateX(-100%);
    }
    
    .settings-sidebar.show {
        transform: translateX(0);
    }
    
    .flex-grow-1 {
        margin-left: 0 !important;
    }
}

/* Main content area */
main {
    background-color: #f8fafc;
}
</style>