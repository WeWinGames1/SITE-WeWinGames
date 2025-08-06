<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

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
    initials: string;
    formatted_date: string;
}

const props = defineProps<{
    testimonials: {
        data: Testimonial[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}>();

function deleteTestimonial(id: number) {
    if (confirm('Are you sure you want to delete this testimonial?')) {
        router.delete(route('admin.testimonials.destroy', id));
    }
}

function togglePublished(testimonial: Testimonial) {
    router.put(
        route('admin.testimonials.update', testimonial.id),
        {
            ...testimonial,
            published: !testimonial.published,
        },
        {
            preserveScroll: true,
        },
    );
}

// Star rating display
function getStarArray(rating: number) {
    return Array(5)
        .fill(0)
        .map((_, i) => i < rating);
}
</script>

<template>
    <AdminLayout>
        <Head title="Testimonials" />

        <div class="container-fluid p-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Testimonials</h1>
                    <p class="text-muted mb-0">Manage customer reviews and testimonials</p>
                </div>
                <Link :href="route('admin.testimonials.create')" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Testimonial
                </Link>
            </div>

            <!-- Testimonials Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <div v-if="props.testimonials.data.length === 0" class="text-center py-5">
                        <i class="bi bi-chat-quote display-1 text-muted mb-3 d-block"></i>
                        <p class="text-muted">No testimonials found. Add your first testimonial to get started.</p>
                        <Link :href="route('admin.testimonials.create')" class="btn btn-primary mt-3">
                            <i class="bi bi-plus-circle me-2"></i>
                            Add First Testimonial
                        </Link>
                    </div>

                    <div v-else class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th class="text-dark fw-medium" width="80">Order</th>
                                    <th class="text-dark fw-medium">Customer</th>
                                    <th class="text-dark fw-medium">Review</th>
                                    <th class="text-dark fw-medium text-center">Rating</th>
                                    <th class="text-dark fw-medium text-center">Date</th>
                                    <th class="text-dark fw-medium text-center">Status</th>
                                    <th class="text-dark fw-medium text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="testimonial in props.testimonials.data" :key="testimonial.id">
                                    <td>
                                        <input
                                            type="number"
                                            class="form-control form-control-sm"
                                            style="width: 60px"
                                            :value="testimonial.sort_order"
                                            disabled
                                        />
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div
                                                v-if="testimonial.image"
                                                class="rounded-circle overflow-hidden me-3"
                                                style="width: 40px; height: 40px"
                                            >
                                                <img :src="testimonial.image" :alt="testimonial.name" class="w-100 h-100 object-fit-cover" />
                                            </div>
                                            <div
                                                v-else
                                                class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3"
                                                style="width: 40px; height: 40px; font-size: 14px; font-weight: 600"
                                            >
                                                {{ testimonial.initials }}
                                            </div>
                                            <div>
                                                <div class="fw-medium text-dark">{{ testimonial.name }}</div>
                                                <small class="text-muted" v-if="testimonial.title">{{ testimonial.title }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px">
                                            {{ testimonial.review }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <i
                                                v-for="(filled, index) in getStarArray(testimonial.stars)"
                                                :key="index"
                                                class="bi"
                                                :class="filled ? 'bi-star-fill text-warning' : 'bi-star text-muted'"
                                                style="font-size: 14px"
                                            ></i>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ testimonial.formatted_date }}</small>
                                    </td>
                                    <td class="text-center">
                                        <button
                                            @click="togglePublished(testimonial)"
                                            class="btn btn-sm"
                                            :class="testimonial.published ? 'btn-success' : 'btn-secondary'"
                                        >
                                            {{ testimonial.published ? 'Published' : 'Draft' }}
                                        </button>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <Link
                                                :href="route('admin.testimonials.edit', testimonial.id)"
                                                class="btn btn-outline-primary"
                                                title="Edit testimonial"
                                            >
                                                <i class="bi bi-pencil"></i>
                                            </Link>
                                            <button
                                                @click="deleteTestimonial(testimonial.id)"
                                                class="btn btn-outline-danger"
                                                title="Delete testimonial"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="props.testimonials.last_page > 1" class="mt-4">
                        <nav>
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item" :class="{ disabled: props.testimonials.current_page === 1 }">
                                    <Link
                                        class="page-link"
                                        :href="route('admin.testimonials.index', { page: props.testimonials.current_page - 1 })"
                                        preserve-scroll
                                    >
                                        Previous
                                    </Link>
                                </li>
                                <li
                                    v-for="page in props.testimonials.last_page"
                                    :key="page"
                                    class="page-item"
                                    :class="{ active: page === props.testimonials.current_page }"
                                >
                                    <Link class="page-link" :href="route('admin.testimonials.index', { page })" preserve-scroll>
                                        {{ page }}
                                    </Link>
                                </li>
                                <li class="page-item" :class="{ disabled: props.testimonials.current_page === props.testimonials.last_page }">
                                    <Link
                                        class="page-link"
                                        :href="route('admin.testimonials.index', { page: props.testimonials.current_page + 1 })"
                                        preserve-scroll
                                    >
                                        Next
                                    </Link>
                                </li>
                            </ul>
                        </nav>
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

.object-fit-cover {
    object-fit: cover;
}
</style>
