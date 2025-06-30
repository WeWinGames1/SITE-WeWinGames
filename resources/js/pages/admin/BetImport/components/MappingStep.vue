<template>
  <div>
    <h2 class="h3 mb-4">Map CSV Columns</h2>
    
    <p class="mb-4">
      Map your CSV columns to the corresponding bet fields. We've detected some mappings automatically, but please review and adjust as needed.
    </p>

    <!-- Column Mappings -->
    <div class="mb-4">
      <div class="card bg-light mb-4">
        <div class="card-body">
          <h3 class="h5 mb-3">Required Fields</h3>
          <div class="row g-3">
            <div
              v-for="(description, field) in columnRequirements.required"
              :key="field"
              class="col-12"
            >
              <div class="row align-items-center">
                <div class="col-md-4">
                  <label :for="`mapping-${field}`" class="form-label fw-medium">
                    {{ field }}
                    <span class="text-danger">*</span>
                    <div class="small text-dark">{{ description }}</div>
                  </label>
                </div>
                <div class="col-md-8">
                  <select
                    :id="`mapping-${field}`"
                    v-model="mappings[field]"
                    class="form-select"
                    :class="{ 'is-invalid': !mappings[field] }"
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
        </div>
      </div>

      <div class="card bg-light">
        <div class="card-body">
          <h3 class="h5 mb-3">Optional Fields</h3>
          <div class="row g-3">
            <div
              v-for="(description, field) in columnRequirements.optional"
              :key="field"
              class="col-12"
            >
              <div class="row align-items-center">
                <div class="col-md-4">
                  <label :for="`mapping-${field}`" class="form-label fw-medium">
                    {{ field }}
                    <div class="small text-dark">{{ description }}</div>
                  </label>
                </div>
                <div class="col-md-8">
                  <select
                    :id="`mapping-${field}`"
                    v-model="mappings[field]"
                    class="form-select"
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
        </div>
      </div>
    </div>

    <!-- Data Preview with Validation -->
    <div class="mb-4">
      <CsvPreview 
        :headers="csvHeaders"
        :rows="sampleData"
        :mappings="transformedMappings"
      />
    </div>

    <!-- Validation Messages -->
    <div v-if="validationErrors.length > 0" class="alert alert-danger mb-4">
      <h4 class="alert-heading">Please fix the following issues:</h4>
      <ul class="mb-0">
        <li v-for="error in validationErrors" :key="error">{{ error }}</li>
      </ul>
    </div>

    <!-- Actions -->
    <div class="d-flex justify-content-between">
      <button
        @click="$emit('back')"
        class="btn btn-outline-secondary"
      >
        <i class="bi bi-arrow-left me-2"></i>Back
      </button>

      <button
        @click="confirmMappings"
        :disabled="!isValid"
        class="btn btn-primary"
      >
        Continue to Validation <i class="bi bi-arrow-right ms-2"></i>
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