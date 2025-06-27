<template>
  <div class="bg-white rounded-lg shadow">
    <div class="px-4 py-3 border-b border-gray-200">
      <h3 class="text-lg font-medium text-gray-900">Data Preview</h3>
      <p class="mt-1 text-sm text-gray-500">
        Showing {{ Math.min(5, rows.length) }} of {{ rows.length }} rows
      </p>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Row
            </th>
            <th 
              v-for="(mapping, index) in mappings" 
              :key="index"
              class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
            >
              <div>
                <div class="font-semibold">{{ mapping.field || 'Unmapped' }}</div>
                <div class="font-normal text-gray-400">{{ headers[index] }}</div>
              </div>
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr 
            v-for="(row, rowIndex) in previewRows" 
            :key="rowIndex"
            :class="{ 'bg-red-50': rowValidationErrors[rowIndex]?.length > 0 }"
          >
            <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500">
              {{ rowIndex + 2 }}
              <div v-if="rowValidationErrors[rowIndex]?.length > 0" class="mt-1">
                <ExclamationCircleIcon class="h-4 w-4 text-red-500" />
              </div>
            </td>
            <td 
              v-for="(mapping, colIndex) in mappings" 
              :key="colIndex"
              class="px-3 py-2 text-sm"
              :class="getCellClass(rowIndex, colIndex)"
            >
              <div class="max-w-xs truncate" :title="row[colIndex]">
                {{ formatCellValue(row[colIndex], mapping.field) }}
              </div>
              <div 
                v-if="getCellErrors(rowIndex, colIndex).length > 0"
                class="mt-1 text-xs text-red-600"
              >
                {{ getCellErrors(rowIndex, colIndex)[0] }}
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    
    <!-- Validation Summary -->
    <div v-if="validationSummary" class="px-4 py-3 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <div class="flex items-center text-sm">
            <CheckCircleIcon class="h-5 w-5 text-green-500 mr-1" />
            <span class="text-gray-700">{{ validationSummary.valid }} valid</span>
          </div>
          <div class="flex items-center text-sm">
            <XCircleIcon class="h-5 w-5 text-red-500 mr-1" />
            <span class="text-gray-700">{{ validationSummary.invalid }} invalid</span>
          </div>
        </div>
        <button
          v-if="validationSummary.invalid > 0"
          @click="showErrorDetails = !showErrorDetails"
          class="text-sm text-indigo-600 hover:text-indigo-900"
        >
          {{ showErrorDetails ? 'Hide' : 'Show' }} error details
        </button>
      </div>
    </div>
    
    <!-- Error Details -->
    <div 
      v-if="showErrorDetails && validationSummary?.errors.length > 0" 
      class="px-4 py-3 border-t border-gray-200 bg-red-50"
    >
      <h4 class="text-sm font-medium text-red-900 mb-2">Validation Errors</h4>
      <div class="max-h-60 overflow-y-auto">
        <div 
          v-for="(error, index) in validationSummary.errors.slice(0, 10)" 
          :key="index"
          class="text-sm text-red-700 mb-2"
        >
          <span class="font-medium">Row {{ error.row }}:</span>
          <ul class="ml-4 mt-1">
            <li v-for="(err, errIndex) in error.errors" :key="errIndex">
              {{ err.field }}: {{ err.message }}
            </li>
          </ul>
        </div>
        <div 
          v-if="validationSummary.errors.length > 10" 
          class="text-sm text-red-700 italic"
        >
          And {{ validationSummary.errors.length - 10 }} more errors...
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { CheckCircleIcon, XCircleIcon, ExclamationCircleIcon } from '@heroicons/vue/24/outline'
import { validateBet, transformBetData, getValidationSummary } from '@/utils/betValidation'
import type { ValidationError } from '@/utils/betValidation'

interface Mapping {
  field: string | null
}

interface Props {
  headers: string[]
  rows: string[][]
  mappings: Mapping[]
}

const props = defineProps<Props>()

const showErrorDetails = ref(false)

// Get first 5 rows for preview
const previewRows = computed(() => props.rows.slice(0, 5))

// Transform rows to objects based on mappings
const transformedRows = computed(() => {
  return props.rows.map(row => {
    const obj: Record<string, any> = {}
    props.mappings.forEach((mapping, index) => {
      if (mapping.field) {
        obj[mapping.field] = row[index]
      }
    })
    return transformBetData(obj)
  })
})

// Validate all rows
const validationSummary = computed(() => {
  if (!props.mappings.some(m => m.field)) return null
  return getValidationSummary(transformedRows.value)
})

// Get validation errors for preview rows
const rowValidationErrors = computed(() => {
  const errors: Record<number, ValidationError[]> = {}
  
  previewRows.value.forEach((row, rowIndex) => {
    const obj: Record<string, any> = {}
    props.mappings.forEach((mapping, index) => {
      if (mapping.field) {
        obj[mapping.field] = row[index]
      }
    })
    
    const transformed = transformBetData(obj)
    const rowErrors = validateBet(transformed)
    
    if (rowErrors.length > 0) {
      errors[rowIndex] = rowErrors
    }
  })
  
  return errors
})

// Get cell-specific errors
const getCellErrors = (rowIndex: number, colIndex: number): string[] => {
  const mapping = props.mappings[colIndex]
  if (!mapping.field) return []
  
  const errors = rowValidationErrors.value[rowIndex] || []
  return errors
    .filter(error => error.field === mapping.field)
    .map(error => error.message)
}

// Get cell styling based on validation
const getCellClass = (rowIndex: number, colIndex: number) => {
  const hasError = getCellErrors(rowIndex, colIndex).length > 0
  const mapping = props.mappings[colIndex]
  
  return {
    'text-gray-900': !hasError && mapping.field,
    'text-gray-400': !mapping.field,
    'text-red-600': hasError,
    'bg-red-100': hasError
  }
}

// Format cell values for display
const formatCellValue = (value: any, field?: string | null) => {
  if (!value) return '-'
  
  // Format numbers for odds and stake fields
  if (field === 'odds' || field === 'stake') {
    const num = parseFloat(value)
    if (!isNaN(num)) {
      return field === 'stake' ? `$${num.toFixed(2)}` : num.toFixed(2)
    }
  }
  
  // Format dates
  if (field === 'game_date') {
    const date = new Date(value)
    if (!isNaN(date.getTime())) {
      return date.toLocaleDateString()
    }
  }
  
  return value
}

// Watch for mapping changes and reset error details
watch(() => props.mappings, () => {
  showErrorDetails.value = false
}, { deep: true })
</script>