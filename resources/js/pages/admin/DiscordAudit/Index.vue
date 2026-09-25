<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { route } from 'ziggy-js';

interface LinkedRow {
    user_id: number;
    name: string;
    email: string;
    discord_id: string;
    discord_username: string | null;
    tier: string | null;
    add: string[];
    remove: string[];
}

interface UnlinkedRow {
    discord_id: string;
    discord_username: string | null;
    display_name: string | null;
    roles: string[];
    matched_user_id: number | null;
    matched_user_email: string | null;
    remove: string[];
}

interface Props {
    configured: boolean;
    audit: {
        members_scanned: number;
        linked: LinkedRow[];
        unlinked: UnlinkedRow[];
        linked_not_in_guild: number;
    } | null;
    roleLabels: Record<string, string>;
}

const props = defineProps<Props>();

const page = usePage();
const flash = computed(() => (page.props.flash ?? {}) as { success?: string; error?: string });
const processing = ref(false);

function labels(roleIds: string[]): string {
    return roleIds.map((id) => props.roleLabels[id] ?? id).join(', ');
}

function fix(scope: 'linked' | 'unlinked') {
    const prompt =
        scope === 'linked'
            ? 'Update Discord roles for every linked member so they match the database?'
            : 'Remove the listed roles from every unlinked Discord member? Members with exempt roles are already excluded.';

    if (!confirm(prompt)) {
        return;
    }

    processing.value = true;
    router.post(
        route('admin.discord-audit.fix'),
        { scope },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function refresh() {
    processing.value = true;
    router.reload({
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Discord Role Audit" />

        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h2 mb-0">Discord Role Audit</h1>
                    <p class="text-muted mb-0">Compares every Discord member's Free / Gold / Platinum roles against current subscriptions</p>
                </div>
                <button class="btn btn-outline-secondary" :disabled="processing" @click="refresh">
                    <i class="bi bi-arrow-clockwise me-2"></i>Re-run audit
                </button>
            </div>

            <div v-if="flash.success" class="alert alert-success">{{ flash.success }}</div>
            <div v-if="flash.error" class="alert alert-danger">{{ flash.error }}</div>

            <div v-if="!configured" class="alert alert-warning">Discord integration is not configured (bot token, guild ID and role IDs).</div>

            <div v-else-if="!audit" class="alert alert-danger">
                Could not list Discord members. Enable the <strong>Server Members Intent</strong> for the bot in the Discord developer portal, then
                re-run.
            </div>

            <template v-else>
                <p class="text-muted small">
                    Scanned {{ audit.members_scanned }} members. {{ audit.linked_not_in_guild }} linked site accounts are not in the server.
                </p>

                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-link-45deg me-2"></i>Linked members with wrong roles ({{ audit.linked.length }})</span>
                        <button v-if="audit.linked.length" class="btn btn-primary btn-sm" :disabled="processing" @click="fix('linked')">
                            Fix all
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <p v-if="!audit.linked.length" class="text-muted p-3 mb-0">Every linked member matches the database.</p>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Discord</th>
                                        <th>DB tier</th>
                                        <th>Add</th>
                                        <th>Remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in audit.linked" :key="row.discord_id">
                                        <td>
                                            <Link :href="route('admin.customers.show', row.user_id)">{{ row.name }}</Link>
                                            <div class="small text-muted">{{ row.email }}</div>
                                        </td>
                                        <td>@{{ row.discord_username }}</td>
                                        <td class="text-capitalize">{{ row.tier ?? 'none' }}</td>
                                        <td class="text-success">{{ labels(row.add) || '—' }}</td>
                                        <td class="text-danger">{{ labels(row.remove) || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-person-x me-2"></i>Members not linked to a site account ({{ audit.unlinked.length }})</span>
                        <button v-if="audit.unlinked.length" class="btn btn-danger btn-sm" :disabled="processing" @click="fix('unlinked')">
                            Remove these roles
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <p class="small text-muted px-3 pt-3 mb-2">
                            These members hold paid roles but never connected Discord on the site, so automatic sync cannot reach them. Review the
                            list first: staff and comped members should be given an exempt role (<code>DISCORD_EXEMPT_ROLES</code>).
                        </p>
                        <p v-if="!audit.unlinked.length" class="text-muted px-3 pb-3 mb-0">None.</p>
                        <div v-else class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Discord</th>
                                        <th>Holds</th>
                                        <th>Site account (matched by username)</th>
                                        <th>Would remove</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="row in audit.unlinked" :key="row.discord_id">
                                        <td>
                                            @{{ row.discord_username }}
                                            <div v-if="row.display_name" class="small text-muted">{{ row.display_name }}</div>
                                        </td>
                                        <td>{{ labels(row.roles) }}</td>
                                        <td>
                                            <Link v-if="row.matched_user_id" :href="route('admin.customers.show', row.matched_user_id)">
                                                {{ row.matched_user_email }}
                                            </Link>
                                            <span v-else class="text-muted">No match</span>
                                        </td>
                                        <td class="text-danger">{{ labels(row.remove) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
