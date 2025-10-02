<template>
    <div class="card border-primary">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="bi bi-calculator me-2"></i>
                Bet Calculator
            </h5>
            <button type="button" class="btn btn-sm btn-light" @click="isExpanded = !isExpanded">
                <i :class="isExpanded ? 'bi-chevron-up' : 'bi-chevron-down'"></i>
            </button>
        </div>
        <div v-show="isExpanded" class="card-body">
            <!-- Calculator Results -->
            <div v-if="calculationResult" class="mb-3">
                <div class="alert" :class="getAlertClass()" role="alert">
                    <h6 class="alert-heading">{{ calculationResult.scenario }}</h6>
                    <hr />
                    <div class="row">
                        <div class="col-md-4">
                            <strong>Stake:</strong>
                            <div class="h5">${{ formatMoney(calculationResult.stake) }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Payout:</strong>
                            <div class="h5 text-success">${{ formatMoney(calculationResult.payout) }}</div>
                        </div>
                        <div class="col-md-4">
                            <strong>Profit:</strong>
                            <div class="h5" :class="calculationResult.profit >= 0 ? 'text-success' : 'text-danger'">
                                ${{ formatMoney(calculationResult.profit) }}
                            </div>
                        </div>
                    </div>
                    <div v-if="calculationResult.breakdown" class="mt-3">
                        <h6>Breakdown:</h6>
                        <ul class="mb-0">
                            <li v-for="(line, index) in calculationResult.breakdown" :key="index" v-html="line"></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Formula Reference -->
            <div class="accordion" id="formulaAccordion">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button
                            class="accordion-button collapsed"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#formulaReference"
                            aria-expanded="false"
                        >
                            <i class="bi bi-book me-2"></i>
                            Formula Reference
                        </button>
                    </h2>
                    <div id="formulaReference" class="accordion-collapse collapse" data-bs-parent="#formulaAccordion">
                        <div class="accordion-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6>Win with Positive Odds (+150)</h6>
                                    <code>Profit = Stake × (Odds ÷ 100)</code><br />
                                    <code>Payout = Stake + Profit</code><br />
                                    <small class="text-muted">Ex: $100 at +150 → Profit = $150, Payout = $250</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Win with Negative Odds (-120)</h6>
                                    <code>Profit = Stake × (100 ÷ |Odds|)</code><br />
                                    <code>Payout = Stake + Profit</code><br />
                                    <small class="text-muted">Ex: $120 at -120 → Profit = $100, Payout = $220</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Bet Loses</h6>
                                    <code>Profit = -Stake</code><br />
                                    <code>Payout = 0</code><br />
                                    <small class="text-muted">Ex: $50 loses → Profit = -$50</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6>Push / Void</h6>
                                    <code>Profit = 0</code><br />
                                    <code>Payout = Stake</code><br />
                                    <small class="text-muted">Ex: $50 push → Payout = $50</small>
                                </div>
                                <div class="col-12 mb-3">
                                    <h6>Each Way (Wins Outright)</h6>
                                    <code>Stake split: Half on Win, Half on Place</code><br />
                                    <code>Win Profit = (Stake ÷ 2) × (Odds ÷ 100 or 100 ÷ |Odds|)</code><br />
                                    <code>Place Odds = 1 + (Decimal - 1) × Place Fraction</code><br />
                                    <code>Place Profit = (Stake ÷ 2) × (Place Odds - 1)</code><br />
                                    <code>Total Payout = Win Payout + Place Payout</code><br />
                                    <small class="text-muted">Ex: $20 at +2800, 1/5 place → Total Payout = $356, Profit = $336</small>
                                </div>
                                <div class="col-12">
                                    <h6>Each Way (Places Only)</h6>
                                    <code>Win half: Lost (-Stake ÷ 2)</code><br />
                                    <code>Place Profit = (Stake ÷ 2) × (Place Odds - 1)</code><br />
                                    <code>Total Profit = Place Profit - (Stake ÷ 2)</code><br />
                                    <small class="text-muted">Ex: $20 at +2800, 1/5 place → Total Payout = $66, Profit = $46</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface Props {
    wagerAmount: number;
    odds: number | string;
    status: string;
    isEachWay?: boolean;
    placeFraction?: number;
}

const props = defineProps<Props>();

const isExpanded = ref(true);

// Parse American odds from string like "+150" or "-120"
const parseOdds = (oddsValue: number | string): number => {
    if (typeof oddsValue === 'number') return oddsValue;
    const cleaned = String(oddsValue).replace(/[^0-9+-]/g, '');
    return parseFloat(cleaned) || 0;
};

const calculationResult = computed(() => {
    const stake = Number(props.wagerAmount) || 0;
    const odds = parseOdds(props.odds);
    const status = props.status?.toLowerCase() || 'pending';

    if (stake === 0) {
        return null;
    }

    // Each Way Bets
    if (props.isEachWay) {
        const halfStake = stake / 2;
        const placeFraction = props.placeFraction || 0.2; // Default 1/5

        if (status === 'won') {
            // Win outright - both parts win
            const winProfit = calculateWinProfit(halfStake, odds);
            const winPayout = halfStake + winProfit;

            const placeOdds = calculatePlaceOdds(odds, placeFraction);
            const placeProfit = halfStake * (placeOdds - 1);
            const placePayout = halfStake + placeProfit;

            const totalPayout = winPayout + placePayout;
            const totalProfit = totalPayout - stake;

            return {
                scenario: 'Each Way Bet - Wins Outright',
                stake,
                payout: totalPayout,
                profit: totalProfit,
                breakdown: [
                    `<strong>Win Half:</strong> $${halfStake.toFixed(2)} at ${formatOdds(odds)} = $${winPayout.toFixed(2)} payout ($${winProfit.toFixed(2)} profit)`,
                    `<strong>Place Half:</strong> $${halfStake.toFixed(2)} at decimal ${placeOdds.toFixed(2)} = $${placePayout.toFixed(2)} payout ($${placeProfit.toFixed(2)} profit)`,
                    `<strong>Total:</strong> $${totalPayout.toFixed(2)} payout, $${totalProfit.toFixed(2)} profit`,
                ],
            };
        } else if (status === 'placed') {
            // Places only - win half loses, place half wins
            const placeOdds = calculatePlaceOdds(odds, placeFraction);
            const placeProfit = halfStake * (placeOdds - 1);
            const placePayout = halfStake + placeProfit;
            const totalProfit = placePayout - stake; // Lost the win half

            return {
                scenario: 'Each Way Bet - Places Only',
                stake,
                payout: placePayout,
                profit: totalProfit,
                breakdown: [
                    `<strong>Win Half:</strong> $${halfStake.toFixed(2)} - <span class="text-danger">LOST</span>`,
                    `<strong>Place Half:</strong> $${halfStake.toFixed(2)} at decimal ${placeOdds.toFixed(2)} = $${placePayout.toFixed(2)} payout ($${placeProfit.toFixed(2)} profit)`,
                    `<strong>Total Profit:</strong> $${placePayout.toFixed(2)} - $${halfStake.toFixed(2)} (lost win half) = $${totalProfit.toFixed(2)}`,
                ],
            };
        } else if (status === 'loss') {
            return {
                scenario: 'Each Way Bet - Lost',
                stake,
                payout: 0,
                profit: -stake,
                breakdown: [`Both win and place halves lost`],
            };
        } else if (status === 'push' || status === 'void') {
            return {
                scenario: 'Each Way Bet - Push/Void',
                stake,
                payout: stake,
                profit: 0,
                breakdown: [`Stake returned in full`],
            };
        }
    }

    // Regular Bets
    if (status === 'won') {
        const profit = calculateWinProfit(stake, odds);
        const payout = stake + profit;

        return {
            scenario: odds > 0 ? `Win with Positive Odds (+${odds})` : `Win with Negative Odds (${odds})`,
            stake,
            payout,
            profit,
            breakdown: [
                odds > 0
                    ? `Profit = $${stake.toFixed(2)} × (${odds} ÷ 100) = $${profit.toFixed(2)}`
                    : `Profit = $${stake.toFixed(2)} × (100 ÷ ${Math.abs(odds)}) = $${profit.toFixed(2)}`,
                `Payout = $${stake.toFixed(2)} + $${profit.toFixed(2)} = $${payout.toFixed(2)}`,
            ],
        };
    } else if (status === 'loss') {
        return {
            scenario: 'Bet Lost',
            stake,
            payout: 0,
            profit: -stake,
            breakdown: [`All stake lost`],
        };
    } else if (status === 'push' || status === 'void') {
        return {
            scenario: 'Push / Void',
            stake,
            payout: stake,
            profit: 0,
            breakdown: [`Stake returned in full`],
        };
    } else {
        // Pending - show potential win
        const profit = calculateWinProfit(stake, odds);
        const payout = stake + profit;

        return {
            scenario: 'Potential Win',
            stake,
            payout,
            profit,
            breakdown: [
                odds > 0
                    ? `If wins: Profit = $${stake.toFixed(2)} × (${odds} ÷ 100) = $${profit.toFixed(2)}`
                    : `If wins: Profit = $${stake.toFixed(2)} × (100 ÷ ${Math.abs(odds)}) = $${profit.toFixed(2)}`,
            ],
        };
    }

    return null;
});

function calculateWinProfit(stake: number, odds: number): number {
    if (odds > 0) {
        // Positive American odds
        return stake * (odds / 100);
    } else {
        // Negative American odds
        return stake * (100 / Math.abs(odds));
    }
}

function calculatePlaceOdds(americanOdds: number, placeFraction: number): number {
    // Convert American odds to decimal
    let decimal: number;
    if (americanOdds > 0) {
        decimal = americanOdds / 100 + 1;
    } else {
        decimal = 100 / Math.abs(americanOdds) + 1;
    }

    // Calculate place odds
    return 1 + (decimal - 1) * placeFraction;
}

function formatOdds(odds: number): string {
    return odds > 0 ? `+${odds}` : String(odds);
}

function formatMoney(amount: number): string {
    return amount.toFixed(2);
}

function getAlertClass(): string {
    if (!calculationResult.value) return 'alert-secondary';

    const profit = calculationResult.value.profit;
    if (profit > 0) return 'alert-success';
    if (profit < 0) return 'alert-danger';
    return 'alert-warning';
}
</script>

<style scoped>
.card-header {
    cursor: default;
}

code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.875rem;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0c63e4;
}
</style>
