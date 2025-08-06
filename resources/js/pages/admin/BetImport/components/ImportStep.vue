<template>
    <div>
        <h2 class="h3 mb-4">Import in Progress</h2>

        <!-- Progress Bar -->
        <div class="mb-4">
            <div class="d-flex justify-content-between small text-dark mb-2">
                <span>Processing bets...</span>
                <span>{{ progress.percentage }}%</span>
            </div>
            <div class="progress" style="height: 12px">
                <div
                    class="progress-bar progress-bar-striped progress-bar-animated"
                    :style="{ width: `${progress.percentage}%` }"
                    role="progressbar"
                    :aria-valuenow="progress.percentage"
                    aria-valuemin="0"
                    aria-valuemax="100"
                ></div>
            </div>
        </div>

        <!-- Progress Stats -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <dt class="small text-dark">Total</dt>
                        <dd class="h3 mb-0">{{ progress.total }}</dd>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-primary bg-opacity-10 border-primary">
                    <div class="card-body">
                        <dt class="small text-primary">Processed</dt>
                        <dd class="h3 mb-0 text-primary">{{ progress.processed }}</dd>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-success bg-opacity-10 border-success">
                    <div class="card-body">
                        <dt class="small text-success">Success</dt>
                        <dd class="h3 mb-0 text-success">{{ progress.success }}</dd>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="card bg-danger bg-opacity-10 border-danger">
                    <div class="card-body">
                        <dt class="small text-danger">Errors</dt>
                        <dd class="h3 mb-0 text-danger">{{ progress.errors }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Messages -->
        <div class="mb-4">
            <div v-if="progress.status === 'processing'" class="alert alert-primary d-flex align-items-center">
                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                <span>Processing your import...</span>
            </div>

            <div v-else-if="progress.status === 'completed'" class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle me-2"></i>
                <span>Import completed successfully!</span>
            </div>

            <div v-else-if="progress.status === 'failed'" class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-exclamation-circle me-2"></i>
                <span>Import failed. Please try again.</span>
            </div>
        </div>

        <!-- Error Log -->
        <div v-if="errorLog.length > 0" class="mb-4">
            <h3 class="h5 mb-2">Error Log</h3>
            <div class="alert alert-danger" style="max-height: 240px; overflow-y: auto">
                <div v-for="(error, index) in errorLog" :key="index" class="small mb-2">
                    <span class="fw-medium">Row {{ error.row }}:</span>
                    <span class="ms-1">{{ error.message }}</span>
                </div>
            </div>
        </div>

        <!-- Skipped Bets Notice -->
        <div
            v-if="
                (progress.skippedEachWayBets && progress.skippedEachWayBets.length > 0) ||
                (progress.skippedParlayBets && progress.skippedParlayBets.length > 0)
            "
            class="mb-4"
        >
            <h3 class="h5 mb-2">Skipped Bets</h3>

            <!-- Each Way Bets -->
            <div v-if="progress.skippedEachWayBets && progress.skippedEachWayBets.length > 0" class="alert alert-warning mb-3">
                <div class="d-flex align-items-start mb-3">
                    <i class="bi bi-info-circle me-2 flex-shrink-0"></i>
                    <div>
                        <strong>Each Way bets must be manually added via the "Add Bet" system.</strong>
                        <p class="mb-2 mt-1">The following rows were skipped because they contain Each Way bet types:</p>
                    </div>
                </div>
                <div style="max-height: 200px; overflow-y: auto">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Team/Selection</th>
                                <th>Sport</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(bet, index) in progress.skippedEachWayBets" :key="`each-way-${index}`">
                                <td>{{ bet.line }}</td>
                                <td>{{ bet.data.wager_name || bet.data.team_one || 'N/A' }}</td>
                                <td>{{ bet.data.sports || 'N/A' }}</td>
                                <td>{{ bet.data.betting_date || bet.data.game_date || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Parlay Bets -->
            <div v-if="progress.skippedParlayBets && progress.skippedParlayBets.length > 0" class="alert alert-warning">
                <div class="d-flex align-items-start mb-3">
                    <i class="bi bi-info-circle me-2 flex-shrink-0"></i>
                    <div>
                        <strong>Parlay bets must be manually added via the "Add Bet" system.</strong>
                        <p class="mb-2 mt-1">The following rows were skipped because they contain Parlay bet types:</p>
                    </div>
                </div>
                <div style="max-height: 200px; overflow-y: auto">
                    <table class="table table-sm table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>Row</th>
                                <th>Team/Selection</th>
                                <th>Sport</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(bet, index) in progress.skippedParlayBets" :key="`parlay-${index}`">
                                <td>{{ bet.line }}</td>
                                <td>{{ bet.data.wager_name || bet.data.team_one || 'N/A' }}</td>
                                <td>{{ bet.data.sports || 'N/A' }}</td>
                                <td>{{ bet.data.betting_date || bet.data.game_date || 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-center gap-3">
            <button
                v-if="errorReportAvailable && (progress.status === 'completed' || progress.status === 'completed_with_errors')"
                @click="downloadErrorReport"
                class="btn btn-outline-secondary"
            >
                <i class="bi bi-download me-2"></i>
                Download Error Report
            </button>

            <button
                v-if="progress.status === 'completed' || progress.status === 'completed_with_errors' || progress.status === 'failed'"
                @click="$emit('import-complete')"
                class="btn btn-primary"
            >
                <i class="bi bi-check me-2"></i>
                View Imported Bets
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue';
// Bootstrap icons are used in template

interface Props {
    importId: string;
}

const props = defineProps<Props>();

const route = window.route as any;

const emit = defineEmits<{
    'import-complete': [];
}>();

const progress = ref({
    total: 0,
    processed: 0,
    success: 0,
    errors: 0,
    percentage: 0,
    status: 'processing', // processing, completed, failed
    skippedEachWayBets: [] as Array<{ line: number; data: any }>,
    skippedParlayBets: [] as Array<{ line: number; data: any }>,
});

const errorLog = ref<Array<{ row: number; message: string }>>([]);
const errorReportAvailable = ref(false);

let pollInterval: number | null = null;

const fetchProgress = async () => {
    try {
        const response = await fetch(route('admin.bets.import.progress', { import_id: props.importId }));
        const result = await response.json();

        if (result.success) {
            progress.value = result.progress;
            errorReportAvailable.value = result.error_report_available || false;

            // Check if import is complete
            if (progress.value.status === 'completed' || progress.value.status === 'completed_with_errors' || progress.value.status === 'failed') {
                progress.value.percentage = 100;
                stopPolling();
            }

            // Update error log if provided
            if (progress.value.error_log) {
                errorLog.value = progress.value.error_log;
            }
        }
    } catch (error) {
        // console.error('Failed to fetch progress:', error)
    }
};

const startPolling = () => {
    fetchProgress(); // Initial fetch
    pollInterval = window.setInterval(fetchProgress, 1000); // Poll every second
};

const stopPolling = () => {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
};

onMounted(() => {
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});

const downloadErrorReport = () => {
    window.location.href = route('admin.bets.import.error-report', { import_id: props.importId });
};
</script>
