<script setup lang="ts">
import MediaPicker from '@/components/MediaPicker.vue';
import PageAssetsPanel, { type PageAsset } from '@/components/PageAssetsPanel.vue';
import { usePagePreview } from '@/composables/usePagePreview';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Image from '@tiptap/extension-image';
import TiptapLink from '@tiptap/extension-link';
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import QrcodeVue from 'qrcode.vue';
import { computed, ref, watch } from 'vue';

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

watch(
    () => form.raw_html,
    (val) => {
        if (isRawMode.value) form.content = val;
    },
);

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

// Homepage helper reference (bound as plain text so the {{ }} tokens are not
// parsed as Vue interpolation).
const homepageWidgets = '[pricing] · [testimonials] · [todays-bets]';
const homepageStatTokens = '{{stat:thisYearROI}} · {{stat:thisYearProfit}} · {{stat:winRatio}} · {{stat:monthlyProfit}} · {{stat:golfROI2026}}';

const preview = ref(props.page?.featured_image || '');

const { openPreview } = usePagePreview();

function previewCurrentContent() {
    openPreview(isRawMode.value ? form.raw_html : form.content, form.title);
}
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
    } else if (!/^[a-z0-9-]+$/.test(form.slug)) {
        errors.slug = 'The slug may only contain lowercase letters, numbers, and hyphens.';
        isValid = false;
    }

    if (isRawMode.value) {
        if (!form.raw_html || form.raw_html.trim() === '') {
            errors.raw_html = 'Paste or upload the HTML for this page.';
            isValid = false;
        }
    } else if (!form.content || !form.content.trim()) {
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
        form.put(route('admin.landing-pages.update', props.page.id));
    } else {
        form.post(route('admin.landing-pages.store'));
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

const qrCodeUrl = computed(() => {
    if (props.page?.slug) {
        return `${window.location.origin}/landing/${props.page.slug}`;
    }
    return '';
});

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
</script>

<template>
    <AdminLayout>
        <Head :title="page ? 'Edit Landing Page' : 'Create Landing Page'" />

        <div class="p-4">
            <!-- Header -->
            <div class="mb-4">
                <Link :href="route('admin.landing-pages.index')" class="btn btn-link text-decoration-none p-0 mb-3">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Landing Pages
                </Link>
                <h1 class="h2 fw-bold text-dark">{{ page ? 'Edit Landing Page' : 'Create New Landing Page' }}</h1>
                <p class="text-secondary small">
                    {{ page ? 'Edit this marketing landing page' : 'Create a new landing page for marketing campaigns' }}
                </p>
            </div>

            <form @submit.prevent="submit">
                <div class="row">
                    <!-- Main Content -->
                    <div class="col-lg-8">
                        <!-- Title -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="title" class="form-label text-dark fw-medium">
                                    Landing Page Title <span class="text-danger">*</span>
                                </label>
                                <input
                                    v-model="form.title"
                                    id="title"
                                    type="text"
                                    class="form-control"
                                    placeholder="Enter landing page title"
                                    @input="generateSlug"
                                    required
                                    maxlength="255"
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
                                <p class="text-secondary small mb-2">The URL path for this landing page (e.g., /landing/your-campaign-slug)</p>
                                <input
                                    v-model="form.slug"
                                    id="slug"
                                    type="text"
                                    class="form-control"
                                    placeholder="campaign-landing-page"
                                    pattern="[a-z0-9\-]+"
                                    required
                                    maxlength="255"
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
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label text-dark fw-medium mb-0">Landing Page Content</label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" @click="previewCurrentContent">
                                        <i class="bi bi-eye me-1"></i>
                                        Preview
                                    </button>
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
                        <PageAssetsPanel
                            :owner-id="page?.id ?? null"
                            owner-key="landing_page_id"
                            :initial-assets="assets"
                            owner-label="landing page"
                        />
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
                                            {{
                                                form.processing
                                                    ? page
                                                        ? 'Updating...'
                                                        : 'Creating...'
                                                    : page
                                                      ? 'Update Landing Page'
                                                      : 'Create Landing Page'
                                            }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Featured Image -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Hero Image</h5>
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

                                    <input
                                        type="file"
                                        @change="handleImageChange"
                                        accept="image/*"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.featured_image }"
                                        :key="fileInputKey"
                                    />
                                    <div class="text-secondary small mt-1">Hero image for the landing page</div>
                                    <div v-if="form.errors.featured_image" class="invalid-feedback d-block">
                                        {{ form.errors.featured_image }}
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code & Actions -->
                            <div v-if="page" class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Sharing & Analytics</h5>
                                </div>
                                <div class="card-body">
                                    <!-- QR Code -->
                                    <div v-if="qrCodeUrl" class="text-center mb-3">
                                        <QrcodeVue :value="qrCodeUrl" :size="150" background="white" foreground="black" class="mx-auto" />
                                        <p class="text-secondary small mt-2">QR Code for easy sharing</p>
                                    </div>

                                    <!-- Actions -->
                                    <div class="d-grid gap-2">
                                        <a :href="route('landing.show', page.slug)" target="_blank" class="btn btn-outline-primary btn-sm">
                                            <i class="bi bi-eye me-1"></i>
                                            View Landing Page
                                        </a>
                                        <button
                                            type="button"
                                            @click="navigator.clipboard.writeText(qrCodeUrl)"
                                            class="btn btn-outline-secondary btn-sm"
                                        >
                                            <i class="bi bi-link me-1"></i>
                                            Copy URL
                                        </button>
                                    </div>
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

                            <!-- Homepage Tokens & Widgets -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="bi bi-house-gear me-1"></i>
                                        Homepage Tokens
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted small mb-2">
                                        When this page is used as the homepage, these tokens are replaced with live stats and the shortcodes render
                                        live widgets:
                                    </p>
                                    <p class="text-muted small mb-1 fw-semibold">Live widgets</p>
                                    <code class="d-block bg-light p-2 rounded small mb-2">{{ homepageWidgets }}</code>
                                    <p class="text-muted small mb-1 fw-semibold">Live stats</p>
                                    <code class="d-block bg-light p-2 rounded small mb-0">{{ homepageStatTokens }}</code>
                                </div>
                            </div>

                            <!-- Marketing Tips -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Marketing Tips</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled small text-secondary mb-0">
                                        <li class="mb-1">
                                            <i class="bi bi-check-circle text-success me-1"></i> Keep headlines clear and benefit-focused
                                        </li>
                                        <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Add strong call-to-action buttons</li>
                                        <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Use high-quality hero images</li>
                                        <li class="mb-1">
                                            <i class="bi bi-check-circle text-success me-1"></i> Include social proof or testimonials
                                        </li>
                                        <li><i class="bi bi-check-circle text-success me-1"></i> Keep forms short and simple</li>
                                    </ul>
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
