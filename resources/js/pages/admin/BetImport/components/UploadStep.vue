<template>
  <div>
    <h2 class="h3 mb-4">Upload CSV File</h2>
    
    <!-- File Requirements -->
    <div class="alert alert-info mb-4">
      <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>File Requirements</h5>
      <ul class="mb-0">
        <li>File must be in CSV format (.csv or .txt)</li>
        <li>Maximum file size: 10MB</li>
        <li>First row must contain column headers</li>
        <li>UTF-8 encoding recommended</li>
      </ul>
    </div>

    <!-- Required Columns -->
    <div class="row mb-4">
      <div class="col-md-6 mb-3 mb-md-0">
        <div class="card bg-success bg-opacity-10 border-success">
          <div class="card-body">
            <h5 class="card-title text-success"><i class="bi bi-check-circle me-2"></i>Required Columns</h5>
            <dl class="mb-0">
              <div v-for="(desc, field) in columnRequirements.required" :key="field" class="mb-1">
                <dt class="d-inline fw-medium">{{ field }}:</dt>
                <dd class="d-inline ms-1">{{ desc }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Optional Columns</h5>
            <dl class="mb-0">
              <div v-for="(desc, field) in columnRequirements.optional" :key="field" class="mb-1">
                <dt class="d-inline fw-medium">{{ field }}:</dt>
                <dd class="d-inline ms-1">{{ desc }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>
    </div>

    <!-- File Upload -->
    <div class="mb-4">
      <div
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent
        @dragleave.prevent
        class="border border-2 rounded p-5 text-center"
        :class="{
          'border-secondary bg-light': !isDragging && !file,
          'border-primary bg-primary bg-opacity-10': isDragging,
          'border-success bg-success bg-opacity-10': file && !error,
          'border-danger bg-danger bg-opacity-10': error
        }"
        style="border-style: dashed !important;"
      >
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.txt"
          @change="handleFileSelect"
          class="d-none"
        />

        <div v-if="!file">
          <i class="bi bi-cloud-upload fs-1 text-muted d-block mb-3"></i>
          <p class="mb-2">Drag and drop your CSV file here, or</p>
          <button @click="$refs.fileInput.click()" class="btn btn-primary">
            <i class="bi bi-folder-open me-2"></i>Browse Files
          </button>
        </div>

        <div v-else>
          <i class="bi bi-file-earmark-check fs-1 text-success d-block mb-3"></i>
          <p class="mb-2 fw-medium">{{ file.name }}</p>
          <p class="text-muted small mb-3">{{ formatFileSize(file.size) }}</p>
          <button @click="removeFile" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash me-1"></i>Remove File
          </button>
        </div>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="alert alert-danger mt-3">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ error }}
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="d-flex justify-content-between">
      <button @click="$emit('download-template')" class="btn btn-outline-secondary">
        <i class="bi bi-download me-2"></i>Download Template
      </button>
      
      <button
        @click="uploadFile"
        :disabled="!file || uploading"
        class="btn btn-primary"
      >
        <span v-if="uploading">
          <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
          Uploading...
        </span>
        <span v-else>
          Next: Map Columns <i class="bi bi-arrow-right ms-2"></i>
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useToast } from '@/composables/useToast'

interface Props {
  columnRequirements: {
    required: Record<string, string>
    optional: Record<string, string>
  }
}

defineProps<Props>()

const emit = defineEmits<{
  'file-uploaded': [data: any]
  'download-template': []
}>()

const { showToast } = useToast()

const file = ref<File | null>(null)
const fileInput = ref<HTMLInputElement>()
const isDragging = ref(false)
const uploading = ref(false)
const error = ref('')

const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragging.value = false
  
  const files = event.dataTransfer?.files
  if (files && files.length > 0) {
    handleFile(files[0])
  }
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    handleFile(target.files[0])
  }
}

const handleFile = (selectedFile: File) => {
  error.value = ''
  
  // Validate file type
  if (!selectedFile.name.match(/\.(csv|txt)$/i)) {
    error.value = 'Please select a CSV or TXT file'
    return
  }
  
  // Validate file size (10MB)
  if (selectedFile.size > 10 * 1024 * 1024) {
    error.value = 'File size must be less than 10MB'
    return
  }
  
  file.value = selectedFile
}

const removeFile = () => {
  file.value = null
  error.value = ''
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const uploadFile = async () => {
  if (!file.value) return
  
  uploading.value = true
  error.value = ''
  
  const formData = new FormData()
  formData.append('file', file.value)
  
  try {
    const response = await fetch('/admin/bets/import/upload', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: formData
    })
    
    const result = await response.json()
    
    if (result.success) {
      emit('file-uploaded', result)
    } else {
      error.value = result.message || 'Failed to upload file'
    }
  } catch (err) {
    error.value = 'An error occurred while uploading the file'
    console.error(err)
  } finally {
    uploading.value = false
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>