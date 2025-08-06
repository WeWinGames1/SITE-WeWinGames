<template>
    <AdminLayout>
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="h3 mb-0">
                            <i class="bi bi-book me-2"></i>
                            Knowledgebase & Documentation
                        </h1>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control form-control-lg"
                                    placeholder="Search documentation by title, content, or route..."
                                    @input="filterArticles"
                                />
                                <button v-if="searchQuery" class="btn btn-outline-secondary" type="button" @click="clearSearch">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Frontend Documentation -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h4 class="mb-0">
                                <i class="bi bi-globe me-2"></i>
                                Frontend Documentation
                            </h4>
                        </div>
                        <div class="card-body">
                            <div v-if="filteredFrontendArticles.length === 0" class="text-center py-4 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                No documentation found
                            </div>
                            <div v-else class="list-group list-group-flush">
                                <div v-for="article in filteredFrontendArticles" :key="article.id" class="list-group-item px-0">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-3 article-title" @click="showArticle(article)">{{ article.title }}</h6>
                                            <div class="ms-auto">
                                                <a
                                                    v-if="getRouteForPage(article.page_identifier)"
                                                    :href="getRouteForPage(article.page_identifier)"
                                                    target="_blank"
                                                    class="btn btn-xs btn-outline-primary me-1"
                                                >
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                    Visit
                                                </a>
                                                <button class="btn btn-xs btn-primary" @click="showArticle(article)">
                                                    <i class="bi bi-book"></i>
                                                    Read
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            Route: <code>{{ article.page_identifier }}</code>
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            {{ truncateContent(article.content) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Documentation -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-warning bg-opacity-10">
                            <h4 class="mb-0">
                                <i class="bi bi-shield-lock me-2"></i>
                                Admin Documentation
                            </h4>
                        </div>
                        <div class="card-body">
                            <div v-if="filteredAdminArticles.length === 0" class="text-center py-4 text-muted">
                                <i class="bi bi-search fs-1 d-block mb-2"></i>
                                No documentation found
                            </div>
                            <div v-else class="list-group list-group-flush">
                                <div v-for="article in filteredAdminArticles" :key="article.id" class="list-group-item px-0">
                                    <div>
                                        <div class="d-flex align-items-center mb-2">
                                            <h6 class="mb-0 me-3 article-title" @click="showArticle(article)">{{ article.title }}</h6>
                                            <div class="ms-auto">
                                                <a
                                                    v-if="getRouteForPage(article.page_identifier)"
                                                    :href="getRouteForPage(article.page_identifier)"
                                                    target="_blank"
                                                    class="btn btn-xs btn-outline-primary me-1"
                                                >
                                                    <i class="bi bi-box-arrow-up-right"></i>
                                                    Visit
                                                </a>
                                                <button class="btn btn-xs btn-primary" @click="showArticle(article)">
                                                    <i class="bi bi-book"></i>
                                                    Read
                                                </button>
                                            </div>
                                        </div>
                                        <p class="mb-1 small text-muted">
                                            Route: <code>{{ article.page_identifier }}</code>
                                        </p>
                                        <p class="mb-0 text-muted small">
                                            {{ truncateContent(article.content) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Article Modal -->
            <div class="modal fade" id="articleModal" tabindex="-1" aria-labelledby="articleModalLabel" aria-hidden="true" ref="modalElement">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="articleModalLabel">
                                {{ selectedArticle?.title }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" v-if="selectedArticle">
                            <div class="mb-3">
                                <span class="badge bg-secondary me-2">{{ selectedArticle.type }}</span>
                                <code>{{ selectedArticle.page_identifier }}</code>
                            </div>

                            <div class="article-content" v-html="selectedArticle.content"></div>

                            <div v-if="selectedArticle.sections && selectedArticle.sections.length > 0" class="sections mt-4">
                                <div v-for="section in selectedArticle.sections" :key="section.title" class="section mb-4">
                                    <h6 class="section-title text-primary">{{ section.title }}</h6>
                                    <div class="section-content" v-html="section.content"></div>
                                </div>
                            </div>

                            <div v-if="selectedArticle.screenshot_path" class="screenshot mt-4">
                                <img
                                    :src="selectedArticle.screenshot_path"
                                    :alt="`Screenshot for ${selectedArticle.title}`"
                                    class="img-fluid rounded shadow"
                                />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <Link
                                v-if="selectedArticle && getRouteForPage(selectedArticle.page_identifier)"
                                :href="getRouteForPage(selectedArticle.page_identifier)"
                                class="btn btn-primary"
                            >
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Visit Page
                            </Link>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';

declare global {
    interface Window {
        bootstrap: any;
    }
}

interface Article {
    id: number;
    page_identifier: string;
    title: string;
    content: string;
    sections?: Array<{
        title: string;
        content: string;
    }>;
    screenshot_path?: string;
    type: 'frontend' | 'admin';
}

const props = defineProps<{
    articles: {
        frontend?: Article[];
        admin?: Article[];
    };
}>();

const selectedArticle = ref<Article | null>(null);
const modalElement = ref<HTMLElement | null>(null);
const searchQuery = ref('');
let modal: any = null;

const frontendArticles = computed(() => props.articles.frontend || []);
const adminArticles = computed(() => props.articles.admin || []);

const filteredFrontendArticles = ref<Article[]>([]);
const filteredAdminArticles = ref<Article[]>([]);

const showArticle = (article: Article) => {
    selectedArticle.value = article;
    if (modal) {
        modal.show();
    }
};

const truncateContent = (content: string, length: number = 150): string => {
    const stripped = content.replace(/<[^>]*>/g, '');
    if (stripped.length <= length) return stripped;
    return stripped.substring(0, length) + '...';
};

const getRouteForPage = (pageIdentifier: string): string | null => {
    try {
        // Check if it's a route name
        if (route().has(pageIdentifier)) {
            return route(pageIdentifier);
        }

        // Map common page identifiers to routes
        const routeMap: { [key: string]: string } = {
            home: 'home',
            dashboard: 'dashboard',
            'todays-bets': 'todays-bets',
            'betting-results': 'betting-results',
            'buy-our-picks': 'buy-our-picks',
            'blog.index': 'blog.index',
            support: 'support.public',
            faq: 'faq',
            'about-us': 'about-us',
            'betting-education': 'betting-tips',
            'subscription.checkout': 'subscription.checkout',
            'admin.dashboard': 'admin.dashboard',
            'admin.bets.index': 'admin.bets.index',
            'admin.bets.import.index': 'admin.bets.import.index',
            'admin.customers.index': 'admin.customers.index',
            'admin.stripe-products.index': 'admin.stripe-products.index',
            'admin.blog-posts.index': 'admin.blog-posts.index',
            'admin.discounts.index': 'admin.discounts.index',
            'admin.teams.index': 'admin.teams.index',
            'admin.support-tickets.index': 'admin.support-tickets.index',
            'admin.pages.index': 'admin.pages.index',
            'admin.faqs.index': 'admin.faqs.index',
            'admin.notifications.email-templates.index': 'admin.notifications.email-templates.index',
            'admin.under-construction.index': 'admin.under-construction.index',
        };

        if (routeMap[pageIdentifier] && route().has(routeMap[pageIdentifier])) {
            return route(routeMap[pageIdentifier]);
        }
    } catch (error) {
        console.error('Error getting route for page:', pageIdentifier, error);
    }

    return null;
};

const filterArticles = () => {
    const query = searchQuery.value.toLowerCase();

    if (!query) {
        filteredFrontendArticles.value = frontendArticles.value;
        filteredAdminArticles.value = adminArticles.value;
        return;
    }

    filteredFrontendArticles.value = frontendArticles.value.filter((article) => {
        const inTitle = article.title.toLowerCase().includes(query);
        const inContent = article.content.toLowerCase().includes(query);
        const inRoute = article.page_identifier.toLowerCase().includes(query);
        const inSections =
            article.sections?.some((section) => section.title.toLowerCase().includes(query) || section.content.toLowerCase().includes(query)) ||
            false;

        return inTitle || inContent || inRoute || inSections;
    });

    filteredAdminArticles.value = adminArticles.value.filter((article) => {
        const inTitle = article.title.toLowerCase().includes(query);
        const inContent = article.content.toLowerCase().includes(query);
        const inRoute = article.page_identifier.toLowerCase().includes(query);
        const inSections =
            article.sections?.some((section) => section.title.toLowerCase().includes(query) || section.content.toLowerCase().includes(query)) ||
            false;

        return inTitle || inContent || inRoute || inSections;
    });
};

const clearSearch = () => {
    searchQuery.value = '';
    filterArticles();
};

onMounted(() => {
    if (modalElement.value && window.bootstrap) {
        modal = new window.bootstrap.Modal(modalElement.value);
    }

    // Initialize filtered articles
    filterArticles();
});
</script>

<style scoped>
/* Ensure white backgrounds throughout */
.card {
    background-color: #ffffff;
    border: 1px solid #dee2e6;
}

.card-header {
    background-color: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
    color: #212529;
}

.card-header.bg-primary.bg-opacity-10 {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.card-header.bg-warning.bg-opacity-10 {
    background-color: rgba(255, 193, 7, 0.1) !important;
}

.card-body {
    background-color: #ffffff;
    color: #212529;
}

.list-group-item {
    transition: all 0.2s ease;
    border-left: 3px solid transparent;
    background-color: transparent;
    color: #212529;
}

.list-group-item:hover {
    background-color: #f8f9fa;
    border-left-color: #0d6efd;
}

.list-group-item h6 {
    color: #212529;
}

.list-group-item .text-muted {
    color: #6c757d !important;
}

/* Modal styling */
.modal-content {
    background-color: #ffffff;
    color: #212529;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-body {
    background-color: #ffffff;
}

.modal-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

.article-content {
    line-height: 1.6;
    color: #212529;
}

.section {
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.section:last-child {
    border-bottom: none;
}

.section-title {
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #0d6efd !important;
}

.section-content {
    color: #212529;
}

.screenshot {
    text-align: center;
}

code {
    background-color: #f8f9fa;
    padding: 2px 4px;
    border-radius: 3px;
    font-size: 0.875em;
    color: #d63384;
}

/* Input styling */
.form-control {
    background-color: #ffffff;
    color: #212529;
    border: 1px solid #ced4da;
}

.form-control:focus {
    background-color: #ffffff;
    color: #212529;
    border-color: #86b7fe;
}

.input-group-text {
    background-color: #e9ecef;
    color: #212529;
    border: 1px solid #ced4da;
}

.input-group-lg .form-control {
    font-size: 1rem;
}

.btn-sm {
    font-size: 0.875rem;
}

/* Extra small buttons */
.btn-xs {
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    line-height: 1.2;
    border-radius: 0.2rem;
}

.btn-xs i {
    font-size: 0.75rem;
}

/* Clickable article titles */
.article-title {
    cursor: pointer;
    transition: color 0.2s ease;
}

.article-title:hover {
    color: #0d6efd;
    text-decoration: underline;
}

/* Badge styling */
.badge.bg-secondary {
    background-color: #6c757d !important;
    color: #ffffff;
}

/* Ensure proper text visibility */
h1,
h3,
h4,
h5,
h6,
p {
    color: #212529;
}

/* Search results text */
.text-center.text-muted {
    color: #6c757d !important;
}
</style>
