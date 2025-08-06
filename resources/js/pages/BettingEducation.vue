<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface Author {
    id: number;
    name: string;
}

interface Post {
    id: number;
    title: string;
    slug: string;
    excerpt: string;
    featured_image?: string | null;
    featured_image_url?: string | null;
    published_at: string;
    reading_time: number;
    author: Author;
    views_count: number;
}

const props = defineProps<{
    posts: Post[];
}>();

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    });
}
</script>

<template>
    <WelcomeLayout>
        <Head title="Betting Education" />
        <div class="min-vh-100" style="background: linear-gradient(180deg, #1a2332 0%, #0a1628 100%)">
            <div class="container-fluid px-4 px-lg-5 py-5">
                <!-- Header Section -->
                <div class="text-center mb-5 pt-5">
                    <h1 class="display-3 fw-bold text-white mb-4">Betting Education</h1>
                    <p class="fs-4 mb-4 text-primary">Master the fundamentals of sports betting with our comprehensive educational resources</p>
                    <p class="fs-5 text-gray-light mx-auto" style="max-width: 800px">
                        When it comes to learning about sports betting it is hard to know who to trust. Often what may look like educational content
                        is just a guise to funnel your attention to a sponsoring sportsbook. At
                        <span class="fw-bold text-primary">We Win Games</span>, we believe a more knowledgeable sports bettor is good for everyone. It
                        provides a richer and more sustainable market for all to enjoy.
                    </p>
                </div>

                <!-- Posts Section -->
                <div v-if="posts.length > 0">
                    <h2 class="h3 fw-semibold mb-4 text-white">Educational Articles</h2>
                    <div class="row g-4">
                        <div v-for="post in posts" :key="post.id" class="col-12 col-md-6 col-lg-4">
                            <article class="card h-100" style="background-color: #1a2332; border: 1px solid #2e4057; transition: all 0.3s ease">
                                <!-- Featured Image -->
                                <div class="card-img-top position-relative" style="height: 200px; overflow: hidden; background-color: #0a1628">
                                    <img
                                        v-if="post.featured_image_url"
                                        :src="post.featured_image_url"
                                        :alt="post.title"
                                        class="w-100 h-100"
                                        style="object-fit: cover"
                                    />
                                    <div
                                        v-else
                                        class="w-100 h-100 d-flex align-items-center justify-content-center"
                                        style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%)"
                                    >
                                        <i class="bi bi-image fs-1 text-white opacity-50"></i>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="card-body d-flex flex-column">
                                    <!-- Title -->
                                    <Link
                                        :href="`/blog/${post.slug}`"
                                        class="h5 fw-bold text-white text-decoration-none mb-3"
                                        style="
                                            line-height: 1.4;
                                            display: -webkit-box;
                                            -webkit-line-clamp: 2;
                                            -webkit-box-orient: vertical;
                                            overflow: hidden;
                                        "
                                    >
                                        {{ post.title }}
                                    </Link>

                                    <!-- Excerpt -->
                                    <p
                                        class="text-gray-light small mb-4"
                                        style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden"
                                    >
                                        {{ post.excerpt }}
                                    </p>

                                    <!-- Meta Info -->
                                    <div class="d-flex justify-content-between align-items-center small text-muted mt-auto">
                                        <div class="d-flex gap-3">
                                            <span>{{ formatDate(post.published_at) }}</span>
                                            <span>{{ post.reading_time }} min read</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <i class="bi bi-eye"></i>
                                            <span>{{ post.views_count }}</span>
                                        </div>
                                    </div>

                                    <!-- Read More Button -->
                                    <Link :href="`/blog/${post.slug}`" class="btn btn-primary btn-sm mt-3"> Read Article </Link>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-book text-muted" style="font-size: 5rem"></i>
                    </div>
                    <h3 class="h3 fw-semibold text-white mb-3">No Educational Content Yet</h3>
                    <p class="text-gray-light fs-5">We're working on creating comprehensive betting education content. Check back soon!</p>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>

<style scoped>
.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

.text-decoration-none:hover {
    color: var(--bs-primary) !important;
}
</style>
