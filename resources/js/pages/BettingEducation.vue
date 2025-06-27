<script setup lang="ts">
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps<{
  pages: Array<{
    id: number,
    title: string,
    slug: string,
    featured_image?: string | null,
    published: boolean,
  }>
}>();

// Filter only published pages
const links = props.pages
  .filter(page => page.published)
  .map(page => ({
    title: page.title,
    href: `/pages/${page.slug}`,
    image: page.featured_image ? `${page.featured_image}` : null,
  }));
</script>

<template>
    <WelcomeLayout>
        <Head title="Betting Education" >
        </Head>
        <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-gray-900 to-black text-gray-200">
            <div class="container mx-auto px-4 py-16 max-w-4xl">
                <h1 class="text-4xl font-bold mb-8 text-white">Betting Education</h1>
                <p class="mb-6 text-lg">
                    <span class="font-bold text-indigo-400">Resources to help you become a better sports bettor</span>
                </p>
                <p class="mb-8">
                    When it comes to learning about sports betting it is hard to know who to trust. Often what may look like educational content is just a guise to funnel your attention to a sponsoring sportsbook. At <span class="font-bold text-indigo-400">We Win Games</span>, we believe a more knowledgeable sports bettor is good for everyone. It provides a richer and more sustainable market for all to enjoy.
                </p>

                <div v-for="section in sections" :key="section.title" class="mb-12">
                  <h2 class="text-2xl font-semibold mb-6 text-white">{{ section.title }}</h2>
                  <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                    <div
                      v-for="link in section.links"
                      :key="link.title"
                      class="cursor-pointer bg-gray-800 hover:bg-indigo-700 transition-colors rounded-lg shadow p-6 flex items-center justify-center text-white text-lg font-semibold text-center relative overflow-hidden"
                      @click="() => link.external ? window.open(link.href, '_blank') : $inertia.visit(link.href)"
                      style="min-height: 120px;"
                    >
                      <!-- Card background image from link.img -->
                      <img
                        v-if="link.image"
                        :src="link.image"
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover opacity-30 pointer-events-none"
                      />
                      <span class="relative z-10">{{ link.title }}</span>
                    </div>
                  </div>
                </div>

                <div class="mb-12">
                  <h2 class="text-2xl font-semibold mb-6 text-white">Additional Resources</h2>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div
                      v-for="link in links"
                      :key="link.href"
                      class="bg-gray-800 rounded-lg shadow p-6 flex flex-col items-start hover:bg-gray-700 transition"
                    >
                      <img
                        v-if="link.image"
                        :src="link.image"
                        alt=""
                        class="mb-4 w-full h-40 object-cover rounded"
                      />
                      <Link :href="link.href" class="text-xl font-semibold text-indigo-400 hover:underline mb-2">
                        {{ link.title }}
                      </Link>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </WelcomeLayout>
</template>