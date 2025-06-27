<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
const props = defineProps<{ pages: Array<any> }>();

function deletePage(id: number) {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.pages.destroy', id));
    }
}
</script>
<template>
    <AppLayout :breadcrumbs="{ title: 'Manage Pages', href: route('admin.pages.index') }">
    <Head title="Manage Pages" />
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">Pages</h1>
        <Link :href="route('admin.pages.create')" class="bg-indigo-600 text-white px-4 py-2 rounded mb-4 inline-block">New Page</Link>
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
                  <Link :href="route('admin.pages.edit', page.id)" class="text-blue-500 hover:underline">Edit</Link>
                  <button @click="deletePage(page.id)" class="text-red-500 hover:underline">Delete</button>
                  <a
                    :href="`/pages/${page.slug}`"
                    class="text-green-500 hover:underline"
                    target="_blank"
                    rel="noopener"
                  >
                    View
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
    </div>
    </AppLayout>
</template>