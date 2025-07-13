<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
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
    show: boolean;
    multiple?: boolean;
}

interface Emits {
    (e: 'select', media: Media | Media[]): void;
    (e: 'close'): void;
}

const props = withDefaults(defineProps<Props>(), {
    multiple: false,
});

const emit = defineEmits<Emits>();

const media = ref<Media[]>([]);
const loading = ref(false);
const searchQuery = ref('');
const selectedMedia = ref<Media[]>([]);
const currentPage = ref(1);
const lastPage = ref(1);
const uploading = ref(false);
const uploadProgress = ref(0);
const selectedFiles = ref<File[]>([]);
const fileInputKey = ref(0);
const hasLoadedInitially = ref(false);

// Load media when modal is shown
watch(() => props.show, (newValue) => {
    if (newValue && !hasLoadedInitially.value) {
        loadMedia();
        hasLoadedInitially.value = true;
    }
});

async function loadMedia(page = 1) {
    loading.value = true;
    try {
        const response = await axios.get(route('admin.media-library.picker'), {
            params: {
                page,
                search: searchQuery.value,
            },
        });
        
        media.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
    } catch (error) {
        console.error('Error loading media:', error);
    } finally {
        loading.value = false;
    }
}

function handleFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files) {
        selectedFiles.value = Array.from(input.files);
        uploadFiles();
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

        // Reload media to show new uploads
        await loadMedia();
        
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

function toggleSelection(item: Media) {
    if (props.multiple) {
        const index = selectedMedia.value.findIndex(m => m.id === item.id);
        if (index > -1) {
            selectedMedia.value.splice(index, 1);
        } else {
            selectedMedia.value.push(item);
        }
    } else {
        selectedMedia.value = [item];
    }
}

function isSelected(item: Media): boolean {
    return selectedMedia.value.some(m => m.id === item.id);
}

function selectMedia() {
    if (selectedMedia.value.length === 0) return;
    
    if (props.multiple) {
        emit('select', selectedMedia.value);
    } else {
        emit('select', selectedMedia.value[0]);
    }
    
    // Reset selection
    selectedMedia.value = [];
}

function close() {
    selectedMedia.value = [];
    searchQuery.value = '';
    emit('close');
}

function search() {
    currentPage.value = 1;
    loadMedia();
}

function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<template>
    <div class="modal fade" :class="{ show }" :style="{ display: show ? 'block' : 'none' }" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Image</h5>
                    <button type="button" class="btn-close" @click="close"></button>
                </div>
                <div class="modal-body">
                    <!-- Upload Section -->
                    <div class="mb-3">
                        <div class="row align-items-center">
                            <div class="col">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    class="form-control"
                                    placeholder="Search images..."
                                    @keyup.enter="search"
                                >
                            </div>
                            <div class="col-auto">
                                <button @click="search" class="btn btn-secondary">
                                    <i class="bi bi-search"></i> Search
                                </button>
                            </div>
                            <div class="col-auto">
                                <input
                                    type="file"
                                    :id="`media-picker-upload-${fileInputKey}`"
                                    multiple
                                    accept="image/*"
                                    @change="handleFileChange"
                                    class="d-none"
                                    :key="fileInputKey"
                                />
                                <label :for="`media-picker-upload-${fileInputKey}`" class="btn btn-primary mb-0">
                                    <i class="bi bi-upload me-2"></i>
                                    Upload New
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Progress -->
                    <div v-if="uploading" class="alert alert-info mb-3">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status">
                                <span class="visually-hidden">Uploading...</span>
                            </div>
                            <div class="flex-grow-1">
                                <div>Uploading...</div>
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

                    <!-- Loading -->
                    <div v-if="loading" class="text-center py-5">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>

                    <!-- Media Grid -->
                    <div v-else-if="media.length > 0" class="row g-3">
                        <div v-for="item in media" :key="item.id" class="col-6 col-md-4 col-lg-3">
                            <div 
                                class="card cursor-pointer" 
                                :class="{ 'border-primary border-2': isSelected(item) }"
                                @click="toggleSelection(item)"
                            >
                                <img 
                                    :src="item.thumb_url" 
                                    :alt="item.name"
                                    class="card-img-top"
                                    style="height: 150px; object-fit: cover;"
                                >
                                <div class="card-body p-2">
                                    <p class="mb-0 small text-truncate" :title="item.name">{{ item.name }}</p>
                                    <p class="mb-0 text-muted" style="font-size: 0.75rem;">
                                        {{ formatFileSize(item.size) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-5">
                        <i class="bi bi-images text-muted" style="font-size: 3rem;"></i>
                        <p class="mt-3 text-muted">No images found.</p>
                    </div>

                    <!-- Pagination -->
                    <nav v-if="lastPage > 1" class="mt-3">
                        <ul class="pagination justify-content-center mb-0">
                            <li class="page-item" :class="{ disabled: currentPage === 1 }">
                                <a class="page-link" href="#" @click.prevent="loadMedia(currentPage - 1)">
                                    Previous
                                </a>
                            </li>
                            
                            <li class="page-item active">
                                <span class="page-link">
                                    Page {{ currentPage }} of {{ lastPage }}
                                </span>
                            </li>
                            
                            <li class="page-item" :class="{ disabled: currentPage === lastPage }">
                                <a class="page-link" href="#" @click.prevent="loadMedia(currentPage + 1)">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="close">Cancel</button>
                    <button 
                        type="button" 
                        class="btn btn-primary" 
                        @click="selectMedia"
                        :disabled="selectedMedia.length === 0"
                    >
                        Select {{ selectedMedia.length > 0 ? `(${selectedMedia.length})` : '' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-if="show" class="modal-backdrop fade show"></div>
</template>

<style scoped>
.cursor-pointer {
    cursor: pointer;
}
</style>