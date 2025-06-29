<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { 
    HomeIcon,
    UsersIcon,
    ChartBarIcon,
    DocumentTextIcon,
    CurrencyDollarIcon,
    TicketIcon,
    CogIcon,
    ArrowLeftOnRectangleIcon,
    Bars3Icon,
    XMarkIcon,
    BellIcon,
    MagnifyingGlassIcon,
    TagIcon,
    GlobeAltIcon,
    ShieldCheckIcon,
    ClipboardDocumentListIcon,
    EnvelopeIcon,
    TrophyIcon,
    PuzzlePieceIcon
} from '@heroicons/vue/24/outline';
import ApplicationLogo from '@/components/AppLogo.vue';

const page = usePage();
const sidebarOpen = ref(false);
const userMenuOpen = ref(false);

interface NavItem {
    name: string;
    href: string;
    icon: any;
    badge?: number;
    children?: NavItem[];
}

const navigation: NavItem[] = [
    {
        name: 'Dashboard',
        href: route('admin.dashboard'),
        icon: HomeIcon,
    },
    {
        name: 'Betting',
        href: '#',
        icon: TrophyIcon,
        children: [
            { name: 'All Bets', href: route('admin.bets.index'), icon: ChartBarIcon },
            { name: 'Import Bets', href: route('admin.bets.import.index'), icon: ArrowLeftOnRectangleIcon },
            // { name: 'Games', href: route('admin.games.index'), icon: PuzzlePieceIcon },
            // { name: 'Teams', href: route('admin.teams.index'), icon: UsersIcon },
            // { name: 'Sports', href: route('admin.sports.index'), icon: GlobeAltIcon },
            // { name: 'Operators', href: route('admin.operators.index'), icon: TicketIcon },
        ],
    },
    {
        name: 'Users',
        href: '#',
        icon: UsersIcon,
        children: [
            { name: 'All Customers', href: route('admin.customers.index'), icon: UsersIcon },
            { name: 'Subscriptions', href: route('admin.subscriptions.index'), icon: CurrencyDollarIcon },
            { name: 'Admin Users', href: route('admin.admins.index'), icon: ShieldCheckIcon },
        ],
    },
    {
        name: 'Content',
        href: '#',
        icon: DocumentTextIcon,
        children: [
            { name: 'Blog Posts', href: route('admin.blog-posts.index'), icon: DocumentTextIcon },
            { name: 'Pages', href: route('admin.pages.index'), icon: ClipboardDocumentListIcon },
            { name: 'Landing Pages', href: route('admin.landing-pages.index'), icon: GlobeAltIcon },
        ],
    },
    {
        name: 'E-commerce',
        href: '#',
        icon: CurrencyDollarIcon,
        children: [
            { name: 'Stripe Products', href: route('admin.stripe-products.index'), icon: CurrencyDollarIcon },
            { name: 'Discount Codes', href: route('admin.discounts.index'), icon: TagIcon },
        ],
    },
    {
        name: 'Communications',
        href: '#',
        icon: EnvelopeIcon,
        children: [
            { name: 'Send Notification', href: route('admin.notifications.create'), icon: BellIcon },
            { name: 'Email Templates', href: route('admin.email-templates.index'), icon: EnvelopeIcon },
        ],
    },
    {
        name: 'Settings',
        href: route('admin.settings.index'),
        icon: CogIcon,
    },
];

const currentRoute = computed(() => route().current());

function isActiveRoute(href: string): boolean {
    if (href === '#') return false;
    return currentRoute.value === href;
}

function isActiveParent(item: NavItem): boolean {
    if (!item.children) return false;
    return item.children.some(child => isActiveRoute(child.href));
}

function logout() {
    router.post(route('admin.logout'));
}
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile sidebar -->
        <div v-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-gray-900/80" @click="sidebarOpen = false"></div>
            <div class="fixed inset-0 flex">
                <div class="relative mr-16 flex w-full max-w-xs flex-1">
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button type="button" @click="sidebarOpen = false" class="-m-2.5 p-2.5">
                            <XMarkIcon class="h-6 w-6 text-white" />
                        </button>
                    </div>
                    <!-- Sidebar content -->
                    <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                        <div class="flex h-16 shrink-0 items-center">
                            <ApplicationLogo class="h-8 w-auto text-white" />
                        </div>
                        <nav class="flex flex-1 flex-col">
                            <ul role="list" class="flex flex-1 flex-col gap-y-7">
                                <li>
                                    <ul role="list" class="-mx-2 space-y-1">
                                        <li v-for="item in navigation" :key="item.name">
                                            <template v-if="!item.children">
                                                <Link
                                                    :href="item.href"
                                                    :class="[
                                                        isActiveRoute(item.href)
                                                            ? 'bg-gray-800 text-white'
                                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                                        'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold'
                                                    ]"
                                                >
                                                    <component :is="item.icon" class="h-6 w-6 shrink-0" />
                                                    {{ item.name }}
                                                    <span v-if="item.badge" class="ml-auto w-9 min-w-max whitespace-nowrap rounded-full bg-gray-800 px-2.5 py-0.5 text-center text-xs font-medium leading-5 text-white ring-1 ring-inset ring-gray-700">
                                                        {{ item.badge }}
                                                    </span>
                                                </Link>
                                            </template>
                                            <template v-else>
                                                <div>
                                                    <div class="text-xs font-semibold leading-6 text-gray-400 mt-4 first:mt-0">
                                                        {{ item.name }}
                                                    </div>
                                                    <ul role="list" class="-mx-2 mt-2 space-y-1">
                                                        <li v-for="child in item.children" :key="child.name">
                                                            <Link
                                                                :href="child.href"
                                                                :class="[
                                                                    isActiveRoute(child.href)
                                                                        ? 'bg-gray-800 text-white'
                                                                        : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                                                    'group flex gap-x-3 rounded-md p-2 pl-9 text-sm leading-6'
                                                                ]"
                                                            >
                                                                {{ child.name }}
                                                            </Link>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </template>
                                        </li>
                                    </ul>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <ApplicationLogo class="h-8 w-auto text-white" />
                    <span class="ml-3 text-xl font-bold text-white">Admin Portal</span>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li v-for="item in navigation" :key="item.name">
                                    <template v-if="!item.children">
                                        <Link
                                            :href="item.href"
                                            :class="[
                                                isActiveRoute(item.href)
                                                    ? 'bg-gray-800 text-white'
                                                    : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                                'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-semibold'
                                            ]"
                                        >
                                            <component :is="item.icon" class="h-6 w-6 shrink-0" />
                                            {{ item.name }}
                                            <span v-if="item.badge" class="ml-auto w-9 min-w-max whitespace-nowrap rounded-full bg-gray-800 px-2.5 py-0.5 text-center text-xs font-medium leading-5 text-white ring-1 ring-inset ring-gray-700">
                                                {{ item.badge }}
                                            </span>
                                        </Link>
                                    </template>
                                    <template v-else>
                                        <div v-if="item.children">
                                            <div
                                                :class="[
                                                    isActiveParent(item) ? 'text-white' : 'text-gray-400',
                                                    'flex items-center gap-x-3 p-2 text-sm leading-6 font-semibold'
                                                ]"
                                            >
                                                <component :is="item.icon" class="h-6 w-6 shrink-0" />
                                                {{ item.name }}
                                            </div>
                                            <ul role="list" class="mt-1 space-y-1">
                                                <li v-for="child in item.children" :key="child.name">
                                                    <Link
                                                        :href="child.href"
                                                        :class="[
                                                            isActiveRoute(child.href)
                                                                ? 'bg-gray-800 text-white'
                                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                                            'group flex gap-x-3 rounded-md py-2 pl-11 pr-2 text-sm leading-6'
                                                        ]"
                                                    >
                                                        {{ child.name }}
                                                    </Link>
                                                </li>
                                            </ul>
                                        </div>
                                    </template>
                                </li>
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <button
                                @click="logout"
                                class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm font-semibold leading-6 text-gray-400 hover:bg-gray-800 hover:text-white w-full"
                            >
                                <ArrowLeftOnRectangleIcon class="h-6 w-6 shrink-0" />
                                Sign out
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-gray-700 lg:hidden">
                    <Bars3Icon class="h-6 w-6" />
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <!-- Search -->
                    <form class="relative flex flex-1" action="#" method="GET">
                        <label for="search-field" class="sr-only">Search</label>
                        <MagnifyingGlassIcon class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-400" />
                        <input
                            id="search-field"
                            class="block h-full w-full border-0 py-0 pl-8 pr-0 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                            placeholder="Search..."
                            type="search"
                            name="search"
                        />
                    </form>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Notifications -->
                        <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                            <BellIcon class="h-6 w-6" />
                        </button>

                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true"></div>

                        <!-- Profile dropdown -->
                        <div class="relative">
                            <button
                                type="button"
                                @click="userMenuOpen = !userMenuOpen"
                                class="-m-1.5 flex items-center p-1.5"
                            >
                                <img
                                    class="h-8 w-8 rounded-full bg-gray-50"
                                    src="https://ui-avatars.com/api/?name=Admin&color=7F9CF5&background=EBF4FF"
                                    alt=""
                                />
                                <span class="hidden lg:flex lg:items-center">
                                    <span class="ml-4 text-sm font-semibold leading-6 text-gray-900">
                                        {{ page.props.auth.user.name }}
                                    </span>
                                </span>
                            </button>

                            <div
                                v-show="userMenuOpen"
                                @click.away="userMenuOpen = false"
                                class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg ring-1 ring-gray-900/5 focus:outline-none"
                            >
                                <Link href="/settings/profile" class="block px-3 py-1 text-sm leading-6 text-gray-900">
                                    Your profile
                                </Link>
                                <button @click="logout" class="block px-3 py-1 text-sm leading-6 text-gray-900 w-full text-left">
                                    Sign out
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>