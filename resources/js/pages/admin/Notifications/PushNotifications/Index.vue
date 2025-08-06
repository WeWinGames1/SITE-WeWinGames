<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Sender {
    id: number;
    name: string;
}

interface Notification {
    id: number;
    title: string;
    body: string;
    url: string | null;
    icon: string | null;
    recipients_type: string;
    tier: string | null;
    sent_count: number;
    failed_count: number;
    sent_by: number;
    sender: Sender;
    created_at: string;
    recipients_label: string;
    success_rate: number;
}

interface Props {
    notifications: {
        data: Notification[];
        links: any[];
        meta: any;
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const searchForm = useForm({
    search: props.filters.search || '',
});

const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null);

watch(
    () => searchForm.search,
    (newValue) => {
        if (searchTimeout.value) {
            clearTimeout(searchTimeout.value);
        }

        searchTimeout.value = setTimeout(() => {
            searchForm.get(route('admin.notifications.push.index'), {
                preserveState: true,
                preserveScroll: true,
            });
        }, 300);
    },
);

function formatDate(date: string): string {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function truncate(text: string, length: number = 50): string {
    return text.length > length ? text.substring(0, length) + '...' : text;
}
</script>

<template>
    <AdminLayout>
        <Head title="Push Notifications" />

        <div class="container-fluid p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Push Notifications</h1>
                    <p class="text-muted mb-0">Manage and track push notifications sent to users</p>
                </div>
                <Link :href="route('admin.notifications.push.create')" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-2"></i>
                    Send New Notification
                </Link>
            </div>

            <!-- Search -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input v-model="searchForm.search" type="text" class="form-control" placeholder="Search by title or message..." />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Recipients</th>
                                <th>Sent To</th>
                                <th>Success Rate</th>
                                <th>Sent By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="notification in notifications.data" :key="notification.id">
                                <td class="fw-medium">
                                    <div class="d-flex align-items-center">
                                        <img
                                            v-if="notification.icon"
                                            :src="notification.icon"
                                            :alt="notification.title"
                                            class="me-2"
                                            style="width: 24px; height: 24px; object-fit: contain"
                                        />
                                        {{ notification.title }}
                                    </div>
                                </td>
                                <td>
                                    <span :title="notification.body">
                                        {{ truncate(notification.body) }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge"
                                        :class="{
                                            'bg-primary': notification.recipients_type === 'all',
                                            'bg-info': notification.recipients_type === 'push_enabled',
                                            'bg-warning text-dark': notification.recipients_type === 'tier',
                                        }"
                                    >
                                        {{ notification.recipients_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="text-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        {{ notification.sent_count }}
                                    </span>
                                    <span v-if="notification.failed_count > 0" class="text-danger ms-2">
                                        <i class="bi bi-x-circle me-1"></i>
                                        {{ notification.failed_count }}
                                    </span>
                                </td>
                                <td>
                                    <div class="progress" style="width: 100px; height: 20px">
                                        <div
                                            class="progress-bar"
                                            :class="{
                                                'bg-success': notification.success_rate >= 90,
                                                'bg-warning': notification.success_rate >= 75 && notification.success_rate < 90,
                                                'bg-danger': notification.success_rate < 75,
                                            }"
                                            :style="`width: ${notification.success_rate}%`"
                                        >
                                            {{ notification.success_rate }}%
                                        </div>
                                    </div>
                                </td>
                                <td>{{ notification.sender.name }}</td>
                                <td>
                                    <small class="text-muted">
                                        {{ formatDate(notification.created_at) }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button v-if="notification.url" class="btn btn-outline-secondary" :title="`URL: ${notification.url}`">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="notifications.data.length === 0" class="text-center py-5">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 3rem"></i>
                        <p class="text-muted mt-3">No push notifications sent yet</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="notifications.links.length > 3" class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ notifications.meta.from }} to {{ notifications.meta.to }} of {{ notifications.meta.total }} results
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <li
                                v-for="link in notifications.links"
                                :key="link.label"
                                class="page-item"
                                :class="{ active: link.active, disabled: !link.url }"
                            >
                                <button v-if="link.url" @click="router.get(link.url)" class="page-link" v-html="link.label" />
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
