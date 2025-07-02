<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';

const props = defineProps<{ page: any | null }>();

const form = useForm({
    title: props.page?.title || '',
    slug: props.page?.slug || '',
    content: props.page?.content || '',
    featured_image: null, // for file upload
    published: props.page?.published ?? true,
});

const preview = ref(props.page?.featured_image || '');
const showSourceCode = ref(false);
const sourceCode = ref('');

// Tiptap setup
const editor = ref(new Editor({
    content: form.content,
    extensions: [StarterKit],
    onUpdate: ({ editor }) => {
        form.content = editor.getHTML();
        if (showSourceCode.value) {
            sourceCode.value = editor.getHTML();
        }
    },
}));

// Keep editor in sync if editing existing page
watch(() => props.page?.content, (val) => {
    if (val && editor.value) editor.value.commands.setContent(val);
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

function submit() {
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
</script>

<template>
    <AdminLayout>
        <Head :title="page ? 'Edit Page' : 'Create Page'" />
        
        <div class="p-4">
            <!-- Header -->
            <div class="mb-4">
                <Link
                    :href="route('admin.pages.index')"
                    class="btn btn-link text-decoration-none p-0 mb-3"
                >
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
                                <label for="title" class="form-label text-dark fw-medium">
                                    Page Title <span class="text-danger">*</span>
                                </label>
                                <input 
                                    v-model="form.title" 
                                    id="title" 
                                    type="text"
                                    class="form-control" 
                                    placeholder="Enter page title"
                                    @input="generateSlug"
                                    required 
                                />
                                <div v-if="form.errors.title" class="invalid-feedback d-block">
                                    {{ form.errors.title }}
                                </div>
                            </div>
                        </div>

                        <!-- Slug -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <label for="slug" class="form-label text-dark fw-medium">
                                    URL Slug <span class="text-danger">*</span>
                                </label>
                                <p class="text-secondary small mb-2">The URL path for this page (e.g., /pages/your-page-slug)</p>
                                <input 
                                    v-model="form.slug" 
                                    id="slug" 
                                    type="text"
                                    class="form-control" 
                                    placeholder="page-url-slug"
                                    pattern="[a-z0-9-]+"
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
                                
                                <!-- Editor Toolbar -->
                                <div v-if="editor" class="border rounded-top bg-light p-2 d-flex flex-wrap align-items-center gap-1">
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleBold().run()"
                                        :class="{ 'active': editor.isActive('bold') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Bold"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-type-bold"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleItalic().run()"
                                        :class="{ 'active': editor.isActive('italic') }"
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
                                        :class="{ 'active': editor.isActive('heading', { level: 1 }) }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Heading 1"
                                        :disabled="showSourceCode"
                                    >
                                        H1
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
                                        :class="{ 'active': editor.isActive('heading', { level: 2 }) }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Heading 2"
                                        :disabled="showSourceCode"
                                    >
                                        H2
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
                                        :class="{ 'active': editor.isActive('heading', { level: 3 }) }"
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
                                        :class="{ 'active': editor.isActive('bulletList') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Bullet List"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                    <button
                                        type="button"
                                        @click="editor.chain().focus().toggleOrderedList().run()"
                                        :class="{ 'active': editor.isActive('orderedList') }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Numbered List"
                                        :disabled="showSourceCode"
                                    >
                                        <i class="bi bi-list-ol"></i>
                                    </button>
                                    <div class="vr"></div>
                                    <button
                                        type="button"
                                        @click="toggleSourceView"
                                        :class="{ 'active': showSourceCode }"
                                        class="btn btn-sm btn-outline-secondary"
                                        title="Show Source Code"
                                    >
                                        <i class="bi bi-code-slash"></i>
                                    </button>
                                </div>

                                <!-- Editor Content -->
                                <div v-if="!showSourceCode">
                                    <EditorContent 
                                        :editor="editor" 
                                        class="border border-top-0 rounded-bottom p-3"
                                        style="min-height: 300px;"
                                    />
                                </div>
                                
                                <!-- Source Code Editor -->
                                <div v-else>
                                    <textarea
                                        v-model="sourceCode"
                                        @input="updateSourceCode"
                                        class="form-control border border-top-0 rounded-bottom"
                                        style="min-height: 300px; font-family: 'Courier New', monospace; font-size: 14px;"
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
                        <div class="sticky-top" style="top: 1rem;">
                        <!-- Publish Settings -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Publish Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check mb-3">
                                    <input
                                        id="published"
                                        v-model="form.published"
                                        type="checkbox"
                                        class="form-check-input"
                                    />
                                    <label for="published" class="form-check-label text-dark">
                                        Published
                                    </label>
                                </div>

                                <div class="d-grid gap-2">
                                    <button
                                        type="submit"
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                        {{ form.processing ? (page ? 'Updating...' : 'Creating...') : (page ? 'Update Page' : 'Create Page') }}
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
                                    <img :src="preview" alt="Preview" class="img-fluid rounded">
                                </div>
                                <input
                                    type="file"
                                    @change="handleImageChange"
                                    accept="image/*"
                                    class="form-control"
                                />
                                <div class="text-secondary small mt-1">Optional header image for the page</div>
                            </div>
                        </div>

                        <!-- Page Preview -->
                        <div v-if="page" class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Actions</h5>
                            </div>
                            <div class="card-body">
                                <a 
                                    :href="route('pages.show', page.slug)" 
                                    target="_blank"
                                    class="btn btn-outline-primary btn-sm w-100"
                                >
                                    <i class="bi bi-eye me-1"></i>
                                    View Page
                                </a>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
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
input[type="file"].form-control {
    color: #495057 !important;
}

input[type="file"].form-control::file-selector-button {
    color: #495057 !important;
    background-color: #e9ecef !important;
    border: 1px solid #ced4da !important;
}

input[type="file"].form-control:hover::file-selector-button {
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