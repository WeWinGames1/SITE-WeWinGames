<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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
        <section class="position-relative text-white py-5" style="background: linear-gradient(180deg, #1e3a5f 0%, #0a1628 100%);">
            <div class="container-fluid px-4 px-lg-5">
                <div class="text-center">
                    <h1 class="display-3 fw-bold mb-4">
                        Sports Betting Blog
                    </h1>
                    <p class="fs-4 mb-5 mx-auto" style="max-width: 800px; color: #a8b9d5;">
                        Expert insights, betting strategies, and the latest sports betting news to help you make informed decisions
                    </p>
                    
                    <!-- Search Bar -->
                    <form @submit.prevent="search" class="mx-auto" style="max-width: 600px;">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input 
                                type="text"
                                v-model="searchForm.search" 
                                class="form-control form-control-lg border-start-0 ps-0" 
                                placeholder="Search articles..."
                                style="box-shadow: none;"
                            />
                            <button 
                                type="submit"
                                class="btn btn-primary btn-lg px-4"
                            >
                                Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <div class="container-fluid px-4 px-lg-5 py-5">
            <div class="row g-4">
                <!-- Main Content -->
                <div class="col-lg-9">
                    <!-- Active Filters -->
                    <div v-if="filters.category || filters.tag || filters.search" class="mb-4 d-flex align-items-center gap-2">
                        <span class="small text-muted">Filters:</span>
                        <div class="d-flex flex-wrap gap-2">
                            <span v-if="filters.category" class="badge bg-primary">
                                {{ categories[filters.category] }}
                                <button @click="clearFilters" class="ms-2 text-white border-0 bg-transparent">×</button>
                            </span>
                            <span v-if="filters.tag" class="badge bg-success">
                                {{ filters.tag }}
                                <button @click="clearFilters" class="ms-2 text-white border-0 bg-transparent">×</button>
                            </span>
                            <span v-if="filters.search" class="badge bg-secondary">
                                "{{ filters.search }}"
                                <button @click="clearFilters" class="ms-2 text-white border-0 bg-transparent">×</button>
                            </span>
                        </div>
                        <button @click="clearFilters" class="btn btn-link btn-sm text-decoration-none">Clear all</button>
                    </div>
                    
                    <!-- Blog Posts Grid -->
                    <div v-if="posts.data.length > 0" class="row g-4">
                        <div v-for="post in posts.data" :key="post.id" class="col-12 col-md-6">
                            <article class="card h-100" style="background-color: #1a2332; border: 1px solid #2e4057; transition: all 0.3s ease;">
                                <Link :href="route('blog.show', post.slug)" class="text-decoration-none">
                                    <div v-if="post.featured_image_url" style="height: 200px; overflow: hidden;">
                                        <img :src="post.featured_image_url" :alt="post.title" class="w-100 h-100" style="object-fit: cover;" />
                                    </div>
                                    <div v-else class="w-100 d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, #1e3a5f 0%, #0a1628 100%);">
                                        <i class="bi bi-image fs-1 text-white opacity-50"></i>
                                    </div>
                                    
                                    <div class="card-body">
                                        <div class="d-flex align-items-center small text-muted mb-2">
                                            <span class="badge bg-primary">
                                                {{ categories[post.category] || post.category }}
                                            </span>
                                            <span class="mx-2">•</span>
                                            <span class="d-flex align-items-center">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ post.reading_time }} min read
                                            </span>
                                        </div>
                                        
                                        <h2 class="h5 fw-bold text-white mb-2">
                                            {{ post.title }}
                                        </h2>
                                        
                                        <p class="text-gray-light small mb-3 line-clamp-3">
                                            {{ post.excerpt }}
                                        </p>
                                        
                                        <div class="d-flex justify-content-between align-items-center small text-muted">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-person me-1"></i>
                                                {{ post.author.name }}
                                            </div>
                                            <div class="d-flex gap-3">
                                                <span class="d-flex align-items-center">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    {{ formatDate(post.published_at) }}
                                                </span>
                                                <span class="d-flex align-items-center">
                                                    <i class="bi bi-eye me-1"></i>
                                                    {{ post.views_count }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div v-if="post.tags.length > 0" class="mt-3 d-flex flex-wrap gap-1">
                                            <span v-for="tag in post.tags.slice(0, 3)" :key="tag" class="badge bg-secondary small">
                                                {{ tag }}
                                            </span>
                                        </div>
                                    </div>
                                </Link>
                            </article>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-newspaper text-muted" style="font-size: 4rem;"></i>
                        <p class="text-muted fs-5 mt-3">No blog posts found.</p>
                    </div>
                    
                    <!-- Pagination -->
                    <div v-if="posts.links.length > 3" class="mt-4 d-flex justify-content-center">
                        <nav>
                            <ul class="pagination">
                                <li v-for="link in posts.links" :key="link.label" 
                                    class="page-item" 
                                    :class="{ active: link.active, disabled: !link.url }">
                                    <button
                                        v-if="link.url"
                                        @click="router.get(link.url)"
                                        class="page-link"
                                        v-html="link.label"
                                    />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                
                <!-- Sidebar -->
                <aside class="col-lg-3">
                    <!-- Categories -->
                    <div class="card mb-4" style="background-color: #1a2332; border: 1px solid #2e4057;">
                        <div class="card-body">
                            <h3 class="h5 fw-bold text-white mb-3">Categories</h3>
                            <ul class="list-unstyled mb-0">
                                <li v-for="(label, value) in categories" :key="value" class="mb-2">
                                    <button 
                                        @click="filterByCategory(value)"
                                        class="btn btn-link text-decoration-none p-0 text-start"
                                        :class="{ 'text-primary fw-semibold': filters.category === value, 'text-gray-light': filters.category !== value }"
                                    >
                                        <i class="bi bi-chevron-right me-1 small"></i>
                                        {{ label }}
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Popular Tags -->
                    <div class="card mb-4" style="background-color: #1a2332; border: 1px solid #2e4057;">
                        <div class="card-body">
                            <h3 class="h5 fw-bold text-white mb-3">Popular Tags</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <button 
                                    v-for="tag in popularTags" 
                                    :key="tag"
                                    @click="filterByTag(tag)"
                                    class="btn btn-sm"
                                    :class="{ 'btn-primary': filters.tag === tag, 'btn-outline-secondary': filters.tag !== tag }"
                                >
                                    {{ tag }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Newsletter CTA -->
                    <div class="card text-white" style="background: linear-gradient(135deg, #6366F1 0%, #7C3AED 100%); border: none;">
                        <div class="card-body">
                            <h3 class="h5 fw-bold mb-2">Stay Updated</h3>
                            <p class="mb-3 opacity-90">Get the latest betting tips and insights delivered to your inbox.</p>
                            <Link href="/register" class="btn btn-white text-primary fw-semibold w-100">
                                Subscribe Now
                            </Link>
                        </div>
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

.card:hover {
    transform: translateY(-2px);
    border-color: #3e5067 !important;
}

.btn-outline-secondary {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: #4a5568;
    color: #a8b9d5;
}

.btn-outline-secondary:hover {
    background-color: #6366F1;
    border-color: #6366F1;
    color: white;
}

.btn-white {
    background-color: white;
    border-color: white;
}

.btn-white:hover {
    background-color: #f8f9fa;
    border-color: #f8f9fa;
}
</style>