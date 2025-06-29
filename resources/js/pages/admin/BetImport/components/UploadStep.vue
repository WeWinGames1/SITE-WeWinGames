<template>
  <div>
    <h2 class="text-2xl font-semibold mb-4">Upload CSV File</h2>
    
    <!-- File Requirements -->
    <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="font-semibold text-blue-900 mb-2">File Requirements</h3>
      <ul class="list-disc list-inside text-sm text-blue-800 space-y-1">
        <li>File must be in CSV format (.csv or .txt)</li>
        <li>Maximum file size: 10MB</li>
        <li>First row must contain column headers</li>
        <li>UTF-8 encoding recommended</li>
      </ul>
    </div>

    <!-- Required Columns -->
    <div class="mb-6 grid md:grid-cols-2 gap-4">
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <h3 class="font-semibold text-green-900 mb-2">Required Columns</h3>
        <dl class="space-y-1">
          <div v-for="(desc, field) in columnRequirements.required" :key="field" class="text-sm">
            <dt class="inline font-medium text-green-800">{{ field }}:</dt>
            <dd class="inline text-green-700 ml-1">{{ desc }}</dd>
          </div>
        </dl>
      </div>

      <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
        <h3 class="font-semibold text-gray-900 mb-2">Optional Columns</h3>
        <dl class="space-y-1">
          <div v-for="(desc, field) in columnRequirements.optional" :key="field" class="text-sm">
            <dt class="inline font-medium text-gray-700">{{ field }}:</dt>
            <dd class="inline text-gray-600 ml-1">{{ desc }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <!-- File Upload -->
    <div class="mb-6">
      <div
        @drop="handleDrop"
        @dragover.prevent
        @dragenter.prevent
        @dragleave.prevent
        class="border-2 border-dashed rounded-lg p-8 text-center transition-colors"
        :class="{
          'border-gray-300 bg-gray-50': !isDragging && !file,
          'border-indigo-500 bg-indigo-50': isDragging,
          'border-green-500 bg-green-50': file && !error
        }"
      >
        <input
          ref="fileInput"
          type="file"
          accept=".csv,.txt"
          @change="handleFileSelect"
          class="hidden"
        />

        <CloudArrowUpIcon class="mx-auto h-12 w-12 text-gray-400 mb-3" />

        <p class="text-base mb-2">
          <button
            type="button"
            @click="$refs.fileInput.click()"
            class="font-medium text-indigo-600 hover:text-indigo-500"
          >
            Click to upload
          </button>
          <span class="text-gray-600"> or drag and drop</span>
        </p>

        <p class="text-sm text-gray-500">CSV files up to 10MB</p>

        <!-- Selected File -->
        <div v-if="file" class="mt-4 p-3 bg-white rounded-md border">
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <DocumentTextIcon class="h-5 w-5 text-gray-400 mr-2" />
              <span class="text-sm font-medium">{{ file.name }}</span>
              <span class="ml-2 text-sm text-gray-500">({{ formatFileSize(file.size) }})</span>
            </div>
            <button
              @click="removeFile"
              class="text-red-600 hover:text-red-800"
            >
              <XMarkIcon class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-md">
          <p class="text-sm text-red-800">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex justify-between">
      <button
        @click="$emit('download-template')"
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
      >
        <CloudArrowDownIcon class="h-4 w-4 mr-2" />
        Download Template
      </button>

      <button
        @click="uploadFile"
        :disabled="!file || uploading"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
      >
        <span v-if="!uploading">Continue</span>
        <span v-else class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Uploading...
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { CloudArrowUpIcon, CloudArrowDownIcon, DocumentTextIcon, XMarkIcon } from '@heroicons/vue/24/outline'

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

const fileInput = ref<HTMLInputElement>()
const file = ref<File | null>(null)
const isDragging = ref(false)
const uploading = ref(false)
const error = ref('')

const handleDrop = (e: DragEvent) => {
  e.preventDefault()
  isDragging.value = false
  
  const files = e.dataTransfer?.files
  if (files && files.length > 0) {
    handleFile(files[0])
  }
}

const handleFileSelect = (e: Event) => {
  const target = e.target as HTMLInputElement
  if (target.files && target.files.length > 0) {
    handleFile(target.files[0])
  }
}

const handleFile = (selectedFile: File) => {
  error.value = ''
  
  // Validate file type
  if (!selectedFile.name.match(/\.(csv|txt)$/i)) {
    error.value = 'Please select a CSV file'
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
      error.value = result.message || 'Upload failed'
    }
  } catch (err) {
    error.value = 'Failed to upload file. Please try again.'
    // console.error(err)
  } finally {
    uploading.value = false
  }
}

const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}
</script>