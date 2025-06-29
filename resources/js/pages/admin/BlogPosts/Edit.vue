<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import { Button as PrimaryButton } from "@/components/ui/button";
import { Button as SecondaryButton } from "@/components/ui/button";
import { Input as TextInput } from "@/components/ui/input";
import { Label as InputLabel } from "@/components/ui/label";
import InputError from '@/components/InputError.vue';
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';

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
    content: string;
    featured_image: string | null;
    featured_image_url: string | null;
    category: string;
    tags: string[];
    is_published: boolean;
    published_at: string | null;
    seo_title: string | null;
    seo_description: string | null;
    seo_keywords: string | null;
    author: Author;
    created_at: string;
    updated_at: string;
    views_count: number;
}

interface Props {
    post: Post;
    categories: Record<string, string>;
    popularTags: string[];
}

const props = defineProps<Props>();

// Form
const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    content: props.post.content,
    featured_image: null as File | null,
    category: props.post.category,
    tags: props.post.tags || [],
    is_published: props.post.is_published,
    published_at: props.post.published_at ? props.post.published_at.slice(0, 16) : '',
    seo_title: props.post.seo_title || '',
    seo_description: props.post.seo_description || '',
    seo_keywords: props.post.seo_keywords || '',
});

// State
const showSlugInput = ref(true);
const featuredImagePreview = ref<string | null>(props.post.featured_image_url);
const newTag = ref('');
const showSeoFields = ref(!!props.post.seo_title || !!props.post.seo_description || !!props.post.seo_keywords);

// Tiptap editor setup
const editor = useEditor({
    content: props.post.content,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [2, 3, 4]
            }
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: {
                class: 'text-blue-600 hover:text-blue-800 underline'
            }
        }),
        Image.configure({
            HTMLAttributes: {
                class: 'max-w-full h-auto rounded-lg'
            }
        }),
        Placeholder.configure({
            placeholder: 'Write your blog post content here...'
        })
    ],
    onUpdate: ({ editor }) => {
        form.content = editor.getHTML();
    }
});

// Add link function
const addLink = () => {
    const url = prompt('Enter URL:');
    if (url) {
        editor.value?.chain().focus().setLink({ href: url }).run();
    }
};

// Remove link function
const removeLink = () => {
    editor.value?.chain().focus().unsetLink().run();
};

// Add image function
const addImage = () => {
    const url = prompt('Enter image URL:');
    if (url) {
        editor.value?.chain().focus().setImage({ src: url }).run();
    }
};

// Computed
const characterCounts = computed(() => ({
    title: form.title.length,
    excerpt: form.excerpt.length,
    seoTitle: form.seo_title.length,
    seoDescription: form.seo_description.length,
}));

// Methods
function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.featured_image = file;
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            featuredImagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    form.featured_image = null;
    featuredImagePreview.value = null;
}

function addTag() {
    if (newTag.value.trim() && !form.tags.includes(newTag.value.trim())) {
        form.tags.push(newTag.value.trim());
        newTag.value = '';
    }
}

function removeTag(index: number) {
    form.tags.splice(index, 1);
}

function submit() {
    form.post(route('admin.blog-posts.update', props.post.id), {
        _method: 'put',
    });
}

function saveAsDraft() {
    form.is_published = false;
    submit();
}

function publish() {
    form.is_published = true;
    if (!form.published_at) {
        form.published_at = new Date().toISOString().slice(0, 16);
    }
    submit();
}

// Auto-fill SEO title from title if empty
function updateSeoTitle() {
    if (!form.seo_title && form.title) {
        form.seo_title = form.title.slice(0, 60);
    }
}

// Auto-fill SEO description from excerpt if empty
function updateSeoDescription() {
    if (!form.seo_description && form.excerpt) {
        form.seo_description = form.excerpt.slice(0, 160);
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Blog Posts', href: route('admin.blog-posts.index') },
        { title: 'Edit Post' }
    ]">
        <Head title="Edit Blog Post" />
        
        <div class="max-w-4xl mx-auto p-6">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Edit Blog Post</h1>
                    <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        <span>Created by {{ post.author.name }} on {{ new Date(post.created_at).toLocaleDateString() }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ post.views_count }} views</span>
                    </div>
                </div>
                <a 
                    :href="route('blog.show', post.slug)" 
                    target="_blank"
                    class="text-blue-600 hover:text-blue-800"
                >
                    View Post →
                </a>
            </div>
            
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Title -->
                <div>
                    <InputLabel for="title" value="Post Title" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">The main headline for your blog post</p>
                    <TextInput 
                        v-model="form.title" 
                        id="title" 
                        class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600" 
                        placeholder="Enter post title"
                        @blur="updateSeoTitle"
                        required 
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ characterCounts.title }} characters</div>
                    <InputError :message="form.errors.title" />
                </div>
                
                <!-- Slug -->
                <div>
                    <InputLabel for="slug" value="URL Slug" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">The URL path for this post (e.g., /blog/your-post-slug)</p>
                    <TextInput 
                        v-model="form.slug" 
                        id="slug" 
                        class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600" 
                        placeholder="custom-url-slug"
                        pattern="[a-z0-9-]+"
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Only lowercase letters, numbers, and hyphens allowed</div>
                    <InputError :message="form.errors.slug" />
                </div>
                
                <!-- Excerpt -->
                <div>
                    <InputLabel for="excerpt" value="Post Excerpt" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">A short summary that appears in post listings and search results</p>
                    <textarea 
                        v-model="form.excerpt" 
                        id="excerpt" 
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm dark:bg-gray-800 dark:text-gray-100"
                        rows="3"
                        placeholder="Brief description of the post"
                        @blur="updateSeoDescription"
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ characterCounts.excerpt }}/500 characters</div>
                    <InputError :message="form.errors.excerpt" />
                </div>
                
                <!-- Content -->
                <div>
                    <InputLabel for="content" value="Content" class="mb-2" />
                    
                    <!-- Tiptap Editor Toolbar -->
                    <div v-if="editor" class="border border-gray-300 dark:border-gray-600 rounded-t-md bg-gray-50 dark:bg-gray-800 p-2 flex flex-wrap items-center gap-1">
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleBold().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('bold') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 font-bold dark:text-gray-100"
                            title="Bold"
                        >
                            B
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleItalic().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('italic') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 italic dark:text-gray-100"
                            title="Italic"
                        >
                            I
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleStrike().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('strike') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 line-through dark:text-gray-100"
                            title="Strikethrough"
                        >
                            S
                        </button>
                        
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('heading', { level: 2 }) }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Heading 2"
                        >
                            H2
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('heading', { level: 3 }) }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Heading 3"
                        >
                            H3
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleHeading({ level: 4 }).run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('heading', { level: 4 }) }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Heading 4"
                        >
                            H4
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().setParagraph().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('paragraph') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Paragraph"
                        >
                            P
                        </button>
                        
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleBulletList().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('bulletList') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 dark:text-gray-100"
                            title="Bullet List"
                        >
                            •
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleOrderedList().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('orderedList') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 dark:text-gray-100"
                            title="Numbered List"
                        >
                            1.
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().toggleBlockquote().run()"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('blockquote') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 dark:text-gray-100"
                            title="Blockquote"
                        >
                            "
                        </button>
                        
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        
                        <button
                            type="button"
                            @click="addLink"
                            :class="{ 'bg-gray-200 dark:bg-gray-700': editor.isActive('link') }"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Add Link"
                        >
                            🔗
                        </button>
                        <button
                            v-if="editor.isActive('link')"
                            type="button"
                            @click="removeLink"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm text-red-600 dark:text-red-400"
                            title="Remove Link"
                        >
                            ✕
                        </button>
                        <button
                            type="button"
                            @click="addImage"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Add Image"
                        >
                            🖼️
                        </button>
                        
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>
                        
                        <button
                            type="button"
                            @click="editor.chain().focus().undo().run()"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Undo"
                        >
                            ↶
                        </button>
                        <button
                            type="button"
                            @click="editor.chain().focus().redo().run()"
                            class="p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 text-sm dark:text-gray-100"
                            title="Redo"
                        >
                            ↷
                        </button>
                    </div>
                    
                    <!-- Tiptap Editor Content -->
                    <EditorContent 
                        :editor="editor" 
                        class="prose prose-lg dark:prose-invert max-w-none border border-gray-300 dark:border-gray-600 rounded-b-md p-4 min-h-[500px] focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 dark:bg-gray-800 dark:text-gray-100"
                    />
                    <InputError :message="form.errors.content" />
                </div>
                
                <!-- Featured Image -->
                <div>
                    <InputLabel for="featured_image" value="Featured Image" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Main image displayed at the top of your post and in post listings</p>
                    <div v-if="featuredImagePreview" class="mb-4">
                        <img :src="featuredImagePreview" class="max-w-md rounded-lg shadow" />
                        <button 
                            type="button" 
                            @click="removeImage"
                            class="mt-2 text-sm text-red-600 hover:text-red-800"
                        >
                            Remove image
                        </button>
                    </div>
                    <input 
                        type="file" 
                        id="featured_image" 
                        accept="image/*"
                        @change="handleImageChange"
                        class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900 file:text-blue-700 dark:file:text-blue-200 hover:file:bg-blue-100 dark:hover:file:bg-blue-800"
                    />
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Maximum file size: 5MB. Recommended size: 1200x630px</div>
                    <InputError :message="form.errors.featured_image" />
                </div>
                
                <!-- Category and Tags -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="category" value="Category" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Select the main topic area for this post</p>
                        <select v-model="form.category" id="category" class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600" required>
                            <option value="">Select a category</option>
                            <option v-for="(label, value) in categories" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.category" />
                    </div>
                    
                    <div>
                        <InputLabel value="Tags" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Add keywords to help readers find related content</p>
                        <div class="flex space-x-2 mb-2">
                            <TextInput 
                                v-model="newTag" 
                                @keyup.enter.prevent="addTag"
                                placeholder="Add a tag" 
                                class="flex-1 dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
                            />
                            <SecondaryButton type="button" @click="addTag">Add</SecondaryButton>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span 
                                v-for="(tag, index) in form.tags" 
                                :key="index"
                                class="inline-flex items-center bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm px-3 py-1 rounded-full"
                            >
                                {{ tag }}
                                <button 
                                    type="button" 
                                    @click="removeTag(index)"
                                    class="ml-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300"
                                >
                                    ×
                                </button>
                            </span>
                        </div>
                        <div v-if="popularTags.length > 0" class="mt-2">
                            <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Popular tags:</div>
                            <div class="flex flex-wrap gap-1">
                                <button 
                                    v-for="tag in popularTags.slice(0, 10)" 
                                    :key="tag"
                                    type="button"
                                    @click="newTag = tag; addTag()"
                                    class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                >
                                    {{ tag }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Publishing Options -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <h3 class="font-medium mb-4 text-gray-900 dark:text-gray-100">Publishing Options</h3>
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                v-model="form.is_published" 
                                class="rounded mr-2"
                            />
                            <span class="text-gray-700 dark:text-gray-300">Publish this post</span>
                        </label>
                        
                        <div v-if="form.is_published">
                            <InputLabel for="published_at" value="Publish Date" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Leave empty for immediate publishing when you hit save</p>
                            <input 
                                type="datetime-local" 
                                v-model="form.published_at" 
                                id="published_at"
                                class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm"
                            />
                            <InputError :message="form.errors.published_at" />
                        </div>
                    </div>
                </div>
                
                <!-- SEO Options -->
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <button 
                        type="button" 
                        @click="showSeoFields = !showSeoFields"
                        class="flex items-center justify-between w-full text-gray-900 dark:text-gray-100"
                    >
                        <h3 class="font-medium text-gray-900 dark:text-gray-100">SEO Options</h3>
                        <span class="text-gray-500 dark:text-gray-400">{{ showSeoFields ? '−' : '+' }}</span>
                    </button>
                    
                    <div v-if="showSeoFields" class="mt-4 space-y-4">
                        <div>
                            <InputLabel for="seo_title" value="SEO Title" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Title that appears in search engine results (60 characters max)</p>
                            <TextInput 
                                v-model="form.seo_title" 
                                id="seo_title" 
                                class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
                                placeholder="SEO optimized title"
                                maxlength="60"
                            />
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ characterCounts.seoTitle }}/60 characters</div>
                            <InputError :message="form.errors.seo_title" />
                        </div>
                        
                        <div>
                            <InputLabel for="seo_description" value="SEO Description" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Summary that appears in search engine results (160 characters max)</p>
                            <textarea 
                                v-model="form.seo_description" 
                                id="seo_description" 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100 shadow-sm"
                                rows="3"
                                placeholder="Meta description for search engines"
                                maxlength="160"
                            />
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ characterCounts.seoDescription }}/160 characters</div>
                            <InputError :message="form.errors.seo_description" />
                        </div>
                        
                        <div>
                            <InputLabel for="seo_keywords" value="SEO Keywords" class="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300" />
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Keywords that describe your content (comma-separated)</p>
                            <TextInput 
                                v-model="form.seo_keywords" 
                                id="seo_keywords" 
                                class="w-full dark:bg-gray-800 dark:text-gray-100 dark:border-gray-600"
                                placeholder="keyword1, keyword2, keyword3"
                            />
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Comma-separated keywords</div>
                            <InputError :message="form.errors.seo_keywords" />
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200 dark:border-gray-700">
                    <SecondaryButton :href="route('admin.blog-posts.index')">
                        Cancel
                    </SecondaryButton>
                    <div class="flex space-x-3">
                        <SecondaryButton 
                            type="button" 
                            @click="saveAsDraft"
                            :disabled="form.processing"
                        >
                            Save as Draft
                        </SecondaryButton>
                        <PrimaryButton 
                            type="submit"
                            :disabled="form.processing"
                        >
                            Update Post
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>