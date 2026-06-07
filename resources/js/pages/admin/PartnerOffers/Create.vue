<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface DiscountCode {
    id: number;
    code: string;
    description: string | null;
}

interface Props {
    discountCodes: DiscountCode[];
    types: string[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    slug: '',
    logo: '',
    offer_text: '',
    description: '',
    external_url: '',
    internal_url: '',
    discount_code_id: null as number | null,
    type: 'sportsbook',
    badge_text: '',
    show_on_picks: false,
    show_on_offers_page: true,
    sort_order: 0,
    is_active: true,
});

function submit() {
    form.post(route('admin.partner-offers.store'));
}

function generateSlug() {
    if (form.name && !form.slug) {
        form.slug = form.name
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/(^-|-$)/g, '');
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Create Partner Offer" />

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item">
                                        <Link :href="route('admin.partner-offers.index')">Partner Offers</Link>
                                    </li>
                                    <li class="breadcrumb-item active">Create</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 mt-2">Create Partner Offer</h1>
                        </div>
                    </div>

                    <form @submit.prevent="submit">
                        <!-- Basic Info -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Basic Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Name *</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.name }"
                                            v-model="form.name"
                                            @blur="generateSlug"
                                            placeholder="e.g., DraftKings"
                                            required
                                        />
                                        <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Slug</label>
                                        <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.slug }" v-model="form.slug" placeholder="auto-generated" />
                                        <div v-if="form.errors.slug" class="invalid-feedback">{{ form.errors.slug }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Offer Text *</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.offer_text }"
                                            v-model="form.offer_text"
                                            placeholder="e.g., Bet $5, Get $200 in Bonus Bets"
                                            required
                                        />
                                        <div v-if="form.errors.offer_text" class="invalid-feedback">{{ form.errors.offer_text }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Description</label>
                                        <textarea
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.description }"
                                            v-model="form.description"
                                            rows="3"
                                            placeholder="Longer description for offers page..."
                                        ></textarea>
                                        <div v-if="form.errors.description" class="invalid-feedback">{{ form.errors.description }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Type *</label>
                                        <select class="form-select" :class="{ 'is-invalid': form.errors.type }" v-model="form.type">
                                            <option v-for="type in types" :key="type" :value="type">
                                                {{ type.charAt(0).toUpperCase() + type.slice(1) }}
                                            </option>
                                        </select>
                                        <div v-if="form.errors.type" class="invalid-feedback">{{ form.errors.type }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Badge Text</label>
                                        <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.badge_text }" v-model="form.badge_text" placeholder="e.g., Featured, New" />
                                        <div v-if="form.errors.badge_text" class="invalid-feedback">{{ form.errors.badge_text }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- URLs & Links -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">URLs & Links</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">External URL (Affiliate Link) *</label>
                                        <input
                                            type="url"
                                            class="form-control"
                                            :class="{ 'is-invalid': form.errors.external_url }"
                                            v-model="form.external_url"
                                            placeholder="https://..."
                                            required
                                        />
                                        <div v-if="form.errors.external_url" class="invalid-feedback">{{ form.errors.external_url }}</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Logo URL</label>
                                        <input type="text" class="form-control" :class="{ 'is-invalid': form.errors.logo }" v-model="form.logo" placeholder="/images/partners/logo.svg" />
                                        <div v-if="form.errors.logo" class="invalid-feedback">{{ form.errors.logo }}</div>
                                        <small class="text-muted">Path to logo image (e.g., /images/partners/draftkings.svg)</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Link to Discount Code</label>
                                        <select class="form-select" v-model="form.discount_code_id">
                                            <option :value="null">None</option>
                                            <option v-for="code in discountCodes" :key="code.id" :value="code.id">
                                                {{ code.code }} {{ code.description ? `- ${code.description}` : '' }}
                                            </option>
                                        </select>
                                        <small class="text-muted">If set, clicking will redirect to checkout with code applied</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Internal URL Override</label>
                                        <input type="text" class="form-control" v-model="form.internal_url" placeholder="/quick-checkout?..." />
                                        <small class="text-muted">Optional: Override the redirect URL</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Visibility -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Visibility & Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Sort Order</label>
                                        <input type="number" class="form-control" v-model.number="form.sort_order" min="0" />
                                        <small class="text-muted">Lower = higher priority</small>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label d-block">Display Options</label>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" id="show_on_picks" v-model="form.show_on_picks" />
                                            <label class="form-check-label" for="show_on_picks">Show on Today's Picks sidebar</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input" id="show_on_offers_page" v-model="form.show_on_offers_page" />
                                            <label class="form-check-label" for="show_on_offers_page">Show on Offers page</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input type="checkbox" class="form-check-input" id="is_active" v-model="form.is_active" />
                                            <label class="form-check-label" for="is_active">Active</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="d-flex justify-content-between">
                            <Link :href="route('admin.partner-offers.index')" class="btn btn-outline-secondary">Cancel</Link>
                            <button type="submit" class="btn btn-primary" :disabled="form.processing">
                                <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                                Create Partner Offer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
