<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
    CalendarIcon,
    UserIcon,
    EyeIcon,
    ClockIcon,
    TagIcon,
    ArrowLeftIcon,
    ShareIcon
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
    content: string;
    featured_image_url: string | null;
    category: string;
    tags: string[];
    published_at: string;
    views_count: number;
    reading_time: number;
    author: Author;
    seo_title: string | null;
    seo_description: string | null;
    seo_keywords: string | null;
}

interface RelatedPost {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    featured_image_url: string | null;
    published_at: string;
    reading_time: number;
    author: Author;
}

interface Props {
    post: Post;
    relatedPosts: RelatedPost[];
}

const props = defineProps<Props>();

// Computed
const pageTitle = computed(() => props.post.seo_title || props.post.title);
const pageDescription = computed(() => props.post.seo_description || props.post.excerpt);
const shareUrl = computed(() => window.location.href);

// Methods
function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function shareOnTwitter() {
    const text = `Check out this article: ${props.post.title}`;
    const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(shareUrl.value)}`;
    window.open(url, '_blank', 'width=550,height=420');
}

function shareOnFacebook() {
    const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`;
    window.open(url, '_blank', 'width=550,height=420');
}

function copyLink() {
    navigator.clipboard.writeText(shareUrl.value);
    // You could add a toast notification here
}
</script>

<template>
    <WelcomeLayout>
        <Head :title="pageTitle">
            <meta name="description" :content="pageDescription" />
            <meta v-if="post.seo_keywords" name="keywords" :content="post.seo_keywords" />
            
            <!-- Open Graph Tags -->
            <meta property="og:title" :content="pageTitle" />
            <meta property="og:description" :content="pageDescription" />
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="shareUrl" />
            <meta v-if="post.featured_image_url" property="og:image" :content="post.featured_image_url" />
            
            <!-- Twitter Card Tags -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:title" :content="pageTitle" />
            <meta name="twitter:description" :content="pageDescription" />
            <meta v-if="post.featured_image_url" name="twitter:image" :content="post.featured_image_url" />
        </Head>
        
        <article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Back to Blog -->
            <Link :href="route('blog.index')" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-6">
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Back to Blog
            </Link>
            
            <!-- Article Header -->
            <header class="mb-8">
                <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                    {{ post.title }}
                </h1>
                
                <div class="flex flex-wrap items-center text-gray-600 space-x-4 mb-6">
                    <span class="flex items-center">
                        <UserIcon class="h-5 w-5 mr-2" />
                        {{ post.author.name }}
                    </span>
                    <span class="flex items-center">
                        <CalendarIcon class="h-5 w-5 mr-2" />
                        {{ formatDate(post.published_at) }}
                    </span>
                    <span class="flex items-center">
                        <ClockIcon class="h-5 w-5 mr-2" />
                        {{ post.reading_time }} min read
                    </span>
                    <span class="flex items-center">
                        <EyeIcon class="h-5 w-5 mr-2" />
                        {{ post.views_count }} views
                    </span>
                </div>
                
                <!-- Tags -->
                <div v-if="post.tags.length > 0" class="flex flex-wrap gap-2">
                    <Link 
                        v-for="tag in post.tags" 
                        :key="tag"
                        :href="route('blog.index', { tag })"
                        class="inline-flex items-center bg-gray-100 text-gray-700 text-sm px-3 py-1 rounded-full hover:bg-gray-200 transition"
                    >
                        <TagIcon class="h-4 w-4 mr-1" />
                        {{ tag }}
                    </Link>
                </div>
            </header>
            
            <!-- Featured Image -->
            <div v-if="post.featured_image_url" class="mb-8">
                <img 
                    :src="post.featured_image_url" 
                    :alt="post.title"
                    class="w-full rounded-lg shadow-lg"
                />
            </div>
            
            <!-- Article Content -->
            <div class="prose prose-lg max-w-none mb-12" v-html="post.content"></div>
            
            <!-- Share Section -->
            <div class="border-t border-b py-6 mb-12">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Share this article</h3>
                    <div class="flex space-x-3">
                        <button 
                            @click="shareOnTwitter"
                            class="p-2 bg-blue-400 text-white rounded-full hover:bg-blue-500 transition"
                            title="Share on Twitter"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </button>
                        <button 
                            @click="shareOnFacebook"
                            class="p-2 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition"
                            title="Share on Facebook"
                        >
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </button>
                        <button 
                            @click="copyLink"
                            class="p-2 bg-gray-600 text-white rounded-full hover:bg-gray-700 transition"
                            title="Copy link"
                        >
                            <ShareIcon class="h-5 w-5" />
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Related Posts -->
            <section v-if="relatedPosts.length > 0">
                <h2 class="text-2xl font-bold mb-6">Related Articles</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <article v-for="relatedPost in relatedPosts" :key="relatedPost.id" class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                        <Link :href="route('blog.show', relatedPost.slug)" class="block">
                            <div v-if="relatedPost.featured_image_url" class="aspect-w-16 aspect-h-9">
                                <img :src="relatedPost.featured_image_url" :alt="relatedPost.title" class="w-full h-40 object-cover" />
                            </div>
                            <div v-else class="w-full h-40 bg-gradient-to-br from-blue-400 to-blue-600"></div>
                            
                            <div class="p-4">
                                <h3 class="font-semibold text-lg mb-2 hover:text-blue-600 transition-colors">
                                    {{ relatedPost.title }}
                                </h3>
                                <p class="text-gray-600 text-sm line-clamp-2 mb-3">
                                    {{ relatedPost.excerpt }}
                                </p>
                                <div class="flex items-center text-xs text-gray-500">
                                    <CalendarIcon class="h-4 w-4 mr-1" />
                                    {{ formatDate(relatedPost.published_at) }}
                                    <span class="mx-2">•</span>
                                    <ClockIcon class="h-4 w-4 mr-1" />
                                    {{ relatedPost.reading_time }} min
                                </div>
                            </div>
                        </Link>
                    </article>
                </div>
            </section>
        </article>
    </WelcomeLayout>
</template>

<style scoped>
.prose {
    max-width: none;
}

.prose :deep(img) {
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.prose :deep(a) {
    color: rgb(37 99 235);
}

.prose :deep(a:hover) {
    color: rgb(30 64 175);
}

.prose :deep(h2) {
    font-size: 1.875rem;
    font-weight: 700;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose :deep(h3) {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose :deep(p) {
    margin-bottom: 1rem;
    line-height: 1.625;
}

.prose :deep(ul), .prose :deep(ol) {
    margin-bottom: 1rem;
    margin-left: 1.5rem;
}

.prose :deep(li) {
    margin-bottom: 0.5rem;
}

.prose :deep(blockquote) {
    border-left-width: 4px;
    border-left-color: rgb(59 130 246);
    padding-left: 1rem;
    font-style: italic;
    margin-top: 1rem;
    margin-bottom: 1rem;
}

.prose :deep(pre) {
    background-color: rgb(243 244 246);
    border-radius: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
}

.prose :deep(code) {
    background-color: rgb(243 244 246);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>