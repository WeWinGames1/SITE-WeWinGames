<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/components/AppLogo.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import KnowledgebaseSidebar from '@/components/Admin/KnowledgebaseSidebar.vue';

const page = usePage();
const sidebarOpen = ref(false);
const userMenuOpen = ref(false);
const knowledgebaseSidebarOpen = ref(false);
const isImpersonating = computed(() => page.props.impersonation?.isImpersonating || false);
const isClearingCache = ref(false);

// Simple reactive state for navigation
const activeParent = computed(() => {
    const currentPath = currentUrl.value;
    
    for (const item of navigation) {
        if (item.children) {
            const hasActiveChild = item.children.some(child => {
                const isActive = isActiveRoute(child.href);
                return isActive;
            });
            if (hasActiveChild) {
                return item.name;
            }
        }
    }
    return null;
});

// Function to update collapse state
const updateCollapseState = async () => {
    await nextTick();
    
    // Close all collapse elements first
    const allCollapses = document.querySelectorAll('.admin-sidebar .collapse');
    allCollapses.forEach(collapse => {
        collapse.classList.remove('show');
        // Also update aria-expanded on parent links
        const parentLink = document.querySelector(`[data-bs-target="#${collapse.id}"]`);
        if (parentLink) {
            parentLink.setAttribute('aria-expanded', 'false');
        }
    });
    
    // Open the active parent collapse
    if (activeParent.value) {
        const collapseId = `collapse-${activeParent.value.replace(/\s+/g, '-')}`;
        const collapseElement = document.getElementById(collapseId);
        const parentLink = document.querySelector(`[data-bs-target="#${collapseId}"]`);
        
        if (collapseElement) {
            collapseElement.classList.add('show');
        }
        if (parentLink) {
            parentLink.setAttribute('aria-expanded', 'true');
        }
    }
};

// Initialize navigation state on mount
onMounted(() => {
    updateCollapseState();
});

interface NavItem {
    name: string;
    href: string;
    icon: string;
    badge?: number | string;
    children?: NavItem[];
    disabled?: boolean;
}

const currentUrl = computed(() => page.url);

// Watch for route changes and update collapse state
watch(currentUrl, () => {
    updateCollapseState();
});

const navigation: NavItem[] = [
    {
        name: 'Dashboard',
        href: route('admin.dashboard'),
        icon: 'bi-speedometer2',
    },
    {
        name: 'Betting Management',
        href: '#',
        icon: 'bi-trophy',
        children: [
            { name: 'Bets', href: route('admin.bets.index'), icon: 'bi-bar-chart' },
            { name: 'Import Bets', href: route('admin.bets.import.index'), icon: 'bi-upload' },
            { name: 'Sports', href: route('admin.sports.index'), icon: 'bi-dribbble' },
            { name: 'Leagues', href: route('admin.leagues.index'), icon: 'bi-flag' },
            { name: 'Teams', href: route('admin.teams.index'), icon: 'bi-people-fill' },
            // { name: 'Games', href: '#', icon: 'bi-calendar-event' }, // TODO: Implement
            // { name: 'Operators', href: '#', icon: 'bi-building' }, // TODO: Implement
        ],
    },
    {
        name: 'User Management',
        href: '#',
        icon: 'bi-people',
        children: [
            { name: 'Customers', href: route('admin.customers.index'), icon: 'bi-person' },
            { name: 'Admin Users', href: route('admin.admins.index'), icon: 'bi-shield-check' },
        ],
    },
    {
        name: 'Content Management',
        href: '#',
        icon: 'bi-file-text',
        children: [
            { name: 'Blog Posts', href: route('admin.blog-posts.index'), icon: 'bi-newspaper' },
            { name: 'Media Library', href: route('admin.media-library.index'), icon: 'bi-images' },
            { name: 'Pages', href: route('admin.pages.index'), icon: 'bi-file-earmark-text' },
            { name: 'Landing Pages', href: route('admin.landing-pages.index'), icon: 'bi-window-stack' },
            { name: 'Testimonials', href: route('admin.testimonials.index'), icon: 'bi-chat-quote' },
            { name: 'FAQs', href: route('admin.faqs.index'), icon: 'bi-question-circle' },
            { name: 'Resume Submissions', href: route('admin.resume-submissions.index'), icon: 'bi-file-earmark-person' },
        ],
    },
    {
        name: 'E-commerce',
        href: '#',
        icon: 'bi-cart',
        children: [
            { name: 'Stripe Products', href: route('admin.stripe-products.index'), icon: 'bi-credit-card-2-back' },
            { name: 'Discount Codes', href: route('admin.discounts.index'), icon: 'bi-percent' },
        ],
    },
    {
        name: 'Support System',
        href: '#',
        icon: 'bi-headset',
        children: [
            { name: 'Support Tickets', href: route('admin.support-tickets.index'), icon: 'bi-ticket-detailed' },
            // { name: 'Ticket Categories', href: '#', icon: 'bi-tags' }, // TODO: Implement
        ],
    },
    {
        name: 'Notifications',
        href: '#',
        icon: 'bi-bell',
        children: [
            { name: 'Email Templates', href: route('admin.notifications.email-templates.index'), icon: 'bi-envelope' },
            { name: 'Email Logs', href: route('admin.notifications.email-logs.index'), icon: 'bi-clock-history' },
            // { name: 'Send Notification', href: '#', icon: 'bi-send' }, // TODO: Implement notification sending
        ],
    },
    {
        name: 'Settings',
        href: '#',
        icon: 'bi-gear',
        children: [
            { name: 'Under Construction', href: route('admin.under-construction.index'), icon: 'bi-cone-striped' },
            { name: 'Knowledgebase', href: route('admin.knowledgebase.index'), icon: 'bi-book' },
            // { name: 'General Settings', href: '#', icon: 'bi-sliders' }, // TODO: Implement
        ],
    },
];

function isActiveRoute(href: string): boolean {
    if (href === '#') return false;
    const current = currentUrl.value;
    // Check if the current URL matches the href exactly or starts with the href
    return current === href || current.startsWith(href + '/') || current.startsWith(href + '?');
}

// No longer needed - using reactive computed property instead

async function clearCache() {
    if (isClearingCache.value) return;
    
    isClearingCache.value = true;
    
    try {
        const response = await fetch(route('admin.cache.clear'), {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message (you can use a toast library here)
            alert(data.message || 'Cache cleared successfully!');
        } else {
            alert(data.message || 'Failed to clear cache');
        }
    } catch (error) {
        console.error('Cache clear error:', error);
        alert('Failed to clear cache. Please try again.');
    } finally {
        isClearingCache.value = false;
    }
}

function logout() {
    router.post(route('admin.logout'));
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
            class="admin-sidebar position-fixed h-100 overflow-auto" 
            :class="{ 'show': sidebarOpen }"
            style="width: 280px; z-index: 1050;"
        >
            <div class="p-3 border-bottom border-secondary">
                <div class="d-flex align-items-center">
                    <a href="/" class="d-flex align-items-center text-decoration-none">
                        <img src="/images/logo.png" alt="WeWinGames" style="height: 40px; width: auto;" />
                        <span class="ms-3 fs-5 fw-bold text-white">Admin Portal</span>
                    </a>
                </div>
            </div>

            <div class="p-3">
                <ul class="nav flex-column">
                    <li v-for="item in navigation" :key="item.name" class="nav-item">
                        <template v-if="!item.children">
                            <Link
                                :href="item.href"
                                :class="[
                                    'nav-link d-flex align-items-center',
                                    isActiveRoute(item.href) ? 'active' : ''
                                ]"
                                @click="sidebarOpen = false"
                            >
                                <i :class="[item.icon, 'me-3']"></i>
                                {{ item.name }}
                                <span v-if="item.badge" class="badge bg-secondary ms-auto">
                                    {{ item.badge }}
                                </span>
                            </Link>
                        </template>
                        <template v-else>
                            <div class="nav-item">
                                <a
                                    href="#"
                                    :class="[
                                        'nav-link d-flex align-items-center',
                                        activeParent === item.name ? 'text-white' : ''
                                    ]"
                                    data-bs-toggle="collapse"
                                    :data-bs-target="`#collapse-${item.name.replace(/\s+/g, '-')}`"
                                    :aria-expanded="activeParent === item.name ? 'true' : 'false'"
                                >
                                    <i :class="[item.icon, 'me-3']"></i>
                                    {{ item.name }}
                                    <i class="bi bi-chevron-down ms-auto"></i>
                                </a>
                                <div
                                    :id="`collapse-${item.name.replace(/\s+/g, '-')}`"
                                    :class="['collapse', activeParent === item.name ? 'show' : '']"
                                >
                                    <ul class="nav flex-column ms-4">
                                        <li v-for="child in item.children" :key="child.name" class="nav-item">
                                            <Link
                                                :href="child.href"
                                                :class="[
                                                    'nav-link py-2',
                                                    isActiveRoute(child.href) ? 'active' : ''
                                                ]"
                                                @click="sidebarOpen = false"
                                            >
                                                {{ child.name }}
                                            </Link>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </template>
                    </li>
                </ul>

                <hr class="my-3 border-secondary">

                <ul class="nav flex-column">
                    <li class="nav-item">
                        <button
                            @click="logout"
                            class="nav-link d-flex align-items-center text-danger w-100 border-0 bg-transparent"
                        >
                            <i class="bi bi-box-arrow-right me-3"></i>
                            Sign out
                        </button>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1 admin-main-content" style="margin-left: 280px;">
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


                    <!-- Search -->
                    <form class="d-flex flex-grow-1 me-3" style="max-width: 400px;">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="search"
                                class="form-control border-start-0"
                                placeholder="Search..."
                                aria-label="Search"
                            />
                        </div>
                    </form>

                    <!-- Right side items -->
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Clear Cache Button -->
                        <li class="nav-item me-3">
                            <button 
                                @click="clearCache"
                                class="btn btn-link text-dark position-relative p-0"
                                :disabled="isClearingCache"
                                title="Clear all caches"
                            >
                                <i class="bi fs-5" :class="isClearingCache ? 'bi-arrow-clockwise spin' : 'bi-arrow-clockwise'"></i>
                            </button>
                        </li>
                        <!-- Notifications -->
                        <li class="nav-item me-3">
                            <button class="btn btn-link text-dark position-relative p-0">
                                <i class="bi bi-bell fs-5"></i>
                            </button>
                        </li>

                        <!-- User dropdown -->
                        <li class="nav-item dropdown">
                            <a
                                class="nav-link dropdown-toggle d-flex align-items-center"
                                href="#"
                                role="button"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                            >
                                <img
                                    class="rounded-circle me-2"
                                    :src="`https://ui-avatars.com/api/?name=${encodeURIComponent(page.props.auth.user.name)}&color=7F9CF5&background=EBF4FF`"
                                    alt="User avatar"
                                    style="width: 32px; height: 32px;"
                                />
                                <span class="d-none d-md-inline">{{ page.props.auth.user.name }}</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <Link href="/settings/profile" class="dropdown-item">
                                        <i class="bi bi-person me-2"></i>Your profile
                                    </Link>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <button @click="logout" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sign out
                                    </button>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Page content -->
            <main class="admin-main-content">
                <!-- Impersonation Banner -->
                <ImpersonationBanner v-if="isImpersonating" />
                
                <slot />
            </main>
            
            <!-- Help Button (Fixed bottom right) -->
            <button
                type="button"
                class="btn btn-primary rounded-circle shadow help-button"
                @click="knowledgebaseSidebarOpen = true"
                title="Help & Documentation"
            >
                <i class="bi bi-question-lg"></i>
            </button>
            
            <!-- Knowledgebase Sidebar -->
            <KnowledgebaseSidebar 
                :is-visible="knowledgebaseSidebarOpen"
                @close="knowledgebaseSidebarOpen = false"
            />
        </div>
    </div>
</template>

<style scoped>
/* Spin animation for cache clear button */
@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.spin {
    animation: spin 1s linear infinite;
}

/* Admin Main Content - Light Theme */
.admin-main-content {
    background-color: #f8f9fa;
    min-height: calc(100vh - 56px);
    /* Override CSS variables for light theme */
    --bs-body-color: #212529;
    --bs-body-bg: #ffffff;
    --bs-secondary-color: #6c757d;
    --bs-text-muted: #6c757d;
}

/* Override Bootstrap dark theme for admin area */
.admin-main-content :deep(.card) {
    background-color: #ffffff !important;
    border: 1px solid #dee2e6 !important;
}

.admin-main-content :deep(.card-body) {
    background-color: #ffffff !important;
}

.admin-main-content :deep(.text-white) {
    color: #212529 !important;
}

.admin-main-content :deep(.text-gray-light) {
    color: #6c757d !important;
}

.admin-main-content :deep(.text-muted) {
    color: #6c757d !important;
}

.admin-main-content :deep(.small.text-muted) {
    color: #6c757d !important;
}

.admin-main-content :deep(.bg-dark) {
    background-color: #f8f9fa !important;
}

.admin-main-content :deep(.bg-gray-dark) {
    background-color: #ffffff !important;
}

.admin-main-content :deep(.border-gray-medium) {
    border-color: #dee2e6 !important;
}

/* Make all headings black in admin area */
.admin-main-content :deep(h1),
.admin-main-content :deep(h2),
.admin-main-content :deep(h3),
.admin-main-content :deep(h4),
.admin-main-content :deep(h5),
.admin-main-content :deep(h6),
.admin-main-content :deep(.h1),
.admin-main-content :deep(.h2),
.admin-main-content :deep(.h3),
.admin-main-content :deep(.h4),
.admin-main-content :deep(.h5),
.admin-main-content :deep(.h6) {
    color: #212529 !important;
}

/* Form labels and text should be dark in admin area */
.admin-main-content :deep(.form-label) {
    color: #212529 !important;
}

.admin-main-content :deep(.form-text) {
    color: #6c757d !important;
}

.admin-main-content :deep(.text-dark) {
    color: #212529 !important;
}

/* Ensure form controls have proper styling */
.admin-main-content :deep(.form-control),
.admin-main-content :deep(.form-select),
.admin-main-content :deep(textarea) {
    background-color: #ffffff !important;
    border-color: #ced4da !important;
    color: #212529 !important;
}

.admin-main-content :deep(.form-control:focus),
.admin-main-content :deep(.form-select:focus),
.admin-main-content :deep(textarea:focus) {
    background-color: #ffffff !important;
    border-color: #86b7fe !important;
    color: #212529 !important;
}

.admin-main-content :deep(.form-control::placeholder) {
    color: #6c757d !important;
}

/* Modal styling in admin area */
.admin-main-content :deep(.modal-content) {
    background-color: #ffffff !important;
    color: #212529 !important;
}

.admin-main-content :deep(.modal-header),
.admin-main-content :deep(.modal-body),
.admin-main-content :deep(.modal-footer) {
    background-color: #ffffff !important;
    color: #212529 !important;
}

.admin-main-content :deep(.modal-title) {
    color: #212529 !important;
}

/* Admin Sidebar Styles */
.admin-sidebar {
    background: linear-gradient(180deg, #1a1f2e 0%, #0f1218 100%);
    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
}

.admin-sidebar .nav-link {
    color: #94a3b8;
    padding: 0.75rem 1rem;
    margin-bottom: 0.25rem;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
    position: relative;
}

.admin-sidebar .nav-link:hover {
    color: #e2e8f0;
    background-color: rgba(148, 163, 184, 0.1);
    padding-left: 1.25rem;
}

.admin-sidebar .nav-link.active {
    color: #fff;
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
    font-weight: 500;
}

.admin-sidebar .nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 24px;
    background-color: #fff;
    border-radius: 0 2px 2px 0;
}

/* Nested navigation */
.admin-sidebar .collapse .nav-link {
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    margin-left: 0.5rem;
    margin-bottom: 0.125rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    position: relative;
}

.admin-sidebar .collapse .nav-link:hover {
    background-color: rgba(148, 163, 184, 0.15);
    color: #e2e8f0;
    padding-left: 1.25rem;
    transform: translateX(2px);
}

.admin-sidebar .collapse .nav-link.active {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.3) 0%, rgba(37, 99, 235, 0.3) 100%);
    color: #fff !important;
    font-weight: 500;
    border-left: 2px solid #3b82f6;
    padding-left: 0.875rem;
}

.admin-sidebar .collapse .nav-link.active:hover {
    background: linear-gradient(90deg, rgba(59, 130, 246, 0.4) 0%, rgba(37, 99, 235, 0.4) 100%);
    transform: translateX(1px);
}

.admin-sidebar .collapse .nav-link.active::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 50%;
    transform: translateY(-50%);
    width: 4px;
    height: 16px;
    background-color: #3b82f6;
    border-radius: 0 2px 2px 0;
}

/* Mobile sidebar styles */
@media (max-width: 991px) {
    .admin-sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    
    .admin-sidebar.show {
        transform: translateX(0);
    }
    
    .flex-grow-1 {
        margin-left: 0 !important;
    }
}

/* Top navbar styling */
.navbar {
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* Enhance dropdown appearance */
.navbar .dropdown-menu {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    border: none;
    margin-top: 0.5rem;
}

/* Search input styling */
.input-group-text {
    background-color: transparent;
    border-right: none;
}

.form-control {
    border-left: none;
}

.form-control:focus {
    box-shadow: none;
    border-color: #3b82f6;
}

.form-control:focus + .input-group-text {
    border-color: #3b82f6;
}

/* Collapse animation */
.collapse {
    transition: height 0.3s ease;
}

/* Active parent link */
.nav-link[data-bs-toggle="collapse"][aria-expanded="true"] {
    color: #fff !important;
    background-color: rgba(148, 163, 184, 0.15);
    font-weight: 500;
}

.nav-link[data-bs-toggle="collapse"][aria-expanded="true"]:hover {
    color: #fff !important;
    background-color: rgba(148, 163, 184, 0.2);
}

.nav-link[data-bs-toggle="collapse"][aria-expanded="true"] .bi-chevron-down {
    transform: rotate(180deg);
}

.nav-link[data-bs-toggle="collapse"]:hover {
    color: #e2e8f0;
    background-color: rgba(148, 163, 184, 0.1);
    padding-left: 1.25rem;
}

/* Help Button Styles */
.help-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1030;
    font-size: 1.25rem;
    transition: all 0.3s ease;
}

.help-button:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 20px rgba(0, 123, 255, 0.3);
}

.help-button i {
    font-size: 1.5rem;
}

@media (max-width: 768px) {
    .help-button {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
}

.bi-chevron-down {
    transition: transform 0.3s ease;
    font-size: 0.75rem;
}

/* Disabled links */
.admin-sidebar .nav-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Badge styling */
.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

/* Main content area */
main {
    background-color: #f8fafc;
    min-height: calc(100vh - 56px);
}

/* Admin Main Content - Light Theme */
.admin-main-content {
    background-color: #f8f9fa;
    min-height: calc(100vh - 56px);
    /* Override CSS variables for light theme */
    --bs-body-color: #212529;
    --bs-body-bg: #ffffff;
    --bs-secondary-color: #6c757d;
    --bs-text-muted: #6c757d;
}

/* Override Bootstrap dark theme for admin area */
.admin-main-content :deep(.card) {
    background-color: #ffffff !important;
    border: 1px solid #dee2e6 !important;
}

.admin-main-content :deep(.table) {
    --bs-table-color: #212529 !important;
    --bs-table-bg: #ffffff !important;
    --bs-table-border-color: #dee2e6 !important;
    --bs-table-striped-bg: #f8f9fa !important;
    --bs-table-striped-color: #212529 !important;
    --bs-table-active-bg: #f8f9fa !important;
    --bs-table-active-color: #212529 !important;
    --bs-table-hover-bg: #f8f9fa !important;
    --bs-table-hover-color: #212529 !important;
    color: #212529 !important;
    background-color: #ffffff !important;
}

.admin-main-content :deep(.table-dark) {
    --bs-table-color: #ffffff !important;
    --bs-table-bg: #212529 !important;
    --bs-table-border-color: #373b3e !important;
}

.admin-main-content :deep(.btn-primary) {
    --bs-btn-color: #fff;
    --bs-btn-bg: #0d6efd;
    --bs-btn-border-color: #0d6efd;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #0b5ed7;
    --bs-btn-hover-border-color: #0a58ca;
}

.admin-main-content :deep(.btn-secondary) {
    --bs-btn-color: #fff;
    --bs-btn-bg: #6c757d;
    --bs-btn-border-color: #6c757d;
}

.admin-main-content :deep(.btn-outline-secondary) {
    --bs-btn-color: #6c757d;
    --bs-btn-border-color: #6c757d;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #6c757d;
    --bs-btn-hover-border-color: #6c757d;
}

.admin-main-content :deep(.form-control),
.admin-main-content :deep(.form-select) {
    background-color: #ffffff !important;
    border: 1px solid #ced4da !important;
    color: #212529 !important;
}

.admin-main-content :deep(.form-control:focus),
.admin-main-content :deep(.form-select:focus) {
    background-color: #ffffff !important;
    border-color: #86b7fe !important;
    color: #212529 !important;
}

.admin-main-content :deep(.input-group-text) {
    background-color: #e9ecef !important;
    border: 1px solid #ced4da !important;
    color: #212529 !important;
}

.admin-main-content :deep(.dropdown-menu) {
    background-color: #ffffff !important;
    border: 1px solid rgba(0,0,0,.15) !important;
}

.admin-main-content :deep(.dropdown-item) {
    color: #212529 !important;
}

.admin-main-content :deep(.dropdown-item:hover) {
    background-color: #f8f9fa !important;
    color: #212529 !important;
}

.admin-main-content :deep(.modal-content) {
    background-color: #ffffff !important;
    color: #212529 !important;
}

.admin-main-content :deep(.modal-header) {
    border-bottom: 1px solid #dee2e6 !important;
}

.admin-main-content :deep(.modal-footer) {
    border-top: 1px solid #dee2e6 !important;
}

.admin-main-content :deep(.alert) {
    color: #212529 !important;
}

.admin-main-content :deep(.list-group-item) {
    background-color: #ffffff !important;
    border: 1px solid #dee2e6 !important;
    color: #212529 !important;
}

.admin-main-content :deep(.badge) {
    color: #ffffff !important;
}

.admin-main-content :deep(.text-muted) {
    color: #6c757d !important;
}

.admin-main-content :deep(.text-secondary) {
    color: #495057 !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .admin-sidebar {
        width: 100% !important;
        max-width: 280px;
    }
}
</style>