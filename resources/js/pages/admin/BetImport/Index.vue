<template>
  <AdminLayout title="Import Bets">
    <div class="container-fluid p-4">
      <!-- Header -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 mb-0">CSV Bet Import Wizard</h1>
      </div>

      <!-- Progress Steps -->
      <div class="row mb-4">
        <div class="col-12">
          <ImportProgress :current-step="currentStep" :steps="steps" />
        </div>
      </div>

      <!-- Step Content -->
      <div class="card mb-4">
        <div class="card-body">
          <!-- Step 1: Upload -->
          <UploadStep
            v-if="currentStep === 1"
            :column-requirements="columnRequirements"
            @file-uploaded="handleFileUploaded"
            @download-template="downloadTemplate"
          />

          <!-- Step 2: Column Mapping -->
          <MappingStep
            v-if="currentStep === 2"
            :csv-headers="csvHeaders"
            :detected-mappings="detectedMappings"
            :column-requirements="columnRequirements"
            :sample-data="sampleData"
            @mappings-confirmed="handleMappingsConfirmed"
            @back="currentStep--"
          />

          <!-- Step 3: Validation & Preview -->
          <ValidationStep
            v-if="currentStep === 3"
            :validation-result="validationResult"
            :validation-rules="validationRules"
            @import-confirmed="handleImportConfirmed"
            @back="currentStep--"
          />

          <!-- Step 4: Import Progress -->
          <ImportStep
            v-if="currentStep === 4"
            :import-id="importId"
            @import-complete="handleImportComplete"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import AdminLayout from '@/layouts/AdminLayout.vue'
import ImportProgress from './components/ImportProgress.vue'
import UploadStep from './components/UploadStep.vue'
import MappingStep from './components/MappingStep.vue'
import ValidationStep from './components/ValidationStep.vue'
import ImportStep from './components/ImportStep.vue'
import { useToast } from '@/composables/useToast'

interface Props {
  columnRequirements: {
    required: Record<string, string>
    optional: Record<string, string>
  }
  validationRules: Record<string, string>
}

const props = defineProps<Props>()
const { showToast } = useToast()

const currentStep = ref(1)
const steps = [
  { number: 1, name: 'Upload File', description: 'Upload your CSV file' },
  { number: 2, name: 'Map Columns', description: 'Map CSV columns to fields' },
  { number: 3, name: 'Validate', description: 'Preview and validate data' },
  { number: 4, name: 'Import', description: 'Import bets to system' }
]

const fileId = ref<string>('')
const csvHeaders = ref<string[]>([])
const detectedMappings = ref<Record<string, string>>({})
const sampleData = ref<any[]>([])
const columnMappings = ref<Record<string, string>>({})
const staticValues = ref<Record<string, string>>({})
const validationResult = ref<any>(null)
const importId = ref<string>('')

const handleFileUploaded = async (data: any) => {
  fileId.value = data.file_id
  csvHeaders.value = data.analysis.headers
  detectedMappings.value = data.analysis.detected_mappings
  sampleData.value = data.analysis.sample_data
  currentStep.value = 2
}

const handleMappingsConfirmed = async (mappings: Record<string, string>, staticVals: Record<string, string>) => {
  columnMappings.value = mappings
  staticValues.value = staticVals
  
  try {
    // Refresh CSRF token first
    try {
      const tokenResponse = await axios.get('/csrf-token')
      axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenResponse.data.token
    } catch (e) {
      console.warn('Could not refresh CSRF token')
    }
    
    const { data } = await axios.post('/admin/bets/import/validate', {
      file_id: fileId.value,
      mappings: mappings,
      static_values: staticVals
    })
    
    if (data.success) {
      validationResult.value = data
      currentStep.value = 3
    } else {
      showToast('error', data.message || 'Validation failed')
    }
  } catch (error: any) {
    if (error.response?.status === 419) {
      showToast('error', 'Session expired. Please refresh the page and try again.')
    } else {
      showToast('error', error.response?.data?.message || 'Failed to validate import')
    }
    console.error('Validation error:', error)
  }
}

const handleImportConfirmed = async (skipErrors: boolean) => {
  try {
    // Refresh CSRF token first
    try {
      const tokenResponse = await axios.get('/csrf-token')
      axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenResponse.data.token
    } catch (e) {
      console.warn('Could not refresh CSRF token')
    }
    
    const { data } = await axios.post('/admin/bets/import/process', {
      file_id: fileId.value,
      mappings: columnMappings.value,
      static_values: staticValues.value,
      skip_errors: skipErrors
    })

    
    if (data.success) {
      importId.value = data.import_id
      
      if (data.queued) {
        // Show progress tracking for queued imports
        showToast('info', data.message || 'Import queued for processing')
        currentStep.value = 4
      } else {
        // For immediate imports, show the import step to display results
        const successCount = data.result?.successCount?.bets || 0
        const errorCount = data.result?.errors?.length || 0
        
        // Show import step to display the completion status
        currentStep.value = 4
        
        // Don't auto-redirect - let the user see the results and click "View Imported Bets"
        // The ImportStep component will handle showing the completion status
      }
    } else {
      showToast('error', data.message || 'Import failed')
    }
  } catch (error: any) {
    showToast('error', error.response?.data?.message || 'Failed to start import')
    console.error('Import error:', error)
  }
}

const handleImportComplete = () => {
  showToast('success', 'Import completed successfully!')
  router.visit('/admin/bets')
}

const downloadTemplate = async () => {
  window.location.href = '/admin/bets/import/template'
}
</script>