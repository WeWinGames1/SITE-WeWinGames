<template>
  <div>
    <h2 class="text-2xl font-semibold mb-4">Validate & Preview Import</h2>
    
    <!-- Summary -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white p-4 rounded-lg border">
        <dt class="text-sm font-medium text-gray-500">Total Rows</dt>
        <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ summary.total }}</dd>
      </div>
      <div class="bg-green-50 p-4 rounded-lg border border-green-200">
        <dt class="text-sm font-medium text-green-700">Valid Rows</dt>
        <dd class="mt-1 text-2xl font-semibold text-green-900">{{ summary.valid }}</dd>
      </div>
      <div class="bg-red-50 p-4 rounded-lg border border-red-200">
        <dt class="text-sm font-medium text-red-700">Invalid Rows</dt>
        <dd class="mt-1 text-2xl font-semibold text-red-900">{{ summary.invalid }}</dd>
      </div>
      <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
        <dt class="text-sm font-medium text-yellow-700">Warnings</dt>
        <dd class="mt-1 text-2xl font-semibold text-yellow-900">{{ warningCount }}</dd>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
      <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8">
          <button
            @click="activeTab = 'valid'"
            :class="{
              'border-indigo-500 text-indigo-600': activeTab === 'valid',
              'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'valid'
            }"
            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
          >
            Valid Rows ({{ summary.valid }})
          </button>
          <button
            @click="activeTab = 'invalid'"
            :class="{
              'border-indigo-500 text-indigo-600': activeTab === 'invalid',
              'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'invalid'
            }"
            class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm"
          >
            Invalid Rows ({{ summary.invalid }})
          </button>
        </nav>
      </div>
    </div>

    <!-- Valid Rows -->
    <div v-if="activeTab === 'valid'" class="mb-6">
      <div v-if="validRows.length === 0" class="text-center py-8 text-gray-500">
        No valid rows found
      </div>
      <div v-else class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Sport</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Teams</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Selection</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Odds</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Stake</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Warnings</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="row in validRows" :key="row.row">
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.row }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.data.sport }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">
                <div class="text-xs">{{ row.data.away_team }}</div>
                <div class="text-xs">@ {{ row.data.home_team }}</div>
              </td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ formatDate(row.data.game_date) }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.data.bet_type }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.data.selection }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.data.odds }}</td>
              <td class="px-3 py-2 text-sm text-gray-900">${{ row.data.stake }}</td>
              <td class="px-3 py-2 text-sm">
                <span :class="getStatusClass(row.data.status)" class="px-2 py-1 text-xs rounded-full">
                  {{ row.data.status }}
                </span>
              </td>
              <td class="px-3 py-2 text-sm text-yellow-700">
                <div v-for="warning in row.warnings" :key="warning" class="text-xs">
                  ⚠️ {{ warning }}
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Invalid Rows -->
    <div v-if="activeTab === 'invalid'" class="mb-6">
      <div v-if="invalidRows.length === 0" class="text-center py-8 text-gray-500">
        No invalid rows found
      </div>
      <div v-else class="space-y-4">
        <div v-for="row in invalidRows" :key="row.row" class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex justify-between items-start mb-2">
            <h4 class="font-semibold text-red-900">Row {{ row.row }}</h4>
          </div>
          
          <!-- Show data -->
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mb-3 text-sm">
            <div v-for="(value, field) in row.data" :key="field">
              <span class="font-medium text-gray-700">{{ field }}:</span>
              <span class="text-gray-900 ml-1">{{ value || '-' }}</span>
            </div>
          </div>
          
          <!-- Show errors -->
          <div class="mt-3 space-y-1">
            <div v-for="(messages, field) in row.errors" :key="field" class="text-sm text-red-700">
              <span class="font-medium">{{ field }}:</span>
              <span v-for="message in messages" :key="message" class="ml-1">{{ message }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Options -->
    <div v-if="summary.invalid > 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
      <h3 class="font-semibold text-yellow-900 mb-2">Import Options</h3>
      <label class="flex items-center">
        <input
          v-model="skipErrors"
          type="checkbox"
          class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        />
        <span class="ml-2 text-sm text-yellow-800">
          Skip invalid rows and import only valid rows ({{ summary.valid }} rows will be imported)
        </span>
      </label>
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
        @click="confirmImport"
        :disabled="summary.valid === 0"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
      >
        Import {{ summary.valid }} {{ summary.valid === 1 ? 'Bet' : 'Bets' }}
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
    'pending': 'bg-gray-100 text-gray-800',
    'won': 'bg-green-100 text-green-800',
    'lost': 'bg-red-100 text-red-800',
    'void': 'bg-gray-100 text-gray-800',
    'push': 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || classes.pending
}

const confirmImport = () => {
  emit('import-confirmed', skipErrors.value)
}
</script>