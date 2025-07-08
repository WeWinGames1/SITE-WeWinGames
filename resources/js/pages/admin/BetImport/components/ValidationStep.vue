<template>
  <div>
    <h2 class="h3 mb-4">Validate & Preview Import</h2>
    
    <!-- Preview Notice -->
    <div v-if="summary.is_preview" class="alert alert-info mb-4">
      <div class="d-flex align-items-start">
        <i class="bi bi-info-circle-fill me-2 flex-shrink-0"></i>
        <div>
          <strong>Preview Mode:</strong> Showing validation results for the first {{ summary.previewed_rows }} rows out of {{ summary.total }} total rows.
          The actual import will process all rows.
        </div>
      </div>
    </div>
    
    <!-- Summary -->
    <div class="row mb-4">
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card">
          <div class="card-body">
            <dt class="small text-dark">Total Rows in File</dt>
            <dd class="h3 mb-0">{{ summary.total }}</dd>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success bg-opacity-10 border-success">
          <div class="card-body">
            <dt class="small text-success">{{ summary.is_preview ? 'Valid (Preview)' : 'Valid Rows' }}</dt>
            <dd class="h3 mb-0 text-success">{{ summary.valid }}</dd>
            <small v-if="summary.is_preview" class="text-muted">First 100 rows only</small>
          </div>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger bg-opacity-10 border-danger">
          <div class="card-body">
            <dt class="small text-danger">{{ summary.is_preview ? 'Invalid (Preview)' : 'Invalid Rows' }}</dt>
            <dd class="h3 mb-0 text-danger">{{ summary.invalid }}</dd>
            <small v-if="summary.is_preview" class="text-muted">First 100 rows only</small>
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
            :style="activeTab === 'valid' ? 'color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff;' : 'color: #495057;'"
          >
            Valid Rows ({{ summary.valid }})
          </button>
        </li>
        <li class="nav-item">
          <button
            @click="activeTab = 'invalid'"
            :class="['nav-link', activeTab === 'invalid' ? 'active' : '']"
            :style="activeTab === 'invalid' ? 'color: #495057; background-color: #fff; border-color: #dee2e6 #dee2e6 #fff;' : 'color: #495057;'"
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
              <th>League</th>
              <th>Teams</th>
              <th>Date</th>
              <th>Bet Type</th>
              <th>Wager Type</th>
              <th>Wager Name</th>
              <th>Odds</th>
              <th>Level</th>
              <th>Code</th>
              <th>Wager</th>
              <th>Status</th>
              <th>ROI</th>
              <th>Profits</th>
              <th>Warnings</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in validRows" :key="row.row">
              <td>{{ row.row }}</td>
              <td>{{ row.original?.Sport || row.data.sport || '-' }}</td>
              <td>{{ row.original?.League || row.data.league || '-' }}</td>
              <td>
                <div class="small">{{ row.original?.Game || row.data.game || '-' }}</div>
              </td>
              <td>{{ row.original?.['Game Date'] || formatDate(row.data.game_date) || '-' }}</td>
              <td>{{ row.original?.['Bet Type'] || row.data.bet_type || '-' }}</td>
              <td>{{ row.original?.['Wager Type'] || row.data.wager_type || '-' }}</td>
              <td>{{ row.original?.['Wager Name'] || row.data.wager_name || '-' }}</td>
              <td>{{ row.original?.Odds || row.data.odds || '-' }}</td>
              <td>{{ row.original?.Level || row.data.level || '-' }}</td>
              <td>{{ row.original?.Code || row.data.code || '-' }}</td>
              <td>{{ row.original?.Wager || row.data.wager || '-' }}</td>
              <td>
                <span :class="getStatusClass(row.original?.Status || row.data.status)" class="badge">
                  {{ row.original?.Status || row.data.status || '-' }}
                </span>
              </td>
              <td>{{ row.original?.['ROI(net)'] || row.data.roi || '-' }}</td>
              <td>{{ row.original?.Profits || row.data.profits || '-' }}</td>
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
      <div v-if="invalidRows.length === 0" class="text-center py-5 text-dark">
        No invalid rows found
      </div>
      <div v-else>
        <div v-for="row in invalidRows" :key="row.row" class="card bg-danger bg-opacity-10 border-danger mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <h4 class="h5 text-danger mb-0">Row {{ row.row }}</h4>
            </div>
            
            <!-- Show errors as stacked alerts -->
            <div class="mb-3">
              <div v-for="(messages, field) in row.errors" :key="field" class="alert alert-danger py-2 mb-2">
                <div class="d-flex align-items-start">
                  <i class="bi bi-exclamation-circle-fill me-2 flex-shrink-0"></i>
                  <div class="w-100">
                    <strong>{{ formatFieldName(field) }}:</strong>
                    <!-- Handle different error message formats -->
                    <template v-if="typeof messages === 'string'">
                      <span class="ms-1">{{ messages }}</span>
                    </template>
                    <template v-else-if="Array.isArray(messages)">
                      <template v-if="messages.length === 1">
                        <span class="ms-1">{{ messages[0] }}</span>
                      </template>
                      <ul v-else class="mb-0 ps-3 mt-1">
                        <li v-for="(message, index) in messages" :key="`${field}-msg-${index}`">
                          {{ typeof message === 'string' ? message : String(message) }}
                        </li>
                      </ul>
                    </template>
                    <template v-else>
                      <span class="ms-1">{{ String(messages) }}</span>
                    </template>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Show original CSV data -->
            <div class="border-top pt-3">
              <h6 class="text-muted mb-2">CSV Row Data:</h6>
              <div class="row g-2">
                <!-- Show original CSV values if available -->
                <template v-if="row.original">
                  <div v-for="(value, column) in row.original" :key="column" class="col-lg-3 col-md-4 col-6">
                    <div class="small">
                      <span class="text-muted">{{ column }}:</span>
                      <div class="fw-medium">{{ value || '-' }}</div>
                    </div>
                  </div>
                </template>
                <!-- Fallback to mapped data if original not available -->
                <template v-else>
                  <div v-for="(value, field) in row.data" :key="field" class="col-lg-3 col-md-4 col-6">
                    <div class="small">
                      <span class="text-muted">{{ formatFieldName(field) }}:</span>
                      <div class="fw-medium">{{ value || '-' }}</div>
                    </div>
                  </div>
                </template>
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
        <i class="bi bi-upload me-2"></i>
        <span v-if="summary.is_preview">Import All {{ summary.total }} Rows</span>
        <span v-else>Import {{ summary.valid }} {{ summary.valid === 1 ? 'Bet' : 'Bets' }}</span>
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
      original?: any
      warnings: string[]
    }>
    invalid_rows: Array<{
      row: number
      data: any
      original?: any
      errors: Record<string, string[]>
    }>
    summary: {
      total: number
      valid: number
      invalid: number
      preview_limit?: number
      previewed_rows?: number
      is_preview?: boolean
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
const summary = computed(() => props.validationResult?.summary || { 
  total: 0, 
  valid: 0, 
  invalid: 0,
  preview_limit: 100,
  previewed_rows: 0,
  is_preview: false
})

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

const formatFieldName = (field: string): string => {
  const nameMap: Record<string, string> = {
    'sport': 'Sport',
    'league': 'League',
    'month': 'Month',
    'game_date': 'Game Date',
    'game': 'Game',
    'home_team': 'Home Team',
    'away_team': 'Away Team',
    'bet_type': 'Bet Type',
    'wager_type': 'Wager Type',
    'wager_name': 'Wager Name',
    'odds': 'Odds',
    'level': 'Level',
    'code': 'Code',
    'status': 'Status',
    'roi': 'ROI',
    'wager': 'Wager',
    'stake': 'Stake',
    'profits': 'Profits',
    'winning_amount': 'Winning Amount'
  }
  return nameMap[field] || field.charAt(0).toUpperCase() + field.slice(1).replace(/_/g, ' ')
}
</script>

<style scoped>
/* Fix Bootstrap tab styling in admin panel */
.nav-tabs .nav-link {
  color: #495057;
  background-color: transparent;
  border: 1px solid transparent;
}

.nav-tabs .nav-link:hover {
  border-color: #e9ecef #e9ecef #dee2e6;
  color: #495057;
}

.nav-tabs .nav-link.active {
  color: #495057 !important;
  background-color: #fff !important;
  border-color: #dee2e6 #dee2e6 #fff !important;
}
</style>