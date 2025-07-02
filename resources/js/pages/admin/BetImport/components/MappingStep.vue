<template>
  <div>
    <h2 class="h3 mb-4">Map CSV Columns</h2>
    
    <p class="mb-4 text-dark">
      Map your CSV columns to the corresponding bet fields. We've detected some mappings automatically, but please review and adjust as needed.
    </p>
    
    <!-- Additional Help Text -->
    <div class="alert alert-light border mb-4">
      <h6 class="alert-heading"><i class="bi bi-lightbulb me-2"></i>Mapping Tips</h6>
      <ul class="mb-0 small">
        <li><strong>Selection/Pick:</strong> Look for columns like "Wager Name", "Pick", "Bet", or similar that contain your specific bet (e.g., "Chiefs -3.5", "Over 220.5")</li>
        <li><strong>Bet Type:</strong> Look for columns like "Wager Type", "Market", or "Type" that describe the bet category (Spread, Moneyline, etc.)</li>
        <li><strong>Game Date:</strong> Can be mapped from "Date", "Month", or similar date columns</li>
        <li><strong>Operator:</strong> May be in columns like "Code", "Book", "Site", or "Sportsbook"</li>
      </ul>
    </div>
    
    <!-- Special Game Column Notice -->
    <div v-if="gameColumnName" class="alert alert-info mb-4">
      <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>{{ gameColumnName }} Column Detected</h5>
      <p class="mb-0">
        We detected a "{{ gameColumnName }}" column that appears to contain both teams in format like "Away Team @ Home Team". 
        This column has been automatically mapped to both the home_team and away_team fields. We'll extract the teams for you during import.
      </p>
    </div>

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
                    {{ formatFieldName(field) }}
                    <span class="text-danger">*</span>
                    <div class="small text-muted">{{ description }}</div>
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
                    {{ formatFieldName(field) }}
                    <div class="small text-muted">{{ description }}</div>
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

// Get the actual name of the game column if detected
const gameColumnName = computed(() => {
  // First check if there's a detected game mapping
  if (props.detectedMappings.game) {
    return props.detectedMappings.game
  }
  
  // Otherwise look for a game-like column in the headers
  const gameColumn = props.csvHeaders.find(header => {
    const normalized = header.toLowerCase().trim()
    return ['game', 'games', 'match', 'matchup', 'fixture', 'event'].includes(normalized)
  })
  
  return gameColumn || null
})

onMounted(() => {
  // Initialize with detected mappings
  mappings.value = { ...props.detectedMappings }
  
  // If game column is mapped, also map it to home_team and away_team
  if (mappings.value.game && !mappings.value.home_team && !mappings.value.away_team) {
    mappings.value.home_team = mappings.value.game
    mappings.value.away_team = mappings.value.game
  }
  
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
  
  // Check for duplicate mappings (but allow game column to be mapped to both team fields)
  const usedColumns = Object.entries(mappings.value)
    .filter(([field, value]) => value)
    .map(([field, value]) => ({ field, value }))
  
  const columnCounts = usedColumns.reduce((acc, { field, value }) => {
    if (!acc[value]) acc[value] = []
    acc[value].push(field)
    return acc
  }, {} as Record<string, string[]>)
  
  Object.entries(columnCounts).forEach(([column, fields]) => {
    // Allow a column to be mapped to both home_team and away_team
    const isTeamMapping = fields.length === 2 && 
      fields.includes('home_team') && 
      fields.includes('away_team')
    
    if (fields.length > 1 && !isTeamMapping) {
      errors.push(`Column "${column}" is mapped to multiple fields: ${fields.join(', ')}`)
    }
  })
  
  return errors
})

const isValid = computed(() => validationErrors.value.length === 0)

// Format field names for display
const formatFieldName = (field: string): string => {
  const nameMap: Record<string, string> = {
    'sport': 'Sport',
    'home_team': 'Home Team',
    'away_team': 'Away Team',
    'game_date': 'Game Date',
    'bet_type': 'Bet Type',
    'selection': 'Selection/Pick',
    'odds': 'Odds',
    'stake': 'Stake/Wager Amount',
    'operator': 'Sportsbook/Operator',
    'status': 'Bet Status',
    'description': 'Notes/Description',
    'placed_at': 'Date Placed',
    'league': 'League/Competition',
    'referrer': 'Referrer/Source'
  }
  return nameMap[field] || field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const confirmMappings = () => {
  if (isValid.value) {
    // Only send mapped fields (exclude empty optional fields)
    const confirmedMappings = Object.entries(mappings.value)
      .filter(([_, value]) => value)
      .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {})
    
    // If both home_team and away_team are mapped to the same column, also include 'game' mapping
    if (confirmedMappings.home_team && 
        confirmedMappings.away_team && 
        confirmedMappings.home_team === confirmedMappings.away_team) {
      confirmedMappings.game = confirmedMappings.home_team
    }
    
    emit('mappings-confirmed', confirmedMappings)
  }
}
</script>