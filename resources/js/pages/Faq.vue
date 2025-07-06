<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import WelcomeLayout from '@/layouts/WelcomeLayout.vue';
import { ref } from 'vue';

interface Faq {
    id: number;
    question: string;
    answer: string;
    category: string | null;
}

const props = defineProps<{
    faqs: Record<string, Faq[]>;
    categories: string[];
}>();

const activeCategory = ref<string | null>(props.categories[0] || null);
const openItems = ref<Set<number>>(new Set());

function setActiveCategory(category: string) {
    activeCategory.value = category;
    openItems.value.clear();
}

function toggleItem(id: number) {
    if (openItems.value.has(id)) {
        openItems.value.delete(id);
    } else {
        openItems.value.add(id);
    }
}

function isOpen(id: number): boolean {
    return openItems.value.has(id);
}
</script>

<template>
    <WelcomeLayout>
        <Head title="Frequently Asked Questions" />

        <!-- Hero Section -->
        <section class="py-5" style="background: linear-gradient(135deg, #0a1628 0%, #1e3a5f 100%);">
            <div class="container">
                <div class="row justify-content-center text-center">
                    <div class="col-lg-8">
                        <h1 class="display-4 fw-bold text-white mb-3">
                            Frequently Asked <span class="text-warning">Questions</span>
                        </h1>
                        <p class="lead text-gray-light">
                            Find answers to common questions about our service, subscriptions, and betting tips.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Content -->
        <section class="py-5">
            <div class="container">
                <div class="row">
                    <!-- Category Navigation -->
                    <div class="col-lg-3 mb-4">
                        <div class="card bg-dark border-secondary sticky-lg-top" style="top: 2rem;">
                            <div class="card-body">
                                <h5 class="card-title text-white mb-3">Categories</h5>
                                <div class="list-group list-group-flush">
                                    <button
                                        v-for="category in categories"
                                        :key="category"
                                        @click="setActiveCategory(category)"
                                        :class="[
                                            'list-group-item list-group-item-action bg-transparent border-0 px-0 py-2',
                                            activeCategory === category ? 'text-warning fw-bold' : 'text-gray-light'
                                        ]"
                                        style="border-radius: 0;"
                                    >
                                        <i class="bi bi-chevron-right me-2"></i>
                                        {{ category }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Items -->
                    <div class="col-lg-9">
                        <div v-if="activeCategory && faqs[activeCategory]" class="accordion" :id="`accordion-${activeCategory}`">
                            <div
                                v-for="faq in faqs[activeCategory]"
                                :key="faq.id"
                                class="accordion-item bg-dark border-secondary mb-3"
                            >
                                <h2 class="accordion-header" :id="`heading-${faq.id}`">
                                    <button
                                        class="accordion-button bg-dark text-white"
                                        :class="{ collapsed: !isOpen(faq.id) }"
                                        type="button"
                                        @click="toggleItem(faq.id)"
                                        :aria-expanded="isOpen(faq.id)"
                                        :aria-controls="`collapse-${faq.id}`"
                                    >
                                        <i class="bi bi-question-circle-fill text-warning me-3"></i>
                                        {{ faq.question }}
                                    </button>
                                </h2>
                                <div
                                    :id="`collapse-${faq.id}`"
                                    class="accordion-collapse collapse"
                                    :class="{ show: isOpen(faq.id) }"
                                    :aria-labelledby="`heading-${faq.id}`"
                                >
                                    <div class="accordion-body text-gray-light" v-html="faq.answer"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-else class="text-center py-5">
                            <i class="bi bi-question-circle display-1 text-muted mb-3 d-block"></i>
                            <p class="text-muted">No FAQs available in this category.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Still Have Questions -->
        <section class="py-5 bg-dark">
            <div class="container text-center">
                <h2 class="h3 text-white mb-3">Still Have Questions?</h2>
                <p class="text-gray-light mb-4">
                    Can't find the answer you're looking for? Our support team is here to help.
                </p>
                <a href="/support" class="btn btn-warning btn-lg">
                    <i class="bi bi-headset me-2"></i>
                    Contact Support
                </a>
            </div>
        </section>
    </WelcomeLayout>
</template>

<style scoped>
.accordion-button:not(.collapsed) {
    background-color: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: rgba(255, 193, 7, 0.5);
}

.accordion-button::after {
    filter: invert(1);
}

.accordion-item {
    border-radius: 0.5rem !important;
    overflow: hidden;
}

.list-group-item:hover {
    color: #ffc107 !important;
}

.text-gray-light {
    color: #adb5bd;
}
</style>