<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';

const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const navLinks = [
    { title: 'Home', href: '/', },
    { title: "Betting Picks", href: '/todays-tips' },
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
        <!-- Header -->
        <header class="bg-[#212240] text-gray-200 shadow">
            <div class="container mx-auto flex items-center justify-between px-4 py-4">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-3">
                    <AppLogo class="h-30 w-30 rounded-full" />
                </div>
                <!-- Desktop Nav -->
                <nav class="hidden md:flex space-x-6 items-center">
                    <Link
                        v-for="link in navLinks"
                        :key="link.title"
                        :href="link.href"
                        class="hover:text-indigo-400 transition-colors"
                    >
                        {{ link.title }}
                    </Link>
                    <!-- Blogs Dropdown -->
                    <div class="relative group">
                        <button class="ml-2 px-4 py-2 rounded flex items-center text-white hover:text-indigo-400 transition-colors focus:outline-none bg-transparent">
                            More
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div class="absolute left-0 mt-2 w-48 bg-gray-800 rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity z-20">
                            <Link  v-for="link in dropdownLinks"
                            :key="link.title"
                            :href="link.href"
                            class="block px-4 py-2 hover:bg-indigo-600 hover:text-white"
                           > {{ link.title }}
                        </Link>
                        </div>
                    </div>
                    <template v-if="!auth.user">
                        <Link href="/login" class="ml-4 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Login</Link>
                        <Link href="/register" class="ml-2 px-4 py-2 rounded bg-gray-700 text-white hover:bg-gray-800 transition-colors">Register</Link>
                    </template>
                    <template v-else>
                        <Link href="/dashboard" class="ml-4 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Dashboard</Link>
                    </template>
                </nav>
                <!-- Mobile Menu Button -->
                <button @click="toggleMobileMenu" class="md:hidden focus:outline-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
            </div>
            <!-- Mobile Nav -->
            <div v-if="mobileMenuOpen" class="md:hidden bg-gray-800">
                <nav class="flex flex-col space-y-2 px-4 py-4">
                    <Link
                        v-for="link in navLinks"
                        :key="link.title"
                        :href="link.href"
                        class="hover:text-indigo-400 transition-colors"
                        @click="mobileMenuOpen = false"
                    >
                        {{ link.title }}
                    </Link>
                    <!-- Blogs Dropdown for Mobile -->
                    <div>
                        <span class="block mt-2 mb-1 font-semibold text-white">More</span>
                        <Link  v-for="link in dropdownLinks"
                            :key="link.title"
                            :href="link.href"
                            class="block px-4 py-2 hover:text-indigo-400"
                           > {{ link.title }}
                        </Link>
                    </div>
                    <template v-if="!auth.user">
                        <Link href="/login" class="mt-2 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Login</Link>
                        <Link href="/register" class="mt-2 px-4 py-2 rounded bg-gray-700 text-white hover:bg-gray-800 transition-colors">Register</Link>
                    </template>
                    <template v-else>
                        <Link href="/dashboard" class="mt-2 px-4 py-2 rounded bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Dashboard</Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Page Content -->
        <main>
            <div class="min-h-screen bg-gradient-to-b from-[#212240] via-blue-900 to-black text-gray-200">
                <slot />
            </div>
        </main>

        <!-- Disclaimer -->
        <div class="bg-gray-800 rounded-lg p-4 mt-10 mx-auto max-w-4xl text-center">
            <p class="text-xs text-gray-400">
                DISCLAIMER: This site is 100% for entertainment purposes only and does not involve real money betting. Gambling can be addictive, please play responsibly. If you or someone you know has a gambling problem and wants help, call 1-800 GAMBLER in the U.S. This service is intended for adult users 21+ only.
            </p>
            <p class="text-xs text-gray-400 mt-2">
                The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor.
            </p>
            <div class="flex flex-wrap justify-center items-center gap-6 mt-4">
                <img src="/images/rg.jpeg" alt="Responsible Gaming" class="h-12 w-auto" />
                <img src="/images/ncpg.png" alt="National Council on Problem Gambling" class="h-12 w-auto" />
                <img src="/images/21plus.png" alt="21+ Only" class="h-12 w-auto" />
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-gray-400 pt-12 pb-6 mt-12 border-t border-gray-800">
            <div class="container mx-auto px-4">
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-8">
                    <!-- Logo and Brand -->
                    <div class="flex flex-col items-center md:items-start">
                        <AppLogo class="h-25 w-25 rounded-full mb-2" />
                        <span class="text-sm mt-1">The sports betting app world is taking off and we want you to enjoy it more by becoming a profitable sports bettor.</span>
                    </div>
                    <!-- Footer Nav -->
                    <nav class="flex flex-wrap justify-center gap-4 md:gap-8">
                        <Link
                            v-for="link in navLinks"
                            :key="link.title"
                            :href="link.href"
                            class="hover:text-indigo-400 transition-colors text-sm"
                        >
                            {{ link.title }}
                        </Link>
                         <Link
                            v-for="link in dropdownLinks"
                            :key="link.title"
                            :href="link.href"
                            class="hover:text-indigo-400 transition-colors text-sm"
                        >
                            {{ link.title }}
                        </Link>
                    </nav>
                    <!-- Social Icons -->
                    <div class="flex justify-center gap-4">
                        <a
                            v-for="social in socialLinks"
                            :key="social.icon"
                            :href="social.url"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-indigo-400 transition-colors"
                        >
                            <span v-if="social.icon === 'mdi:twitter'">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22.46 6c-.77.35-1.6.58-2.47.69a4.3 4.3 0 0 0 1.88-2.37 8.59 8.59 0 0 1-2.72 1.04 4.28 4.28 0 0 0-7.29 3.9A12.13 12.13 0 0 1 3.11 4.86a4.28 4.28 0 0 0 1.32 5.71c-.7-.02-1.36-.21-1.94-.53v.05a4.28 4.28 0 0 0 3.43 4.19c-.33.09-.68.14-1.04.14-.25 0-.5-.02-.74-.07a4.29 4.29 0 0 0 4 2.98A8.6 8.6 0 0 1 2 19.54a12.13 12.13 0 0 0 6.56 1.92c7.88 0 12.2-6.53 12.2-12.2 0-.19 0-.39-.01-.58A8.72 8.72 0 0 0 24 4.59a8.51 8.51 0 0 1-2.54.7z"/>
                                </svg>
                            </span>
                            <span v-else-if="social.icon === 'mdi:instagram'">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M7.75 2h8.5A5.75 5.75 0 0 1 22 7.75v8.5A5.75 5.75 0 0 1 16.25 22h-8.5A5.75 5.75 0 0 1 2 16.25v-8.5A5.75 5.75 0 0 1 7.75 2zm0 1.5A4.25 4.25 0 0 0 3.5 7.75v8.5A4.25 4.25 0 0 0 7.75 20.5h8.5A4.25 4.25 0 0 0 20.5 16.25v-8.5A4.25 4.25 0 0 0 16.25 3.5h-8.5zm4.25 2.25a6.25 6.25 0 1 1 0 12.5 6.25 6.25 0 0 1 0-12.5zm0 1.5a4.75 4.75 0 1 0 0 9.5 4.75 4.75 0 0 0 0-9.5zm6.13 1.12a1.13 1.13 0 1 1-2.25 0 1.13 1.13 0 0 1 2.25 0z"/>
                                </svg>
                            </span>
                            <span v-else-if="social.icon === 'mdi:facebook'">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M22 12a10 10 0 1 0-11.5 9.95v-7.05h-2.1V12h2.1v-1.7c0-2.07 1.23-3.22 3.12-3.22.9 0 1.84.16 1.84.16v2.02h-1.04c-1.03 0-1.35.64-1.35 1.3V12h2.3l-.37 2.9h-1.93v7.05A10 10 0 0 0 22 12"/>
                                </svg>
                            </span>
                        </a>
                    </div>
                </div>
                <div class="mt-8 text-center text-xs text-gray-500">
                    &copy; {{ new Date().getFullYear() }} We Win Games. All Rights Reserved. Designed by <a href="https://jcompsolu.com" target="_blank" class="text-indigo-400 hover:underline">J Computer Solutions LLC</a>.
                </div>
            </div>
        </footer>
    </div>
</template>