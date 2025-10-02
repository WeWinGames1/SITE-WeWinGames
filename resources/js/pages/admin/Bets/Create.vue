<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import BetCalculator from '@/components/BetCalculator.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useForm, usePeriodicCsrfRefresh } from '@/composables/useInertiaForm';
import { useSessionKeepAlive } from '@/composables/useSessionKeepAlive';
import axios from 'axios';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

interface User {
    id: number;
    name: string;
    email: string;
}

interface Sport {
    id: number;
    name: string;
}

interface League {
    id: number;
    name: string;
    sport_id: number;
}

interface Team {
    id: number;
    name: string;
    sport_id: number;
    league_id?: number;
    logo_url?: string;
}

interface Props {
    sports: Sport[];
    leagues: League[];
    betTypes: Record<string, string>;
}

const props = defineProps<Props>();

// Modal state
const showLogoModal = ref(false);
const logoModalTeam = ref<'one' | 'two'>('one');
const selectedTeamForLogo = ref<Team | null>(null);
const logoFile = ref<File | null>(null);

// Teams for logo display
const teamOne = ref<Team | null>(null);
const teamTwo = ref<Team | null>(null);

const form = useForm({
    wager_type: '',
    sports: '',
    sport_id: null as number | null,
    league: '',
    league_id: null as number | null,
    month: '',
    matches: '',
    markets: '',
    team_one: '',
    team_one_id: null as number | null,
    team_one_is_new: false,
    team_two: '',
    team_two_id: null as number | null,
    team_two_is_new: false,
    parlay_teams: [],
    tips: '',
    premium_notes: '',
    premium_notes_enabled: false,
    premium_notes_heading: '',
    betting_date: new Date().toISOString().slice(0, 16),
    game_date: new Date().toISOString().slice(0, 16),
    wager_odds: '',
    membership: 'bronze',
    level: '',
    code: '',
    roi: 0,
    wager_amount: 0,
    winning_amount: 0,
    profit_amount: 0,
    status: 'pending',
    referrer: '',
    place_fraction: 0,
    golf_place: false,
});

// Initialize Select2 after component is mounted and start CSRF refresh
const { start: startCsrfRefresh, stop: stopCsrfRefresh } = usePeriodicCsrfRefresh(10); // Refresh every 10 minutes

// Keep session alive while user is creating bet
useSessionKeepAlive(15); // Ping every 15 minutes

onMounted(() => {
    nextTick(() => {
        initializeSelect2();
    });
    // Start periodic CSRF refresh for long forms
    startCsrfRefresh();
});

onUnmounted(() => {
    // Clean up CSRF refresh
    stopCsrfRefresh();
});

// Computed properties
const filteredLeagues = computed(() => {
    if (!form.sport_id) return [];
    return props.leagues.filter((league) => league.sport_id === form.sport_id);
});

const isParlay = computed(() => form.wager_type === 'parlay');

const canSelectTeams = computed(() => {
    return form.sport_id !== null && form.league_id !== null;
});

const isIndividualSport = computed(() => {
    if (!form.sports) return false;
    const individualSports = ['golf', 'tennis', 'boxing', 'mma', 'ufc', 'racing', 'nascar'];
    return individualSports.includes(form.sports.toLowerCase());
});

const teamOneLogo = computed(() => {
    if (teamOne.value?.logo_url) {
        return `/storage/${teamOne.value.logo_url}`;
    }
    return null;
});

const teamTwoLogo = computed(() => {
    if (teamTwo.value?.logo_url) {
        return `/storage/${teamTwo.value.logo_url}`;
    }
    return null;
});

// Watch for sport changes to update the sport name and reset league
watch(
    () => form.sport_id,
    (newSportId) => {
        if (newSportId) {
            const sport = props.sports.find((s) => s.id === newSportId);
            if (sport) {
                form.sports = sport.name;
            }
        } else {
            // Clear sport name if no sport selected
            form.sports = '';
        }
        // Reset league if it doesn't belong to the new sport
        if (form.league_id) {
            const league = props.leagues.find((l) => l.id === form.league_id);
            if (!league || league.sport_id !== newSportId) {
                form.league_id = null;
                form.league = '';
            }
        }

        // Reinitialize Select2 dropdowns with new sport filter
        nextTick(() => {
            initializeSelect2();
        });
    },
);

// Watch for league changes to update the league name
watch(
    () => form.league_id,
    (newLeagueId) => {
        if (newLeagueId) {
            const league = props.leagues.find((l) => l.id === newLeagueId);
            if (league) {
                form.league = league.name;
            }
        }

        // Reinitialize Select2 dropdowns with new league filter
        nextTick(() => {
            initializeSelect2();
        });
    },
);

// Watch for bet type changes to reinitialize Select2 and reset golf_place
watch(
    () => form.wager_type,
    () => {
        // Reset golf_place if bet type is not each_way
        if (form.wager_type !== 'each_way') {
            form.golf_place = false;
        }
        nextTick(() => {
            initializeSelect2();
        });
    },
);

// Calculate potential win when odds or stake change
watch([() => form.wager_odds, () => form.wager_amount], () => {
    calculatePotentialWin();
});

// Calculate profit when status changes
watch(
    () => form.status,
    () => {
        calculateProfit();
    },
);

function calculatePotentialWin() {
    const odds = typeof form.wager_odds === 'string' ? parseFloat(form.wager_odds) : form.wager_odds;
    const stake = Number(form.wager_amount);

    if (odds && stake) {
        if (odds > 0) {
            // Positive American odds
            form.winning_amount = stake + (stake * odds) / 100;
        } else {
            // Negative American odds
            form.winning_amount = stake + (stake * 100) / Math.abs(odds);
        }
    } else {
        form.winning_amount = 0;
    }
}

function calculateProfit() {
    const stake = Number(form.wager_amount);
    const winning = Number(form.winning_amount);

    if (form.status === 'won') {
        form.profit_amount = winning - stake;
    } else if (form.status === 'loss') {
        form.profit_amount = -stake;
    } else if (form.status === 'push' || form.status === 'void') {
        form.profit_amount = 0;
    }
}

function initializeSelect2() {
    // Initialize Select2 for team dropdowns
    if (!isParlay.value) {
        initTeamSelect('team_one_select', 'one');
        initTeamSelect('team_two_select', 'two');
    }
}

function initTeamSelect(elementId: string, teamType: 'one' | 'two') {
    const element = document.getElementById(elementId) as any;
    if (!element || !window.$) return;

    const $element = window.$(element);

    // Destroy existing instance if it exists
    if ($element.hasClass('select2-hidden-accessible')) {
        $element.select2('destroy');
    }

    $element.select2({
        ajax: {
            url: route('admin.api.teams.search'),
            dataType: 'json',
            delay: 250,
            data: function (params: any) {
                return {
                    q: params.term,
                    sport_id: form.sport_id,
                    league_id: form.league_id,
                };
            },
            processResults: function (data: any) {
                // Add option to create new team if no results or search term doesn't match
                const results = data.results || [];
                const searchTerm = data.q || '';

                // Check if search term exactly matches any result
                const exactMatch = results.some((team: any) => team.name.toLowerCase() === searchTerm.toLowerCase());

                // Add "create new" option if search term exists and no exact match
                if (searchTerm && searchTerm.length >= 2 && !exactMatch) {
                    results.unshift({
                        id: `new:${searchTerm}`,
                        name: searchTerm,
                        text: `Add "${searchTerm}" as new team/player`,
                        isNew: true,
                    });
                }

                return {
                    results: results,
                };
            },
            cache: true,
        },
        placeholder: `Search or enter ${teamType === 'one' ? 'team one/player' : 'team two'} name...`,
        minimumInputLength: 2,
        templateResult: formatTeam,
        templateSelection: formatTeamSelection,
        allowClear: true,
        tags: true,
        createTag: function (params: any) {
            const term = params.term?.trim();
            if (!term || term.length < 2) {
                return null;
            }

            return {
                id: `new:${term}`,
                name: term,
                text: term,
                isNew: true,
            };
        },
        dropdownParent: window.$('.modal').length ? window.$('.modal') : window.$('body'),
    });

    // Handle selection
    $element.on('select2:select', function (e: any) {
        const data = e.params.data;

        // Check if this is a new team/player
        const isNewTeam = data.isNew || (typeof data.id === 'string' && data.id.startsWith('new:'));

        if (teamType === 'one') {
            if (isNewTeam) {
                // For new teams, store the name and set ID to null
                form.team_one_id = null;
                form.team_one = data.name || data.text;
                form.team_one_is_new = true;
                teamOne.value = {
                    id: null,
                    name: data.name || data.text,
                    sport_id: form.sport_id!,
                    league_id: form.league_id,
                    logo_url: null,
                    isNew: true,
                };
            } else {
                form.team_one_id = data.id;
                form.team_one = data.name;
                form.team_one_is_new = false;
                teamOne.value = {
                    id: data.id,
                    name: data.name,
                    sport_id: data.sport_id || form.sport_id!,
                    league_id: data.league_id || form.league_id,
                    logo_url: data.logo_url,
                };
            }
        } else {
            if (isNewTeam) {
                // For new teams, store the name and set ID to null
                form.team_two_id = null;
                form.team_two = data.name || data.text;
                form.team_two_is_new = true;
                teamTwo.value = {
                    id: null,
                    name: data.name || data.text,
                    sport_id: form.sport_id!,
                    league_id: form.league_id,
                    logo_url: null,
                    isNew: true,
                };
            } else {
                form.team_two_id = data.id;
                form.team_two = data.name;
                form.team_two_is_new = false;
                teamTwo.value = {
                    id: data.id,
                    name: data.name,
                    sport_id: data.sport_id || form.sport_id!,
                    league_id: data.league_id || form.league_id,
                    logo_url: data.logo_url,
                };
            }
        }
    });

    // Handle clearing
    $element.on('select2:clear', function () {
        if (teamType === 'one') {
            form.team_one_id = null;
            form.team_one = '';
            form.team_one_is_new = false;
            teamOne.value = null;
        } else {
            form.team_two_id = null;
            form.team_two = '';
            form.team_two_is_new = false;
            teamTwo.value = null;
        }
    });
}

function formatTeam(team: any) {
    if (!team.id) return team.text;

    // Check if this is a new team option
    const isNewTeam = team.isNew || (typeof team.id === 'string' && team.id.startsWith('new:'));

    const $container = window.$('<div class="d-flex align-items-center">');

    if (isNewTeam) {
        // Show special formatting for new team option
        $container.append(
            '<div class="me-2" style="width: 30px; height: 30px; background: #28a745; border-radius: 4px; display: flex; align-items: center; justify-content: center;"><i class="bi bi-plus text-white"></i></div>',
        );

        const $text = window.$('<div>');
        if (team.text && team.text.includes('Add "')) {
            // This is the "Add as new" option
            $text.append(`<div>${team.text}</div>`);
            $text.append('<small class="text-dark fw-bold">Will be created as new team/player</small>');
        } else {
            // This is a typed tag
            $text.append(`<div>Add "${team.name || team.text}" as new team/player</div>`);
            $text.append('<small class="text-dark fw-bold">Will be created as new team/player</small>');
        }
        $container.append($text);
    } else {
        // Existing team formatting
        if (team.logo_url) {
            $container.append(`<img src="${team.logo_url}" class="me-2" style="width: 30px; height: 30px; object-fit: contain;" />`);
        } else {
            $container.append('<div class="me-2" style="width: 30px; height: 30px; background: #e9ecef; border-radius: 4px;"></div>');
        }

        const $text = window.$('<div>');
        $text.append(`<div>${team.name}</div>`);
        if (team.sport || team.league) {
            $text.append(`<small class="text-muted">${team.sport || ''} ${team.league ? '• ' + team.league : ''}</small>`);
        }

        $container.append($text);
    }

    return $container;
}

function formatTeamSelection(team: any) {
    return team.name || team.text;
}

function openLogoModal(teamType: 'one' | 'two') {
    logoModalTeam.value = teamType;

    // Get the selected team
    if (teamType === 'one' && form.team_one_id && teamOne.value) {
        selectedTeamForLogo.value = teamOne.value;
    } else if (teamType === 'two' && form.team_two_id && teamTwo.value) {
        selectedTeamForLogo.value = teamTwo.value;
    } else {
        return; // No team selected
    }

    showLogoModal.value = true;
}

function handleLogoFileChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        logoFile.value = target.files[0];
    }
}

async function uploadLogo() {
    if (!logoFile.value || !selectedTeamForLogo.value) return;

    const formData = new FormData();
    formData.append('logo', logoFile.value);

    try {
        const response = await axios.post(route('admin.api.teams.update-logo', selectedTeamForLogo.value.id), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        if (response.data.success) {
            // Update the team logo in the local state
            const logoUrl = response.data.logo_url.replace('/storage/', '');
            if (logoModalTeam.value === 'one' && teamOne.value) {
                teamOne.value.logo_url = logoUrl;
            } else if (logoModalTeam.value === 'two' && teamTwo.value) {
                teamTwo.value.logo_url = logoUrl;
            }

            // Close modal and reset
            showLogoModal.value = false;
            logoFile.value = null;
            selectedTeamForLogo.value = null;
        }
    } catch (error) {
        console.error('Error uploading logo:', error);
        alert('Failed to upload logo. Please try again.');
    }
}

function addParlayTeam() {
    form.parlay_teams.push({
        id: null,
        name: '',
        sport_id: form.sport_id!,
        league_id: form.league_id,
        logo_url: null,
    });
}

function removeParlayTeam(index: number) {
    form.parlay_teams.splice(index, 1);
}

function validateForm(): boolean {
    // Clear previous errors
    form.clearErrors();

    let isValid = true;
    const errors: Record<string, string> = {};

    // Required fields validation
    if (!form.wager_type) {
        errors.wager_type = 'The bet type field is required.';
        isValid = false;
    }

    if (!form.sports || !form.sport_id) {
        errors.sports = 'The sport field is required.';
        isValid = false;
    }

    if (!form.betting_date) {
        errors.betting_date = 'The betting date field is required.';
        isValid = false;
    }

    if (!form.game_date) {
        errors.game_date = 'The game date field is required.';
        isValid = false;
    }

    if (!form.wager_odds) {
        errors.wager_odds = 'The odds field is required.';
        isValid = false;
    }

    if (form.wager_amount === null || form.wager_amount === undefined || form.wager_amount < 0) {
        errors.wager_amount = 'The wager amount must be at least 0.';
        isValid = false;
    }

    if (!form.status) {
        errors.status = 'The status field is required.';
        isValid = false;
    }

    if (!form.membership) {
        errors.membership = 'The membership field is required.';
        isValid = false;
    }

    // Numeric validation
    if (form.wager_odds && isNaN(parseFloat(form.wager_odds as string))) {
        errors.wager_odds = 'The odds must be a number.';
        isValid = false;
    }

    if (form.place_fraction !== null && form.place_fraction !== undefined) {
        if (form.place_fraction < 0 || form.place_fraction > 1) {
            errors.place_fraction = 'The place fraction must be between 0 and 1.';
            isValid = false;
        }
    }

    // Set errors if any
    if (!isValid) {
        form.setError(errors);
    }

    return isValid;
}

function submit() {
    if (validateForm()) {
        // Set is_each_way based on wager_type
        const data = {
            ...form.data(),
            is_each_way: form.wager_type === 'each_way',
        };

        form.transform(() => data).post(route('admin.bets.store'));
    }
}

// Helper function to calculate place odds
function calculatePlaceOdds(americanOdds: number, placeFraction: number): number {
    if (!americanOdds || !placeFraction) return 0;

    if (americanOdds > 0) {
        // Positive odds: +2200 with 1/5 = +440
        return americanOdds * placeFraction;
    } else {
        // Negative odds: Convert to decimal, apply fraction, convert back
        const decimal = americanOdds < 0 ? 100 / Math.abs(americanOdds) + 1 : americanOdds / 100 + 1;
        const placeDecimal = 1 + (decimal - 1) * placeFraction;

        // Convert back to American
        if (placeDecimal >= 2) {
            return (placeDecimal - 1) * 100;
        } else {
            return -100 / (placeDecimal - 1);
        }
    }
}

// Helper function to format odds display
function formatOdds(odds: number): string {
    if (!odds) return '';
    return odds > 0 ? `+${Math.round(odds)}` : Math.round(odds).toString();
}

// Helper function to calculate Each Way payouts
function calculateEachWayPayout(type: 'win' | 'place'): number {
    const stake = (form.wager_amount || 10) / 2; // Each Way splits the stake
    const winOdds = parseFloat(form.wager_odds) || 0;
    const placeOdds = calculatePlaceOdds(winOdds, form.place_fraction);

    if (type === 'win') {
        // Win = both win and place parts pay out
        const winPayout = calculateAmericanOddsPayout(stake, winOdds);
        const placePayout = calculateAmericanOddsPayout(stake, placeOdds);
        return winPayout + placePayout;
    } else {
        // Place = only place part pays out
        return calculateAmericanOddsPayout(stake, placeOdds);
    }
}

// Helper function to calculate payout from American odds
function calculateAmericanOddsPayout(stake: number, odds: number): number {
    if (!stake || !odds) return stake;

    if (odds > 0) {
        // Positive odds: profit = stake * (odds/100)
        return stake + (stake * odds) / 100;
    } else {
        // Negative odds: profit = stake * (100/|odds|)
        return stake + (stake * 100) / Math.abs(odds);
    }
}

// Helper function to get ordinal suffix
function getOrdinal(n: number): string {
    const s = ['th', 'st', 'nd', 'rd'];
    const v = n % 100;
    return n + (s[(v - 20) % 10] || s[v] || s[0]);
}

// Declare global for TypeScript
declare global {
    interface Window {
        $: any;
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Create Bet" />

        <div class="container-fluid">
            <!-- Header -->
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
                                    <li class="breadcrumb-item active">Create</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 text-dark">Create New Bet</h1>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Validation Errors Alert -->
                <div v-if="form.hasErrors" class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                    <strong>Validation Error!</strong> Please fix the following errors:
                    <ul class="mb-0 mt-2">
                        <li v-for="(error, field) in form.errors" :key="field">{{ error }}</li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>

                <!-- Wager Type Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Wager Type</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="wager_type" class="form-label">Select Wager Type <span class="text-danger">*</span></label>
                                <select
                                    id="wager_type"
                                    v-model="form.wager_type"
                                    class="form-select form-select-lg"
                                    :class="{ 'is-invalid': form.errors.wager_type }"
                                    required
                                >
                                    <option value="">Select bet type...</option>
                                    <option v-for="(label, value) in betTypes" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                                <div v-if="form.errors.wager_type" class="invalid-feedback">
                                    {{ form.errors.wager_type }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Basic Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Sport -->
                            <div class="col-md-4">
                                <label for="sport_id" class="form-label">Sport <span class="text-danger">*</span></label>
                                <select
                                    id="sport_id"
                                    v-model="form.sport_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.sport_id || form.errors.sports }"
                                    required
                                >
                                    <option :value="null">Select sport...</option>
                                    <option v-for="sport in sports" :key="sport.id" :value="sport.id">
                                        {{ sport.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.sport_id || form.errors.sports" class="invalid-feedback">
                                    {{ form.errors.sport_id || form.errors.sports }}
                                </div>
                            </div>

                            <!-- League -->
                            <div class="col-md-4">
                                <label for="league_id" class="form-label">League</label>
                                <select
                                    id="league_id"
                                    v-model="form.league_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.league_id || form.errors.league }"
                                    :disabled="!form.sport_id"
                                >
                                    <option :value="null">Select league...</option>
                                    <option v-for="league in filteredLeagues" :key="league.id" :value="league.id">
                                        {{ league.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.league_id || form.errors.league" class="invalid-feedback">
                                    {{ form.errors.league_id || form.errors.league }}
                                </div>
                            </div>

                            <!-- Month -->
                            <div class="col-md-4">
                                <label for="month" class="form-label">Month</label>
                                <input
                                    id="month"
                                    v-model="form.month"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.month }"
                                    placeholder="e.g., January, February"
                                />
                                <div v-if="form.errors.month" class="invalid-feedback">
                                    {{ form.errors.month }}
                                </div>
                            </div>

                            <!-- Teams for non-parlay bets -->
                            <template v-if="!isParlay">
                                <!-- Sport and League Selection Notice -->
                                <div v-if="!canSelectTeams" class="col-12">
                                    <div class="alert alert-info mb-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Please select a <strong>Sport</strong> and <strong>League</strong> first to enable team/player selection.
                                    </div>
                                </div>

                                <!-- Team One -->
                                <div v-if="canSelectTeams" class="col-md-6">
                                    <label for="team_one_select" class="form-label">Team One / Player <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select id="team_one_select" class="form-control" style="width: calc(100% - 50px)"></select>
                                        <button
                                            v-if="teamOneLogo"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('one')"
                                            title="View/Update Logo"
                                        >
                                            <img :src="teamOneLogo" alt="Logo" style="width: 20px; height: 20px; object-fit: contain" />
                                        </button>
                                        <button
                                            v-else-if="form.team_one_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('one')"
                                        >
                                            <i class="bi bi-image"></i>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.team_one || form.errors.team_one_id" class="invalid-feedback d-block">
                                        {{ form.errors.team_one || form.errors.team_one_id }}
                                    </div>
                                </div>

                                <!-- Team Two -->
                                <div v-if="canSelectTeams" class="col-md-6">
                                    <label for="team_two_select" class="form-label"
                                        >Team Two <span v-if="!isIndividualSport" class="text-danger">*</span></label
                                    >
                                    <div class="input-group">
                                        <select id="team_two_select" class="form-control" style="width: calc(100% - 50px)"></select>
                                        <button
                                            v-if="teamTwoLogo"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('two')"
                                            title="View/Update Logo"
                                        >
                                            <img :src="teamTwoLogo" alt="Logo" style="width: 20px; height: 20px; object-fit: contain" />
                                        </button>
                                        <button
                                            v-else-if="form.team_two_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('two')"
                                        >
                                            <i class="bi bi-image"></i>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.team_two || form.errors.team_two_id" class="invalid-feedback d-block">
                                        {{ form.errors.team_two || form.errors.team_two_id }}
                                    </div>
                                </div>
                            </template>

                            <!-- Parlay Teams -->
                            <template v-else>
                                <div class="col-12">
                                    <label class="form-label">Parlay Teams</label>
                                    <div v-for="(team, index) in form.parlay_teams" :key="index" class="mb-2">
                                        <div class="input-group">
                                            <input v-model="team.name" type="text" class="form-control" :placeholder="`Team ${index + 1}`" />
                                            <button type="button" class="btn btn-outline-danger" @click="removeParlayTeam(index)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" @click="addParlayTeam">
                                        <i class="bi bi-plus-circle me-1"></i> Add Team
                                    </button>
                                </div>
                            </template>

                            <!-- Betting Date -->
                            <div class="col-md-6">
                                <label for="betting_date" class="form-label">Betting Date <span class="text-danger">*</span></label>
                                <input
                                    id="betting_date"
                                    v-model="form.betting_date"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.betting_date }"
                                    required
                                />
                                <div v-if="form.errors.betting_date" class="invalid-feedback">
                                    {{ form.errors.betting_date }}
                                </div>
                            </div>

                            <!-- Game Date -->
                            <div class="col-md-6">
                                <label for="game_date" class="form-label">Game Date <span class="text-danger">*</span></label>
                                <input
                                    id="game_date"
                                    v-model="form.game_date"
                                    type="datetime-local"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.game_date }"
                                    required
                                />
                                <div v-if="form.errors.game_date" class="invalid-feedback">
                                    {{ form.errors.game_date }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bet Details -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Bet Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Markets -->
                            <div class="col-md-6">
                                <label for="markets" class="form-label">Markets *</label>
                                <input
                                    id="markets"
                                    v-model="form.markets"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.markets }"
                                    placeholder="e.g., Spread, Over/Under, Moneyline"
                                />
                                <div v-if="form.errors.markets" class="invalid-feedback">
                                    {{ form.errors.markets }}
                                </div>
                            </div>

                            <!-- Tips -->
                            <div class="col-md-6">
                                <label for="tips" class="form-label">Tips / Selection</label>
                                <input
                                    id="tips"
                                    v-model="form.tips"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.tips }"
                                    placeholder="e.g., Lakers -5.5, Over 220.5"
                                />
                                <div v-if="form.errors.tips" class="invalid-feedback">
                                    {{ form.errors.tips }}
                                </div>
                            </div>

                            <!-- Odds -->
                            <div class="col-md-4">
                                <label for="wager_odds" class="form-label">Odds <span class="text-danger">*</span></label>
                                <input
                                    id="wager_odds"
                                    v-model="form.wager_odds"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.wager_odds }"
                                    placeholder="e.g., -110, +150"
                                    required
                                />
                                <div v-if="form.errors.wager_odds" class="invalid-feedback">
                                    {{ form.errors.wager_odds }}
                                </div>
                            </div>

                            <!-- Wager Amount -->
                            <div class="col-md-4">
                                <label for="wager_amount" class="form-label">Wager Amount <span class="text-danger">*</span></label>
                                <input
                                    id="wager_amount"
                                    v-model.number="form.wager_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.wager_amount }"
                                    required
                                />
                                <div v-if="form.errors.wager_amount" class="invalid-feedback">
                                    {{ form.errors.wager_amount }}
                                </div>
                            </div>

                            <!-- Winning Amount -->
                            <div class="col-md-4">
                                <label for="winning_amount" class="form-label">Winning Amount</label>
                                <input
                                    id="winning_amount"
                                    v-model.number="form.winning_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.winning_amount }"
                                    readonly
                                />
                                <div v-if="form.errors.winning_amount" class="invalid-feedback">
                                    {{ form.errors.winning_amount }}
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="status" v-model="form.status" class="form-select" :class="{ 'is-invalid': form.errors.status }" required>
                                    <option value="">Select status...</option>
                                    <option value="pending">Pending</option>
                                    <option value="won">Won</option>
                                    <option v-if="form.wager_type === 'each_way'" value="placed">Placed</option>
                                    <option value="loss">Loss</option>
                                    <option value="push">Push</option>
                                    <option value="void">Void</option>
                                </select>
                                <div v-if="form.errors.status" class="invalid-feedback">
                                    {{ form.errors.status }}
                                </div>
                            </div>

                            <!-- Profit Amount -->
                            <div class="col-md-4">
                                <label for="profit_amount" class="form-label">Profit Amount</label>
                                <input
                                    id="profit_amount"
                                    v-model.number="form.profit_amount"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.profit_amount }"
                                    readonly
                                />
                                <div v-if="form.errors.profit_amount" class="invalid-feedback">
                                    {{ form.errors.profit_amount }}
                                </div>
                            </div>

                            <!-- ROI -->
                            <div class="col-md-4">
                                <label for="roi" class="form-label">ROI (%)</label>
                                <input
                                    id="roi"
                                    v-model.number="form.roi"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.roi }"
                                />
                                <div v-if="form.errors.roi" class="invalid-feedback">
                                    {{ form.errors.roi }}
                                </div>
                            </div>

                            <!-- Bet Calculator -->
                            <div class="col-12 my-3">
                                <BetCalculator
                                    :wager-amount="form.wager_amount"
                                    :odds="form.wager_odds"
                                    :status="form.status"
                                    :is-each-way="form.wager_type === 'each_way'"
                                    :place-fraction="form.place_fraction"
                                />
                            </div>

                            <!-- Place Fraction -->
                            <div v-if="form.wager_type === 'each_way'" class="col-md-6">
                                <label for="place_fraction" class="form-label">Place Fraction</label>
                                <select
                                    id="place_fraction"
                                    v-model="form.place_fraction"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.place_fraction }"
                                >
                                    <option value="0.2">1/5</option>
                                    <option value="0.25">1/4</option>
                                    <option value="0.333">1/3</option>
                                    <option value="0.5">1/2</option>
                                </select>
                                <div v-if="form.errors.place_fraction" class="invalid-feedback">
                                    {{ form.errors.place_fraction }}
                                </div>
                            </div>

                            <!-- Place Odds Preview -->
                            <div v-if="form.wager_type === 'each_way'" class="col-md-6">
                                <label class="form-label">Place Odds Preview</label>
                                <div class="border rounded p-2" style="background-color: #f8f9fa">
                                    <div class="small text-muted mb-2">
                                        Place Odds:
                                        <strong>{{ formatOdds(calculatePlaceOdds(parseFloat(form.wager_odds), form.place_fraction)) }}</strong>
                                    </div>
                                    <table class="table table-sm table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center" style="width: 60px">Position</th>
                                                <th class="text-end">Payout (per ${{ form.wager_amount / 2 || 5 }})</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="table-success">
                                                <td class="text-center fw-bold">1st</td>
                                                <td class="text-end">
                                                    ${{ calculateEachWayPayout('win').toFixed(2) }}
                                                    <small class="text-muted d-block">Win + Place</small>
                                                </td>
                                            </tr>
                                            <tr
                                                v-for="position in [2, 3, 4, 5, 6, 7, 8, 9, 10]"
                                                :key="position"
                                                :class="{ 'table-info': position <= 5, 'text-muted': position > 5 }"
                                            >
                                                <td class="text-center">{{ getOrdinal(position) }}</td>
                                                <td class="text-end">
                                                    <span v-if="position <= 5">
                                                        ${{ calculateEachWayPayout('place').toFixed(2) }}
                                                        <small class="text-muted d-block">Place only</small>
                                                    </span>
                                                    <span v-else>
                                                        $0.00
                                                        <small class="text-muted d-block">No payout</small>
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div class="text-muted small mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Assuming top 5 places pay (typical for Golf)
                                    </div>
                                </div>
                            </div>

                            <!-- Golf Only: Place -->
                            <div v-if="form.wager_type === 'each_way'" class="col-12 mt-3">
                                <div class="form-check">
                                    <input
                                        id="golf_place"
                                        v-model="form.golf_place"
                                        type="checkbox"
                                        class="form-check-input"
                                        :class="{ 'is-invalid': form.errors.golf_place }"
                                    />
                                    <label class="form-check-label" for="golf_place">
                                        Golf Only: Place
                                        <small class="text-muted d-block">Check if the bet won by placing (not outright win)</small>
                                    </label>
                                    <div v-if="form.errors.golf_place" class="invalid-feedback">
                                        {{ form.errors.golf_place }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="h5 mb-0">Additional Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Membership -->
                            <div class="col-md-4">
                                <label for="membership" class="form-label">Membership <span class="text-danger">*</span></label>
                                <select
                                    id="membership"
                                    v-model="form.membership"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.membership }"
                                    required
                                >
                                    <option value="">Select membership...</option>
                                    <option value="bronze">Bronze</option>
                                    <option value="silver">Silver</option>
                                    <option value="gold">Gold</option>
                                    <option value="platinum">Platinum</option>
                                </select>
                                <div v-if="form.errors.membership" class="invalid-feedback">
                                    {{ form.errors.membership }}
                                </div>
                            </div>

                            <!-- Level -->
                            <div class="col-md-4">
                                <label for="level" class="form-label">Level</label>
                                <input
                                    id="level"
                                    v-model="form.level"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.level }"
                                    placeholder="e.g., 1, 2, 3"
                                />
                                <div class="form-text">Optional: Additional categorization (separate from membership tier)</div>
                                <div v-if="form.errors.level" class="invalid-feedback">
                                    {{ form.errors.level }}
                                </div>
                            </div>

                            <!-- Code -->
                            <div class="col-md-4">
                                <label for="code" class="form-label">Code</label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.code }"
                                    placeholder="Reference code"
                                />
                                <div v-if="form.errors.code" class="invalid-feedback">
                                    {{ form.errors.code }}
                                </div>
                            </div>

                            <!-- Referrer -->
                            <div class="col-md-6">
                                <label for="referrer" class="form-label">Referrer</label>
                                <input
                                    id="referrer"
                                    v-model="form.referrer"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.referrer }"
                                    placeholder="Referral source"
                                />
                                <div v-if="form.errors.referrer" class="invalid-feedback">
                                    {{ form.errors.referrer }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Premium Notes Section -->
                <div class="card mt-4">
                    <div class="card-header bg-warning bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="bi bi-star-fill text-warning me-2"></i>
                            Premium Notes
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Enable/Disable Toggle -->
                            <div class="col-md-12 mb-3">
                                <div class="form-check form-switch">
                                    <input
                                        id="premium_notes_enabled"
                                        v-model="form.premium_notes_enabled"
                                        type="checkbox"
                                        class="form-check-input"
                                        role="switch"
                                    />
                                    <label class="form-check-label" for="premium_notes_enabled"> Enable Premium Notes </label>
                                </div>
                                <div class="form-text">
                                    When enabled, premium notes will be displayed to customers at this membership level or higher
                                </div>
                            </div>

                            <!-- Notes Heading -->
                            <div v-if="form.premium_notes_enabled" class="col-md-12 mb-3">
                                <label for="premium_notes_heading" class="form-label">Notes Heading</label>
                                <input
                                    id="premium_notes_heading"
                                    v-model="form.premium_notes_heading"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.premium_notes_heading }"
                                    placeholder="e.g., Premium Analysis, Expert Insights, VIP Notes"
                                />
                                <div class="form-text">Leave empty to use default heading "Premium Analysis"</div>
                                <div v-if="form.errors.premium_notes_heading" class="invalid-feedback">
                                    {{ form.errors.premium_notes_heading }}
                                </div>
                            </div>

                            <!-- Notes Content -->
                            <div v-if="form.premium_notes_enabled" class="col-md-12">
                                <label for="premium_notes" class="form-label">Notes Content</label>
                                <textarea
                                    id="premium_notes"
                                    v-model="form.premium_notes"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.premium_notes }"
                                    rows="5"
                                    placeholder="Enter detailed analysis, insider information, statistical breakdowns, or any premium content that provides extra value to your subscribers..."
                                />
                                <div v-if="form.errors.premium_notes" class="invalid-feedback">
                                    {{ form.errors.premium_notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <Link :href="route('admin.bets.index')" class="btn btn-secondary"> Cancel </Link>
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        {{ form.processing ? 'Creating...' : 'Create Bet' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Logo Upload Modal -->
        <div v-if="showLogoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0, 0, 0, 0.5)">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Team Logo</h5>
                        <button type="button" class="btn-close" @click="showLogoModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedTeamForLogo">
                            <p>
                                Update logo for: <strong>{{ selectedTeamForLogo.name }}</strong>
                            </p>

                            <div v-if="selectedTeamForLogo.logo_url" class="text-center mb-3">
                                <img
                                    :src="`/storage/${selectedTeamForLogo.logo_url}`"
                                    :alt="selectedTeamForLogo.name"
                                    style="max-width: 200px; max-height: 200px"
                                />
                            </div>

                            <div class="mb-3">
                                <label for="logoFile" class="form-label">Choose new logo</label>
                                <input type="file" id="logoFile" class="form-control" accept="image/*" @change="handleLogoFileChange" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showLogoModal = false">Cancel</button>
                        <button type="button" class="btn btn-primary" @click="uploadLogo" :disabled="!logoFile">Upload Logo</button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
/* Select2 Bootstrap 5 Theme */
.select2-container--default .select2-selection--single {
    height: calc(1.5em + 0.75rem + 2px);
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    transition:
        border-color 0.15s ease-in-out,
        box-shadow 0.15s ease-in-out;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #212529;
    padding-left: 0;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + 0.75rem);
    top: 1px;
    right: 10px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.select2-search--dropdown .select2-search__field {
    padding: 0.375rem 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
}
</style>
