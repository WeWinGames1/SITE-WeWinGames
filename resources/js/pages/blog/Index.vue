<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Input as TextInput } from "@/components/ui/input";
import { 
    MagnifyingGlassIcon,
    CalendarIcon,
    UserIcon,
    EyeIcon,
    ClockIcon,
    TagIcon
} from '@heroicons/vue/24/outline';

interface Author {
    id: number;
    name: string;
}

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    featured_image_url: string | null;
    category: string;
    tags: string[];
    published_at: string;
    views_count: number;
    reading_time: number;
    author: Author;
}

interface Props {
    posts: {
        data: Post[];
        links: any[];
        meta: any;
    };
    categories: Record<string, string>;
    popularTags: string[];
    filters: {
        category?: string;
        tag?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

// Forms
const searchForm = useForm({
    search: props.filters.search || '',
});

// Methods
function search() {
    searchForm.get(route('blog.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}

function filterByCategory(category: string) {
    router.get(route('blog.index'), { category }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function filterByTag(tag: string) {
    router.get(route('blog.index'), { tag }, {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    router.get(route('blog.index'));
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}
</script>

<template>
    <WelcomeLayout>
        <Head title="Blog - Sports Betting Insights & Tips" />
        
        <!-- Hero Section -->
        <section class="relative bg-gradient-to-b from-blue-900 to-blue-800 text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">
                        Sports Betting Blog
                    </h1>
                    <p class="text-xl text-blue-100 max-w-3xl mx-auto">
                        Expert insights, betting strategies, and the latest sports betting news to help you make informed decisions
                    </p>
                    
                    <!-- Search Bar -->
                    <form @submit.prevent="search" class="mt-8 max-w-2xl mx-auto">
                        <div class="relative">
                            <MagnifyingGlassIcon class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <TextInput 
                                v-model="searchForm.search" 
                                class="pl-12 pr-4 py-4 w-full text-gray-900 rounded-full shadow-lg" 
                                placeholder="Search articles..."
                            />
                            <button 
                                type="submit"
                                class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-6 py-2 rounded-full hover:bg-blue-700 transition"
                            >
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Active Filters -->
                    <div v-if="filters.category || filters.tag || filters.search" class="mb-6 flex items-center space-x-2">
                        <span class="text-sm text-gray-600">Filters:</span>
                        <div class="flex flex-wrap gap-2">
                            <span v-if="filters.category" class="inline-flex items-center bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">
                                {{ categories[filters.category] }}
                                <button @click="clearFilters" class="ml-2 text-blue-600 hover:text-blue-800">×</button>
                            </span>
                            <span v-if="filters.tag" class="inline-flex items-center bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full">
                                {{ filters.tag }}
                                <button @click="clearFilters" class="ml-2 text-green-600 hover:text-green-800">×</button>
                            </span>
                            <span v-if="filters.search" class="inline-flex items-center bg-gray-100 text-gray-800 text-sm px-3 py-1 rounded-full">
                                "{{ filters.search }}"
                                <button @click="clearFilters" class="ml-2 text-gray-600 hover:text-gray-800">×</button>
                            </span>
                        </div>
                        <button @click="clearFilters" class="text-sm text-blue-600 hover:text-blue-800">Clear all</button>
                    </div>
                    
                    <!-- Blog Posts Grid -->
                    <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <article v-for="post in posts.data" :key="post.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <Link :href="route('blog.show', post.slug)" class="block">
                                <div v-if="post.featured_image_url" class="aspect-w-16 aspect-h-9">
                                    <img :src="post.featured_image_url" :alt="post.title" class="w-full h-48 object-cover" />
                                </div>
                                <div v-else class="w-full h-48 bg-gradient-to-br from-blue-400 to-blue-600"></div>
                                
                                <div class="p-6">
                                    <div class="flex items-center text-sm text-gray-500 mb-2">
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                            {{ categories[post.category] || post.category }}
                                        </span>
                                        <span class="mx-2">•</span>
                                        <span class="flex items-center">
                                            <ClockIcon class="h-4 w-4 mr-1" />
                                            {{ post.reading_time }} min read
                                        </span>
                                    </div>
                                    
                                    <h2 class="text-xl font-bold text-gray-900 mb-2 hover:text-blue-600 transition-colors">
                                        {{ post.title }}
                                    </h2>
                                    
                                    <p class="text-gray-600 mb-4 line-clamp-3">
                                        {{ post.excerpt }}
                                    </p>
                                    
                                    <div class="flex items-center justify-between text-sm text-gray-500">
                                        <div class="flex items-center">
                                            <UserIcon class="h-4 w-4 mr-1" />
                                            {{ post.author.name }}
                                        </div>
                                        <div class="flex items-center space-x-4">
                                            <span class="flex items-center">
                                                <CalendarIcon class="h-4 w-4 mr-1" />
                                                {{ formatDate(post.published_at) }}
                                            </span>
                                            <span class="flex items-center">
                                                <EyeIcon class="h-4 w-4 mr-1" />
                                                {{ post.views_count }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div v-if="post.tags.length > 0" class="mt-3 flex flex-wrap gap-1">
                                        <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded">
                                            {{ tag }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </article>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500 text-lg">No blog posts found.</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="posts.links.length > 3" class="mt-8 flex justify-center">
                        <nav class="flex space-x-1">
                            <template v-for="link in posts.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="router.get(link.url)"
                                    class="px-4 py-2 text-sm rounded-md transition"
                                    :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border'"
                                    v-html="link.label"
                                />
                                <span v-else class="px-4 py-2 text-sm text-gray-400" v-html="link.label" />
                            </template>
                        </nav>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <aside class="lg:col-span-1">
                    <!-- Categories -->
                    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                        <h3 class="text-lg font-bold mb-4">Categories</h3>
                        <ul class="space-y-2">
                            <li v-for="(label, value) in categories" :key="value">
                                <button 
                                    @click="filterByCategory(value)"
                                    class="text-gray-700 hover:text-blue-600 transition-colors"
                                    :class="{ 'font-semibold text-blue-600': filters.category === value }"
                                >
                                    {{ label }}
                                </button>
                            </li>
                        </ul>
                    </div>
                    
                    <!-- Popular Tags -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-bold mb-4">Popular Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <button 
                                v-for="tag in popularTags" 
                                :key="tag"
                                @click="filterByTag(tag)"
                                class="text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded-full hover:bg-blue-100 hover:text-blue-700 transition"
                                :class="{ 'bg-blue-100 text-blue-700': filters.tag === tag }"
                            >
                                {{ tag }}
                            </button>
                        </div>
                    </div>
                    
                    <!-- Newsletter CTA -->
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 text-white rounded-lg shadow-md p-6 mt-6">
                        <h3 class="text-lg font-bold mb-2">Stay Updated</h3>
                        <p class="text-blue-100 mb-4">Get the latest betting tips and insights delivered to your inbox.</p>
                        <Link href="/register" class="block w-full bg-white text-blue-600 text-center py-2 rounded-md font-semibold hover:bg-blue-50 transition">
                            Subscribe Now
                        </Link>
                    </div>
                </aside>
            </div>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>