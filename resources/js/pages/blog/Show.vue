<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

// Reactive state
const showCopyNotification = ref(false);

// Computed
const pageTitle = computed(() => props.post.seo_title || props.post.title);
const pageDescription = computed(() => props.post.seo_description || props.post.excerpt);
const shareUrl = computed(() => window.location.href);

// Process content to add img-fluid class to all images
const processedContent = computed(() => {
    if (!props.post.content) return '';
    
    // Create a temporary div to parse HTML
    const div = document.createElement('div');
    div.innerHTML = props.post.content;
    
    // Find all images and add img-fluid class
    const images = div.querySelectorAll('img');
    images.forEach(img => {
        img.classList.add('img-fluid');
        // Also add some styling for better control
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
    });
    
    return div.innerHTML;
});

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
    showCopyNotification.value = true;
    setTimeout(() => {
        showCopyNotification.value = false;
    }, 3000);
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
        
        <div class="py-5" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%); min-height: 100vh;">
            <article class="container-fluid px-4 px-lg-5" style="max-width: 1200px;">
                <!-- Back to Blog -->
                <Link :href="route('blog.index')" class="btn btn-link text-primary text-decoration-none mb-4 p-0">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Blog
                </Link>
                
                <!-- Article Header -->
                <header class="mb-5">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        {{ post.title }}
                    </h1>
                    
                    <div class="d-flex flex-wrap align-items-center gap-3 text-gray-light mb-4">
                        <span class="d-flex align-items-center">
                            <i class="bi bi-person me-2"></i>
                            {{ post.author.name }}
                        </span>
                        <span class="d-flex align-items-center">
                            <i class="bi bi-calendar me-2"></i>
                            {{ formatDate(post.published_at) }}
                        </span>
                        <span class="d-flex align-items-center">
                            <i class="bi bi-clock me-2"></i>
                            {{ post.reading_time }} min read
                        </span>
                        <span class="d-flex align-items-center">
                            <i class="bi bi-eye me-2"></i>
                            {{ post.views_count }} views
                        </span>
                    </div>
                    
                    <!-- Tags -->
                    <div v-if="post.tags.length > 0" class="d-flex flex-wrap gap-2">
                        <Link 
                            v-for="tag in post.tags" 
                            :key="tag"
                            :href="route('blog.index', { tag })"
                            class="badge bg-secondary text-decoration-none"
                        >
                            <i class="bi bi-tag me-1"></i>
                            {{ tag }}
                        </Link>
                    </div>
                </header>
                
                <!-- Featured Image -->
                <div v-if="post.featured_image_url" class="mb-5">
                    <img 
                        :src="post.featured_image_url" 
                        :alt="post.title"
                        class="img-fluid rounded shadow"
                        style="width: 100%; max-height: 500px; object-fit: cover;"
                    />
                </div>
                
                <!-- Article Content -->
                <div class="article-content mb-5" v-html="processedContent"></div>
                
                <!-- Share Section -->
                <div class="border-top border-bottom py-4 mb-5" style="border-color: #2e4057 !important;">
                    <div class="d-flex align-items-center justify-content-between">
                        <h3 class="h5 fw-semibold text-white mb-0">Share this article</h3>
                        <div class="d-flex gap-2 position-relative">
                            <button 
                                @click="shareOnTwitter"
                                class="btn btn-sm"
                                style="background-color: #1DA1F2; color: white;"
                                title="Share on Twitter"
                            >
                                <svg class="bi" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                                </svg>
                            </button>
                            <button 
                                @click="shareOnFacebook"
                                class="btn btn-sm btn-primary"
                                title="Share on Facebook"
                            >
                                <svg class="bi" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </button>
                            <button 
                                @click="copyLink"
                                class="btn btn-sm btn-secondary"
                                title="Copy link"
                            >
                                <i class="bi bi-share"></i>
                            </button>
                            
                            <!-- Copy notification -->
                            <Transition name="fade">
                                <div 
                                    v-if="showCopyNotification" 
                                    class="position-absolute end-0 top-100 mt-2 alert alert-success py-2 px-3 d-flex align-items-center"
                                    style="white-space: nowrap; z-index: 1000;"
                                >
                                    <i class="bi bi-check-circle-fill me-2"></i>
                                    Link copied to clipboard!
                                </div>
                            </Transition>
                        </div>
                    </div>
                </div>
                
                <!-- Related Posts -->
                <section v-if="relatedPosts.length > 0">
                    <h2 class="h3 fw-bold text-white mb-4">Related Articles</h2>
                    <div class="row g-4">
                        <div v-for="relatedPost in relatedPosts" :key="relatedPost.id" class="col-12 col-md-6 col-lg-4">
                            <article class="card h-100" style="background-color: #1a2332; border: 1px solid #2e4057;">
                                <Link :href="route('blog.show', relatedPost.slug)" class="text-decoration-none">
                                    <div v-if="relatedPost.featured_image_url" style="height: 160px; overflow: hidden;">
                                        <img :src="relatedPost.featured_image_url" :alt="relatedPost.title" class="w-100 h-100" style="object-fit: cover;" />
                                    </div>
                                    <div v-else class="w-100 d-flex align-items-center justify-content-center" style="height: 160px; background: linear-gradient(135deg, #6366F1 0%, #7C3AED 100%);">
                                        <i class="bi bi-image fs-1 text-white opacity-50"></i>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h3 class="h6 fw-semibold text-white mb-2 line-clamp-2">
                                            {{ relatedPost.title }}
                                        </h3>
                                        <p class="text-gray-light small line-clamp-2 mb-3">
                                            {{ relatedPost.excerpt }}
                                        </p>
                                        <div class="d-flex align-items-center small text-muted">
                                            <i class="bi bi-calendar me-1"></i>
                                            {{ formatDate(relatedPost.published_at) }}
                                            <span class="mx-2">•</span>
                                            <i class="bi bi-clock me-1"></i>
                                            {{ relatedPost.reading_time }} min
                                        </div>
                                    </div>
                                </Link>
                            </article>
                        </div>
                    </div>
                </section>
            </article>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.article-content {
    color: #e5e7eb;
    font-size: 1.125rem;
    line-height: 1.75;
}

.article-content :deep(h1),
.article-content :deep(h2),
.article-content :deep(h3),
.article-content :deep(h4),
.article-content :deep(h5),
.article-content :deep(h6) {
    color: white;
    font-weight: 600;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.article-content :deep(h2) {
    font-size: 1.875rem;
}

.article-content :deep(h3) {
    font-size: 1.5rem;
}

.article-content :deep(p) {
    margin-bottom: 1.25rem;
}

.article-content :deep(a) {
    color: #6366F1;
    text-decoration: none;
}

.article-content :deep(a:hover) {
    color: #7C3AED;
    text-decoration: underline;
}

.article-content :deep(ul),
.article-content :deep(ol) {
    margin-bottom: 1.25rem;
    padding-left: 2rem;
}

.article-content :deep(li) {
    margin-bottom: 0.5rem;
}

.article-content :deep(blockquote) {
    border-left: 4px solid #6366F1;
    padding-left: 1rem;
    margin: 1.5rem 0;
    font-style: italic;
    color: #a8b9d5;
}

.article-content :deep(pre) {
    background-color: #1a2332;
    border: 1px solid #2e4057;
    border-radius: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.article-content :deep(code) {
    background-color: #1a2332;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
    color: #e5e7eb;
}

.article-content :deep(img) {
    max-width: 100%;
    height: auto;
    border-radius: 0.5rem;
    margin: 1.5rem 0;
}

.article-content :deep(table) {
    width: 100%;
    border-collapse: collapse;
    margin: 1.5rem 0;
}

.article-content :deep(th),
.article-content :deep(td) {
    border: 1px solid #2e4057;
    padding: 0.75rem;
    text-align: left;
}

.article-content :deep(th) {
    background-color: #1a2332;
    font-weight: 600;
    color: white;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card:hover {
    transform: translateY(-2px);
    border-color: #3e5067 !important;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>