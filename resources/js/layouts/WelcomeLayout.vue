<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';

const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const navLinks = [
    { title: 'Home', href: '/', },
    { title: "Today's Picks", href: '/todays-tips' },
    { title: 'Results', href: '/betting-results' },
    { title: 'Pricing', href: '/#pricing' }
   
];

const dropdownLinks = [
    { title: 'Betting Education', href: '/betting-education' },
    
    { title: 'Careers', href: '/careers-jobs' },
    { title: 'About Us', href: '/about-us' },
    //{ title: 'Odds', href: '/odds' },
    //{ title: 'Futures', href: '/futures' }
];
const socialLinks = [
    { icon: 'mdi:twitter', url: 'https://twitter.com/wewingames' },
    { icon: 'mdi:instagram', url: 'https://instagram.com/wewingames' },
    { icon: 'mdi:facebook', url: 'https://facebook.com/wewingames' },
];

const page = usePage();
const auth = computed(() => page.props.auth || null);
// console.log(auth)
</script>

<template>
    <div>
        <!-- Impersonation Banner -->
        <ImpersonationBanner />
        
        <!-- Header -->
        <header class="navbar navbar-expand-lg navbar-dark" style="background-color: rgba(17, 24, 39, 0.95); backdrop-filter: blur(20px); border-bottom: 1px solid rgba(55, 65, 81, 0.5);">
            <div class="container">
                <!-- Logo and Brand -->
                <Link href="/" class="navbar-brand d-flex align-items-center">
                    <AppLogo style="height: 40px; width: 40px;" class="me-2" />
                    <span class="fw-bold fs-4 text-white">WeWinGames</span>
                </Link>
                
                <!-- Mobile Menu Button -->
                <button 
                    class="navbar-toggler" 
                    type="button" 
                    @click="toggleMobileMenu"
                    aria-controls="navbarNav" 
                    aria-expanded="false" 
                    aria-label="Toggle navigation"
                >
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Desktop and Mobile Nav -->
                <div class="collapse navbar-collapse" :class="{ 'show': mobileMenuOpen }" id="navbarNav">
                    <ul class="navbar-nav ms-auto align-items-center">
                        <!-- Main Nav Links -->
                        <li class="nav-item" v-for="link in navLinks" :key="link.title">
                            <Link 
                                :href="link.href"
                                class="nav-link text-light"
                                @click="mobileMenuOpen = false"
                            >
                                {{ link.title }}
                            </Link>
                        </li>
                        
                        <!-- More Dropdown -->
                        <li class="nav-item dropdown">
                            <a 
                                class="nav-link dropdown-toggle text-light" 
                                href="#" 
                                role="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                            >
                                More
                            </a>
                            <ul class="dropdown-menu dropdown-menu-dark">
                                <li v-for="link in dropdownLinks" :key="link.title">
                                    <Link 
                                        :href="link.href"
                                        class="dropdown-item"
                                        @click="mobileMenuOpen = false"
                                    >
                                        {{ link.title }}
                                    </Link>
                                </li>
                            </ul>
                        </li>
                        
                        <!-- Auth Buttons -->
                        <li class="nav-item ms-lg-3" v-if="!auth.user">
                            <Link href="/login" class="nav-link text-light fw-medium">Login</Link>
                        </li>
                        <li class="nav-item ms-lg-2" v-if="!auth.user">
                            <Link href="/register" class="btn btn-primary fw-semibold px-4 py-2">Get Started</Link>
                        </li>
                        <!-- Account Dropdown for Authenticated Users -->
                        <li class="nav-item dropdown ms-lg-3" v-else>
                            <a 
                                class="nav-link dropdown-toggle d-flex align-items-center text-light" 
                                href="#" 
                                role="button" 
                                data-bs-toggle="dropdown" 
                                aria-expanded="false"
                            >
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle bg-purple d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-fill text-white"></i>
                                    </div>
                                    <span class="fw-medium">Account</span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--bs-gray-dark); border: 1px solid var(--bs-gray-medium); min-width: 220px;">
                                <li class="px-3 py-2 border-bottom" style="border-color: var(--bs-gray-medium) !important;">
                                    <div class="text-white fw-semibold">{{ auth.user.data.name }}</div>
                                    <div class="text-gray-light small">{{ auth.user.data.email }}</div>
                                </li>
                                <li><Link href="/todays-tips" class="dropdown-item text-light"><i class="bi bi-lightbulb me-2 text-purple"></i>Today's Tips</Link></li>
                                <li><Link href="/settings/profile" class="dropdown-item text-light"><i class="bi bi-gear me-2 text-purple"></i>Settings</Link></li>
                                <li><Link href="/settings/billing" class="dropdown-item text-light"><i class="bi bi-credit-card me-2 text-purple"></i>Billing</Link></li>
                                <li><Link href="/support" class="dropdown-item text-light"><i class="bi bi-headset me-2 text-purple"></i>Support</Link></li>
                                <li v-if="auth.user.data.is_admin">
                                    <hr class="dropdown-divider" style="border-color: var(--bs-gray-medium) !important;">
                                </li>
                                <li v-if="auth.user.data.is_admin">
                                    <Link href="/admin" class="dropdown-item text-light"><i class="bi bi-speedometer2 me-2 text-purple"></i>Admin Dashboard</Link>
                                </li>
                                <li><hr class="dropdown-divider" style="border-color: var(--bs-gray-medium) !important;"></li>
                                <li>
                                    <Link href="/logout" method="post" as="button" class="dropdown-item text-light">
                                        <i class="bi bi-box-arrow-right me-2 text-danger"></i>Logout
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <slot />
        </main>

        <!-- Disclaimer -->
        <div class="container mt-5">
            <div class="rounded p-4 text-center" style="background-color: var(--bs-gray-dark); border: 1px solid var(--bs-gray-medium);">
                <p class="text-white small mb-2">
                    DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
                </p>
                <p class="text-white small mb-3">
                    The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor.
                </p>
                <div class="d-flex flex-wrap justify-content-center align-items-center gap-4">
                    <img src="/images/rg.jpeg" alt="Responsible Gaming" style="height: 48px; width: auto;" />
                    <img src="/images/ncpg.png" alt="National Council on Problem Gambling" style="height: 48px; width: auto;" />
                    <img src="/images/21plus.png" alt="21+ Only" style="height: 48px; width: auto;" />
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="py-5" style="background-color: var(--bs-gray-dark); border-top: 1px solid var(--bs-gray-medium);">
            <div class="container">
                <div class="row mb-5">
                    <!-- Company Info -->
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <div class="d-flex align-items-center mb-3">
                            <AppLogo style="height: 40px; width: 40px;" class="me-2" />
                            <h5 class="mb-0 text-white fw-bold">WeWinGames</h5>
                        </div>
                        <p class="text-gray-light mb-4">The most transparent sports betting platform with consistent profits and expert picks.</p>
                        <div class="d-flex gap-3">
                            <a
                                v-for="social in socialLinks"
                                :key="social.icon"
                                :href="social.url"
                                target="_blank"
                                rel="noopener"
                                class="btn btn-sm btn-outline-secondary rounded-circle p-2"
                                style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;"
                            >
                                <i v-if="social.icon === 'mdi:twitter'" class="bi bi-twitter"></i>
                                <i v-else-if="social.icon === 'mdi:instagram'" class="bi bi-instagram"></i>
                                <i v-else-if="social.icon === 'mdi:facebook'" class="bi bi-facebook"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Quick Links -->
                    <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                        <h6 class="text-white fw-semibold mb-3">Product</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <Link href="/todays-tips" class="text-gray-light text-decoration-none footer-link">Today's Picks</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/betting-results" class="text-gray-light text-decoration-none footer-link">Results</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/#pricing" class="text-gray-light text-decoration-none footer-link">Pricing</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/blog" class="text-gray-light text-decoration-none footer-link">Blog</Link>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Resources -->
                    <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                        <h6 class="text-white fw-semibold mb-3">Resources</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <Link href="/betting-education" class="text-gray-light text-decoration-none footer-link">Education</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/about-us" class="text-gray-light text-decoration-none footer-link">About Us</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/careers-jobs" class="text-gray-light text-decoration-none footer-link">Careers</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/support" class="text-gray-light text-decoration-none footer-link">Support</Link>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Legal -->
                    <div class="col-lg-2 col-md-4 mb-4 mb-lg-0">
                        <h6 class="text-white fw-semibold mb-3">Legal</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <Link href="/terms" class="text-gray-light text-decoration-none footer-link">Terms of Service</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/privacy" class="text-gray-light text-decoration-none footer-link">Privacy Policy</Link>
                            </li>
                            <li class="mb-2">
                                <Link href="/responsible-gaming" class="text-gray-light text-decoration-none footer-link">Responsible Gaming</Link>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Newsletter -->
                    <div class="col-lg-2">
                        <h6 class="text-white fw-semibold mb-3">Stay Updated</h6>
                        <p class="text-gray-light small mb-3">Get the latest picks and updates</p>
                        <Link href="/register" class="btn btn-primary btn-sm w-100">
                            <i class="bi bi-envelope me-2"></i>
                            Subscribe
                        </Link>
                    </div>
                </div>
                
                <!-- Bottom Bar -->
                <div class="pt-4 border-top" style="border-color: var(--bs-gray-medium) !important;">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <p class="text-gray-light small mb-0">
                                &copy; {{ new Date().getFullYear() }} WeWinGames. All rights reserved.
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-gray-light small mb-0">
                                Made with <i class="bi bi-heart-fill text-danger mx-1"></i> by 
                                <a href="https://jcompsolu.com" target="_blank" class="text-purple text-decoration-none">J Computer Solutions LLC</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Custom hover effects */
.nav-link {
    transition: color 0.3s ease;
    font-weight: var(--bs-font-weight-medium);
}

.nav-link:hover {
    color: var(--bs-purple-light) !important;
}

.dropdown-item {
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: rgba(124, 58, 237, 0.1);
    color: white !important;
}

.dropdown-item:hover i {
    color: white !important;
}

/* Mobile menu background */
@media (max-width: 991px) {
    .navbar-collapse {
        background-color: var(--bs-gray-dark);
        padding: 1rem;
        margin-top: 1rem;
        border-radius: var(--bs-border-radius);
        border: 1px solid var(--bs-gray-medium);
    }
}

/* Footer link hover effect */
.footer-link {
    transition: color 0.3s ease;
}

.footer-link:hover {
    color: var(--bs-purple-light) !important;
}
</style>