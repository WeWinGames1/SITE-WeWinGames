<script setup lang="ts">
import MediaPicker from '@/components/MediaPicker.vue';
import { useToast } from '@/composables/useToast';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Image from '@tiptap/extension-image';
import TiptapLink from '@tiptap/extension-link';
import Placeholder from '@tiptap/extension-placeholder';
import StarterKit from '@tiptap/starter-kit';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import { computed, onMounted, ref } from 'vue';

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

// Initialize toast
const { showToast } = useToast();

// Helper function to format date for datetime-local input
const formatDateForInput = (dateString: string | null): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    // Format as YYYY-MM-DDTHH:mm
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Form
const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt,
    content: props.post.content,
    featured_image: null as File | null,
    featured_image_media_id: null as number | null,
    category: props.post.category,
    tags: props.post.tags,
    is_published: props.post.is_published,
    published_at: formatDateForInput(props.post.published_at),
    seo_title: props.post.seo_title || '',
    seo_description: props.post.seo_description || '',
    seo_keywords: props.post.seo_keywords || '',
});

// State
const showSlugInput = ref(false);
const featuredImagePreview = ref<string | null>(props.post.featured_image_url);
const newTag = ref('');
const showSeoFields = ref(false);
const showSourceCode = ref(false);
const sourceCode = ref('');
const errorMessage = ref<string | null>(null);
const fileInputKey = ref(0); // Key to force file input reset
const showMediaPicker = ref(false);
const showContentMediaPicker = ref(false);

// Tiptap editor setup
const editor = useEditor({
    content: props.post.content,
    extensions: [
        StarterKit.configure({
            heading: {
                levels: [1, 2, 3],
            },
        }),
        TiptapLink.configure({
            openOnClick: false,
        }),
        Image,
        Placeholder.configure({
            placeholder: 'Start writing your blog post...',
        }),
    ],
    onUpdate: ({ editor }) => {
        form.content = editor.getHTML();
        if (showSourceCode.value) {
            sourceCode.value = editor.getHTML();
        }
    },
});

onMounted(() => {
    if (props.post.seo_title || props.post.seo_description || props.post.seo_keywords) {
        showSeoFields.value = true;
    }
});

// Auto-generate slug from title
function updateSlug() {
    if (!showSlugInput.value) {
        form.slug = form.title
            .toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-')
            .trim();
    }
}

// Auto-generate SEO title from title
function updateSeoTitle() {
    if (!form.seo_title) {
        form.seo_title = form.title;
    }
}

// Auto-generate SEO description from excerpt
function updateSeoDescription() {
    if (!form.seo_description) {
        form.seo_description = form.excerpt.substring(0, 160);
    }
}

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
    showContentMediaPicker.value = true;
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
        // Check file size (20MB = 20 * 1024 * 1024 bytes)
        const maxSize = 20 * 1024 * 1024; // 20MB
        if (file.size > maxSize) {
            errorMessage.value = `File size exceeds 20MB limit. Please choose a smaller image. (Current size: ${(file.size / 1024 / 1024).toFixed(2)}MB)`;
            // Reset the file input
            (event.target as HTMLInputElement).value = '';
            return;
        }

        // Check file type
        if (!file.type.startsWith('image/')) {
            errorMessage.value = 'Please select a valid image file (JPG, PNG, GIF, etc.)';
            (event.target as HTMLInputElement).value = '';
            return;
        }

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
    form.featured_image_media_id = null;
    featuredImagePreview.value = null;
    fileInputKey.value++; // Force file input to reset
}

function selectFeaturedImage(media: any) {
    // media can be a single object or array depending on picker mode
    const selectedMedia = Array.isArray(media) ? media[0] : media;

    form.featured_image_media_id = selectedMedia.id;
    featuredImagePreview.value = selectedMedia.full_url;
    showMediaPicker.value = false;

    // Clear file input since we're using media library
    form.featured_image = null;
    fileInputKey.value++;
}

function selectContentImage(media: any) {
    // media can be a single object or array depending on picker mode
    const selectedMedia = Array.isArray(media) ? media[0] : media;

    if (editor.value) {
        // Ask for image size
        const sizeOptions = [
            { label: 'Small (25%)', value: '25' },
            { label: 'Medium (50%)', value: '50' },
            { label: 'Large (75%)', value: '75' },
            { label: 'Full Size (100%)', value: '100' },
        ];

        const selectedOption = prompt(
            'Select image size:\n1. Small (25%)\n2. Medium (50%)\n3. Large (75%)\n4. Full Size (100%)\n\nEnter number (1-4) or percentage (e.g., 60):',
            '4',
        );

        let width = '100%';
        if (selectedOption) {
            switch (selectedOption) {
                case '1':
                    width = '25%';
                    break;
                case '2':
                    width = '50%';
                    break;
                case '3':
                    width = '75%';
                    break;
                case '4':
                    width = '100%';
                    break;
                default:
                    // Allow custom percentage
                    const customWidth = parseInt(selectedOption);
                    if (!isNaN(customWidth) && customWidth > 0 && customWidth <= 100) {
                        width = customWidth + '%';
                    }
            }
        }

        // Insert image with Bootstrap classes and custom width
        const imgHtml = `<div style="width: ${width}; display: inline-block;"><img src="${selectedMedia.full_url}" alt="${selectedMedia.name || 'Image'}" class="img-fluid" /></div>`;
        editor.value.chain().focus().insertContent(imgHtml).run();
    }
    showContentMediaPicker.value = false;
}

function addTag() {
    if (newTag.value && !form.tags.includes(newTag.value)) {
        form.tags.push(newTag.value);
        newTag.value = '';
    }
}

function removeTag(index: number) {
    form.tags.splice(index, 1);
}

function addPopularTag(tag: string) {
    if (!form.tags.includes(tag)) {
        form.tags.push(tag);
    }
}

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();
    errorMessage.value = null;

    let isValid = true;
    const errors: Record<string, string> = {};

    // Required fields validation
    if (!form.title || !form.title.trim()) {
        errors.title = 'The title field is required.';
        isValid = false;
    } else if (form.title.length > 255) {
        errors.title = 'The title may not be greater than 255 characters.';
        isValid = false;
    }

    if (!form.content || !form.content.trim() || form.content === '<p></p>') {
        errors.content = 'The content field is required.';
        isValid = false;
    }

    if (!form.category) {
        errors.category = 'The category field is required.';
        isValid = false;
    }

    // Optional fields validation
    if (form.slug) {
        if (form.slug.length > 255) {
            errors.slug = 'The slug may not be greater than 255 characters.';
            isValid = false;
        } else if (!/^[a-z0-9-]+$/.test(form.slug)) {
            errors.slug = 'The slug may only contain lowercase letters, numbers, and hyphens.';
            isValid = false;
        }
    }

    if (form.excerpt && form.excerpt.length > 500) {
        errors.excerpt = 'The excerpt may not be greater than 500 characters.';
        isValid = false;
    }

    if (form.seo_title && form.seo_title.length > 60) {
        errors.seo_title = 'The seo title may not be greater than 60 characters.';
        isValid = false;
    }

    if (form.seo_description && form.seo_description.length > 160) {
        errors.seo_description = 'The seo description may not be greater than 160 characters.';
        isValid = false;
    }

    if (form.seo_keywords && form.seo_keywords.length > 255) {
        errors.seo_keywords = 'The seo keywords may not be greater than 255 characters.';
        isValid = false;
    }

    // Tags validation
    if (form.tags && form.tags.length > 0) {
        for (let i = 0; i < form.tags.length; i++) {
            if (form.tags[i].length > 50) {
                errors[`tags.${i}`] = 'Each tag may not be greater than 50 characters.';
                isValid = false;
            }
        }
    }

    // File validation
    if (form.featured_image) {
        const maxSize = 20 * 1024 * 1024; // 20MB in bytes
        if (form.featured_image.size > maxSize) {
            errors.featured_image = 'The featured image may not be greater than 20MB.';
            isValid = false;
        }

        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        if (!allowedTypes.includes(form.featured_image.type)) {
            errors.featured_image = 'The featured image must be an image file (jpeg, png, gif, svg, webp).';
            isValid = false;
        }
    }

    // Set errors if any
    if (!isValid) {
        form.setError(errors);
        const firstError = Object.values(errors)[0];
        errorMessage.value = firstError;
    }

    return isValid;
}

function submit() {
    if (!validateForm()) {
        return;
    }

    // For file uploads with PUT requests, we need to use POST with _method
    // Reset the form with current values to ensure they're properly bound
    const currentData = {
        title: form.title,
        slug: form.slug || '',
        excerpt: form.excerpt || '',
        content: form.content,
        category: form.category,
        tags: form.tags || [],
        is_published: form.is_published,
        published_at: form.published_at || '',
        seo_title: form.seo_title || '',
        seo_description: form.seo_description || '',
        seo_keywords: form.seo_keywords || '',
        featured_image: form.featured_image,
        featured_image_media_id: form.featured_image_media_id,
        _method: 'PUT',
    };

    // Create a new form instance with the data
    const updateForm = useForm(currentData);

    // Submit the form
    updateForm.post(route('admin.blog-posts.update', props.post.slug), {
        forceFormData: true, // This ensures multipart/form-data encoding
        preserveScroll: true,
        onSuccess: (page) => {
            showToast('success', 'Blog post updated successfully!');
            // Update the original form with any changes
            Object.assign(form, updateForm.data());
            // Update the featured image preview if the post has a featured image
            if (page.props.post && page.props.post.featured_image_url) {
                featuredImagePreview.value = page.props.post.featured_image_url;
            }
            // Clear the file input
            form.featured_image = null;
            fileInputKey.value++;
        },
        onError: (errors) => {
            console.error('Form validation errors:', errors);
            // Copy errors back to original form
            form.errors = errors;
            // Get the first error message
            const firstError = Object.values(errors)[0];
            errorMessage.value = Array.isArray(firstError) ? firstError[0] : firstError || 'An error occurred while updating the blog post.';
        },
        onFinish: () => {
            // Re-enable form after submission
            form.processing = false;
        },
    });
}

function duplicate() {
    // Create a new post with the same data
    const duplicateForm = useForm({
        title: form.title + ' (Copy)',
        slug: form.slug + '-copy',
        excerpt: form.excerpt,
        content: form.content,
        featured_image: null,
        category: form.category,
        tags: [...form.tags],
        is_published: false,
        published_at: '',
        seo_title: form.seo_title,
        seo_description: form.seo_description,
        seo_keywords: form.seo_keywords,
    });

    duplicateForm.post(route('admin.blog-posts.store'));
}

function toggleSourceView() {
    showSourceCode.value = !showSourceCode.value;
    if (showSourceCode.value) {
        // Switch to source mode
        sourceCode.value = editor.value?.getHTML() || '';
    } else {
        // Switch back to visual mode
        if (editor.value) {
            editor.value.commands.setContent(sourceCode.value);
            form.content = sourceCode.value;
        }
    }
}

function updateSourceCode(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    sourceCode.value = target.value;
    form.content = target.value;
}
</script>

<template>
    <AdminLayout>
        <Head title="Edit Blog Post" />

        <div class="p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <Link :href="route('admin.blog-posts.index')" class="btn btn-link text-decoration-none p-0 mb-3">
                        <i class="bi bi-arrow-left me-2"></i>
                        Back to Blog Posts
                    </Link>
                    <h1 class="h2 fw-bold text-dark">Edit Blog Post</h1>
                    <div class="text-muted small">
                        <span>Created by {{ post.author.name }} on {{ new Date(post.created_at).toLocaleDateString() }}</span>
                        <span class="mx-2">•</span>
                        <span>{{ post.views_count }} views</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a :href="route('blog.show', post.slug)" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>
                        View Post
                    </a>
                    <button type="button" @click="duplicate" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-files me-1"></i>
                        Duplicate
                    </button>
                </div>
            </div>

            <!-- Alert Messages -->
            <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ errorMessage }}
                <button type="button" class="btn-close" @click="errorMessage = null" aria-label="Close"></button>
            </div>

            <form @submit.prevent="submit">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Title -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="title" class="form-label text-dark fw-medium"> Post Title <span class="text-danger">*</span> </label>
                                <p class="text-muted small mb-2">The main headline for your blog post</p>
                                <input
                                    v-model="form.title"
                                    id="title"
                                    type="text"
                                    class="form-control"
                                    placeholder="Enter post title"
                                    @blur="updateSeoTitle"
                                    @input="updateSlug"
                                    required
                                />
                                <div class="text-muted small mt-1">{{ characterCounts.title }} characters</div>
                                <div v-if="form.errors.title" class="invalid-feedback d-block">
                                    {{ form.errors.title }}
                                </div>
                            </div>
                        </div>

                        <!-- Slug -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="slug" class="form-label text-dark fw-medium mb-0">URL Slug</label>
                                    <button type="button" @click="showSlugInput = !showSlugInput" class="btn btn-sm btn-outline-secondary">
                                        {{ showSlugInput ? 'Auto' : 'Custom' }}
                                    </button>
                                </div>
                                <p class="text-muted small mb-2">The URL path for this post</p>
                                <input
                                    v-if="showSlugInput"
                                    v-model="form.slug"
                                    id="slug"
                                    type="text"
                                    class="form-control"
                                    placeholder="custom-url-slug"
                                    pattern="[a-z0-9\-]+"
                                />
                                <div v-else class="form-control bg-light">
                                    {{ form.slug || 'auto-generated-from-title' }}
                                </div>
                                <div class="text-muted small mt-1">Only lowercase letters, numbers, and hyphens allowed</div>
                                <div v-if="form.errors.slug" class="invalid-feedback d-block">
                                    {{ form.errors.slug }}
                                </div>
                            </div>
                        </div>

                        <!-- Excerpt -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="excerpt" class="form-label text-dark fw-medium">Post Excerpt</label>
                                <p class="text-muted small mb-2">A short summary that appears in post listings and search results</p>
                                <textarea
                                    v-model="form.excerpt"
                                    id="excerpt"
                                    class="form-control"
                                    rows="3"
                                    placeholder="Brief description of the post"
                                    @blur="updateSeoDescription"
                                ></textarea>
                                <div class="text-muted small mt-1">{{ characterCounts.excerpt }}/500 characters</div>
                                <div v-if="form.errors.excerpt" class="invalid-feedback d-block">
                                    {{ form.errors.excerpt }}
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label class="form-label text-dark fw-medium mb-2">Content <span class="text-danger">*</span></label>

                                <!-- Tiptap Editor Toolbar -->
                                <div v-if="editor" class="border rounded-top bg-light p-2 d-flex flex-wrap align-items-center gap-1">
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleBold().run()"
                                        :class="{ active: editor.isActive('bold') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Bold"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-type-bold"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleItalic().run()"
                                        :class="{ active: editor.isActive('italic') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Italic"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-type-italic"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleStrike().run()"
                                        :class="{ active: editor.isActive('strike') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Strikethrough"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-type-strikethrough"></i>
                                    </button>
                                    <div class="vr"></div>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
                                        :class="{ active: editor.isActive('heading', { level: 1 }) }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Heading 1"
                                        :disabled="showSourceCode"
                                    >
                                        H1
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                                        :class="{ active: editor.isActive('heading', { level: 2 }) }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Heading 2"
                                        :disabled="showSourceCode"
                                    >
                                        H2
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                                        :class="{ active: editor.isActive('heading', { level: 3 }) }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Heading 3"
                                        :disabled="showSourceCode"
                                    >
                                        H3
                                    </button>
                                    <div class="vr"></div>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleBulletList().run()"
                                        :class="{ active: editor.isActive('bulletList') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Bullet List"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleOrderedList().run()"
                                        :class="{ active: editor.isActive('orderedList') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Numbered List"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-list-ol"></i>
                                    </button>
                                    <div class="vr"></div>
                                    <button
                                        type="button"
                                        @click="addLink"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Add Link"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-link"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="removeLink"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Remove Link"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="addImage"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Add Image"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-image"></i>
                                    </button>
                                    <div class="vr"></div>
                                    <button
                                        type="button"
                                        @click="toggleSourceView"
                                        :class="{ active: showSourceCode }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Show Source Code"
                                    >
                                        <i class="bi bi-code-slash"></i>
                                    </button>
                                </div>

                                <!-- Editor Content -->
                                <div v-if="!showSourceCode">
                                    <EditorContent :editor="editor" class="border border-top-0 rounded-bottom p-3" style="min-height: 300px" />
                                </div>

                                <!-- Source Code Editor -->
                                <div v-else>
                                    <textarea
                                        v-model="sourceCode"
                                        @input="updateSourceCode"
                                        class="form-control border border-top-0 rounded-bottom"
                                        style="min-height: 300px; font-family: 'Courier New', monospace; font-size: 14px"
                                        placeholder="Enter HTML source code..."
                                    ></textarea>
                                </div>
                                <div v-if="form.errors.content" class="invalid-feedback d-block">
                                    {{ form.errors.content }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="col-lg-4">
                        <div class="sticky-top" style="top: 1rem">
                            <!-- Publish Settings -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Publish Settings</h5>
                                </div>
                                <div class="card-body">
                                    <div class="form-check mb-3">
                                        <input id="is_published" v-model="form.is_published" type="checkbox" class="form-check-input" />
                                        <label for="is_published" class="form-check-label text-dark"> Published </label>
                                    </div>

                                    <div v-if="form.is_published">
                                        <label for="published_at" class="form-label text-dark fw-medium"> Publish Date </label>
                                        <input id="published_at" v-model="form.published_at" type="datetime-local" class="form-control" />
                                        <div v-if="form.errors.published_at" class="invalid-feedback d-block">
                                            {{ form.errors.published_at }}
                                        </div>
                                    </div>

                                    <div class="d-grid gap-2 mt-3">
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            {{ form.processing ? 'Updating...' : 'Update Post' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Featured Image</h5>
                                </div>
                                <div class="card-body">
                                    <div v-if="featuredImagePreview" class="mb-3">
                                        <!-- Thumbnail -->
                                        <div class="position-relative">
                                            <img
                                                :src="featuredImagePreview"
                                                alt="Featured image preview"
                                                class="img-fluid rounded shadow-sm"
                                                style="max-height: 200px; width: 100%; object-fit: cover"
                                            />
                                            <!-- Overlay buttons -->
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <div class="btn-group" role="group">
                                                    <a
                                                        :href="featuredImagePreview"
                                                        target="_blank"
                                                        class="btn btn-sm btn-primary"
                                                        title="View full size"
                                                    >
                                                        <i class="bi bi-eye"></i> Preview
                                                    </a>
                                                    <button type="button" @click="removeImage" class="btn btn-sm btn-danger" title="Remove image">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-muted small mt-2">
                                            <i class="bi bi-info-circle"></i>
                                            Upload a new image to replace the current one
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4 bg-light rounded mb-3">
                                        <i class="bi bi-image text-muted" style="font-size: 3rem"></i>
                                        <p class="text-muted mt-2 mb-0">No featured image</p>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <input type="file" @change="handleImageChange" accept="image/*" class="form-control" :key="fileInputKey" />
                                        <button type="button" @click="showMediaPicker = true" class="btn btn-outline-primary">
                                            <i class="bi bi-images"></i>
                                            Choose
                                        </button>
                                    </div>
                                    <div class="text-muted small mt-1">
                                        <i class="bi bi-info-circle"></i>
                                        Upload new or choose from media library • 1200x630px • Max 20MB
                                    </div>
                                    <div v-if="form.errors.featured_image" class="invalid-feedback d-block">
                                        {{ form.errors.featured_image }}
                                    </div>
                                </div>
                            </div>

                            <!-- Category -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Category <span class="text-danger">*</span></h5>
                                </div>
                                <div class="card-body">
                                    <select v-model="form.category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option v-for="(label, value) in categories" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                    <div v-if="form.errors.category" class="invalid-feedback d-block">
                                        {{ form.errors.category }}
                                    </div>
                                </div>
                            </div>

                            <!-- Tags -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Tags</h5>
                                </div>
                                <div class="card-body">
                                    <div class="input-group mb-2">
                                        <input v-model="newTag" type="text" class="form-control" placeholder="Add tag" @keyup.enter="addTag" />
                                        <button type="button" @click="addTag" class="btn btn-outline-secondary">Add</button>
                                    </div>

                                    <!-- Current Tags -->
                                    <div v-if="form.tags.length" class="mb-3">
                                        <span v-for="(tag, index) in form.tags" :key="index" class="badge bg-primary me-1 mb-1">
                                            {{ tag }}
                                            <button
                                                type="button"
                                                @click="removeTag(index)"
                                                class="btn-close btn-close-white ms-1"
                                                style="font-size: 0.6em"
                                            ></button>
                                        </span>
                                    </div>

                                    <!-- Popular Tags -->
                                    <div v-if="popularTags.length">
                                        <small class="text-muted">Popular tags:</small>
                                        <div class="mt-1">
                                            <button
                                                v-for="tag in popularTags"
                                                :key="tag"
                                                type="button"
                                                @click="addPopularTag(tag)"
                                                class="btn btn-sm btn-outline-secondary me-1 mb-1"
                                                :disabled="form.tags.includes(tag)"
                                            >
                                                {{ tag }}
                                            </button>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.tags" class="invalid-feedback d-block">
                                        {{ form.errors.tags }}
                                    </div>
                                </div>
                            </div>

                            <!-- SEO Settings -->
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">SEO Settings</h5>
                                    <button type="button" @click="showSeoFields = !showSeoFields" class="btn btn-sm btn-outline-secondary">
                                        {{ showSeoFields ? 'Hide' : 'Show' }}
                                    </button>
                                </div>
                                <div v-if="showSeoFields" class="card-body">
                                    <div class="mb-3">
                                        <label for="seo_title" class="form-label text-dark fw-medium">SEO Title</label>
                                        <input
                                            id="seo_title"
                                            v-model="form.seo_title"
                                            type="text"
                                            class="form-control"
                                            placeholder="Custom title for search engines"
                                        />
                                        <div class="text-muted small mt-1">{{ characterCounts.seoTitle }}/60 characters</div>
                                        <div v-if="form.errors.seo_title" class="invalid-feedback d-block">
                                            {{ form.errors.seo_title }}
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="seo_description" class="form-label text-dark fw-medium">SEO Description</label>
                                        <textarea
                                            id="seo_description"
                                            v-model="form.seo_description"
                                            class="form-control"
                                            rows="3"
                                            placeholder="Meta description for search engines"
                                        ></textarea>
                                        <div class="text-muted small mt-1">{{ characterCounts.seoDescription }}/160 characters</div>
                                        <div v-if="form.errors.seo_description" class="invalid-feedback d-block">
                                            {{ form.errors.seo_description }}
                                        </div>
                                    </div>

                                    <div>
                                        <label for="seo_keywords" class="form-label text-dark fw-medium">SEO Keywords</label>
                                        <input
                                            id="seo_keywords"
                                            v-model="form.seo_keywords"
                                            type="text"
                                            class="form-control"
                                            placeholder="Comma-separated keywords"
                                        />
                                        <div v-if="form.errors.seo_keywords" class="invalid-feedback d-block">
                                            {{ form.errors.seo_keywords }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Media Pickers -->
        <MediaPicker :show="showMediaPicker" @select="selectFeaturedImage" @close="showMediaPicker = false" />

        <MediaPicker :show="showContentMediaPicker" @select="selectContentImage" @close="showContentMediaPicker = false" />
    </AdminLayout>
</template>

<style scoped>
.btn.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}

:deep(.ProseMirror) {
    outline: none;
    min-height: 300px;
    color: #212529;
}

:deep(.ProseMirror p) {
    color: #212529;
}

:deep(.ProseMirror h1),
:deep(.ProseMirror h2),
:deep(.ProseMirror h3),
:deep(.ProseMirror h4),
:deep(.ProseMirror h5),
:deep(.ProseMirror h6) {
    color: #212529;
}

:deep(.ProseMirror ul),
:deep(.ProseMirror ol),
:deep(.ProseMirror li) {
    color: #212529;
}

:deep(.ProseMirror p.is-editor-empty:first-child::before) {
    content: attr(data-placeholder);
    float: left;
    color: #adb5bd;
    pointer-events: none;
    height: 0;
}
</style>
