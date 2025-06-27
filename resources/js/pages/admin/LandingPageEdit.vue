<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import AppLayout from '@/layouts/AppLayout.vue';
import QrcodeVue from 'qrcode.vue';

const props = defineProps<{ page: any | null }>();
const form = useForm({
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    featured_image: null, // for file upload
    published: props.page?.published ?? true,
});
const preview = ref(props.page?.featured_image || '');

// Tiptap setup
const editor = ref(new Editor({
    content: form.content,
    extensions: [StarterKit],
    onUpdate: ({ editor }) => {
        form.content = editor.getHTML();
    },
}));

// Keep editor in sync if editing existing page
watch(() => props.page?.content, (val) => {
    if (val && editor.value) editor.value.commands.setContent(val);
});

function submit() {
    if (props.page) {
        form.put(route('admin.landing-pages.update', props.page.id));
    } else {
        form.post(route('admin.landing-pages.store'));
    }
}

function onFileChange(e) {
    const file = e.target.files[0];
    if (file) {
        form.featured_image = file;
        preview.value = URL.createObjectURL(file);
    }
}

const showQR = ref(false);
const qrUrl = ref('');

function generateQR() {
    qrUrl.value = `${window.location.origin}/landing/${form.slug}`;
    showQR.value = true;
}
</script>

<template>
    <AppLayout :breadcrumbs="{ title: props.page ? 'Edit Landing Page' : 'Create Landing Page', href: route('admin.pages.index') }">
    <Head :title="props.page ? 'Edit Landing Page' : 'Create Landing Page'" />
    <div class="max-w-3xl mx-auto p-6">
        <h1 class="text-2xl font-bold mb-4">{{ props.page ? 'Edit' : 'Create' }} Landing Page</h1>
        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <label class="block mb-1">Title</label>
                <input v-model="form.title" class="w-full rounded border-gray-300" required />
            </div>
            <div>
                <label class="block mb-1">Slug</label>
                <input v-model="form.slug" class="w-full rounded border-gray-300" required />
            </div>
            <div>
                <label class="block mb-1">Content</label>
                <div class="border rounded bg-white text-black">
                    <EditorContent :editor="editor" class="min-h-[200px] p-2" />
                </div>
            </div>
            <div>
                <label><input type="checkbox" v-model="form.published" /> Published</label>
            </div>
            <div>
                <label class="block mb-1">Featured Image</label>
                <input type="file" accept="image/*" @change="onFileChange" />
                <div v-if="preview" class="mt-2">
                    <img :src="preview" alt="Preview" class="max-h-40 rounded" />
                </div>
                <div v-else-if="props.page?.featured_image" class="mt-2">
                    <img :src="props.page.featured_image" alt="Current" class="max-h-40 rounded" />
                </div>
            </div>
            <button class="bg-indigo-600 text-white px-4 py-2 rounded" type="submit">Save</button>
        </form>
        
    </div>
</AppLayout>
</template>