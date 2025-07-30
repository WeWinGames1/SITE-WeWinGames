<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';

interface Bet {
    id: number;
    sports: string;
    matches?: string;
    betting_date: string;
    wager_amount: number;
    winning_amount?: number;
    profit_amount?: number;
    wager_odds?: number;
    odds?: number;
    status: string;
    is_each_way?: boolean;
}

interface Props {
    bets: {
        data: Bet[];
        links?: any;
        meta?: any;
    };
    filters?: {
        date_from?: string;
        date_to?: string;
        sport?: string;
        status?: string;
        each_way?: string;
    };
    sports?: string[];
}

const props = defineProps<Props>();

// Form for filters
const filterForm = useForm({
    sport: props.filters?.sport || '',
    status: props.filters?.status || '',
    each_way: props.filters?.each_way || '',
    date_from: props.filters?.date_from || '',
    date_to: props.filters?.date_to || '',
});

// Track edited values
const editedBets = ref<Map<number, { winning_amount: number; profit_amount: number }>>(new Map());

// Calculate if there are unsaved changes
const hasUnsavedChanges = computed(() => editedBets.value.size > 0);

// Get display value for a bet
function getDisplayValue(bet: Bet, field: 'winning_amount' | 'profit_amount'): number {
    const edited = editedBets.value.get(bet.id);
    return edited ? edited[field] : (bet[field] || 0);
}

// Update a bet value
function updateBetValue(bet: Bet, field: 'winning_amount' | 'profit_amount', value: string) {
    const numValue = parseFloat(value) || 0;
    
    const current = editedBets.value.get(bet.id) || {
        winning_amount: bet.winning_amount || 0,
        profit_amount: bet.profit_amount || 0,
    };
    
    current[field] = numValue;
    
    // Auto-calculate profit when winning amount changes
    if (field === 'winning_amount') {
        current.profit_amount = numValue - bet.wager_amount;
    }
    
    editedBets.value.set(bet.id, current);
}

// Check if a value has been edited
function isEdited(betId: number): boolean {
    return editedBets.value.has(betId);
}

// Save all changes
async function saveChanges() {
    if (!hasUnsavedChanges.value) return;
    
    const updates = Array.from(editedBets.value.entries()).map(([id, values]) => ({
        id,
        winning_amount: values.winning_amount,
        profit_amount: values.profit_amount,
    }));
    
    try {
        const response = await axios.post(route('admin.bets.mass-edit.update'), {
            updates,
        });
        
        if (response.data.success || response.status === 200) {
            // Clear edited values and refresh
            editedBets.value.clear();
            filterForm.get(route('admin.bets.mass-edit.index'));
        }
    } catch (error) {
        console.error('Failed to save changes:', error);
        alert('Failed to save changes. Please try again.');
    }
}

// Apply filters
function applyFilters() {
    filterForm.get(route('admin.bets.mass-edit.index'));
}

// Clear filters
function clearFilters() {
    filterForm.sport = '';
    filterForm.status = '';
    filterForm.each_way = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.get(route('admin.bets.mass-edit.index'));
}

// Format date for display
function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <AdminLayout>
        <Head title="Mass Edit Bets" />
        
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item">
                                        <Link :href="route('admin.dashboard')">Dashboard</Link>
                                    </li>
                                    <li class="breadcrumb-item">
                                        <Link :href="route('admin.bets.index')">Bets</Link>
                                    </li>
                                    <li class="breadcrumb-item active">Mass Edit</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 text-dark">Mass Edit Bets</h1>
                            <p class="text-muted mb-0">Edit winning amounts and profits for multiple bets</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Filters</h5>
                    <form @submit.prevent="applyFilters">
                        <div class="row g-3">
                            <div class="col-md-2">
                                <label for="sport" class="form-label">Sport</label>
                                <select
                                    id="sport"
                                    v-model="filterForm.sport"
                                    class="form-select"
                                >
                                    <option value="">All Sports</option>
                                    <option v-for="sport in sports" :key="sport" :value="sport">
                                        {{ sport }}
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="status" class="form-label">Status</label>
                                <select
                                    id="status"
                                    v-model="filterForm.status"
                                    class="form-select"
                                >
                                    <option value="">All Status</option>
                                    <option value="won">Won</option>
                                    <option value="placed">Placed</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Each Way</label>
                                <div class="form-check mt-2">
                                    <input
                                        id="each_way"
                                        v-model="filterForm.each_way"
                                        type="checkbox"
                                        class="form-check-input"
                                        value="1"
                                        :true-value="'1'"
                                        :false-value="''">
                                    <label for="each_way" class="form-check-label">
                                        Each Way Bets Only
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">Date From</label>
                                <input
                                    id="date_from"
                                    v-model="filterForm.date_from"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">Date To</label>
                                <input
                                    id="date_to"
                                    v-model="filterForm.date_to"
                                    type="date"
                                    class="form-control"
                                />
                            </div>
                            <div class="col-md-2 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-funnel me-1"></i>Apply Filters
                                </button>
                                <button type="button" class="btn btn-secondary" @click="clearFilters">
                                    Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bets Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Bets ({{ bets.meta?.total || bets.data.length }} total)</h5>
                    <button
                        v-if="hasUnsavedChanges"
                        class="btn btn-success"
                        @click="saveChanges"
                    >
                        <i class="bi bi-check-circle me-1"></i>
                        Save {{ editedBets.size }} Changes
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Sport</th>
                                    <th>Player/Match</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                    <th>Odds</th>
                                    <th>Stake</th>
                                    <th>Winning Amount</th>
                                    <th>Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="bet in bets.data" :key="bet.id" :class="{ 'table-warning': isEdited(bet.id) }">
                                    <td>{{ formatDate(bet.betting_date) }}</td>
                                    <td>{{ bet.sports }}</td>
                                    <td>{{ bet.matches || 'Unknown' }}</td>
                                    <td>
                                        <span class="badge" :class="{
                                            'bg-success': bet.status === 'won',
                                            'bg-info': bet.status === 'placed',
                                            'bg-danger': bet.status === 'lost'
                                        }">
                                            {{ bet.status }}
                                        </span>
                                    </td>
                                    <td>
                                        <span v-if="bet.is_each_way" class="badge bg-primary">EW</span>
                                        <span v-else class="badge bg-secondary">Straight</span>
                                    </td>
                                    <td>{{ (bet.odds || bet.wager_odds || 0) > 0 ? '+' : '' }}{{ bet.odds || bet.wager_odds || 0 }}</td>
                                    <td>${{ bet.wager_amount.toFixed(2) }}</td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <span class="input-group-text">$</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                :value="getDisplayValue(bet, 'winning_amount').toFixed(2)"
                                                @input="updateBetValue(bet, 'winning_amount', ($event.target as HTMLInputElement).value)"
                                            />
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 120px;">
                                            <span class="input-group-text">$</span>
                                            <input
                                                type="number"
                                                step="0.01"
                                                class="form-control"
                                                :value="getDisplayValue(bet, 'profit_amount').toFixed(2)"
                                                @input="updateBetValue(bet, 'profit_amount', ($event.target as HTMLInputElement).value)"
                                            />
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div v-if="bets.meta && bets.meta.last_page > 1" class="card-footer">
                    <nav>
                        <ul class="pagination mb-0">
                            <li
                                v-for="link in bets.links"
                                :key="link.label"
                                class="page-item"
                                :class="{ active: link.active, disabled: !link.url }"
                            >
                                <Link
                                    v-if="link.url"
                                    :href="link.url"
                                    class="page-link"
                                    preserve-scroll
                                    v-html="link.label"
                                />
                                <span v-else class="page-link" v-html="link.label" />
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.table-warning {
    background-color: rgba(255, 193, 7, 0.1) !important;
}
</style>