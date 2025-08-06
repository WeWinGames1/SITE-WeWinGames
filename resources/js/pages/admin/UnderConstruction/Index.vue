<template>
    <AdminLayout>
        <Head title="Under Construction Settings" />

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Under Construction Settings</h1>
                    <p class="text-muted mb-0">Manage site maintenance mode and visitor messages</p>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input v-model="form.is_enabled" type="checkbox" class="form-check-input" id="is_enabled" />
                                        <label class="form-check-label" for="is_enabled"> Enable Under Construction Mode </label>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input
                                                v-model="form.start_date"
                                                type="datetime-local"
                                                class="form-control"
                                                id="start_date"
                                                :class="{ 'is-invalid': form.errors.start_date }"
                                            />
                                            <div v-if="form.errors.start_date" class="invalid-feedback">
                                                {{ form.errors.start_date }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input
                                                v-model="form.end_date"
                                                type="datetime-local"
                                                class="form-control"
                                                id="end_date"
                                                :class="{ 'is-invalid': form.errors.end_date }"
                                            />
                                            <div v-if="form.errors.end_date" class="invalid-feedback">
                                                {{ form.errors.end_date }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label for="blurb" class="form-label mb-0">Message</label>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="toggleSourceView">
                                            <i class="bi" :class="showSourceCode ? 'bi-eye' : 'bi-code-slash'"></i>
                                            {{ showSourceCode ? 'Visual Editor' : 'Source Code' }}
                                        </button>
                                    </div>

                                    <!-- Visual Editor -->
                                    <div v-if="!showSourceCode" class="editor-wrapper">
                                        <div class="editor-toolbar btn-toolbar mb-2" role="toolbar">
                                            <div class="btn-group me-2" role="group">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleBold().run()"
                                                    :class="{ active: editor?.isActive('bold') }"
                                                    title="Bold"
                                                >
                                                    <i class="bi bi-type-bold"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleItalic().run()"
                                                    :class="{ active: editor?.isActive('italic') }"
                                                    title="Italic"
                                                >
                                                    <i class="bi bi-type-italic"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group me-2" role="group">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleHeading({ level: 2 }).run()"
                                                    :class="{ active: editor?.isActive('heading', { level: 2 }) }"
                                                    title="Heading 2"
                                                >
                                                    <i class="bi bi-type-h2"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleHeading({ level: 3 }).run()"
                                                    :class="{ active: editor?.isActive('heading', { level: 3 }) }"
                                                    title="Heading 3"
                                                >
                                                    <i class="bi bi-type-h3"></i>
                                                </button>
                                            </div>
                                            <div class="btn-group me-2" role="group">
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleBulletList().run()"
                                                    :class="{ active: editor?.isActive('bulletList') }"
                                                    title="Bullet List"
                                                >
                                                    <i class="bi bi-list-ul"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-secondary"
                                                    @click="editor?.chain().focus().toggleOrderedList().run()"
                                                    :class="{ active: editor?.isActive('orderedList') }"
                                                    title="Numbered List"
                                                >
                                                    <i class="bi bi-list-ol"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <EditorContent :editor="editor" class="form-control editor-content" style="min-height: 200px" />
                                    </div>

                                    <!-- Source Code Editor -->
                                    <div v-else>
                                        <textarea
                                            :value="sourceCode"
                                            @input="updateSourceCode"
                                            class="form-control font-monospace"
                                            rows="10"
                                            style="font-size: 0.875rem"
                                        ></textarea>
                                    </div>

                                    <div v-if="form.errors.blurb" class="invalid-feedback d-block">
                                        {{ form.errors.blurb }}
                                    </div>
                                    <div class="form-text">Format your message using the rich text editor above.</div>
                                </div>

                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                        <span v-if="!form.processing">Save Settings</span>
                                        <span v-else>
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Saving...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">Preview</h5>
                            <p class="card-text text-muted">This is how your message will appear to visitors:</p>
                            <div class="border rounded p-3 bg-light">
                                <div v-html="form.blurb"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-body">
                            <h5 class="card-title">How it works</h5>
                            <p class="card-text">
                                When enabled, visitors to your site will see an under construction page instead of the regular content.
                            </p>
                            <ul>
                                <li><strong>Enable/Disable:</strong> Toggle to turn the under construction mode on or off immediately.</li>
                                <li>
                                    <strong>Start Date:</strong> Optional. If set, the under construction mode will automatically activate at this
                                    date/time.
                                </li>
                                <li>
                                    <strong>End Date:</strong> Optional. If set, the under construction mode will automatically deactivate at this
                                    date/time.
                                </li>
                                <li><strong>Message:</strong> The message displayed to visitors. You can use HTML for formatting.</li>
                            </ul>
                            <p class="card-text">
                                <strong>Note:</strong> Admin users will always be able to access the site, even when under construction mode is
                                active.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import StarterKit from '@tiptap/starter-kit';
import { Editor, EditorContent } from '@tiptap/vue-3';
import { ref, watch } from 'vue';

interface UnderConstructionSetting {
    id?: number;
    is_enabled: boolean;
    start_date: string | null;
    end_date: string | null;
    blurb: string | null;
}

interface Props {
    settings?: UnderConstructionSetting;
}

const props = defineProps<Props>();

const showSourceCode = ref(false);
const sourceCode = ref('');

// Tiptap editor setup
const editor = ref(
    new Editor({
        content:
            props.settings?.blurb ||
            "<p>We're making some exciting improvements to our website! We'll be back online shortly. Thank you for your patience – see you soon! ✨</p>",
        extensions: [StarterKit],
        onUpdate: ({ editor }) => {
            form.blurb = editor.getHTML();
            if (showSourceCode.value) {
                sourceCode.value = editor.getHTML();
            }
        },
    }),
);

// Keep editor in sync if settings change
watch(
    () => props.settings?.blurb,
    (val) => {
        if (val && editor.value) editor.value.commands.setContent(val);
    },
);

const formatDateForInput = (dateString: string | null): string => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const form = useForm({
    is_enabled: props.settings?.is_enabled || false,
    start_date: formatDateForInput(props.settings?.start_date || null),
    end_date: formatDateForInput(props.settings?.end_date || null),
    blurb:
        props.settings?.blurb ||
        "<p>We're making some exciting improvements to our website! We'll be back online shortly. Thank you for your patience – see you soon! ✨</p>",
});

const submit = () => {
    form.post(route('admin.under-construction.update'), {
        preserveScroll: true,
    });
};

function toggleSourceView() {
    showSourceCode.value = !showSourceCode.value;
    if (showSourceCode.value) {
        // Switch to source mode
        sourceCode.value = editor.value?.getHTML() || '';
    } else {
        // Switch back to visual mode
        if (editor.value) {
            editor.value.commands.setContent(sourceCode.value);
            form.blurb = sourceCode.value;
        }
    }
}

function updateSourceCode(event: Event) {
    const target = event.target as HTMLTextAreaElement;
    sourceCode.value = target.value;
    form.blurb = target.value;
}
</script>

<style scoped>
/* Tiptap Editor Styles */
.editor-wrapper {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #fff;
}

.editor-toolbar {
    background-color: #f8f9fa;
    padding: 0.5rem;
    border-bottom: 1px solid #dee2e6;
    border-radius: 0.375rem 0.375rem 0 0;
}

.editor-content {
    border: none !important;
    min-height: 200px;
    padding: 1rem;
}

.editor-content :deep(.ProseMirror) {
    min-height: 180px;
    outline: none;
}

.editor-content :deep(.ProseMirror > * + *) {
    margin-top: 0.75em;
}

.editor-content :deep(.ProseMirror ul),
.editor-content :deep(.ProseMirror ol) {
    padding-left: 1.5rem;
}

.editor-content :deep(.ProseMirror h2) {
    font-size: 1.5rem;
    font-weight: 600;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.editor-content :deep(.ProseMirror h3) {
    font-size: 1.25rem;
    font-weight: 600;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}

.editor-content :deep(.ProseMirror p) {
    margin-bottom: 0.75rem;
}

.editor-content :deep(.ProseMirror ul li),
.editor-content :deep(.ProseMirror ol li) {
    margin-bottom: 0.25rem;
}

.btn-toolbar .btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}
</style>
