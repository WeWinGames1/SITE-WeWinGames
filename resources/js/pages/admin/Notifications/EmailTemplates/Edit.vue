<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

interface EmailTemplate {
    id: number;
    key: string;
    name: string;
    description: string | null;
    subject: string;
    from_email: string | null;
    from_name: string | null;
    body_html: string;
    body_text: string | null;
    available_variables: string[];
    is_active: boolean;
}

const props = defineProps<{
    template: EmailTemplate;
}>();

const form = useForm({
    subject: props.template.subject,
    from_email: props.template.from_email || '',
    from_name: props.template.from_name || '',
    body_html: props.template.body_html,
    body_text: props.template.body_text || '',
    is_active: props.template.is_active,
});

const showPreview = ref(false);
const previewLoading = ref(false);
const previewData = ref<any>(null);

const availableVariablesText = computed(() => {
    return props.template.available_variables.map(v => `{{${v}}}`).join(', ');
});

function updateTemplate() {
    form.put(`/admin/notifications/email-templates/${props.template.id}`, {
        preserveScroll: true,
    });
}

async function previewTemplate() {
    showPreview.value = true;
    previewLoading.value = true;
    
    try {
        const response = await fetch(`/admin/notifications/email-templates/${props.template.id}/preview`);
        previewData.value = await response.json();
    } catch (error) {
        console.error('Failed to preview template:', error);
    } finally {
        previewLoading.value = false;
    }
}

function resetToDefault() {
    if (confirm('Are you sure you want to reset this template to its default content? This cannot be undone.')) {
        router.post(`/admin/notifications/email-templates/${props.template.id}/reset`, {}, {
            preserveScroll: true,
        });
    }
}

function insertVariable(variable: string) {
    const textarea = document.getElementById('body_html') as HTMLTextAreaElement;
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = form.body_html;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        form.body_html = before + `{{${variable}}}` + after;
        
        // Set cursor position after inserted text
        setTimeout(() => {
            textarea.selectionStart = textarea.selectionEnd = start + variable.length + 4;
            textarea.focus();
        }, 0);
    }
}
</script>

<template>
    <AdminLayout>
        <Head :title="`Edit ${template.name} Template`" />
        
        <div class="container-fluid p-4">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h2 mb-1 text-dark">Edit Email Template</h1>
                            <p class="text-muted mb-0">
                                {{ template.name }} - {{ template.description }}
                            </p>
                        </div>
                        <Link 
                            href="/admin/notifications/email-templates"
                            class="btn btn-outline-secondary"
                        >
                            <i class="bi bi-arrow-left me-2"></i>
                            Back to Templates
                        </Link>
                    </div>
                </div>
            </div>

            <form @submit.prevent="updateTemplate">
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Subject -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Email Settings</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="subject" class="form-label text-dark fw-medium">Subject Line</label>
                                    <input 
                                        v-model="form.subject" 
                                        type="text" 
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.subject }"
                                        id="subject"
                                        required
                                    />
                                    <div v-if="form.errors.subject" class="invalid-feedback">
                                        {{ form.errors.subject }}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="from_name" class="form-label text-dark fw-medium">From Name</label>
                                            <input 
                                                v-model="form.from_name" 
                                                type="text" 
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.from_name }"
                                                id="from_name"
                                                placeholder="Leave empty to use default"
                                            />
                                            <div v-if="form.errors.from_name" class="invalid-feedback">
                                                {{ form.errors.from_name }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="from_email" class="form-label text-dark fw-medium">From Email</label>
                                            <input 
                                                v-model="form.from_email" 
                                                type="email" 
                                                class="form-control"
                                                :class="{ 'is-invalid': form.errors.from_email }"
                                                id="from_email"
                                                placeholder="Leave empty to use default"
                                            />
                                            <div v-if="form.errors.from_email" class="invalid-feedback">
                                                {{ form.errors.from_email }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- HTML Content -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">HTML Content</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <textarea 
                                        v-model="form.body_html" 
                                        class="form-control font-monospace"
                                        :class="{ 'is-invalid': form.errors.body_html }"
                                        id="body_html"
                                        rows="15"
                                        required
                                    ></textarea>
                                    <div v-if="form.errors.body_html" class="invalid-feedback">
                                        {{ form.errors.body_html }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Plain Text Content -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Plain Text Content</h5>
                                    <small class="text-muted">Optional</small>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <textarea 
                                        v-model="form.body_text" 
                                        class="form-control font-monospace"
                                        :class="{ 'is-invalid': form.errors.body_text }"
                                        id="body_text"
                                        rows="10"
                                        placeholder="Leave empty to auto-generate from HTML"
                                    ></textarea>
                                    <div v-if="form.errors.body_text" class="invalid-feedback">
                                        {{ form.errors.body_text }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Available Variables -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Available Variables</h5>
                            </div>
                            <div class="card-body">
                                <p class="small text-muted mb-3">
                                    Click on a variable to insert it at cursor position
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <button
                                        v-for="variable in template.available_variables"
                                        :key="variable"
                                        type="button"
                                        @click="insertVariable(variable)"
                                        class="btn btn-sm btn-outline-primary"
                                    >
                                        {{{{ variable }}}}
                                    </button>
                                </div>
                                <hr class="my-3">
                                <p class="small text-muted mb-0">
                                    <strong>Default variables:</strong><br>
                                    <code>{{app_name}}</code>, <code>{{app_url}}</code>
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input 
                                        v-model="form.is_active" 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="is_active"
                                    />
                                    <label class="form-check-label" for="is_active">
                                        Template is active
                                    </label>
                                </div>
                                <small class="text-muted">
                                    Inactive templates will not be sent
                                </small>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card">
                            <div class="card-body">
                                <button 
                                    type="submit" 
                                    class="btn btn-primary w-100 mb-2"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing">
                                        <span class="spinner-border spinner-border-sm me-2"></span>
                                        Saving...
                                    </span>
                                    <span v-else>
                                        <i class="bi bi-check-circle me-2"></i>
                                        Save Changes
                                    </span>
                                </button>
                                
                                <button 
                                    type="button"
                                    @click="previewTemplate"
                                    class="btn btn-outline-primary w-100 mb-2"
                                >
                                    <i class="bi bi-eye me-2"></i>
                                    Preview
                                </button>
                                
                                <button 
                                    type="button"
                                    @click="resetToDefault"
                                    class="btn btn-outline-danger w-100"
                                >
                                    <i class="bi bi-arrow-clockwise me-2"></i>
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Preview Modal -->
        <div class="modal fade" :class="{ show: showPreview, 'd-block': showPreview }" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Email Preview</h5>
                        <button type="button" class="btn-close" @click="showPreview = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="previewLoading" class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                            <p class="mt-3 text-muted">Loading preview...</p>
                        </div>
                        <div v-else-if="previewData">
                            <div class="mb-4">
                                <h6 class="text-muted">From:</h6>
                                <p class="mb-0">{{ previewData.from_name }} &lt;{{ previewData.from_email }}&gt;</p>
                            </div>
                            <div class="mb-4">
                                <h6 class="text-muted">Subject:</h6>
                                <p class="mb-0 fw-bold">{{ previewData.subject }}</p>
                            </div>
                            <div class="mb-4">
                                <h6 class="text-muted">HTML Preview:</h6>
                                <div class="border rounded p-3 bg-white">
                                    <div v-html="previewData.body_html"></div>
                                </div>
                            </div>
                            <div v-if="previewData.body_text">
                                <h6 class="text-muted">Plain Text Preview:</h6>
                                <pre class="border rounded p-3 bg-light">{{ previewData.body_text }}</pre>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showPreview = false">Close</button>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showPreview" class="modal-backdrop fade show"></div>
    </AdminLayout>
</template>

<style scoped>
.font-monospace {
    font-size: 0.875rem;
}

code {
    background-color: rgba(13, 110, 253, 0.1);
    padding: 0.125rem 0.25rem;
    border-radius: 0.25rem;
}
</style>