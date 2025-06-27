<template>
  <div>
    <h2 class="text-2xl font-semibold mb-4">Map CSV Columns</h2>
    
    <p class="text-gray-600 mb-6">
      Map your CSV columns to the corresponding bet fields. We've detected some mappings automatically, but please review and adjust as needed.
    </p>

    <!-- Column Mappings -->
    <div class="space-y-4 mb-6">
      <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold mb-3">Required Fields</h3>
        <div class="grid gap-3">
          <div
            v-for="(description, field) in columnRequirements.required"
            :key="field"
            class="flex items-center"
          >
            <label :for="`mapping-${field}`" class="w-1/3 text-sm font-medium text-gray-700">
              {{ field }}
              <span class="text-red-500">*</span>
              <span class="block text-xs font-normal text-gray-500">{{ description }}</span>
            </label>
            <select
              :id="`mapping-${field}`"
              v-model="mappings[field]"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
              :class="{ 'border-red-300': !mappings[field] }"
            >
              <option value="">-- Select Column --</option>
              <option v-for="header in csvHeaders" :key="header" :value="header">
                {{ header }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="bg-gray-50 rounded-lg p-4">
        <h3 class="font-semibold mb-3">Optional Fields</h3>
        <div class="grid gap-3">
          <div
            v-for="(description, field) in columnRequirements.optional"
            :key="field"
            class="flex items-center"
          >
            <label :for="`mapping-${field}`" class="w-1/3 text-sm font-medium text-gray-700">
              {{ field }}
              <span class="block text-xs font-normal text-gray-500">{{ description }}</span>
            </label>
            <select
              :id="`mapping-${field}`"
              v-model="mappings[field]"
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
              <option value="">-- Select Column --</option>
              <option v-for="header in csvHeaders" :key="header" :value="header">
                {{ header }}
              </option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Preview with Validation -->
    <div class="mb-6">
      <CsvPreview 
        :headers="csvHeaders"
        :rows="sampleData"
        :mappings="transformedMappings"
      />
    </div>

    <!-- Validation Messages -->
    <div v-if="validationErrors.length > 0" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
      <h4 class="font-semibold text-red-900 mb-2">Please fix the following issues:</h4>
      <ul class="list-disc list-inside text-sm text-red-800 space-y-1">
        <li v-for="error in validationErrors" :key="error">{{ error }}</li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="flex justify-between">
      <button
        @click="$emit('back')"
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
      >
        Back
      </button>

      <button
        @click="confirmMappings"
        :disabled="!isValid"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
      >
        Continue to Validation
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import CsvPreview from './CsvPreview.vue'

interface Props {
  csvHeaders: string[]
  detectedMappings: Record<string, string>
  columnRequirements: {
    required: Record<string, string>
    optional: Record<string, string>
  }
  sampleData: any[]
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'mappings-confirmed': [mappings: Record<string, string>]
  'back': []
}>()

const mappings = ref<Record<string, string>>({})

onMounted(() => {
  // Initialize with detected mappings
  mappings.value = { ...props.detectedMappings }
  
  // Ensure all required fields are initialized
  Object.keys(props.columnRequirements.required).forEach(field => {
    if (!mappings.value[field]) {
      mappings.value[field] = ''
    }
  })
  
  // Initialize optional fields
  Object.keys(props.columnRequirements.optional).forEach(field => {
    if (!mappings.value[field]) {
      mappings.value[field] = ''
    }
  })
})

// Transform mappings for CsvPreview component
const transformedMappings = computed(() => {
  // Create array-based mappings where index corresponds to CSV column index
  return props.csvHeaders.map(header => {
    // Find if this header is mapped to any field
    const mappedField = Object.entries(mappings.value).find(([_, csvCol]) => csvCol === header)?.[0]
    return { field: mappedField || null }
  })
})

const validationErrors = computed(() => {
  const errors: string[] = []
  
  // Check required fields
  Object.keys(props.columnRequirements.required).forEach(field => {
    if (!mappings.value[field]) {
      errors.push(`${field} is required`)
    }
  })
  
  // Check for duplicate mappings
  const usedColumns = Object.values(mappings.value).filter(v => v)
  const duplicates = usedColumns.filter((v, i) => usedColumns.indexOf(v) !== i)
  if (duplicates.length > 0) {
    errors.push(`Duplicate column mappings: ${[...new Set(duplicates)].join(', ')}`)
  }
  
  return errors
})

const isValid = computed(() => validationErrors.value.length === 0)

const confirmMappings = () => {
  if (isValid.value) {
    // Only send mapped fields (exclude empty optional fields)
    const confirmedMappings = Object.entries(mappings.value)
      .filter(([_, value]) => value)
      .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {})
    
    emit('mappings-confirmed', confirmedMappings)
  }
}
</script>