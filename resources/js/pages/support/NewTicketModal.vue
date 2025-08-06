<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
const categories = page.props.categories || [];

const form = useForm({
    category_id: '',
    subject: '',
    content: '',
    priority: 'medium',
});

function submitTicket() {
    form.post('/support/tickets', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            // Close modal
            const modal = document.getElementById('newTicketModal');
            const modalInstance = window.bootstrap.Modal.getInstance(modal);
            if (modalInstance) {
                modalInstance.hide();
            }
        },
    });
}
</script>

<template>
    <div class="modal fade" id="newTicketModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Support Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form @submit.prevent="submitTicket">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select v-model="form.category_id" class="form-select" :class="{ 'is-invalid': form.errors.category_id }" required>
                                <option value="">Select a category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <div v-if="form.errors.category_id" class="invalid-feedback">
                                {{ form.errors.category_id }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input
                                v-model="form.subject"
                                type="text"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.subject }"
                                placeholder="Brief description of your issue"
                                required
                            />
                            <div v-if="form.errors.subject" class="invalid-feedback">
                                {{ form.errors.subject }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Priority</label>
                            <select v-model="form.priority" class="form-select" :class="{ 'is-invalid': form.errors.priority }">
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                            <div v-if="form.errors.priority" class="invalid-feedback">
                                {{ form.errors.priority }}
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea
                                v-model="form.content"
                                class="form-control"
                                :class="{ 'is-invalid': form.errors.content }"
                                rows="5"
                                placeholder="Please describe your issue in detail"
                                required
                            ></textarea>
                            <div v-if="form.errors.content" class="invalid-feedback">
                                {{ form.errors.content }}
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Please provide as much detail as possible to help us resolve your issue quickly.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="form.processing">
                            <span v-if="form.processing">
                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Creating...
                            </span>
                            <span v-else>
                                <i class="bi bi-check-circle me-2"></i>
                                Create Ticket
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
