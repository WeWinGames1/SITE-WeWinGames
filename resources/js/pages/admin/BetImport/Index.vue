<template>
  <AuthenticatedLayout title="Import Bets">
    <div class="container mx-auto px-4 py-8">
      <div class="max-w-6xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">CSV Bet Import Wizard</h1>

        <!-- Progress Steps -->
        <div class="mb-8">
          <ImportProgress :current-step="currentStep" :steps="steps" />
        </div>

        <!-- Step Content -->
        <div class="bg-white rounded-lg shadow-lg p-6">
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
  </AuthenticatedLayout>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/layouts/AuthenticatedLayout.vue'
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
const validationResult = ref<any>(null)
const importId = ref<string>('')

const handleFileUploaded = async (data: any) => {
  fileId.value = data.file_id
  csvHeaders.value = data.analysis.headers
  detectedMappings.value = data.analysis.detected_mappings
  sampleData.value = data.analysis.sample_data
  currentStep.value = 2
}

const handleMappingsConfirmed = async (mappings: Record<string, string>) => {
  columnMappings.value = mappings
  
  try {
    const response = await fetch('/admin/bets/import/validate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        file_id: fileId.value,
        mappings: mappings
      })
    })

    const result = await response.json()
    
    if (result.success) {
      validationResult.value = result
      currentStep.value = 3
    } else {
      showToast('error', result.message || 'Validation failed')
    }
  } catch (error) {
    showToast('error', 'Failed to validate import')
    console.error(error)
  }
}

const handleImportConfirmed = async (skipErrors: boolean) => {
  try {
    const response = await fetch('/admin/bets/import/process', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify({
        file_id: fileId.value,
        mappings: columnMappings.value,
        skip_errors: skipErrors
      })
    })

    const result = await response.json()
    
    if (result.success) {
      importId.value = result.import_id
      
      if (result.queued) {
        // Show progress tracking for queued imports
        showToast('info', result.message || 'Import queued for processing')
        currentStep.value = 4
      } else {
        // For immediate imports, show quick success and redirect
        const successCount = result.result?.successCount?.bets || 0
        const errorCount = result.result?.errors?.length || 0
        showToast('success', `Import completed: ${successCount} successful, ${errorCount} errors`)
        
        // Show import step briefly for consistency
        currentStep.value = 4
        
        // Auto-redirect after 2 seconds for immediate imports
        setTimeout(() => {
          router.visit('/admin')
        }, 2000)
      }
    } else {
      showToast('error', result.message || 'Import failed')
    }
  } catch (error) {
    showToast('error', 'Failed to start import')
    console.error(error)
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