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
    { title: "Betting Picks", href: '/todays-bets' },
    { title: 'Betting Results', href: '/betting-results' },
    { title: 'Buy Our Picks', href: '/buy-our-picks' }
   
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
        
        <!-- Top Bar -->
        <div class="bg-dark py-2">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <a href="#" class="text-warning text-decoration-none small">
                            <i class="bi bi-headset"></i> Support
                        </a>
                        <div class="text-secondary small">|</div>
                        <div class="social-links d-flex gap-2">
                            <a href="#" class="text-secondary"><i class="bi bi-facebook"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-instagram"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-github"></i></a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" style="width: auto;">
                            <option>English</option>
                            <option>Spanish</option>
                        </select>
                        <select class="form-select form-select-sm bg-dark text-white border-secondary" style="width: auto;">
                            <option>Colorado</option>
                            <option>Nevada</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Header -->
        <header class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);">
            <div class="container">
                <!-- Logo and Brand -->
                <Link href="/" class="navbar-brand d-flex align-items-center">
                    <div class="d-flex align-items-center">
                        <span class="text-warning fw-bold fs-3 me-1">W</span>
                        <span class="text-white fw-bold fs-3">G</span>
                    </div>
                    <div class="ms-2">
                        <div class="text-warning fw-bold" style="font-size: 0.9rem; line-height: 1;">WeWinGames</div>
                        <div class="text-white" style="font-size: 0.6rem; line-height: 1;">THE BEST SPORT BETTING TIPS</div>
                    </div>
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
                            <Link href="/login" class="btn btn-outline-warning text-warning fw-medium px-4">Login</Link>
                        </li>
                        <li class="nav-item ms-lg-2" v-if="!auth.user">
                            <Link href="/register" class="btn btn-warning text-dark fw-bold px-4">Sign Up</Link>
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
                                    <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                        <i class="bi bi-person-fill text-dark"></i>
                                    </div>
                                    <span class="fw-medium">Account</span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" style="background-color: var(--bs-gray-dark); border: 1px solid var(--bs-gray-medium); min-width: 220px;">
                                <li class="px-3 py-2 border-bottom" style="border-color: var(--bs-gray-medium) !important;">
                                    <div class="text-white fw-semibold">{{ auth.user.data.name }}</div>
                                    <div class="text-gray-light small">{{ auth.user.data.email }}</div>
                                </li>
                                <li><Link href="/dashboard" class="dropdown-item text-light"><i class="bi bi-speedometer2 me-2 text-warning"></i>Dashboard</Link></li>
                                <li><Link href="/todays-bets" class="dropdown-item text-light"><i class="bi bi-lightbulb me-2 text-warning"></i>Today's Tips</Link></li>
                                <li><Link href="/settings/profile" class="dropdown-item text-light"><i class="bi bi-gear me-2 text-warning"></i>Settings</Link></li>
                                <li><Link href="/settings/billing" class="dropdown-item text-light"><i class="bi bi-credit-card me-2 text-warning"></i>Billing</Link></li>
                                <li><Link href="/support" class="dropdown-item text-light"><i class="bi bi-headset me-2 text-warning"></i>Support</Link></li>
                                <li v-if="auth.user.data.is_admin">
                                    <hr class="dropdown-divider" style="border-color: var(--bs-gray-medium) !important;">
                                </li>
                                <li v-if="auth.user.data.is_admin">
                                    <Link href="/admin" class="dropdown-item text-light"><i class="bi bi-speedometer2 me-2 text-warning"></i>Admin Dashboard</Link>
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
        <footer style="background-color: #0a0e1a;">
            <!-- FAQ Section -->
            <section class="py-5">
                <div class="container">
                    <div class="row align-items-center mb-5">
                        <div class="col-lg-3">
                            <img src="/images/trophy-sports.png" alt="Trophy" class="img-fluid" style="max-height: 250px;" />
                        </div>
                        <div class="col-lg-9">
                            <h2 class="text-warning fw-bold mb-4">Frequently Asked Questions</h2>
                            <div class="accordion accordion-flush" id="faqAccordion">
                                <div class="accordion-item bg-transparent border-bottom border-secondary">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                            What is the difference between silver, gold and platinum bets?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-secondary">
                                            Silver bets are our standard picks, Gold bets offer higher value with better odds, and Platinum bets are our premium selections with the highest potential returns.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item bg-transparent border-bottom border-secondary">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                            Can I rely on the accuracy of the betting tips provided by WeWinGames.com?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-secondary">
                                            Our tips are based on extensive research and analysis. While we maintain a strong track record, remember that sports betting always involves risk.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item bg-transparent border-bottom border-secondary">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                            How much should I place on each bet?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-secondary">
                                            We recommend responsible bankroll management. Never bet more than you can afford to lose, and consider using a fixed percentage of your bankroll per bet.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Footer Links Section -->
            <section class="py-5" style="background-color: #0d1829;">
                <div class="container">
                    <div class="row">
                        <!-- Logo and Info -->
                        <div class="col-lg-4 mb-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="text-warning fw-bold fs-2 me-1">W</span>
                                <span class="text-white fw-bold fs-2">G</span>
                                <div class="ms-2">
                                    <div class="text-warning fw-bold">WeWinGames</div>
                                    <div class="text-white small">THE BEST SPORT BETTING TIPS</div>
                                </div>
                            </div>
                            <p class="text-secondary mb-4">The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor.</p>
                            <div class="d-flex align-items-center p-3 border border-warning rounded">
                                <span class="text-warning fw-bold fs-4 me-3">18+</span>
                                <Link href="/responsible-gaming" class="text-white text-decoration-none">Responsible Gaming</Link>
                            </div>
                        </div>
                        
                        <!-- Navigate To -->
                        <div class="col-lg-2 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">NAVIGATE TO</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/" class="text-secondary text-decoration-none footer-link">Home</Link></li>
                                <li class="mb-2"><Link href="/about-us" class="text-secondary text-decoration-none footer-link">About Us</Link></li>
                                <li class="mb-2"><Link href="/blog" class="text-secondary text-decoration-none footer-link">Sports News</Link></li>
                                <li class="mb-2"><Link href="/partner-offers" class="text-secondary text-decoration-none footer-link">Partners Offers</Link></li>
                            </ul>
                        </div>
                        
                        <!-- Navigate To 2 -->
                        <div class="col-lg-2 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">NAVIGATE TO</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/careers-jobs" class="text-secondary text-decoration-none footer-link">Our Events</Link></li>
                                <li class="mb-2"><Link href="/todays-bets" class="text-secondary text-decoration-none footer-link">Today Tips</Link></li>
                                <li class="mb-2"><Link href="/betting-education" class="text-secondary text-decoration-none footer-link">Betting Education</Link></li>
                                <li class="mb-2"><Link href="/testimonials" class="text-secondary text-decoration-none footer-link">Our Clients</Link></li>
                            </ul>
                        </div>
                        
                        <!-- Support -->
                        <div class="col-lg-4 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">SUPPORT</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/privacy" class="text-secondary text-decoration-none footer-link">Privacy Policy</Link></li>
                                <li class="mb-2"><Link href="/sweepstakes-rules" class="text-secondary text-decoration-none footer-link">Sweepstakes Rules</Link></li>
                                <li class="mb-2"><Link href="/terms" class="text-secondary text-decoration-none footer-link">Terms & Condition</Link></li>
                                <li class="mb-2"><Link href="/team" class="text-secondary text-decoration-none footer-link">Our Team</Link></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Bottom Bar -->
            <section class="py-3 border-top border-secondary">
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-3 mb-2 mb-md-0">
                            <span class="text-secondary">FOLLOW US</span>
                            <div class="social-links d-flex gap-2">
                                <a href="#" class="text-secondary"><i class="bi bi-facebook"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-instagram"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-youtube"></i></a>
                            </div>
                        </div>
                        <div class="text-secondary small">
                            © Copyright {{ new Date().getFullYear() }} We Win Games. All Rights Reserved. Designed by 
                            <a href="https://adsrole.com" target="_blank" class="text-warning text-decoration-none">AdsRole Pvt. Ltd.</a>
                        </div>
                    </div>
                </div>
            </section>
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
    color: #ffc107 !important;
}

.social-links a {
    transition: color 0.3s ease;
}

.social-links a:hover {
    color: #ffc107 !important;
}

.dropdown-item {
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107 !important;
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
    color: #ffc107 !important;
}

/* Accordion Customization */
.accordion-button {
    box-shadow: none !important;
    padding: 1.25rem 0;
}

.accordion-button:not(.collapsed) {
    color: #ffc107;
    background-color: transparent;
}

.accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffc107'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    filter: brightness(0) invert(1);
}

.accordion-button:not(.collapsed)::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ffc107'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    filter: none;
}

/* Top bar styles */
.form-select-sm {
    font-size: 0.875rem;
}

/* Button hover effects */
.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
}

.btn-outline-warning:hover {
    transform: translateY(-2px);
}
</style>