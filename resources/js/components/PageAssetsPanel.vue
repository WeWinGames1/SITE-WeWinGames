<script setup lang="ts">
import MediaPicker from '@/components/MediaPicker.vue';
import axios from 'axios';
import { computed, ref } from 'vue';

export interface PageAsset {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    full_url: string;
    thumb_url: string;
}

const props = withDefaults(
    defineProps<{
        ownerId: number | null;
        ownerKey: 'page_id' | 'landing_page_id';
        initialAssets?: PageAsset[];
        ownerLabel?: string;
    }>(),
    {
        initialAssets: () => [],
        ownerLabel: 'page',
    },
);

const assets = ref<PageAsset[]>([...props.initialAssets]);
const assetFiles = ref<File[]>([]);
const assetFileInputKey = ref(0);
const assetsUploading = ref(false);
const assetsUploadProgress = ref(0);
const assetsError = ref('');
const urlListCopied = ref(false);
const showLibraryPicker = ref(false);
const showHowToModal = ref(false);
const promptCopied = ref(false);

function handleAssetFilesChange(event: Event) {
    const input = event.target as HTMLInputElement;
    assetFiles.value = input.files ? Array.from(input.files) : [];
    assetsError.value = '';
}

function prependNewAssets(incoming: PageAsset[]) {
    const existing = new Set(assets.value.map((a) => a.id));
    assets.value = [...incoming.filter((m) => !existing.has(m.id)), ...assets.value];
}

async function uploadAssets() {
    if (!props.ownerId || assetFiles.value.length === 0) return;

    assetsUploading.value = true;
    assetsUploadProgress.value = 0;
    assetsError.value = '';

    const formData = new FormData();
    assetFiles.value.forEach((file) => formData.append('files[]', file));
    formData.append(props.ownerKey, String(props.ownerId));

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

        prependNewAssets(response.data.media || []);
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

async function assignFromLibrary(selected: PageAsset | PageAsset[]) {
    const items = Array.isArray(selected) ? selected : [selected];
    showLibraryPicker.value = false;
    if (!props.ownerId || items.length === 0) return;

    assetsError.value = '';
    try {
        const response = await axios.post(route('admin.media-library.assign'), {
            media_ids: items.map((m) => m.id),
            [props.ownerKey]: props.ownerId,
        });
        prependNewAssets(response.data.media || []);
    } catch (error: any) {
        assetsError.value = error.response?.data?.message || 'Error assigning media to this page.';
    }
}

async function detachAsset(asset: PageAsset) {
    if (!props.ownerId) return;

    assetsError.value = '';
    try {
        await axios.post(route('admin.media-library.assign'), {
            media_ids: [asset.id],
            [props.ownerKey]: props.ownerId,
            detach: true,
        });
        assets.value = assets.value.filter((a) => a.id !== asset.id);
    } catch (error: any) {
        assetsError.value = error.response?.data?.message || 'Error removing media from this page.';
    }
}

async function deleteAsset(asset: PageAsset) {
    if (!confirm(`Permanently delete "${asset.file_name}" from the media library? If any page still references it, the image will break.`)) return;

    assetsError.value = '';
    try {
        await axios.delete(route('admin.media-library.destroy', asset.id));
        assets.value = assets.value.filter((a) => a.id !== asset.id);
    } catch (error: any) {
        assetsError.value = error.response?.data?.message || 'Error deleting file.';
    }
}

function copyAssetUrl(url: string) {
    navigator.clipboard.writeText(url);
}

const urlListText = computed(() => assets.value.map((a) => `${a.file_name}: ${a.full_url}`).join('\n'));

function copyUrlList() {
    navigator.clipboard.writeText(urlListText.value);
    urlListCopied.value = true;
    setTimeout(() => (urlListCopied.value = false), 2000);
}

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
                    v-if="assets.length > 0"
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
            <div v-if="!ownerId" class="text-secondary small">
                <i class="bi bi-info-circle me-1"></i>
                Save the {{ ownerLabel }} first, then upload images and other assets here.
            </div>

            <template v-else>
                <p class="text-secondary small mb-2">
                    Upload the images/assets referenced by your HTML, or pick existing ones from the library, then use
                    <strong>Copy URL List</strong> and paste it into Claude (or any tool) to rewrite the references in your HTML to these hosted URLs.
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
                        {{ assetsUploading ? `${assetsUploadProgress}%` : `Upload${assetFiles.length ? ` (${assetFiles.length})` : ''}` }}
                    </button>
                    <button type="button" class="btn btn-outline-primary text-nowrap" :disabled="assetsUploading" @click="showLibraryPicker = true">
                        <i class="bi bi-images me-1"></i>
                        Library
                    </button>
                </div>

                <div v-if="assetsError" class="alert alert-danger small py-2">{{ assetsError }}</div>

                <div v-if="assets.length === 0" class="text-secondary small">No assets assigned to this {{ ownerLabel }} yet.</div>

                <div v-else class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            <tr v-for="asset in assets" :key="asset.id">
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
                                <td class="text-end" style="width: 120px">
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
                                        class="btn btn-sm btn-outline-warning me-1"
                                        :title="`Remove from this ${ownerLabel} (keeps the file in the library)`"
                                        @click="detachAsset(asset)"
                                    >
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete from media library"
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

    <!-- Library picker: upload new or select existing media to assign -->
    <MediaPicker :show="showLibraryPicker" :multiple="true" @select="assignFromLibrary" @close="showLibraryPicker = false" />

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
                    <ol class="mb-4">
                        <li class="mb-3">
                            <strong>Paste your HTML.</strong>
                            In the sidebar, set <em>Page Type</em> to <em>Content only</em> or <em>Full page (raw HTML)</em>, then paste or upload
                            your HTML in the content editor. <strong>Save the {{ ownerLabel }}</strong> — assets can only be attached to a saved
                            {{ ownerLabel }}.
                        </li>
                        <li class="mb-3">
                            <strong>Add the assets.</strong>
                            In the <em>Page Assets</em> panel, upload the images/media your HTML references (multi-select works), or click
                            <em>Library</em> to pick files already in the media library. Each file gets a hosted URL and stays grouped with this
                            {{ ownerLabel }}.
                        </li>
                        <li class="mb-3">
                            <strong>Copy the AI prompt.</strong>
                            Click <em>Copy Prompt + URL List</em> below. It contains ready-made instructions plus the filename → URL list for
                            everything you added.
                        </li>
                        <li class="mb-3">
                            <strong>Run it in Claude (or any AI tool).</strong>
                            Paste the prompt, add your HTML where marked, and send. You'll get back the full HTML with every reference pointing to the
                            hosted URLs.
                        </li>
                        <li class="mb-3">
                            <strong>Missing files?</strong>
                            If the AI reports filenames not in the list, add them here, click <em>Copy URL List</em>, and reply with the new URLs.
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
                    <div v-if="assets.length === 0" class="text-warning small mt-2">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        No assets added yet — the URL list in the prompt is empty. Add files first, then copy.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showHowToModal = false">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="showHowToModal" class="modal-backdrop fade show"></div>
</template>
