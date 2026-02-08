<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    show: boolean;
    betId: number;
    currentQueryId?: string | null;
    currentGameName?: string | null;
    teamOne?: string;
    teamTwo?: string;
    sport?: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'linked', payload: { queryId: string; gameName: string }): void;
}>();

const queryId = ref(props.currentQueryId || '');
const gameName = ref(props.currentGameName || '');
const isProcessing = ref(false);

// Construct the MetaBet hosted odds URL with search hints
const metabetUrl = computed(() => {
    let url = 'https://www.metabet.io/products/hosted-odds?siteID=wewingames';

    // Add search parameters if available
    const searchParams: string[] = [];
    if (props.teamOne) searchParams.push(props.teamOne);
    if (props.teamTwo) searchParams.push(props.teamTwo);

    if (searchParams.length > 0) {
        url += `&search=${encodeURIComponent(searchParams.join(' '))}`;
    }

    return url;
});

function handleClose() {
    emit('close');
}

function handleLink() {
    if (!queryId.value.trim()) {
        alert('Please enter a MetaBet Query ID');
        return;
    }

    isProcessing.value = true;

    emit('linked', {
        queryId: queryId.value.trim(),
        gameName: gameName.value.trim() || 'Unknown Game',
    });

    // Reset processing state after a short delay
    setTimeout(() => {
        isProcessing.value = false;
    }, 1000);
}

function handleUnlink() {
    if (confirm('Are you sure you want to unlink this bet from MetaBet?')) {
        emit('linked', {
            queryId: '',
            gameName: '',
        });
    }
}
</script>

<template>
    <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5)">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title">
                        <i class="bi bi-link-45deg me-2"></i>
                        Link MetaBet Live Odds
                    </h5>
                    <button type="button" class="btn-close" @click="handleClose"></button>
                </div>

                <div class="modal-body">
                    <!-- Instructions -->
                    <div class="alert alert-info mb-4">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-2"></i>
                            How to Link MetaBet Odds
                        </h6>
                        <ol class="mb-0 ps-3">
                            <li>Click the "Open MetaBet in New Tab" button below</li>
                            <li>Find your game in the MetaBet odds board</li>
                            <li>Right-click on the game row and select "Inspect" or "Inspect Element"</li>
                            <li>Look for identifiers like <code>data-game-id</code>, <code>data-event-id</code>, or a numeric ID in the HTML</li>
                            <li>Copy the ID and paste it into the Query ID field below</li>
                            <li>Optionally, add a descriptive game name for reference</li>
                        </ol>
                        <div class="mt-3">
                            <a :href="metabetUrl" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Open MetaBet in New Tab
                            </a>
                        </div>
                    </div>

                    <!-- Current Status -->
                    <div v-if="currentQueryId" class="alert alert-success mb-4">
                        <h6 class="alert-heading">
                            <i class="bi bi-check-circle me-2"></i>
                            Currently Linked
                        </h6>
                        <p class="mb-1"><strong>Query ID:</strong> {{ currentQueryId }}</p>
                        <p v-if="currentGameName" class="mb-0"><strong>Game:</strong> {{ currentGameName }}</p>
                    </div>

                    <!-- MetaBet Reference -->
                    <div class="mb-4">
                        <h6 class="mb-3">Common Query ID Formats</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <h6 class="text-muted small mb-2">Numeric IDs</h6>
                                        <code class="d-block mb-2">594584</code>
                                        <code class="d-block">123456789</code>
                                        <small class="text-muted">Most common format - just numbers</small>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted small mb-2">Slug Format</h6>
                                        <code class="d-block mb-2">nba-lakers-celtics</code>
                                        <code class="d-block">nfl-chiefs-bills-2024</code>
                                        <small class="text-muted">URL-friendly format with dashes</small>
                                    </div>
                                </div>
                                <hr class="my-3" />
                                <div class="alert alert-warning mb-0">
                                    <small>
                                        <strong>Tip:</strong> In the MetaBet page, look for attributes like:
                                        <ul class="mb-0 mt-2">
                                            <li><code>data-game-id="..."</code></li>
                                            <li><code>data-event-id="..."</code></li>
                                            <li><code>id="game-..."</code></li>
                                            <li><code>class="... query-..."</code></li>
                                        </ul>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Link Form -->
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="queryId" class="form-label"> MetaBet Query ID <span class="text-danger">*</span> </label>
                                    <input
                                        id="queryId"
                                        v-model="queryId"
                                        type="text"
                                        class="form-control form-control-lg"
                                        placeholder="e.g., 594584, nba-lakers-celtics, or game-123456"
                                        required
                                    />
                                    <div class="form-text">The unique identifier found in MetaBet's HTML (inspect element to find it)</div>
                                </div>

                                <div class="col-12">
                                    <label for="gameName" class="form-label"> Game Name (Optional) </label>
                                    <input
                                        id="gameName"
                                        v-model="gameName"
                                        type="text"
                                        class="form-control"
                                        placeholder="e.g., Lakers vs Celtics - Jan 15, 2025"
                                    />
                                    <div class="form-text">A descriptive name for your reference in the admin panel</div>
                                </div>

                                <div class="col-12">
                                    <div class="bg-light p-3 rounded">
                                        <h6 class="mb-2">
                                            <i class="bi bi-code-square me-1"></i>
                                            Preview: How Odds Will Be Embedded
                                        </h6>
                                        <p class="mb-2 text-muted small">
                                            When you save this link, MetaBet will automatically populate live odds using this HTML:
                                        </p>
                                        <code class="d-block p-2 bg-white border rounded">
                                            &lt;span class="metabet-odds metabet-market-moneyLine-home metabet-query-{{
                                                queryId || 'XXXXX'
                                            }}"&gt;<br />
                                            &nbsp;&nbsp;<!-- MetaBet fills this automatically --><br />
                                            &lt;/span&gt;
                                        </code>
                                        <small class="text-muted d-block mt-2">
                                            <i class="bi bi-info-circle me-1"></i>
                                            The MetaBet script on your site will automatically detect this and inject live odds
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button v-if="currentQueryId" type="button" class="btn btn-outline-danger me-auto" @click="handleUnlink">
                        <i class="bi bi-x-circle me-1"></i>
                        Unlink MetaBet
                    </button>
                    <button type="button" class="btn btn-secondary" @click="handleClose">Cancel</button>
                    <button type="button" class="btn btn-primary" :disabled="!queryId.trim() || isProcessing" @click="handleLink">
                        <span v-if="isProcessing" class="spinner-border spinner-border-sm me-2"></span>
                        <i v-else class="bi bi-link-45deg me-1"></i>
                        {{ currentQueryId ? 'Update Link' : 'Link MetaBet' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal {
    display: block;
}

code {
    background: #f8f9fa;
    padding: 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875rem;
}
</style>
