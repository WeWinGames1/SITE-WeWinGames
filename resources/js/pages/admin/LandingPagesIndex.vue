<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import { ref } from 'vue';

const props = defineProps<{ pages: Array<any> }>();
const showQR = ref(false);
const qrUrl = ref('');
const qrTitle = ref('');

function deletePage(id: number) {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.landing-pages.destroy', id));
    }
}
function openQR(page) {
    qrUrl.value = `${window.location.origin}/landing/${page.slug}`;
    qrTitle.value = page.title;
    showQR.value = true;
}
function closeQR() {
    showQR.value = false;
}
</script>
<template>
    <AppLayout :breadcrumbs="{ title: 'Manage Pages', href: route('admin.landing-pages.index') }">
    <Head title="Manage Pages" />
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Landing Pages</h1>
        <Link :href="route('admin.landing-pages.create')" class="bg-indigo-600 text-white px-4 py-2 rounded mb-4 inline-block">New Page</Link>
        <table class="w-full mt-4 border">
          <thead>
            <tr>
              <th class="text-left p-3">Title</th>
              <th class="text-left p-3">Slug</th>
              <th class="text-left p-3">Published</th>
              <th class="text-left p-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="page in props.pages" :key="page.id">
              <td class="p-3">{{ page.title }}</td>
              <td class="p-3">{{ page.slug }}</td>
              <td class="p-3">{{ page.published ? 'Yes' : 'No' }}</td>
              <td class="p-3">
                <div class="flex flex-wrap gap-3">
                  <Link :href="route('admin.landing-pages.edit', page.id)" class="text-blue-500 hover:underline">Edit</Link>
                  <button @click="deletePage(page.id)" class="text-red-500 hover:underline">Delete</button>
                  <a
                    :href="`/landing/${page.slug}`"
                    class="text-green-500 hover:underline"
                    target="_blank"
                    rel="noopener"
                  >
                    View
                  </a>
                  <button @click="openQR(page)" class="text-indigo-500 hover:underline">Generate QR</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="showQR" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div class="bg-white rounded-lg p-6 flex flex-col items-center relative">
            <button @click="closeQR" class="absolute top-2 right-2 text-gray-500 hover:text-black text-xl">&times;</button>
            <h2 class="text-lg font-bold mb-2 text-black">{{ qrTitle }}</h2>
            <QrcodeVue :value="qrUrl" :size="200" />
            <div class="mt-2 text-xs text-gray-700">{{ qrUrl }}</div>
          </div>
        </div>
    </div>
    </AppLayout>
</template>