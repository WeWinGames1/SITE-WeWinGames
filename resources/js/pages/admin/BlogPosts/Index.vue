<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { debounce } from 'lodash';

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

// Debounced search
const debouncedSearch = debounce((value: string) => {
    filterForm.search = value;
    applyFilters();
}, 300);

// Methods
function applyFilters() {
    router.get(route('admin.blog-posts.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function clearFilters() {
    filterForm.reset();
    applyFilters();
}

function deletePost(post: Post) {
    if (confirm(`Are you sure you want to delete "${post.title}"?`)) {
        router.delete(route('admin.blog-posts.destroy', post.slug), {
            preserveScroll: true,
        });
    }
}

function duplicatePost(post: Post) {
    router.post(route('admin.blog-posts.duplicate', post.slug), {}, {
        preserveScroll: true,
    });
}

async function loadStats() {
    showStats.value = !showStats.value;
    if (showStats.value && !stats.value) {
        try {
            const response = await fetch(route('admin.blog-posts.statistics'));
            stats.value = await response.json();
        } catch (error) {
            console.error('Failed to load statistics:', error);
        }
    }
}

function getStatusBadgeClass(status: string): string {
    const classes: Record<string, string> = {
        published: 'badge bg-success',
        draft: 'badge bg-secondary',
        scheduled: 'badge bg-info',
    };
    return classes[status] || 'badge bg-secondary';
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
    <AdminLayout>
        <Head title="Blog Posts" />
        
        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Blog Post Management</h1>
                    <p class="text-muted">Create, edit, and manage your blog content</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" @click="loadStats">
                        <i class="bi bi-bar-chart-line me-2"></i>
                        {{ showStats ? 'Hide' : 'Show' }} Statistics
                    </button>
                    <Link :href="route('admin.blog-posts.create')" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>
                        New Post
                    </Link>
                </div>
            </div>
            
            <!-- Statistics Panel -->
            <div v-if="showStats && stats" class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Blog Statistics</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0">{{ stats.total_posts }}</h2>
                                <small class="text-muted">Total Posts</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0 text-success">{{ stats.published_posts }}</h2>
                                <small class="text-muted">Published</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0 text-secondary">{{ stats.draft_posts }}</h2>
                                <small class="text-muted">Drafts</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0 text-info">{{ stats.scheduled_posts }}</h2>
                                <small class="text-muted">Scheduled</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0 text-primary">{{ stats.total_views }}</h2>
                                <small class="text-muted">Total Views</small>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <div class="text-center">
                                <h2 class="mb-0 text-warning">{{ stats.posts_this_month }}</h2>
                                <small class="text-muted">This Month</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="mb-3">Popular Categories</h6>
                            <div class="list-group list-group-flush">
                                <div v-for="cat in stats.popular_categories" :key="cat.category" 
                                     class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span>{{ categories[cat.category] || cat.category }}</span>
                                    <span class="badge bg-secondary rounded-pill">{{ cat.count }} posts</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-3">Top Posts by Views</h6>
                            <div class="list-group list-group-flush">
                                <div v-for="post in stats.top_posts" :key="post.id" 
                                     class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <Link :href="route('admin.blog-posts.edit', post.slug)" 
                                          class="text-decoration-none text-truncate me-2">
                                        {{ post.title }}
                                    </Link>
                                    <span class="badge bg-primary rounded-pill">{{ post.views_count }} views</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Filter Posts</h6>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small">Search</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input 
                                    type="search"
                                    class="form-control"
                                    placeholder="Search by title, content, or author..."
                                    :value="filterForm.search"
                                    @input="debouncedSearch($event.target.value)"
                                />
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small">Status</label>
                            <select v-model="filterForm.status" @change="applyFilters" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="published">Published</option>
                                <option value="draft">Draft</option>
                                <option value="scheduled">Scheduled</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label small">Category</label>
                            <select v-model="filterForm.category" @change="applyFilters" class="form-select">
                                <option value="">All Categories</option>
                                <option v-for="(label, value) in categories" :key="value" :value="value">
                                    {{ label }}
                                </option>
                            </select>
                        </div>
                        
                        <div class="col-md-1">
                            <button type="button" class="btn btn-secondary w-100" @click="clearFilters">
                                Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Posts Table -->
            <div class="card mb-4">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Author</th>
                                <th>Status</th>
                                <th>Published</th>
                                <th>Views</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="post in posts.data" :key="post.id">
                                <td>
                                    <div>
                                        <div class="fw-medium">{{ post.title }}</div>
                                        <small class="text-muted">{{ post.slug }}</small>
                                        <div v-if="post.tags.length > 0" class="mt-1">
                                            <span v-for="tag in post.tags.slice(0, 3)" :key="tag" 
                                                  class="badge bg-secondary me-1">
                                                <i class="bi bi-tag"></i> {{ tag }}
                                            </span>
                                            <span v-if="post.tags.length > 3" class="text-muted small">
                                                +{{ post.tags.length - 3 }} more
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ categories[post.category] || post.category }}</td>
                                <td>
                                    <div>
                                        <div class="small">{{ post.author.name }}</div>
                                        <small class="text-muted">{{ post.author.email }}</small>
                                    </div>
                                </td>
                                <td>
                                    <span :class="getStatusBadgeClass(post.status)">
                                        {{ post.status }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3"></i>
                                        {{ formatDate(post.published_at) }}
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-eye"></i> {{ post.views_count }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a :href="route('blog.show', post.slug)" 
                                           target="_blank"
                                           class="btn btn-outline-secondary"
                                           title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <Link :href="route('admin.blog-posts.edit', post.slug)" 
                                              class="btn btn-outline-primary"
                                              title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </Link>
                                        <button type="button"
                                                @click="duplicatePost(post)" 
                                                class="btn btn-outline-secondary"
                                                title="Duplicate">
                                            <i class="bi bi-files"></i>
                                        </button>
                                        <button type="button"
                                                @click="deletePost(post)" 
                                                class="btn btn-outline-danger"
                                                title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty state -->
                <div v-if="posts.data.length === 0" class="text-center py-5">
                    <i class="bi bi-file-text display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No blog posts found</p>
                    <Link :href="route('admin.blog-posts.create')" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i>
                        Create your first post
                    </Link>
                </div>
                
                <!-- Pagination -->
                <div v-if="posts.links.length > 3" class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing {{ posts.meta.from }} to {{ posts.meta.to }} of {{ posts.meta.total }} results
                        </div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0">
                                <li v-for="link in posts.links" :key="link.label" 
                                    class="page-item" 
                                    :class="{ 'active': link.active, 'disabled': !link.url }">
                                    <button v-if="link.url"
                                            @click="router.get(link.url)"
                                            class="page-link"
                                            v-html="link.label" />
                                    <span v-else class="page-link" v-html="link.label" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>