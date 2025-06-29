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
  posts: Post[]
}>();

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}
</script>

<template>
    <WelcomeLayout>
        <Head title="Betting Education" />
        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200">
            <div class="container mx-auto px-4 py-16 max-w-6xl">
                <!-- Header Section -->
                <div class="text-center mb-16">
                    <h1 class="text-5xl font-bold mb-6 text-white">Betting Education</h1>
                    <p class="text-xl mb-4 text-indigo-300">
                        Master the fundamentals of sports betting with our comprehensive educational resources
                    </p>
                    <p class="text-lg text-gray-300 max-w-4xl mx-auto">
                        When it comes to learning about sports betting it is hard to know who to trust. Often what may look like educational content is just a guise to funnel your attention to a sponsoring sportsbook. At <span class="font-bold text-indigo-400">We Win Games</span>, we believe a more knowledgeable sports bettor is good for everyone. It provides a richer and more sustainable market for all to enjoy.
                    </p>
                </div>

                <!-- Posts Section -->
                <div v-if="posts.length > 0">
                    <h2 class="text-3xl font-semibold mb-8 text-white">Educational Articles</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <article
                            v-for="post in posts"
                            :key="post.id"
                            class="bg-gray-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:transform hover:scale-105"
                        >
                            <!-- Featured Image -->
                            <div class="aspect-video bg-gray-700 overflow-hidden">
                                <img
                                    v-if="post.featured_image_url"
                                    :src="post.featured_image_url"
                                    :alt="post.title"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-indigo-600 to-purple-600"
                                >
                                    <svg class="w-16 h-16 text-white opacity-60" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-6">
                                <!-- Title -->
                                <Link
                                    :href="`/blog/${post.slug}`"
                                    class="block text-xl font-bold text-white hover:text-indigo-400 transition-colors mb-3 line-clamp-2"
                                >
                                    {{ post.title }}
                                </Link>

                                <!-- Excerpt -->
                                <p class="text-gray-300 text-sm mb-4 line-clamp-3">
                                    {{ post.excerpt }}
                                </p>

                                <!-- Meta Info -->
                                <div class="flex items-center justify-between text-xs text-gray-400">
                                    <div class="flex items-center space-x-4">
                                        <span>{{ formatDate(post.published_at) }}</span>
                                        <span>{{ post.reading_time }} min read</span>
                                    </div>
                                    <div class="flex items-center space-x-1">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                        <span>{{ post.views_count }}</span>
                                    </div>
                                </div>

                                <!-- Read More Button -->
                                <Link
                                    :href="`/blog/${post.slug}`"
                                    class="inline-block mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                                >
                                    Read Article
                                </Link>
                            </div>
                        </article>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="text-center py-16">
                    <div class="mb-8">
                        <svg class="w-24 h-24 mx-auto text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <h3 class="text-2xl font-semibold text-white mb-4">No Educational Content Yet</h3>
                    <p class="text-gray-400 text-lg">
                        We're working on creating comprehensive betting education content. Check back soon!
                    </p>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>