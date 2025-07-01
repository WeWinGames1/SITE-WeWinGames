<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import QrcodeVue from 'qrcode.vue';
import { ref } from 'vue';

const props = defineProps<{ pages: Array<any> }>();
const showQR = ref(false);
const qrUrl = ref('');
const qrTitle = ref('');

function deletePage(id: number) {
    if (confirm('Delete this page?')) {
        router.delete(route('admin.landing-pages.destroy', id));
    }
}
function openQR(page) {
    qrUrl.value = `${window.location.origin}/landing/${page.slug}`;
    qrTitle.value = page.title;
    showQR.value = true;
}
function closeQR() {
    showQR.value = false;
}
</script>
<template>
    <AdminLayout>
        <Head title="Landing Pages" />
        
        <div class="container-fluid">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Landing Pages</h1>
                            <p class="text-muted mb-0">
                                Manage your marketing and landing pages
                            </p>
                        </div>
                        <Link 
                            :href="route('admin.landing-pages.create')" 
                            class="btn btn-primary"
                        >
                            <i class="bi bi-plus-circle me-1"></i>
                            Create New Page
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Pages Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div v-if="props.pages.length === 0" class="text-center py-5">
                        <i class="bi bi-file-earmark-text display-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No landing pages found. Create your first landing page to get started.</p>
                        <Link 
                            :href="route('admin.landing-pages.create')" 
                            class="btn btn-primary mt-3"
                        >
                            <i class="bi bi-plus-circle me-2"></i>
                            Create First Page
                        </Link>
                    </div>
                    
                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-dark fw-medium">Title</th>
                                    <th class="text-dark fw-medium">URL Slug</th>
                                    <th class="text-dark fw-medium text-center">Status</th>
                                    <th class="text-dark fw-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="page in props.pages" :key="page.id">
                                    <td>
                                        <div class="fw-medium text-dark">{{ page.title }}</div>
                                        <small class="text-muted">Created {{ new Date(page.created_at).toLocaleDateString() }}</small>
                                    </td>
                                    <td>
                                        <code class="text-primary">/landing/{{ page.slug }}</code>
                                    </td>
                                    <td class="text-center">
                                        <span 
                                            class="badge"
                                            :class="page.published ? 'bg-success' : 'bg-secondary'"
                                        >
                                            {{ page.published ? 'Published' : 'Draft' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <Link 
                                                :href="route('admin.landing-pages.edit', page.id)" 
                                                class="btn btn-sm btn-outline-primary"
                                                title="Edit page"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </Link>
                                            <a
                                                :href="`/landing/${page.slug}`"
                                                class="btn btn-sm btn-outline-success"
                                                target="_blank"
                                                rel="noopener"
                                                title="View page"
                                            >
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <button 
                                                @click="openQR(page)" 
                                                class="btn btn-sm btn-outline-info"
                                                title="Generate QR code"
                                            >
                                                <i class="bi bi-qr-code"></i>
                                            </button>
                                            <button 
                                                @click="deletePage(page.id)" 
                                                class="btn btn-sm btn-outline-danger"
                                                title="Delete page"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Modal -->
        <div 
            v-if="showQR" 
            class="modal fade show d-block" 
            tabindex="-1" 
            style="background-color: rgba(0,0,0,0.5);"
        >
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">QR Code: {{ qrTitle }}</h5>
                        <button 
                            type="button" 
                            class="btn-close" 
                            @click="closeQR"
                            aria-label="Close"
                        ></button>
                    </div>
                    <div class="modal-body text-center">
                        <QrcodeVue :value="qrUrl" :size="250" class="mb-3" />
                        <div class="alert alert-info mb-0">
                            <small class="text-break">{{ qrUrl }}</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button 
                            type="button" 
                            class="btn btn-secondary" 
                            @click="closeQR"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.table th {
    border-bottom: 2px solid #dee2e6;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.table td {
    vertical-align: middle;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

code {
    background-color: rgba(13, 110, 253, 0.1);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}

.modal.show {
    display: block;
}
</style>