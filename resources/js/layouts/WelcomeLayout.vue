<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import ImpersonationBanner from '@/components/ImpersonationBanner.vue';
import { socialMediaLinks } from '@/config/social';

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

const page = usePage();
const auth = computed(() => page.props.auth || null);
// console.log(auth)
</script>

<template>
    <div>
        <!-- Impersonation Banner -->
        <ImpersonationBanner />
        
        <!-- Top Bar -->
        <div class="py-2" style="background-color: #0a1628; border-bottom: 1px solid rgba(255, 255, 255, 0.1);">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-3">
                        <Link href="/support" class="text-white text-decoration-none small d-flex align-items-center">
                            <i class="bi bi-headset me-1"></i> Support
                        </Link>
                        <div class="text-secondary small">|</div>
                        <div class="social-links d-flex gap-2">
                            <a :href="socialMediaLinks.facebook" target="_blank" rel="noopener" class="text-white opacity-75"><i class="bi bi-facebook"></i></a>
                            <a :href="socialMediaLinks.instagram" target="_blank" rel="noopener" class="text-white opacity-75"><i class="bi bi-instagram"></i></a>
                            <a :href="socialMediaLinks.twitter" target="_blank" rel="noopener" class="text-white opacity-75"><i class="bi bi-twitter"></i></a>
                            <a href="#" class="text-white opacity-75"><i class="bi bi-telegram"></i></a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-white small me-2">English</span>
                        <div class="dropdown">
                            <button class="btn btn-sm text-white p-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                        <div class="text-secondary mx-2">|</div>
                        <div class="d-flex align-items-center">
                            <span class="text-white small">🇺🇸 Colorado</span>
                            <button class="btn btn-sm text-white p-0 ms-1" type="button">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </div>
                        <div class="text-secondary mx-2">|</div>
                        <i class="bi bi-person-circle text-white"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Header -->
        <header class="navbar navbar-expand-lg navbar-dark py-3" style="background-color: #0a1628;">
            <div class="container">
                <!-- Logo and Brand -->
                <Link href="/" class="navbar-brand d-flex align-items-center">
                    <img src="/images/logo.png" alt="WeWinGames" style="height: 45px; width: auto;" class="me-2">
                    <div class="d-none d-lg-block ms-2">
                        <div class="text-white fw-medium" style="font-size: 0.85rem; line-height: 1.2; letter-spacing: 0.5px;">We make sports betting easy</div>
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
                            <Link href="/login" class="btn btn-sm btn-outline-light px-3 py-2" style="border-radius: 20px; font-size: 14px;">Login</Link>
                        </li>
                        <li class="nav-item ms-lg-2" v-if="!auth.user">
                            <Link href="/register" class="btn btn-sm btn-warning text-dark fw-bold px-3 py-2" style="border-radius: 20px; font-size: 14px;">Sign Up</Link>
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


        <!-- Footer -->
        <footer style="background-color: #1a1a1a; position: relative; overflow: hidden;">
            <!-- Background Ellipse -->
            <div class="position-absolute bottom-0 end-0" style="width: 600px; height: 600px; pointer-events: none;">
                <img src="/images/footer-bottom-ellipse.png" alt="" class="img-fluid" style="opacity: 0.5;" />
            </div>
            
            <!-- Trophy positioned to align with footer top -->
            <div class="position-absolute" style="left: 5%; bottom: 100%; transform: translateY(100px); z-index: 10;">
                <img src="/images/footer-trophy.png" alt="Trophy" style="height: 250px; width: auto;" />
            </div>
            
            <!-- FAQ Section -->
            <section class="py-5 position-relative">
                <div class="container">
                    <div class="position-relative">
                        <!-- FAQ Icon floating right -->
                        <img src="/images/footer-faq-icon.png" alt="FAQ" class="position-absolute" style="height: 120px; right: 0; top: -20px; z-index: 10;" />
                        
                        <div class="text-center mb-4">
                            <h2 class="text-white fw-bold d-inline">FREQUENTLY ASKED </h2>
                            <h2 class="text-warning fw-bold d-inline">QUESTIONS</h2>
                        </div>
                        <p class="text-center text-secondary mb-5">Hopefully, Any Queries Are Covered Below, If Not, Please Get In Touch.</p>
                            <div class="accordion accordion-flush" id="faqAccordion" style="background-color: transparent;">
                                <div class="accordion-item bg-transparent border-0 mb-3">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button bg-transparent text-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid #333;">
                                            Is there a membership or subscription fee to access the betting tips on WeWinGames.com?
                                        </button>
                                    </h2>
                                    <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-white p-3" style="background-color: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 193, 7, 0.3); border-top: none;">
                                            We give out our basic silver picks free and charge a good value monthly subscription for our premium gold and platinum picks. These range from a total of $100 for gold to $225 for gold & platinum FOR ALL SPORTS. This competes to the US industry-leading sites that charge up to $300-400 a month PER TIPSTER.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item bg-transparent border-0 mb-3">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" style="background-color: transparent; border: 1px solid #333;">
                                            What is the difference between silver, gold and platinum bets?
                                        </button>
                                    </h2>
                                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-white p-3" style="background-color: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 193, 7, 0.3); border-top: none;">
                                            Silver bets are our standard picks, Gold bets offer higher value with better odds, and Platinum bets are our premium selections with the highest potential returns.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item bg-transparent border-0 mb-3">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq3" style="background-color: transparent; border: 1px solid #333;">
                                            Can I rely on the accuracy of the betting tips provided by WeWinGames.com?
                                        </button>
                                    </h2>
                                    <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-white p-3" style="background-color: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 193, 7, 0.3); border-top: none;">
                                            Our tips are based on extensive research and analysis. While we maintain a strong track record, remember that sports betting always involves risk.
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item bg-transparent border-0">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed bg-transparent text-white p-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq4" style="background-color: transparent; border: 1px solid #333;">
                                            How much should I place on each bet?
                                        </button>
                                    </h2>
                                    <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                        <div class="accordion-body text-white p-3" style="background-color: rgba(0, 0, 0, 0.5); border: 1px solid rgba(255, 193, 7, 0.3); border-top: none;">
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
            <section class="py-5 position-relative" style="background-color: #0d1829; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div class="container">
                    <div class="row">
                        <!-- Logo and Info -->
                        <div class="col-lg-4 mb-4">
                            <div class="mb-3">
                                <img src="/images/logo.png" alt="WeWinGames" style="height: 50px; width: auto;" class="mb-2">
                            </div>
                            <p class="text-white mb-4" style="font-size: 14px; line-height: 1.6;">The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor.</p>
                            <div class="d-flex align-items-center p-2 rounded mb-3" style="background-color: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 193, 7, 0.3); width: fit-content;">
                                <span class="text-warning fw-bold me-3" style="font-size: 18px;">21+</span>
                                <span class="text-white" style="font-size: 14px;">Responsible Gaming</span>
                            </div>
                            <p class="text-white small mb-3" style="font-size: 12px; line-height: 1.5;">
                                DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
                            </p>
                            <div class="d-flex align-items-center gap-3">
                                <img src="/images/legal-ncpg.png" alt="National Council on Problem Gambling" style="height: 32px; width: auto;" />
                                <img src="/images/legal-rg.jpg" alt="Responsible Gaming" style="height: 32px; width: auto;" />
                                <img src="/images/legal-21plus.png" alt="21+ Only" style="height: 32px; width: auto;" />
                            </div>
                        </div>
                        
                        <!-- Navigate To -->
                        <div class="col-lg-2 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">NAVIGATE TO</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/" class="text-white text-decoration-none footer-link">Home</Link></li>
                                <li class="mb-2"><Link href="/about-us" class="text-white text-decoration-none footer-link">About Us</Link></li>
                                <li class="mb-2"><Link href="/blog" class="text-white text-decoration-none footer-link">Sports News</Link></li>
                                <li class="mb-2"><Link href="/partner-offers" class="text-white text-decoration-none footer-link">Partners Offers</Link></li>
                            </ul>
                        </div>
                        
                        <!-- Navigate To 2 -->
                        <div class="col-lg-2 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">NAVIGATE TO</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/careers-jobs" class="text-white text-decoration-none footer-link">Our Events</Link></li>
                                <li class="mb-2"><Link href="/todays-bets" class="text-white text-decoration-none footer-link">Today Tips</Link></li>
                                <li class="mb-2"><Link href="/betting-education" class="text-white text-decoration-none footer-link">Betting Education</Link></li>
                                <li class="mb-2"><Link href="/testimonials" class="text-white text-decoration-none footer-link">Our Clients</Link></li>
                            </ul>
                        </div>
                        
                        <!-- Support -->
                        <div class="col-lg-4 col-md-4 mb-4">
                            <h5 class="text-warning mb-3">SUPPORT</h5>
                            <ul class="list-unstyled">
                                <li class="mb-2"><Link href="/privacy" class="text-white text-decoration-none footer-link">Privacy Policy</Link></li>
                                <li class="mb-2"><Link href="/sweepstakes-rules" class="text-white text-decoration-none footer-link">Sweepstakes Rules</Link></li>
                                <li class="mb-2"><Link href="/terms" class="text-white text-decoration-none footer-link">Terms & Condition</Link></li>
                                <li class="mb-2"><Link href="/team" class="text-white text-decoration-none footer-link">Our Team</Link></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </section>
            
            <!-- Bottom Bar -->
            <section class="py-3" style="background-color: #000; border-top: 1px solid rgba(255, 255, 255, 0.1);">
                <div class="container">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-4 mb-2 mb-md-0">
                            <span class="text-white text-uppercase small">FOLLOW US</span>
                            <div class="social-links d-flex gap-3">
                                <a :href="socialMediaLinks.facebook" target="_blank" rel="noopener" class="text-secondary"><i class="bi bi-facebook"></i></a>
                                <a :href="socialMediaLinks.instagram" target="_blank" rel="noopener" class="text-secondary"><i class="bi bi-instagram"></i></a>
                                <a :href="socialMediaLinks.twitter" target="_blank" rel="noopener" class="text-secondary"><i class="bi bi-twitter"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-linkedin"></i></a>
                                <a href="#" class="text-secondary"><i class="bi bi-telegram"></i></a>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-secondary small">
                                © Copyright {{ new Date().getFullYear() }} We Win Games. All Right Reserved. Designed by 
                                <a href="https://adsrole.com" target="_blank" class="text-warning text-decoration-none">AdsRole Pvt. Ltd.</a>
                            </div>
                            <a href="/support" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-headset"></i> Support
                            </a>
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
    font-weight: 500;
    font-size: 14px;
    padding: 0.5rem 1rem !important;
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
    font-size: 14px;
    opacity: 0.9;
}

.footer-link:hover {
    color: #ffc107 !important;
    opacity: 1;
}

/* Accordion Customization */
.accordion-button {
    box-shadow: none !important;
    border-radius: 0 !important;
    font-weight: 500;
    transition: all 0.3s ease;
}

.accordion-button:hover {
    background-color: rgba(255, 193, 7, 0.05) !important;
}

.accordion-button:not(.collapsed) {
    color: #000;
    background-color: #ffc107 !important;
    font-weight: 600;
}

.accordion-button:focus {
    box-shadow: none !important;
}

.accordion-button::after {
    content: '+';
    background-image: none;
    font-size: 1.5rem;
    font-weight: 300;
    width: auto;
    height: auto;
    color: #ffc107;
    transition: transform 0.3s ease;
}

.accordion-button:not(.collapsed)::after {
    content: '+';
    transform: rotate(45deg);
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