<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

interface Page {
    id: number;
    title: string;
    slug: string;
    published: boolean;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{ pages: Page[] }>();

function deletePage(page: Page) {
    if (confirm(`Are you sure you want to delete "${page.title}"?`)) {
        router.delete(route('admin.pages.destroy', { page: page.id }), {
            preserveScroll: true,
        });
    }
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Pages" />
        
        <div class="p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 fw-bold text-dark">Content Pages</h1>
                    <p class="text-muted small">Create and manage static content pages for your website</p>
                </div>
                <Link 
                    :href="route('admin.pages.create')" 
                    class="btn btn-primary"
                >
                    <i class="bi bi-plus-lg me-1"></i>
                    New Page
                </Link>
            </div>

            <!-- Pages Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>URL Slug</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th width="150">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="page in pages" :key="page.id">
                                <td>
                                    <div class="fw-medium">{{ page.title }}</div>
                                </td>
                                <td>
                                    <code class="text-muted small">/pages/{{ page.slug }}</code>
                                </td>
                                <td>
                                    <span v-if="page.published" class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Published
                                    </span>
                                    <span v-else class="badge bg-secondary">
                                        <i class="bi bi-eye-slash me-1"></i>
                                        Draft
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        {{ formatDate(page.created_at) }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a 
                                            :href="route('pages.show', page.slug)" 
                                            target="_blank"
                                            class="btn btn-outline-secondary"
                                            title="View Page"
                                        >
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <Link 
                                            :href="route('admin.pages.edit', { page: page.id })" 
                                            class="btn btn-outline-primary"
                                            title="Edit Page"
                                        >
                                            <i class="bi bi-pencil"></i>
                                        </Link>
                                        <button 
                                            type="button"
                                            @click="deletePage(page)" 
                                            class="btn btn-outline-danger"
                                            title="Delete Page"
                                        >
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Empty state -->
                <div v-if="pages.length === 0" class="text-center py-5">
                    <i class="bi bi-file-text display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No pages found</p>
                    <p class="text-muted small">Create your first content page to get started</p>
                    <Link 
                        :href="route('admin.pages.create')" 
                        class="btn btn-primary"
                    >
                        <i class="bi bi-plus-lg me-1"></i>
                        Create First Page
                    </Link>
                </div>
            </div>
            
            <!-- Page Management Tips -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-info-circle me-2"></i>
                        Page Management Tips
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-2">Content Pages</h6>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Use for static content like About, Privacy Policy, Terms</li>
                                <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> SEO-friendly URLs with custom slugs</li>
                                <li class="mb-1"><i class="bi bi-check-circle text-success me-1"></i> Rich text editor with formatting options</li>
                                <li><i class="bi bi-check-circle text-success me-1"></i> Draft/published status control</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-2">Best Practices</h6>
                            <ul class="list-unstyled small text-muted mb-0">
                                <li class="mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> Keep URLs short and descriptive</li>
                                <li class="mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> Use headings to structure content</li>
                                <li class="mb-1"><i class="bi bi-lightbulb text-warning me-1"></i> Preview pages before publishing</li>
                                <li><i class="bi bi-lightbulb text-warning me-1"></i> Regular content updates improve SEO</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>