<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { ref } from 'vue';

interface Testimonial {
    id: number;
    name: string;
    title: string | null;
    stars: number;
    review: string;
    image: string | null;
    review_date: string;
    published: boolean;
    sort_order: number;
}

const props = defineProps<{
    testimonial: Testimonial;
}>();

const form = useForm({
    name: props.testimonial.name,
    title: props.testimonial.title || '',
    stars: props.testimonial.stars,
    review: props.testimonial.review,
    review_date: props.testimonial.review_date.split(' ')[0], // Extract date only
    published: props.testimonial.published,
    sort_order: props.testimonial.sort_order,
    image: null as File | null
});

const imagePreview = ref<string | null>(props.testimonial.image);

function handleImageChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.image = target.files[0];
        
        // Create preview
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(target.files[0]);
    }
}

function removeImage() {
    form.image = null;
    imagePreview.value = null;
    const input = document.getElementById('image') as HTMLInputElement;
    if (input) input.value = '';
}

function submit() {
    form.put(route('admin.testimonials.update', props.testimonial.id), {
        forceFormData: true
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Edit Testimonial" />
        
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Edit Testimonial</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item">
                                <Link :href="route('admin.dashboard')">Dashboard</Link>
                            </li>
                            <li class="breadcrumb-item">
                                <Link :href="route('admin.testimonials.index')">Testimonials</Link>
                            </li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
            </div>

            <!-- Form -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form @submit.prevent="submit">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label text-dark fw-medium">Customer Name <span class="text-danger">*</span></label>
                                        <input
                                            id="name"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.name }"
                                            v-model="form.name"
                                            required
                                            placeholder="John Doe"
                                        />
                                        <div v-if="form.errors.name" class="invalid-feedback">
                                            {{ form.errors.name }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="title" class="form-label text-dark fw-medium">Title/Position</label>
                                        <input
                                            id="title"
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.title }"
                                            v-model="form.title"
                                            placeholder="CEO at Company (optional)"
                                        />
                                        <div v-if="form.errors.title" class="invalid-feedback">
                                            {{ form.errors.title }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label text-dark fw-medium">Rating <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <button
                                            v-for="star in 5"
                                            :key="star"
                                            type="button"
                                            class="btn btn-link p-0 text-decoration-none"
                                            @click="form.stars = star"
                                        >
                                            <i 
                                                class="bi fs-4"
                                                :class="star <= form.stars ? 'bi-star-fill text-warning' : 'bi-star text-muted'"
                                            ></i>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.stars" class="text-danger small mt-1">
                                        {{ form.errors.stars }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="review" class="form-label text-dark fw-medium">Review <span class="text-danger">*</span></label>
                                    <textarea
                                        id="review"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.review }"
                                        v-model="form.review"
                                        rows="4"
                                        required
                                        placeholder="Write the customer's review here..."
                                    ></textarea>
                                    <div v-if="form.errors.review" class="invalid-feedback">
                                        {{ form.errors.review }}
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label for="review_date" class="form-label text-dark fw-medium">Review Date <span class="text-danger">*</span></label>
                                        <input
                                            id="review_date"
                                            type="date"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.review_date }"
                                            v-model="form.review_date"
                                            required
                                        />
                                        <div v-if="form.errors.review_date" class="invalid-feedback">
                                            {{ form.errors.review_date }}
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sort_order" class="form-label text-dark fw-medium">Sort Order</label>
                                        <input
                                            id="sort_order"
                                            type="number"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.sort_order }"
                                            v-model="form.sort_order"
                                            min="0"
                                        />
                                        <small class="text-muted">Lower numbers appear first</small>
                                        <div v-if="form.errors.sort_order" class="invalid-feedback">
                                            {{ form.errors.sort_order }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="image" class="form-label text-dark fw-medium">Customer Photo</label>
                                    <div v-if="imagePreview" class="mb-3">
                                        <div class="position-relative d-inline-block">
                                            <img 
                                                :src="imagePreview" 
                                                alt="Preview" 
                                                class="rounded-circle"
                                                style="width: 100px; height: 100px; object-fit: cover;"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 rounded-circle"
                                                @click="removeImage"
                                                style="width: 30px; height: 30px; padding: 0;"
                                            >
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <input
                                        id="image"
                                        type="file"
                                        class="form-control"
                                        :class="{ 'is-invalid': form.errors.image }"
                                        accept="image/*"
                                        @change="handleImageChange"
                                    />
                                    <small class="text-muted">Upload a new image to replace the existing one</small>
                                    <div v-if="form.errors.image" class="invalid-feedback">
                                        {{ form.errors.image }}
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-check">
                                        <input
                                            id="published"
                                            type="checkbox"
                                            class="form-check-input"
                                            v-model="form.published"
                                        />
                                        <label for="published" class="form-check-label text-dark fw-medium">
                                            Published
                                        </label>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button 
                                        type="submit" 
                                        class="btn btn-primary"
                                        :disabled="form.processing"
                                    >
                                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </span>
                                        <i v-else class="bi bi-save me-2"></i>
                                        Update Testimonial
                                    </button>
                                    <Link 
                                        :href="route('admin.testimonials.index')" 
                                        class="btn btn-secondary"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Preview</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div 
                                    v-if="imagePreview"
                                    class="rounded-circle overflow-hidden mx-auto mb-3"
                                    style="width: 80px; height: 80px;"
                                >
                                    <img 
                                        :src="imagePreview" 
                                        alt="Preview"
                                        class="w-100 h-100 object-fit-cover"
                                    />
                                </div>
                                <div 
                                    v-else
                                    class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3"
                                    style="width: 80px; height: 80px; font-size: 24px; font-weight: 600;"
                                >
                                    {{ form.name ? form.name.split(' ').map(n => n[0]?.toUpperCase()).join('').slice(0, 2) : '??' }}
                                </div>
                                <h6 class="mb-0">{{ form.name || 'Customer Name' }}</h6>
                                <small class="text-muted">{{ form.title || 'Title/Position' }}</small>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-center gap-1 mb-2">
                                    <i 
                                        v-for="star in 5" 
                                        :key="star"
                                        class="bi"
                                        :class="star <= form.stars ? 'bi-star-fill text-warning' : 'bi-star text-muted'"
                                    ></i>
                                </div>
                                <p class="text-muted small mb-2">{{ form.review || 'Review text will appear here...' }}</p>
                                <small class="text-muted">
                                    {{ form.review_date ? new Date(form.review_date).toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) : 'Review Date' }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.breadcrumb {
    background-color: transparent;
    padding: 0;
}

.breadcrumb-item a {
    text-decoration: none;
    color: #6c757d;
}

.breadcrumb-item a:hover {
    color: #0d6efd;
}

.object-fit-cover {
    object-fit: cover;
}
</style>