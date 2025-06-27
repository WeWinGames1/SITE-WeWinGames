export interface ValidationRule {
  required?: boolean
  type?: 'string' | 'number' | 'date'
  min?: number
  max?: number
  pattern?: RegExp
  in?: string[]
  message?: string
}

export interface ValidationRules {
  [field: string]: ValidationRule[]
}

export interface ValidationError {
  field: string
  message: string
}

export const betValidationRules: ValidationRules = {
  sport: [
    { required: true, message: 'Sport is required' },
    { type: 'string', message: 'Sport must be text' },
    { max: 255, message: 'Sport name is too long' }
  ],
  home_team: [
    { required: true, message: 'Home team is required' },
    { type: 'string', message: 'Home team must be text' },
    { max: 255, message: 'Team name is too long' }
  ],
  away_team: [
    { required: true, message: 'Away team is required' },
    { type: 'string', message: 'Away team must be text' },
    { max: 255, message: 'Team name is too long' }
  ],
  game_date: [
    { required: true, message: 'Game date is required' },
    { type: 'date', message: 'Invalid date format' }
  ],
  bet_type: [
    { required: true, message: 'Bet type is required' },
    { type: 'string', message: 'Bet type must be text' },
    { max: 50, message: 'Bet type is too long' }
  ],
  selection: [
    { required: true, message: 'Selection is required' },
    { type: 'string', message: 'Selection must be text' },
    { max: 255, message: 'Selection is too long' }
  ],
  odds: [
    { required: true, message: 'Odds are required' },
    { type: 'number', message: 'Odds must be a number' },
    { min: 1.01, message: 'Odds must be at least 1.01' },
    { max: 1000, message: 'Odds cannot exceed 1000' }
  ],
  stake: [
    { required: true, message: 'Stake is required' },
    { type: 'number', message: 'Stake must be a number' },
    { min: 0.01, message: 'Minimum stake is 0.01' },
    { max: 100000, message: 'Maximum stake is 100,000' }
  ],
  operator: [
    { required: true, message: 'Operator is required' },
    { type: 'string', message: 'Operator must be text' },
    { max: 255, message: 'Operator name is too long' }
  ],
  status: [
    { type: 'string', message: 'Status must be text' },
    { in: ['pending', 'won', 'lost', 'void', 'push'], message: 'Invalid status value' }
  ],
  description: [
    { type: 'string', message: 'Description must be text' },
    { max: 500, message: 'Description is too long' }
  ]
}

export function validateBet(data: Record<string, any>): ValidationError[] {
  const errors: ValidationError[] = []

  Object.entries(betValidationRules).forEach(([field, rules]) => {
    const value = data[field]

    rules.forEach(rule => {
      // Check required
      if (rule.required && (!value || value === '')) {
        errors.push({ field, message: rule.message || `${field} is required` })
        return
      }

      // Skip other validations if value is empty and not required
      if (!value && !rule.required) return

      // Type validation
      if (rule.type) {
        switch (rule.type) {
          case 'string':
            if (typeof value !== 'string') {
              errors.push({ field, message: rule.message || `${field} must be text` })
            }
            break
          case 'number':
            if (isNaN(Number(value))) {
              errors.push({ field, message: rule.message || `${field} must be a number` })
            }
            break
          case 'date':
            if (!isValidDate(value)) {
              errors.push({ field, message: rule.message || `${field} must be a valid date` })
            }
            break
        }
      }

      // Min validation
      if (rule.min !== undefined) {
        const numValue = Number(value)
        if (!isNaN(numValue) && numValue < rule.min) {
          errors.push({ field, message: rule.message || `${field} must be at least ${rule.min}` })
        }
      }

      // Max validation
      if (rule.max !== undefined) {
        if (rule.type === 'string' && value.length > rule.max) {
          errors.push({ field, message: rule.message || `${field} is too long (max ${rule.max} characters)` })
        } else if (rule.type === 'number') {
          const numValue = Number(value)
          if (!isNaN(numValue) && numValue > rule.max) {
            errors.push({ field, message: rule.message || `${field} cannot exceed ${rule.max}` })
          }
        }
      }

      // In validation
      if (rule.in && !rule.in.includes(value)) {
        errors.push({ field, message: rule.message || `${field} must be one of: ${rule.in.join(', ')}` })
      }

      // Pattern validation
      if (rule.pattern && !rule.pattern.test(value)) {
        errors.push({ field, message: rule.message || `${field} format is invalid` })
      }
    })
  })

  return errors
}

export function isValidDate(value: any): boolean {
  if (!value) return false
  const date = new Date(value)
  return date instanceof Date && !isNaN(date.getTime())
}

export function formatValidationErrors(errors: ValidationError[]): Record<string, string[]> {
  const formatted: Record<string, string[]> = {}
  
  errors.forEach(error => {
    if (!formatted[error.field]) {
      formatted[error.field] = []
    }
    formatted[error.field].push(error.message)
  })
  
  return formatted
}

// Transform data types for validation
export function transformBetData(data: Record<string, any>): Record<string, any> {
  const transformed = { ...data }

  // Convert odds to number
  if (transformed.odds) {
    transformed.odds = parseFloat(transformed.odds)
  }

  // Convert stake to number
  if (transformed.stake) {
    transformed.stake = parseFloat(transformed.stake)
  }

  // Normalize status
  if (transformed.status) {
    transformed.status = transformed.status.toLowerCase()
  }

  return transformed
}

// Get validation summary
export function getValidationSummary(data: Record<string, any>[]): {
  valid: number
  invalid: number
  errors: Array<{ row: number; errors: ValidationError[] }>
} {
  let valid = 0
  let invalid = 0
  const errors: Array<{ row: number; errors: ValidationError[] }> = []

  data.forEach((row, index) => {
    const transformedRow = transformBetData(row)
    const rowErrors = validateBet(transformedRow)
    
    if (rowErrors.length === 0) {
      valid++
    } else {
      invalid++
      errors.push({ row: index + 2, errors: rowErrors }) // +2 for header row and 0-index
    }
  })

  return { valid, invalid, errors }
}