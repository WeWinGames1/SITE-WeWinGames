<template>
  <div>
    <h2 class="text-2xl font-semibold mb-4">Import in Progress</h2>
    
    <!-- Progress Bar -->
    <div class="mb-6">
      <div class="flex justify-between text-sm text-gray-600 mb-2">
        <span>Processing bets...</span>
        <span>{{ progress.percentage }}%</span>
      </div>
      <div class="w-full bg-gray-200 rounded-full h-3">
        <div
          class="bg-indigo-600 h-3 rounded-full transition-all duration-300"
          :style="{ width: `${progress.percentage}%` }"
        />
      </div>
    </div>

    <!-- Progress Stats -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-lg border">
        <dt class="text-sm font-medium text-gray-500">Total</dt>
        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ progress.total }}</dd>
      </div>
      <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
        <dt class="text-sm font-medium text-blue-700">Processed</dt>
        <dd class="mt-1 text-2xl font-semibold text-blue-900">{{ progress.processed }}</dd>
      </div>
      <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <dt class="text-sm font-medium text-green-700">Success</dt>
        <dd class="mt-1 text-2xl font-semibold text-green-900">{{ progress.success }}</dd>
      </div>
      <div class="bg-red-50 p-4 rounded-lg border border-red-200">
        <dt class="text-sm font-medium text-red-700">Errors</dt>
        <dd class="mt-1 text-2xl font-semibold text-red-900">{{ progress.errors }}</dd>
      </div>
    </div>

    <!-- Status Messages -->
    <div class="mb-6">
      <div v-if="progress.status === 'processing'" class="flex items-center text-blue-700">
        <svg class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Processing your import...</span>
      </div>
      
      <div v-else-if="progress.status === 'completed'" class="flex items-center text-green-700">
        <CheckCircleIcon class="h-5 w-5 mr-2" />
        <span>Import completed successfully!</span>
      </div>
      
      <div v-else-if="progress.status === 'failed'" class="flex items-center text-red-700">
        <XCircleIcon class="h-5 w-5 mr-2" />
        <span>Import failed. Please try again.</span>
      </div>
    </div>

    <!-- Error Log -->
    <div v-if="errorLog.length > 0" class="mb-6">
      <h3 class="font-semibold mb-2">Error Log</h3>
      <div class="bg-red-50 border border-red-200 rounded-lg p-4 max-h-60 overflow-y-auto">
        <div v-for="(error, index) in errorLog" :key="index" class="text-sm text-red-700 mb-2">
          <span class="font-medium">Row {{ error.row }}:</span>
          <span class="ml-1">{{ error.message }}</span>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-center space-x-4">
      <button
        v-if="errorReportAvailable && (progress.status === 'completed' || progress.status === 'completed_with_errors')"
        @click="downloadErrorReport"
        class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-md shadow-sm text-base font-medium text-gray-700 bg-white hover:bg-gray-50"
      >
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Download Error Report
      </button>
      
      <button
        v-if="progress.status === 'completed' || progress.status === 'completed_with_errors' || progress.status === 'failed'"
        @click="$emit('import-complete')"
        class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-indigo-600 hover:bg-indigo-700"
      >
        <CheckIcon class="h-5 w-5 mr-2" />
        View Imported Bets
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'
import { CheckCircleIcon, XCircleIcon, CheckIcon } from '@heroicons/vue/24/outline'

interface Props {
  importId: string
}

const props = defineProps<Props>()

const route = window.route as any

const emit = defineEmits<{
  'import-complete': []
}>()

const progress = ref({
  total: 0,
  processed: 0,
  success: 0,
  errors: 0,
  percentage: 0,
  status: 'processing' // processing, completed, failed
})

const errorLog = ref<Array<{ row: number; message: string }>>([])
const errorReportAvailable = ref(false)

let pollInterval: number | null = null

const fetchProgress = async () => {
  try {
    const response = await fetch(route('admin.bets.import.progress', { import_id: props.importId }))
    const result = await response.json()
    
    if (result.success) {
      progress.value = result.progress
      errorReportAvailable.value = result.error_report_available || false
      
      // Check if import is complete
      if (progress.value.status === 'completed' || progress.value.status === 'completed_with_errors' || progress.value.status === 'failed') {
        progress.value.percentage = 100
        stopPolling()
      }
      
      // Update error log if provided
      if (progress.value.error_log) {
        errorLog.value = progress.value.error_log
      }
    }
  } catch (error) {
    // console.error('Failed to fetch progress:', error)
  }
}

const startPolling = () => {
  fetchProgress() // Initial fetch
  pollInterval = window.setInterval(fetchProgress, 1000) // Poll every second
}

const stopPolling = () => {
  if (pollInterval) {
    clearInterval(pollInterval)
    pollInterval = null
  }
}

onMounted(() => {
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})

const downloadErrorReport = () => {
  window.location.href = route('admin.bets.import.error-report', { import_id: props.importId })
}
</script>