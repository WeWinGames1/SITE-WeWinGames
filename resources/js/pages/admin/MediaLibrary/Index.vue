<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Media {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    full_url: string;
    thumb_url: string;
    preview_url: string;
    created_at: string;
}

interface Props {
    media: {
        data: Media[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        next_page_url: string | null;
        prev_page_url: string | null;
    };
}

const props = defineProps<Props>();

const selectedFiles = ref<File[]>([]);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedMedia = ref<number[]>([]);
const showDeleteModal = ref(false);
const mediaToDelete = ref<Media | null>(null);
const showPreviewModal = ref(false);
const previewMedia = ref<Media | null>(null);
const fileInputKey = ref(0);

// Computed
const isAllSelected = computed(() => {
    return props.media.data.length > 0 && selectedMedia.value.length === props.media.data.length;
});

// Methods
function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        selectedFiles.value = Array.from(input.files);
    }
}

async function uploadFiles() {
    if (selectedFiles.value.length === 0) return;

    uploading.value = true;
    uploadProgress.value = 0;

    const formData = new FormData();
    selectedFiles.value.forEach(file => {
        formData.append('files[]', file);
    });

    try {
        const response = await axios.post(route('admin.media-library.store'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            onUploadProgress: (progressEvent) => {
                if (progressEvent.total) {
                    uploadProgress.value = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                }
            },
        });

        // Refresh the page to show new media
        router.reload({ only: ['media'] });
        
        // Reset file input
        selectedFiles.value = [];
        fileInputKey.value++;
    } catch (error: any) {
        console.error('Upload error:', error);
        if (error.response?.status === 413) {
            alert('File too large. Maximum file size is 2MB. Please use a smaller image.');
        } else if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert('Error uploading files. Please try again.');
        }
    } finally {
        uploading.value = false;
        uploadProgress.value = 0;
    }
}

function toggleSelection(mediaId: number) {
    const index = selectedMedia.value.indexOf(mediaId);
    if (index > -1) {
        selectedMedia.value.splice(index, 1);
    } else {
        selectedMedia.value.push(mediaId);
    }
}

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedMedia.value = [];
    } else {
        selectedMedia.value = props.media.data.map(m => m.id);
    }
}

function confirmDelete(media: Media) {
    mediaToDelete.value = media;
    showDeleteModal.value = true;
}

async function deleteMedia() {
    if (!mediaToDelete.value) return;

    try {
        await axios.delete(route('admin.media-library.destroy', mediaToDelete.value.id));
        router.reload({ only: ['media'] });
        showDeleteModal.value = false;
        mediaToDelete.value = null;
    } catch (error) {
        console.error('Delete error:', error);
        alert('Error deleting media. Please try again.');
    }
}

function previewImage(media: Media) {
    console.log('Preview media:', media);
    previewMedia.value = media;
    showPreviewModal.value = true;
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function copyUrl(url: string) {
    navigator.clipboard.writeText(url);
    alert('URL copied to clipboard!');
}
</script>

<template>
    <AdminLayout>
        <Head title="Media Library" />
        
        <div class="p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 fw-bold text-dark">Media Library</h1>
                <div>
                    <input
                        type="file"
                        id="file-upload"
                        multiple
                        accept="image/*"
                        @change="handleFileChange"
                        class="d-none"
                        :key="fileInputKey"
                    />
                    <label for="file-upload" class="btn btn-primary">
                        <i class="bi bi-upload me-2"></i>
                        Upload Images
                    </label>
                </div>
            </div>

            <!-- Upload Progress -->
            <div v-if="uploading" class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-3" role="status">
                        <span class="visually-hidden">Uploading...</span>
                    </div>
                    <div class="flex-grow-1">
                        <div>Uploading {{ selectedFiles.length }} file(s)...</div>
                        <div class="progress mt-2">
                            <div 
                                class="progress-bar" 
                                role="progressbar" 
                                :style="`width: ${uploadProgress}%`"
                                :aria-valuenow="uploadProgress" 
                                aria-valuemin="0" 
                                aria-valuemax="100"
                            >
                                {{ uploadProgress }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Files Preview -->
            <div v-if="selectedFiles.length > 0 && !uploading" class="alert alert-primary mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-file-earmark-image me-2"></i>
                        {{ selectedFiles.length }} file(s) selected
                    </div>
                    <div>
                        <button @click="uploadFiles" class="btn btn-sm btn-success me-2">
                            <i class="bi bi-cloud-upload me-1"></i>
                            Upload
                        </button>
                        <button @click="selectedFiles = []; fileInputKey++" class="btn btn-sm btn-secondary">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>

            <!-- Media Grid -->
            <div v-if="media.data.length > 0">
                <!-- Select All -->
                <div class="mb-3">
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            id="selectAll"
                            :checked="isAllSelected"
                            @change="toggleSelectAll"
                        >
                        <label class="form-check-label text-dark" for="selectAll">
                            Select All
                        </label>
                    </div>
                </div>

                <!-- Grid -->
                <div class="row g-3">
                    <div v-for="item in media.data" :key="item.id" class="col-6 col-md-4 col-lg-3 col-xl-2">
                        <div class="card h-100" :class="{ 'border-primary': selectedMedia.includes(item.id) }">
                            <!-- Image -->
                            <div class="position-relative">
                                <img 
                                    :src="item.thumb_url || item.full_url || '/storage/placeholder.png'" 
                                    :alt="item.name"
                                    class="card-img-top cursor-pointer"
                                    style="height: 150px; object-fit: cover;"
                                    @click="previewImage(item)"
                                    @error="(e) => { (e.target as HTMLImageElement).src = '/storage/placeholder.png' }"
                                >
                                <!-- Selection checkbox -->
                                <div class="position-absolute top-0 start-0 p-2">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        :checked="selectedMedia.includes(item.id)"
                                        @change="toggleSelection(item.id)"
                                        @click.stop
                                    >
                                </div>
                            </div>
                            
                            <!-- Info -->
                            <div class="card-body p-2">
                                <p class="mb-1 small text-truncate" :title="item.name">{{ item.name }}</p>
                                <p class="mb-1 text-muted" style="font-size: 0.75rem;">
                                    {{ formatFileSize(item.size) }}
                                </p>
                                
                                <!-- Actions -->
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <button 
                                        @click="copyUrl(item.full_url || item.thumb_url || '')" 
                                        class="btn btn-outline-secondary"
                                        title="Copy URL"
                                    >
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <button 
                                        @click="previewImage(item)" 
                                        class="btn btn-outline-primary"
                                        title="Preview"
                                    >
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button 
                                        @click="confirmDelete(item)" 
                                        class="btn btn-outline-danger"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <nav v-if="media.last_page > 1" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item" :class="{ disabled: !media.prev_page_url }">
                            <Link 
                                class="page-link" 
                                :href="media.prev_page_url || '#'"
                                preserve-scroll
                            >
                                Previous
                            </Link>
                        </li>
                        
                        <li class="page-item active">
                            <span class="page-link">
                                Page {{ media.current_page }} of {{ media.last_page }}
                            </span>
                        </li>
                        
                        <li class="page-item" :class="{ disabled: !media.next_page_url }">
                            <Link 
                                class="page-link" 
                                :href="media.next_page_url || '#'"
                                preserve-scroll
                            >
                                Next
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-5">
                <i class="bi bi-images text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3">No Images Yet</h3>
                <p class="text-muted">Upload your first image to get started.</p>
                <label for="file-upload-empty" class="btn btn-primary">
                    <i class="bi bi-upload me-2"></i>
                    Upload Images
                </label>
                <input
                    type="file"
                    id="file-upload-empty"
                    multiple
                    accept="image/*"
                    @change="handleFileChange"
                    class="d-none"
                />
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade" :class="{ show: showDeleteModal }" :style="{ display: showDeleteModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Media</h5>
                        <button type="button" class="btn-close" @click="showDeleteModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this image?</p>
                        <p v-if="mediaToDelete" class="fw-bold">{{ mediaToDelete.name }}</p>
                        <p class="text-danger mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            This action cannot be undone.
                        </p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showDeleteModal = false">Cancel</button>
                        <button type="button" class="btn btn-danger" @click="deleteMedia">
                            <i class="bi bi-trash me-1"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDeleteModal" class="modal-backdrop fade show"></div>

        <!-- Preview Modal -->
        <div class="modal fade" :class="{ show: showPreviewModal }" :style="{ display: showPreviewModal ? 'block' : 'none' }" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ previewMedia?.name }}</h5>
                        <button type="button" class="btn-close" @click="showPreviewModal = false"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img 
                            v-if="previewMedia" 
                            :src="previewMedia.preview_url || previewMedia.full_url || ''" 
                            :alt="previewMedia.name"
                            class="img-fluid"
                            @error="(e) => { (e.target as HTMLImageElement).src = '/storage/placeholder.png' }"
                        >
                        <div v-if="previewMedia" class="mt-3">
                            <dl class="row">
                                <dt class="col-sm-3">File Name:</dt>
                                <dd class="col-sm-9">{{ previewMedia.file_name }}</dd>
                                
                                <dt class="col-sm-3">Size:</dt>
                                <dd class="col-sm-9">{{ formatFileSize(previewMedia.size) }}</dd>
                                
                                <dt class="col-sm-3">Type:</dt>
                                <dd class="col-sm-9">{{ previewMedia.mime_type }}</dd>
                                
                                <dt class="col-sm-3">Uploaded:</dt>
                                <dd class="col-sm-9">{{ formatDate(previewMedia.created_at) }}</dd>
                                
                                <dt class="col-sm-3">URL:</dt>
                                <dd class="col-sm-9">
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            class="form-control form-control-sm" 
                                            :value="previewMedia.full_url || previewMedia.preview_url || ''" 
                                            readonly
                                        >
                                        <button 
                                            class="btn btn-sm btn-outline-secondary" 
                                            @click="copyUrl(previewMedia.full_url || previewMedia.preview_url || '')"
                                        >
                                            <i class="bi bi-clipboard"></i>
                                        </button>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showPreviewModal = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showPreviewModal" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>