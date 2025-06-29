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
        router.delete(route('admin.blog-posts.destroy', post.id), {
            preserveScroll: true,
        });
    }
}

function duplicatePost(post: Post) {
    router.post(route('admin.blog-posts.duplicate', post.id), {}, {
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
        published: 'bg-green-100 text-green-800',
        draft: 'bg-gray-100 text-gray-800',
        scheduled: 'bg-blue-100 text-blue-800',
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
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
                <h1 class="text-2xl font-bold">Blog Post Management</h1>
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
            <div v-if="showStats && stats" class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4">Blog Statistics</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ stats.total_posts }}</div>
                        <div class="text-sm text-gray-500">Total Posts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-600">{{ stats.published_posts }}</div>
                        <div class="text-sm text-gray-500">Published</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-gray-600">{{ stats.draft_posts }}</div>
                        <div class="text-sm text-gray-500">Drafts</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-blue-600">{{ stats.scheduled_posts }}</div>
                        <div class="text-sm text-gray-500">Scheduled</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-600">{{ stats.total_views }}</div>
                        <div class="text-sm text-gray-500">Total Views</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ stats.posts_this_month }}</div>
                        <div class="text-sm text-gray-500">This Month</div>
                    </div>
                </div>
                
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="font-medium mb-2">Popular Categories</h3>
                        <div class="space-y-2">
                            <div v-for="cat in stats.popular_categories" :key="cat.category" class="flex justify-between text-sm">
                                <span>{{ categories[cat.category] || cat.category }}</span>
                                <span class="text-gray-500">{{ cat.count }} posts</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-medium mb-2">Top Posts by Views</h3>
                        <div class="space-y-2">
                            <div v-for="post in stats.top_posts" :key="post.id" class="flex justify-between text-sm">
                                <Link :href="route('admin.blog-posts.edit', post.id)" class="text-blue-600 hover:text-blue-800 truncate flex-1">
                                    {{ post.title }}
                                </Link>
                                <span class="text-gray-500 ml-2">{{ post.views_count }} views</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow p-4 mb-6">
                <div class="flex items-center space-x-4">
                    <div class="flex-1 relative">
                        <MagnifyingGlassIcon class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <TextInput 
                            v-model="filterForm.search" 
                            @keyup.enter="applyFilters"
                            class="pl-10 pr-4 w-full" 
                            placeholder="Search posts..."
                        />
                    </div>
                    
                    <select v-model="filterForm.status" @change="applyFilters" class="w-40">
                        <option value="">All Statuses</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                    
                    <select v-model="filterForm.category" @change="applyFilters" class="w-48">
                        <option value="">All Categories</option>
                        <option v-for="(label, value) in categories" :key="value" :value="value">
                            {{ label }}
                        </option>
                    </select>
                    
                    <SecondaryButton @click="clearFilters">Clear</SecondaryButton>
                </div>
            </div>
            
            <!-- Posts Table -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Published</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="post in posts.data" :key="post.id">
                            <td class="px-6 py-4">
                                <div>
                                    <div class="font-medium">{{ post.title }}</div>
                                    <div class="text-sm text-gray-500">{{ post.slug }}</div>
                                    <div v-if="post.tags.length > 0" class="mt-1 flex flex-wrap gap-1">
                                        <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="inline-flex items-center text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">
                                            <TagIcon class="h-3 w-3 mr-0.5" />
                                            {{ tag }}
                                        </span>
                                        <span v-if="post.tags.length > 3" class="text-xs text-gray-500">
                                            +{{ post.tags.length - 3 }} more
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm">{{ categories[post.category] || post.category }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="font-medium">{{ post.author.name }}</div>
                                    <div class="text-gray-500">{{ post.author.email }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span :class="getStatusColor(post.status)" class="inline-flex text-xs px-2 py-1 rounded-full">
                                    {{ post.status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="flex items-center">
                                    <CalendarIcon class="h-4 w-4 mr-1" />
                                    {{ formatDate(post.published_at) }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex items-center">
                                    <EyeIcon class="h-4 w-4 mr-1 text-gray-400" />
                                    {{ post.views_count }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-2">
                                    <Link 
                                        :href="route('blog.show', post.slug)" 
                                        target="_blank"
                                        class="text-gray-600 hover:text-gray-800"
                                        title="View"
                                    >
                                        <EyeIcon class="h-4 w-4" />
                                    </Link>
                                    <Link 
                                        :href="route('admin.blog-posts.edit', post.id)" 
                                        class="text-blue-600 hover:text-blue-800"
                                        title="Edit"
                                    >
                                        <PencilIcon class="h-4 w-4" />
                                    </Link>
                                    <button 
                                        @click="duplicatePost(post)" 
                                        class="text-purple-600 hover:text-purple-800"
                                        title="Duplicate"
                                    >
                                        <DocumentDuplicateIcon class="h-4 w-4" />
                                    </button>
                                    <button 
                                        @click="deletePost(post)" 
                                        class="text-red-600 hover:text-red-800"
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
                <div v-if="posts.links.length > 3" class="bg-gray-50 px-6 py-3 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ posts.meta.from }} to {{ posts.meta.to }} of {{ posts.meta.total }} results
                    </div>
                    <div class="flex space-x-1">
                        <template v-for="link in posts.links" :key="link.label">
                            <button
                                v-if="link.url"
                                @click="router.get(link.url)"
                                class="px-3 py-1 text-sm rounded"
                                :class="link.active ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
                                v-html="link.label"
                            />
                            <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>