<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { usePage, Link } from '@inertiajs/vue3';
import { BookOpen, Folder, LayoutGrid, HandCoins, Trophy, Bell, UsersRound, DollarSign, CreditCard, House } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const isAdmin = page.props.auth?.isAdmin;

const mainNavItems: NavItem[] = [
    { title: 'Home', href: '/', icon: House },
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Billing',
        href: '/settings/billing',
        icon: CreditCard,
    },
   // { title: "Betting Picks", href: '/todays-tips', icon: Bell },
   //{ title: 'Betting Results', href: '/betting-results', icon: Trophy },
    
];

const footerNavItems: NavItem[] = [
    { title: 'Blog', href: '/blog', icon: BookOpen },
    { title: 'Betting Education', href: '/betting-education', icon: BookOpen },
    { title: 'Careers', href: '/careers-jobs', icon: DollarSign },
    { title: 'About Us', href: '/about-us', icon: UsersRound }
    
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <!-- Admin Links -->
            <div v-if="isAdmin" class="mt-6">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/pages">Manage Pages</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/customers">Manage Customers</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/admins">Manage Admins</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <!-- Add this for Landing Pages -->
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/landing-pages">Manage Landing Pages</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <!-- Blog Posts -->
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/blog-posts">Manage Blog Posts</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <!-- Stripe Products -->
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/stripe-products">Stripe Products</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <!-- Subscriptions -->
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/subscriptions">Subscription Dashboard</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <!-- Discount Codes -->
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <Link href="/admin/discounts">Discount Codes</Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
