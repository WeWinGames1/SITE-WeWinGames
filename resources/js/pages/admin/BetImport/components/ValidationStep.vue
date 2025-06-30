<template>
  <div>
    <h2 class="h3 mb-4">Validate & Preview Import</h2>
    
    <!-- Summary -->
    <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <dt class="small text-muted">Total Rows</dt>
            <dd class="h3 mb-0">{{ summary.total }}</dd>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success bg-opacity-10 border-success">
          <div class="card-body">
            <dt class="small text-success">Valid Rows</dt>
            <dd class="h3 mb-0 text-success">{{ summary.valid }}</dd>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger bg-opacity-10 border-danger">
          <div class="card-body">
            <dt class="small text-danger">Invalid Rows</dt>
            <dd class="h3 mb-0 text-danger">{{ summary.invalid }}</dd>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning bg-opacity-10 border-warning">
          <div class="card-body">
            <dt class="small text-warning">Warnings</dt>
            <dd class="h3 mb-0 text-warning">{{ warningCount }}</dd>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-4">
      <ul class="nav nav-tabs">
        <li class="nav-item">
          <button
            @click="activeTab = 'valid'"
            :class="['nav-link', activeTab === 'valid' ? 'active' : '']"
          >
            Valid Rows ({{ summary.valid }})
          </button>
        </li>
        <li class="nav-item">
          <button
            @click="activeTab = 'invalid'"
            :class="['nav-link', activeTab === 'invalid' ? 'active' : '']"
          >
            Invalid Rows ({{ summary.invalid }})
          </button>
        </li>
      </ul>
    </div>

    <!-- Valid Rows -->
    <div v-if="activeTab === 'valid'" class="mb-4">
      <div v-if="validRows.length === 0" class="text-center py-5 text-muted">
        No valid rows found
      </div>
      <div v-else class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>Row</th>
              <th>Sport</th>
              <th>Teams</th>
              <th>Date</th>
              <th>Type</th>
              <th>Selection</th>
              <th>Odds</th>
              <th>Stake</th>
              <th>Status</th>
              <th>Warnings</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in validRows" :key="row.row">
              <td>{{ row.row }}</td>
              <td>{{ row.data.sport }}</td>
              <td>
                <div class="small">{{ row.data.away_team }}</div>
                <div class="small">@ {{ row.data.home_team }}</div>
              </td>
              <td>{{ formatDate(row.data.game_date) }}</td>
              <td>{{ row.data.bet_type }}</td>
              <td>{{ row.data.selection }}</td>
              <td>{{ row.data.odds }}</td>
              <td>${{ row.data.stake }}</td>
              <td>
                <span :class="getStatusClass(row.data.status)" class="badge">
                  {{ row.data.status }}
                </span>
              </td>
              <td class="text-warning">
                <div v-for="warning in row.warnings" :key="warning" class="small">
                  ⚠️ {{ warning }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Invalid Rows -->
    <div v-if="activeTab === 'invalid'" class="mb-4">
      <div v-if="invalidRows.length === 0" class="text-center py-5 text-muted">
        No invalid rows found
      </div>
      <div v-else>
        <div v-for="row in invalidRows" :key="row.row" class="card bg-danger bg-opacity-10 border-danger mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h4 class="h6 text-danger">Row {{ row.row }}</h4>
            </div>
            
            <!-- Show data -->
            <div class="row g-2 mb-3">
              <div v-for="(value, field) in row.data" :key="field" class="col-md-4 col-6">
                <span class="fw-medium">{{ field }}:</span>
                <span class="ms-1">{{ value || '-' }}</span>
              </div>
            </div>
            
            <!-- Show errors -->
            <div class="mt-3">
              <div v-for="(messages, field) in row.errors" :key="field" class="small text-danger mb-1">
                <span class="fw-medium">{{ field }}:</span>
                <span v-for="message in messages" :key="message" class="ms-1">{{ message }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Options -->
    <div v-if="summary.invalid > 0" class="alert alert-warning mb-4">
      <h3 class="alert-heading h6">Import Options</h3>
      <div class="form-check">
        <input
          v-model="skipErrors"
          type="checkbox"
          class="form-check-input"
          id="skipErrors"
        />
        <label class="form-check-label" for="skipErrors">
          Skip invalid rows and import only valid rows ({{ summary.valid }} rows will be imported)
        </label>
      </div>
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
        @click="confirmImport"
        :disabled="summary.valid === 0"
        class="btn btn-primary"
      >
        <i class="bi bi-upload me-2"></i>Import {{ summary.valid }} {{ summary.valid === 1 ? 'Bet' : 'Bets' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface Props {
  validationResult: {
    valid_rows: Array<{
      row: number
      data: any
      warnings: string[]
    }>
    invalid_rows: Array<{
      row: number
      data: any
      errors: Record<string, string[]>
    }>
    summary: {
      total: number
      valid: number
      invalid: number
    }
  }
  validationRules: Record<string, string>
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'import-confirmed': [skipErrors: boolean]
  'back': []
}>()

const activeTab = ref<'valid' | 'invalid'>('valid')
const skipErrors = ref(false)

const validRows = computed(() => props.validationResult?.valid_rows || [])
const invalidRows = computed(() => props.validationResult?.invalid_rows || [])
const summary = computed(() => props.validationResult?.summary || { total: 0, valid: 0, invalid: 0 })

const warningCount = computed(() => {
  return validRows.value.reduce((count, row) => count + row.warnings.length, 0)
})

const formatDate = (date: string) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString()
}

const getStatusClass = (status: string) => {
  const classes = {
    'pending': 'bg-secondary',
    'won': 'bg-success',
    'lost': 'bg-danger',
    'void': 'bg-secondary',
    'push': 'bg-warning'
  }
  return classes[status] || classes.pending
}

const confirmImport = () => {
  emit('import-confirmed', skipErrors.value)
}
</script>