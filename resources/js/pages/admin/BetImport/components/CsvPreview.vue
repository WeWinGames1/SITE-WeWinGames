<template>
    <div class="card">
        <div class="card-header">
            <h3 class="h5 mb-1">Data Preview</h3>
            <p class="text-dark mb-0">Showing {{ Math.min(5, rows.length) }} of {{ rows.length }} rows</p>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-uppercase small">Row</th>
                        <th v-for="(mapping, index) in mappings" :key="index" class="text-uppercase small">
                            <div>
                                <div class="fw-bold">{{ mapping.field || 'Unmapped' }}</div>
                                <div class="fw-normal text-muted">{{ headers[index] }}</div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, rowIndex) in previewRows" :key="rowIndex" :class="{ 'table-danger': rowValidationErrors[rowIndex]?.length > 0 }">
                        <td class="text-nowrap">
                            {{ rowIndex + 2 }}
                            <div v-if="rowValidationErrors[rowIndex]?.length > 0" class="mt-1">
                                <i class="bi bi-exclamation-circle-fill text-danger"></i>
                            </div>
                        </td>
                        <td v-for="(mapping, colIndex) in mappings" :key="colIndex" :class="getCellClass(rowIndex, colIndex)">
                            <div class="text-truncate" style="max-width: 200px" :title="row[colIndex]">
                                {{ formatCellValue(row[colIndex], mapping.field) }}
                            </div>
                            <div v-if="getCellErrors(rowIndex, colIndex).length > 0" class="mt-1 small text-danger">
                                {{ getCellErrors(rowIndex, colIndex)[0] }}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Validation Summary -->
        <div v-if="validationSummary" class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex gap-3">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill text-success me-1"></i>
                        <span class="text-dark">{{ validationSummary.valid }} valid</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <i class="bi bi-x-circle-fill text-danger me-1"></i>
                        <span class="text-dark">{{ validationSummary.invalid }} invalid</span>
                    </div>
                </div>
                <button v-if="validationSummary.invalid > 0" @click="showErrorDetails = !showErrorDetails" class="btn btn-link btn-sm p-0">
                    {{ showErrorDetails ? 'Hide' : 'Show' }} error details
                </button>
            </div>
        </div>

        <!-- Error Details -->
        <div v-if="showErrorDetails && validationSummary?.errors.length > 0" class="card-footer bg-danger-subtle">
            <h4 class="h6 text-danger mb-2">Validation Errors</h4>
            <div style="max-height: 15rem; overflow-y: auto">
                <div v-for="(error, index) in validationSummary.errors.slice(0, 10)" :key="index" class="mb-2">
                    <span class="fw-bold text-dark">Row {{ error.row }}:</span>
                    <ul class="ms-4 mt-1">
                        <li v-for="(err, errIndex) in error.errors" :key="errIndex" class="text-dark">{{ err.field }}: {{ err.message }}</li>
                    </ul>
                </div>
                <div v-if="validationSummary.errors.length > 10" class="fst-italic text-dark">
                    And {{ validationSummary.errors.length - 10 }} more errors...
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
// Icons removed - using Bootstrap Icons instead
import type { ValidationError } from '@/utils/betValidation';
import { getValidationSummary, transformBetData, validateBet } from '@/utils/betValidation';

interface Mapping {
    field: string | null;
}

interface Props {
    headers: string[];
    rows: string[][];
    mappings: Mapping[];
}

const props = defineProps<Props>();

const showErrorDetails = ref(false);

// Get first 5 rows for preview
const previewRows = computed(() => props.rows.slice(0, 5));

// Transform rows to objects based on mappings
const transformedRows = computed(() => {
    return props.rows.map((row) => {
        const obj: Record<string, any> = {};
        props.mappings.forEach((mapping, index) => {
            if (mapping.field) {
                obj[mapping.field] = row[index];
            }
        });
        return transformBetData(obj);
    });
});

// Validate all rows
const validationSummary = computed(() => {
    if (!props.mappings.some((m) => m.field)) return null;
    return getValidationSummary(transformedRows.value);
});

// Get validation errors for preview rows
const rowValidationErrors = computed(() => {
    const errors: Record<number, ValidationError[]> = {};

    previewRows.value.forEach((row, rowIndex) => {
        const obj: Record<string, any> = {};
        props.mappings.forEach((mapping, index) => {
            if (mapping.field) {
                obj[mapping.field] = row[index];
            }
        });

        const transformed = transformBetData(obj);
        const rowErrors = validateBet(transformed);

        if (rowErrors.length > 0) {
            errors[rowIndex] = rowErrors;
        }
    });

    return errors;
});

// Get cell-specific errors
const getCellErrors = (rowIndex: number, colIndex: number): string[] => {
    const mapping = props.mappings[colIndex];
    if (!mapping.field) return [];

    const errors = rowValidationErrors.value[rowIndex] || [];
    return errors.filter((error) => error.field === mapping.field).map((error) => error.message);
};

// Get cell styling based on validation
const getCellClass = (rowIndex: number, colIndex: number) => {
    const hasError = getCellErrors(rowIndex, colIndex).length > 0;
    const mapping = props.mappings[colIndex];

    return {
        'text-dark': !hasError && mapping.field,
        'text-muted': !mapping.field,
        'text-danger': hasError,
        'bg-danger-subtle': hasError,
    };
};

// Format cell values for display
const formatCellValue = (value: any, field?: string | null) => {
    if (!value) return '-';

    // Format numbers for odds and stake fields
    if (field === 'odds' || field === 'stake') {
        const num = parseFloat(value);
        if (!isNaN(num)) {
            return field === 'stake' ? `$${num.toFixed(2)}` : num.toFixed(2);
        }
    }

    // Format dates
    if (field === 'game_date') {
        const date = new Date(value);
        if (!isNaN(date.getTime())) {
            return date.toLocaleDateString();
        }
    }

    return value;
};

// Watch for mapping changes and reset error details
watch(
    () => props.mappings,
    () => {
        showErrorDetails.value = false;
    },
    { deep: true },
);
</script>
