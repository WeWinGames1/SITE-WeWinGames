<template>
    <div class="knowledgebase-sidebar" :class="{ show: isVisible }">
        <div class="sidebar-header">
            <h5 class="mb-0">Help & Documentation</h5>
            <button type="button" class="btn-close" @click="close" aria-label="Close"></button>
        </div>

        <div class="sidebar-content">
            <div v-if="loading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>

            <div v-else-if="article" class="article-content">
                <h6 class="article-title">{{ article.title }}</h6>
                <div class="article-main-content" v-html="article.content"></div>

                <div v-if="article.sections && article.sections.length > 0" class="sections">
                    <div v-for="section in article.sections" :key="section.title" class="section">
                        <h6 class="section-title">{{ section.title }}</h6>
                        <div class="section-content" v-html="section.content"></div>
                    </div>
                </div>

                <div v-if="article.screenshot_path" class="screenshot mt-3">
                    <img :src="article.screenshot_path" :alt="`Screenshot for ${article.title}`" class="img-fluid rounded" />
                </div>
            </div>

            <div v-else class="no-article">
                <p class="text-muted">No documentation available for this page.</p>
                <p class="text-muted small">If you need help with this page, please contact support.</p>
            </div>

            <div class="sidebar-footer">
                <Link :href="route('admin.knowledgebase.index')" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-book me-1"></i>
                    View All Documentation
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    isVisible: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const page = usePage();
const loading = ref(false);
const article = ref<any>(null);

const close = () => {
    emit('close');
};

const loadArticleForCurrentPage = async () => {
    loading.value = true;
    article.value = null;

    try {
        // Get current route name
        const routeName = page.props.route_name as string;

        if (!routeName) {
            loading.value = false;
            return;
        }

        // Use the general knowledgebase API route that works from both admin and frontend
        const response = await axios.get(route('knowledgebase.api.page'), {
            params: {
                page_identifier: routeName,
            },
        });

        if (response.data.article) {
            article.value = response.data.article;
        }
    } catch (error) {
        console.error('Failed to load knowledgebase article:', error);
    } finally {
        loading.value = false;
    }
};

// Load article when sidebar becomes visible
watch(
    () => props.isVisible,
    (newValue) => {
        if (newValue) {
            loadArticleForCurrentPage();
        }
    },
);

// Handle escape key
const handleEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isVisible) {
        close();
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleEscape);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape);
});
</script>

<style scoped>
.knowledgebase-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: #ffffff;
    box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
    transition: right 0.3s ease-in-out;
    z-index: 1040;
    display: flex;
    flex-direction: column;
}

.knowledgebase-sidebar.show {
    right: 0;
}

.sidebar-header {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background-color: #f8f9fa;
}

.sidebar-header h5 {
    color: #212529 !important;
    margin: 0;
}

.btn-close {
    opacity: 0.8;
}

.btn-close:hover {
    opacity: 1;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem;
    background-color: #ffffff;
}

.article-title {
    color: #212529;
    margin-bottom: 1rem;
    font-weight: 600;
}

.article-main-content {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1.5rem;
}

.sections {
    margin-top: 2rem;
}

.section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e9ecef;
}

.section:last-child {
    border-bottom: none;
}

.section-title {
    color: #0d6efd;
    font-size: 1rem;
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.section-content {
    color: #6c757d;
    line-height: 1.6;
    font-size: 0.95rem;
}

.no-article {
    text-align: center;
    padding: 3rem 1rem;
}

.no-article .text-muted {
    color: #6c757d !important;
}

.sidebar-footer {
    padding: 1rem;
    border-top: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.screenshot {
    margin-top: 1rem;
}

/* Ensure text is readable */
.knowledgebase-sidebar {
    color: #212529 !important;
}

.knowledgebase-sidebar p,
.knowledgebase-sidebar div,
.knowledgebase-sidebar span,
.knowledgebase-sidebar h1,
.knowledgebase-sidebar h2,
.knowledgebase-sidebar h3,
.knowledgebase-sidebar h4,
.knowledgebase-sidebar h5,
.knowledgebase-sidebar h6 {
    color: #212529 !important;
}

/* Spinner color */
.spinner-border.text-primary {
    color: #0d6efd !important;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .knowledgebase-sidebar {
        width: 100%;
        right: -100%;
    }
}
</style>
