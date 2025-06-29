<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button as PrimaryButton } from "@/components/ui/button";
import { Button as SecondaryButton } from "@/components/ui/button";
import { Input as TextInput } from "@/components/ui/input";
import { 
    PlusIcon,
    MagnifyingGlassIcon,
    PencilIcon,
    TrashIcon,
    DocumentDuplicateIcon,
    EyeIcon,
    CalendarIcon,
    TagIcon,
    ChartBarIcon
} from '@heroicons/vue/24/outline';

interface Author {
    id: number;
    name: string;
    email: string;
}

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    category: string;
    tags: string[];
    status: 'published' | 'draft' | 'scheduled';
    is_published: boolean;
    published_at: string | null;
    views_count: number;
    author: Author;
    created_at: string;
    updated_at: string;
}

interface Props {
    posts: {
        data: Post[];
        links: any[];
        meta: any;
    };
    filters: {
        status?: string;
        category?: string;
        search?: string;
    };
    categories: Record<string, string>;
}

const props = defineProps<Props>();

// State
const showStats = ref(false);
const stats = ref<any>(null);

// Forms
const filterForm = useForm({
    status: props.filters.status || '',
    category: props.filters.category || '',
    search: props.filters.search || '',
});

// Methods
function applyFilters() {
    filterForm.get(route('admin.blog-posts.index'), {
        preserveState: true,
        preserveScroll: true,
    });
}

function clearFilters() {
    filterForm.reset();
    router.get(route('admin.blog-posts.index'));
}

function deletePost(post: Post) {
    if (confirm(`Are you sure you want to delete "${post.title}"?`)) {
        router.delete(route('admin.blog-posts.destroy', { post: post.id }), {
            preserveScroll: true,
        });
    }
}

function duplicatePost(post: Post) {
    router.post(route('admin.blog-posts.duplicate', { post: post.id }), {}, {
        preserveScroll: true,
    });
}

async function loadStats() {
    showStats.value = true;
    if (!stats.value) {
        try {
            const response = await fetch(route('admin.blog-posts.statistics'));
            stats.value = await response.json();
        } catch (error) {
            // console.error('Failed to load statistics:', error);
        }
    }
}

function getStatusColor(status: string): string {
    const colors: Record<string, string> = {
        published: 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
        draft: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
        scheduled: 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
    };
    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
}

function formatDate(date: string | null): string {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="{ title: 'Blog Posts', href: route('admin.blog-posts.index') }">
        <Head title="Blog Posts" />
        
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Blog Post Management</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Create, edit, and manage your blog content</p>
                </div>
                <div class="flex space-x-2">
                    <SecondaryButton @click="loadStats">
                        <ChartBarIcon class="h-4 w-4 mr-1" />
                        Statistics
                    </SecondaryButton>
                    <PrimaryButton :href="route('admin.blog-posts.create')">
                        <PlusIcon class="h-4 w-4 mr-1" />
                        New Post
                    </PrimaryButton>
                </div>
            </div>
            
            <!-- Statistics Panel -->
            <div v-if="showStats && stats" class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">Blog Statistics</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.total_posts }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Posts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.published_posts }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Published</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ stats.draft_posts }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Drafts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.scheduled_posts }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Scheduled</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ stats.total_views }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">Total Views</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.posts_this_month }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">This Month</div>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-2 text-gray-900 dark:text-gray-100">Popular Categories</h3>
                        <div class="space-y-2">
                            <div v-for="cat in stats.popular_categories" :key="cat.category" class="flex justify-between text-sm">
                                <span>{{ categories[cat.category] || cat.category }}</span>
                                <span class="text-gray-500 dark:text-gray-400">{{ cat.count }} posts</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-medium mb-2 text-gray-900 dark:text-gray-100">Top Posts by Views</h3>
                        <div class="space-y-2">
                            <div v-for="post in stats.top_posts" :key="post.id" class="flex justify-between text-sm">
                                <Link :href="route('admin.blog-posts.edit', { post: post.id })" class="text-blue-600 hover:text-blue-800 truncate flex-1">
                                    {{ post.title }}
                                </Link>
                                <span class="text-gray-500 dark:text-gray-400 ml-2">{{ post.views_count }} views</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="mb-3">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filter Posts</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Search and filter your blog posts by status, category, or keywords</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500" />
                        <TextInput 
                            v-model="filterForm.search" 
                            @keyup.enter="applyFilters"
                            class="pl-10 pr-4 w-full dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600" 
                            placeholder="Search by title, content, or author..."
                        />
                    </div>
                    
                    <div class="flex flex-col">
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1">Status</label>
                        <select v-model="filterForm.status" @change="applyFilters" class="w-40 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
                            <option value="">All Statuses</option>
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                            <option value="scheduled">Scheduled</option>
                        </select>
                    </div>
                    
                    <div class="flex flex-col">
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1">Category</label>
                        <select v-model="filterForm.category" @change="applyFilters" class="w-48 dark:bg-gray-700 dark:text-gray-100 dark:border-gray-600">
                            <option value="">All Categories</option>
                            <option v-for="(label, value) in categories" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    
                    <SecondaryButton @click="clearFilters">Clear</SecondaryButton>
                </div>
            </div>
            
            <!-- Posts Table -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Published</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-for="post in posts.data" :key="post.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ post.title }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ post.slug }}</div>
                                    <div v-if="post.tags.length > 0" class="mt-1 flex flex-wrap gap-1">
                                        <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="inline-flex items-center text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-0.5 rounded">
                                            <TagIcon class="h-3 w-3 mr-0.5" />
                                            {{ tag }}
                                        </span>
                                        <span v-if="post.tags.length > 3" class="text-xs text-gray-500 dark:text-gray-400">
                                            +{{ post.tags.length - 3 }} more
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ categories[post.category] || post.category }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ post.author.name }}</div>
                                    <div class="text-gray-500 dark:text-gray-400">{{ post.author.email }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(post.status)" class="inline-flex text-xs px-2 py-1 rounded-full">
                                    {{ post.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                <div class="flex items-center">
                                    <CalendarIcon class="h-4 w-4 mr-1" />
                                    {{ formatDate(post.published_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                <div class="flex items-center">
                                    <EyeIcon class="h-4 w-4 mr-1 text-gray-400 dark:text-gray-500" />
                                    {{ post.views_count }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <Link 
                                        :href="route('blog.show', post.slug)" 
                                        target="_blank"
                                        class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200"
                                        title="View"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link 
                                        :href="route('admin.blog-posts.edit', { post: post.id })" 
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>
                                    <button 
                                        @click="duplicatePost(post)" 
                                        class="text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-300"
                                        title="Duplicate"
                                    >
                                        <DocumentDuplicateIcon class="h-4 w-4" />
                                    </button>
                                    <button 
                                        @click="deletePost(post)" 
                                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete"
                                    >
                                        <TrashIcon class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div v-if="posts.links.length > 3" class="bg-gray-50 dark:bg-gray-700 px-6 py-3 flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Showing {{ posts.meta.from }} to {{ posts.meta.to }} of {{ posts.meta.total }} results
                    </div>
                    <div class="flex space-x-1">
                        <template v-for="link in posts.links" :key="link.label">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                class="px-3 py-1 text-sm rounded"
                                :class="link.active ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-500'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-gray-400 dark:text-gray-500" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>