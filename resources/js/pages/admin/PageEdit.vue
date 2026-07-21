<script setup lang="ts">
import MediaPicker from '@/components/MediaPicker.vue';
import { useImageResize } from '@/composables/useImageResize';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Image from '@tiptap/extension-image';
import TiptapLink from '@tiptap/extension-link';
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

interface PageAsset {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    full_url: string;
    thumb_url: string;
}

const props = defineProps<{ page: any | null; assets?: PageAsset[] }>();

const form = useForm({
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    render_mode: props.page?.render_mode || 'normal',
    raw_html: props.page?.raw_html || props.page?.content || '',
    featured_image: null as File | null, // for file upload
    featured_image_media_id: null as number | null,
    published: props.page?.published ?? true,
});

// Raw modes bypass the Tiptap editor entirely so pasted HTML/scripts are
// preserved verbatim (Tiptap would strip unknown tags on the visual round-trip).
const isRawMode = computed(() => form.render_mode !== 'normal');
const isBladeRaw = computed(() => form.render_mode === 'blade_raw');
const htmlFileKey = ref(0);

// Keep content in sync with the raw HTML while in a raw mode so the public
// renderer (v-html for inertia_raw) and validation both have the markup.
watch(
    () => form.raw_html,
    (val) => {
        if (isRawMode.value) form.content = val;
    },
);

// Seed the raw editor from existing content the first time a raw mode is chosen.
watch(
    () => form.render_mode,
    (mode) => {
        if (mode !== 'normal' && !form.raw_html) {
            form.raw_html = editor.value?.getHTML() || form.content || '';
        }
        if (mode === 'normal') {
            form.content = editor.value?.getHTML() || form.content || '';
        }
    },
);

function handleHtmlFileChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (e) => {
        const text = (e.target?.result as string) || '';
        form.raw_html = text;
        form.content = text;
    };
    reader.readAsText(file);
    htmlFileKey.value++;
}

const preview = ref(props.page?.featured_image_url || '');
const showSourceCode = ref(false);
const sourceCode = ref('');
const showMediaPicker = ref(false);
const showContentMediaPicker = ref(false);
const fileInputKey = ref(0);

// Tiptap setup
const editor = ref(
    new Editor({
        content: form.content,
        extensions: [
            StarterKit,
            TiptapLink.configure({
                openOnClick: false,
            }),
            Image,
        ],
        onUpdate: ({ editor }) => {
            form.content = editor.getHTML();
            if (showSourceCode.value) {
                sourceCode.value = editor.getHTML();
            }
        },
    }),
);

// Keep editor in sync if editing existing page
watch(
    () => props.page?.content,
    (val) => {
        if (val && editor.value) editor.value.commands.setContent(val);
    },
);

// Setup image resize functionality
let cleanupImageResize: (() => void) | null = null;

onMounted(() => {
    if (editor.value) {
        const { setupImageResize } = useImageResize(editor.value);
        cleanupImageResize = setupImageResize();
    }
});

onBeforeUnmount(() => {
    if (cleanupImageResize) {
        cleanupImageResize();
    }
    if (editor.value) {
        editor.value.destroy();
    }
});

function handleImageChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (file) {
        form.featured_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            preview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();

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

    if (!form.slug || !form.slug.trim()) {
        errors.slug = 'The slug field is required.';
        isValid = false;
    } else if (form.slug.length > 255) {
        errors.slug = 'The slug may not be greater than 255 characters.';
        isValid = false;
    }

    if (isRawMode.value) {
        if (!form.raw_html || form.raw_html.trim() === '') {
            errors.raw_html = 'Paste or upload the HTML for this page.';
            isValid = false;
        }
    } else if (!form.content || form.content.trim() === '' || form.content.trim() === '<p></p>') {
        errors.content = 'The content field is required.';
        isValid = false;
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
    }

    return isValid;
}

function submit() {
    if (!validateForm()) {
        return;
    }

    if (props.page) {
        form.put(route('admin.pages.update', props.page.id));
    } else {
        form.post(route('admin.pages.store'));
    }
}

function generateSlug() {
    form.slug = form.title
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .trim();
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

// Media picker functions
function selectFeaturedImage(media: any) {
    // media can be a single object or array depending on picker mode
    const selectedMedia = Array.isArray(media) ? media[0] : media;

    form.featured_image_media_id = selectedMedia.id;
    preview.value = selectedMedia.full_url;
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

function removeImage() {
    form.featured_image = null;
    form.featured_image_media_id = null;
    preview.value = '';
    fileInputKey.value++; // Force file input to reset
}

// Add link function
const addLink = () => {
    const url = prompt('Enter URL:');
    if (url && editor.value) {
        editor.value.chain().focus().toggleLink({ href: url }).run();
    }
};

// Add image function
const addImage = () => {
    showContentMediaPicker.value = true;
};

// --- Page Assets (bulk upload + URL list for pasting into HTML tooling) ---
const pageAssets = ref<PageAsset[]>(props.assets ? [...props.assets] : []);
const assetFiles = ref<File[]>([]);
const assetFileInputKey = ref(0);
const assetsUploading = ref(false);
const assetsUploadProgress = ref(0);
const assetsError = ref('');
const urlListCopied = ref(false);

function handleAssetFilesChange(event: Event) {
    const input = event.target as HTMLInputElement;
    assetFiles.value = input.files ? Array.from(input.files) : [];
    assetsError.value = '';
}

async function uploadAssets() {
    if (!props.page || assetFiles.value.length === 0) return;

    assetsUploading.value = true;
    assetsUploadProgress.value = 0;
    assetsError.value = '';

    const formData = new FormData();
    assetFiles.value.forEach((file) => formData.append('files[]', file));
    formData.append('page_id', String(props.page.id));

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const response = await axios.post(route('admin.media-library.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': csrfToken,
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    assetsUploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            },
        });

        pageAssets.value = [...(response.data.media || []), ...pageAssets.value];
        assetFiles.value = [];
        assetFileInputKey.value++;
    } catch (error: any) {
        const errors = error.response?.data?.errors;
        assetsError.value = errors
            ? Object.values(errors).flat().join(' ')
            : error.response?.data?.message || 'Error uploading files. Please try again.';
    } finally {
        assetsUploading.value = false;
        assetsUploadProgress.value = 0;
    }
}

async function deleteAsset(asset: PageAsset) {
    if (!confirm(`Delete "${asset.file_name}"? If the page HTML still references it, the image will break.`)) return;

    try {
        await axios.delete(route('admin.media-library.destroy', asset.id));
        pageAssets.value = pageAssets.value.filter((a) => a.id !== asset.id);
    } catch (error: any) {
        assetsError.value = error.response?.data?.message || 'Error deleting file.';
    }
}

function copyAssetUrl(url: string) {
    navigator.clipboard.writeText(url);
}

const urlListText = computed(() => pageAssets.value.map((a) => `${a.file_name}: ${a.full_url}`).join('\n'));

function copyUrlList() {
    navigator.clipboard.writeText(urlListText.value);
    urlListCopied.value = true;
    setTimeout(() => (urlListCopied.value = false), 2000);
}

// --- How To modal + ready-made AI prompt ---
const showHowToModal = ref(false);
const promptCopied = ref(false);

const aiPrompt = computed(
    () => `Using the URL list below, update my HTML page so every image/media reference points to its matching hosted URL.

Instructions:
- Match each reference to the list by filename — check src, srcset, poster, favicon/link href, CSS url(...), fonts, and video/audio sources.
- Replace relative or local paths (e.g. ./images/hero.jpg, assets/logo.png) with the full URL from the list.
- Leave existing external URLs (https://...) unchanged unless their filename matches an entry in the list.
- Do not change anything else — no reformatting, no content or style edits.
- If the HTML references a file that is NOT in the list, PAUSE before outputting anything: tell me the missing filename(s) and wait for me to reply with their URLs.
- Once every reference is resolved, output the FULL updated HTML in a single code block, ready to paste.

URL list:
${urlListText.value || '(no assets uploaded yet — upload files on the page editor, then copy this prompt again)'}

Here is my HTML:
[PASTE YOUR HTML HERE]`,
);

function copyPrompt() {
    navigator.clipboard.writeText(aiPrompt.value);
    promptCopied.value = true;
    setTimeout(() => (promptCopied.value = false), 2000);
}

function isImageAsset(asset: PageAsset): boolean {
    return asset.mime_type?.startsWith('image/');
}

function formatAssetSize(bytes: number): string {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}
</script>

<template>
    <AdminLayout>
        <Head :title="page ? 'Edit Page' : 'Create Page'" />

        <div class="p-4">
            <!-- Header -->
            <div class="mb-4">
                <Link :href="route('admin.pages.index')" class="btn btn-link text-decoration-none p-0 mb-3">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Pages
                </Link>
                <h1 class="h2 fw-bold text-dark">{{ page ? 'Edit Page' : 'Create New Page' }}</h1>
                <p class="text-secondary small">
                    {{ page ? 'Edit this content page' : 'Create a new content page for your website' }}
                </p>
            </div>

            <form @submit.prevent="submit">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Title -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="title" class="form-label text-dark fw-medium"> Page Title <span class="text-danger">*</span> </label>
                                <input
                                    v-model="form.title"
                                    id="title"
                                    type="text"
                                    class="form-control"
                                    placeholder="Enter page title"
                                    maxlength="255"
                                    required
                                    @input="generateSlug"
                                />
                                <div v-if="form.errors.title" class="invalid-feedback d-block">
                                    {{ form.errors.title }}
                                </div>
                            </div>
                        </div>

                        <!-- Slug -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="slug" class="form-label text-dark fw-medium"> URL Slug <span class="text-danger">*</span> </label>
                                <p class="text-secondary small mb-2">The URL path for this page (e.g., /pages/your-page-slug)</p>
                                <input
                                    v-model="form.slug"
                                    id="slug"
                                    type="text"
                                    class="form-control"
                                    placeholder="page-url-slug"
                                    pattern="[a-z0-9-]+"
                                    maxlength="255"
                                    required
                                />
                                <div class="text-secondary small mt-1">Only lowercase letters, numbers, and hyphens allowed</div>
                                <div v-if="form.errors.slug" class="invalid-feedback d-block">
                                    {{ form.errors.slug }}
                                </div>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label class="form-label text-dark fw-medium mb-2">Page Content</label>
                                <div v-if="!isRawMode" class="text-muted small mb-2">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Tip: Double-click on any image to resize it
                                </div>

                                <!-- Raw HTML editor (raw render modes) -->
                                <div v-if="isRawMode">
                                    <div class="alert alert-info small py-2">
                                        <i class="bi bi-code-square me-1"></i>
                                        <template v-if="isBladeRaw">
                                            <strong>Full page (raw):</strong> this HTML controls the entire page. Paste a complete document — your
                                            <code>&lt;script&gt;</code> tags (tracking, widgets) will run. Site analytics are injected automatically.
                                        </template>
                                        <template v-else>
                                            <strong>Content only (no header/footer):</strong> your HTML is shown without the site chrome. Note:
                                            <code>&lt;script&gt;</code> tags will not execute in this mode — use "Full page (raw)" if you need scripts
                                            to run.
                                        </template>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label text-dark small fw-medium mb-1">Import from .html file</label>
                                        <input
                                            type="file"
                                            accept=".html,.htm,text/html"
                                            class="form-control form-control-sm"
                                            :key="htmlFileKey"
                                            @change="handleHtmlFileChange"
                                        />
                                        <div class="text-secondary small mt-1">
                                            Uploading fills the editor below — you can still tweak it before saving.
                                        </div>
                                    </div>

                                    <textarea
                                        v-model="form.raw_html"
                                        class="form-control"
                                        style="min-height: 400px; font-family: 'Courier New', monospace; font-size: 13px"
                                        placeholder="Paste your full HTML page here..."
                                    ></textarea>
                                    <div v-if="form.errors.raw_html" class="invalid-feedback d-block">
                                        {{ form.errors.raw_html }}
                                    </div>
                                </div>

                                <!-- Editor Toolbar -->
                                <div v-if="!isRawMode && editor" class="border rounded-top bg-light p-2 d-flex flex-wrap align-items-center gap-1">
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
                                        :class="{ active: editor.isActive('link') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Add Link"
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
                                <div v-if="!isRawMode && !showSourceCode">
                                    <EditorContent :editor="editor" class="border border-top-0 rounded-bottom p-3" style="min-height: 300px" />
                                </div>

                                <!-- Source Code Editor -->
                                <div v-else-if="!isRawMode">
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

                        <!-- Page Assets -->
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-folder2-open me-1"></i>
                                    Page Assets
                                </h5>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" @click="showHowToModal = true">
                                        <i class="bi bi-question-circle me-1"></i>
                                        How To
                                    </button>
                                    <button
                                        v-if="pageAssets.length > 0"
                                        type="button"
                                        class="btn btn-sm"
                                        :class="urlListCopied ? 'btn-success' : 'btn-outline-primary'"
                                        @click="copyUrlList"
                                    >
                                        <i class="bi me-1" :class="urlListCopied ? 'bi-check-lg' : 'bi-clipboard'"></i>
                                        {{ urlListCopied ? 'Copied!' : 'Copy URL List' }}
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div v-if="!page" class="text-secondary small">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Save the page first, then upload images and other assets here.
                                </div>

                                <template v-else>
                                    <p class="text-secondary small mb-2">
                                        Upload the images/assets referenced by your HTML, then use <strong>Copy URL List</strong> and paste it into
                                        Claude (or any tool) to rewrite the references in your HTML to these hosted URLs.
                                    </p>

                                    <div class="d-flex gap-2 mb-3">
                                        <input
                                            type="file"
                                            multiple
                                            class="form-control"
                                            :key="assetFileInputKey"
                                            :disabled="assetsUploading"
                                            @change="handleAssetFilesChange"
                                        />
                                        <button
                                            type="button"
                                            class="btn btn-primary text-nowrap"
                                            :disabled="assetFiles.length === 0 || assetsUploading"
                                            @click="uploadAssets"
                                        >
                                            <span v-if="assetsUploading" class="spinner-border spinner-border-sm me-1"></span>
                                            <i v-else class="bi bi-cloud-upload me-1"></i>
                                            {{
                                                assetsUploading
                                                    ? `${assetsUploadProgress}%`
                                                    : `Upload${assetFiles.length ? ` (${assetFiles.length})` : ''}`
                                            }}
                                        </button>
                                    </div>

                                    <div v-if="assetsError" class="alert alert-danger small py-2">{{ assetsError }}</div>

                                    <div v-if="pageAssets.length === 0" class="text-secondary small">No assets uploaded for this page yet.</div>

                                    <div v-else class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <tbody>
                                                <tr v-for="asset in pageAssets" :key="asset.id">
                                                    <td style="width: 56px">
                                                        <img
                                                            v-if="isImageAsset(asset)"
                                                            :src="asset.thumb_url || asset.full_url"
                                                            :alt="asset.name"
                                                            class="rounded"
                                                            style="width: 48px; height: 48px; object-fit: cover"
                                                        />
                                                        <div
                                                            v-else
                                                            class="bg-light rounded d-flex align-items-center justify-content-center"
                                                            style="width: 48px; height: 48px"
                                                        >
                                                            <i class="bi bi-file-earmark text-secondary fs-4"></i>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <div class="small fw-medium text-truncate" style="max-width: 200px" :title="asset.file_name">
                                                            {{ asset.file_name }}
                                                        </div>
                                                        <div class="text-secondary" style="font-size: 0.75rem">{{ formatAssetSize(asset.size) }}</div>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" :value="asset.full_url" readonly />
                                                    </td>
                                                    <td class="text-end" style="width: 90px">
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-secondary me-1"
                                                            title="Copy URL"
                                                            @click="copyAssetUrl(asset.full_url)"
                                                        >
                                                            <i class="bi bi-link-45deg"></i>
                                                        </button>
                                                        <button
                                                            type="button"
                                                            class="btn btn-sm btn-outline-danger"
                                                            title="Delete"
                                                            @click="deleteAsset(asset)"
                                                        >
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
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
                                    <div class="mb-3">
                                        <label for="render_mode" class="form-label text-dark fw-medium">Page Type</label>
                                        <select id="render_mode" v-model="form.render_mode" class="form-select">
                                            <option value="normal">Normal (site header &amp; footer)</option>
                                            <option value="inertia_raw">Content only (no header/footer)</option>
                                            <option value="blade_raw">Full page (raw HTML, scripts run)</option>
                                        </select>
                                        <div class="text-secondary small mt-1">
                                            <span v-if="form.render_mode === 'normal'">Standard page wrapped in the site layout.</span>
                                            <span v-else-if="form.render_mode === 'inertia_raw'"
                                                >Your HTML only, no site chrome. Scripts do not run.</span
                                            >
                                            <span v-else>Your HTML controls the whole page. Scripts run; site tracking is injected.</span>
                                        </div>
                                    </div>

                                    <div class="form-check mb-3">
                                        <input id="published" v-model="form.published" type="checkbox" class="form-check-input" />
                                        <label for="published" class="form-check-label text-dark"> Published </label>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                            <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                            {{ form.processing ? (page ? 'Updating...' : 'Creating...') : page ? 'Update Page' : 'Create Page' }}
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
                                    <div v-if="preview" class="mb-3">
                                        <div class="position-relative">
                                            <img :src="preview" alt="Preview" class="img-fluid rounded" />
                                            <button
                                                type="button"
                                                @click="removeImage"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2"
                                            >
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <button type="button" @click="showMediaPicker = true" class="btn btn-primary me-2">
                                            <i class="bi bi-images me-1"></i>
                                            Choose from Library
                                        </button>
                                        <span class="text-muted">or</span>
                                    </div>

                                    <input type="file" @change="handleImageChange" accept="image/*" class="form-control" :key="fileInputKey" />
                                    <div class="text-secondary small mt-1">Optional header image for the page</div>
                                </div>
                            </div>

                            <!-- Page Preview -->
                            <div v-if="page" class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Actions</h5>
                                </div>
                                <div class="card-body">
                                    <a :href="route('pages.show', page.slug)" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-eye me-1"></i>
                                        View Page
                                    </a>
                                </div>
                            </div>

                            <!-- Tips & Embeds -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-lightbulb me-1"></i>
                                        Tips & Embeds
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="fw-semibold mb-2">Elfsight Widgets</h6>
                                    <p class="text-muted small mb-2">
                                        Embed Elfsight carousels, reviews, or other widgets by pasting the shortcode in your content:
                                    </p>
                                    <code class="d-block bg-light p-2 rounded small mb-2"> {elfsight YOUR-WIDGET-ID} </code>
                                    <p class="text-muted small mb-0">
                                        Find your widget ID in your Elfsight dashboard. Example:
                                        <code class="small">{elfsight 80550162-ade3-4967-9296-3ebecb119bd7}</code>
                                    </p>
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

        <!-- How To Modal: HTML import workflow -->
        <div class="modal fade" :class="{ show: showHowToModal }" :style="{ display: showHowToModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-question-circle me-2"></i>
                            How To: Import an HTML Page with Images &amp; Assets
                        </h5>
                        <button type="button" class="btn-close" @click="showHowToModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <ol class="mb-4 how-to-steps">
                            <li class="mb-3">
                                <strong>Paste your HTML.</strong>
                                In the sidebar, set <em>Page Type</em> to <em>Content only</em> or <em>Full page (raw HTML)</em>, then paste or upload
                                your HTML in the content editor. <strong>Save the page</strong> — assets can only be uploaded to a saved page.
                            </li>
                            <li class="mb-3">
                                <strong>Upload the assets.</strong>
                                In the <em>Page Assets</em> panel, select all the images/media your HTML references (you can multi-select) and click
                                <em>Upload</em>. Each file gets a hosted URL and stays grouped with this page.
                            </li>
                            <li class="mb-3">
                                <strong>Copy the AI prompt.</strong>
                                Click <em>Copy Prompt + URL List</em> below. It contains ready-made instructions plus the filename → URL list for
                                everything you uploaded.
                            </li>
                            <li class="mb-3">
                                <strong>Run it in Claude (or any AI tool).</strong>
                                Paste the prompt, add your HTML where marked, and send. You'll get back the full HTML with every reference pointing to
                                the hosted URLs.
                            </li>
                            <li class="mb-3">
                                <strong>Missing files?</strong>
                                If the AI reports filenames not in the list, upload them here, click <em>Copy URL List</em>, and reply with the new
                                URLs.
                            </li>
                            <li>
                                <strong>Paste the result back.</strong>
                                Replace the HTML in the content editor with the AI's full output and <strong>Save</strong>. Use <em>View Page</em> to
                                confirm everything loads.
                            </li>
                        </ol>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-semibold mb-0">The Prompt</h6>
                            <button type="button" class="btn btn-sm" :class="promptCopied ? 'btn-success' : 'btn-primary'" @click="copyPrompt">
                                <i class="bi me-1" :class="promptCopied ? 'bi-check-lg' : 'bi-clipboard'"></i>
                                {{ promptCopied ? 'Copied!' : 'Copy Prompt + URL List' }}
                            </button>
                        </div>
                        <pre class="bg-light border rounded p-3 small mb-0" style="white-space: pre-wrap; max-height: 260px; overflow-y: auto">{{
                            aiPrompt
                        }}</pre>
                        <div v-if="pageAssets.length === 0" class="text-warning small mt-2">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            No assets uploaded yet — the URL list in the prompt is empty. Upload files first, then copy.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showHowToModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showHowToModal" class="modal-backdrop fade show"></div>
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

/* Fix file input styling for better visibility */
input[type='file'].form-control {
    color: #495057 !important;
}

input[type='file'].form-control::file-selector-button {
    color: #495057 !important;
    background-color: #e9ecef !important;
    border: 1px solid #ced4da !important;
}

input[type='file'].form-control:hover::file-selector-button {
    background-color: #dde0e3 !important;
}

/* Ensure better contrast for secondary text */
.text-secondary {
    color: #495057 !important;
}

/* Better contrast for form check labels in admin */
.form-check-label.text-dark {
    color: #212529 !important;
    font-weight: 500;
}
</style>
