<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch, computed, onMounted, nextTick } from 'vue';
import axios from 'axios';

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

interface BetTeam {
    team: Team;
}

interface Bet {
    id: number;
    sports: string;
    league?: string;
    month?: string;
    matches?: string;
    markets?: string;
    wager_type?: string;
    team_one?: string;
    team_one_id?: number;
    team_one_logo?: string;
    team_two?: string;
    team_two_id?: number;
    team_two_logo?: string;
    teamOne?: Team;
    teamTwo?: Team;
    parlayTeams?: BetTeam[];
    tips?: string;
    betting_date: string;
    wager_odds: number | string;
    membership: string;
    level?: string;
    code?: string;
    roi?: number;
    wager_amount: number;
    winning_amount?: number;
    profit_amount?: number;
    status: string;
    referrer?: string;
    place_fraction?: number;
    user_id?: number;
    is_parlay?: boolean;
}

interface Props {
    bet: Bet;
    users: User[];
    sports: Sport[];
    leagues: League[];
    betTypes: Record<string, string>;
    wagerTypes: Record<string, string>;
}

const props = defineProps<Props>();

// Modal state
const showLogoModal = ref(false);
const logoModalTeam = ref<'one' | 'two'>('one');
const selectedTeamForLogo = ref<Team | null>(null);
const logoFile = ref<File | null>(null);

const form = useForm({
    wager_type: props.bet.wager_type || 'single',
    sports: props.bet.sports || '',
    sport_id: null as number | null,
    league: props.bet.league || '',
    league_id: null as number | null,
    month: props.bet.month || '',
    matches: props.bet.matches || '',
    markets: props.bet.markets || '',
    team_one: props.bet.team_one || '',
    team_one_id: props.bet.team_one_id || null,
    team_two: props.bet.team_two || '',
    team_two_id: props.bet.team_two_id || null,
    parlay_teams: props.bet.parlayTeams?.map(pt => ({ 
        id: pt.team.id, 
        name: pt.team.name,
        sport_id: pt.team.sport_id,
        league_id: pt.team.league_id,
        logo_url: pt.team.logo_url
    })) || [],
    tips: props.bet.tips || '',
    betting_date: props.bet.betting_date ? new Date(props.bet.betting_date).toISOString().slice(0, 16) : '',
    wager_odds: props.bet.wager_odds || '',
    membership: String(props.bet.membership || 'bronze').toLowerCase(),
    level: props.bet.level || '',
    code: props.bet.code || '',
    roi: props.bet.roi || 0,
    wager_amount: props.bet.wager_amount || 0,
    winning_amount: props.bet.winning_amount || 0,
    profit_amount: props.bet.profit_amount || 0,
    status: String(props.bet.status || 'pending').toLowerCase(),
    referrer: props.bet.referrer || '',
    place_fraction: props.bet.place_fraction || 0,
    user_id: props.bet.user_id || null,
});

// Initialize sport_id and league_id from existing data
onMounted(() => {
    // Try to find sport by name
    const sport = props.sports.find(s => s.name === props.bet.sports);
    if (sport) {
        form.sport_id = sport.id;
    }
    
    // Try to find league by name and sport
    if (form.sport_id && props.bet.league) {
        const league = props.leagues.find(l => l.name === props.bet.league && l.sport_id === form.sport_id);
        if (league) {
            form.league_id = league.id;
        }
    }
    
    // Initialize Select2 after component is mounted
    nextTick(() => {
        initializeSelect2();
    });
});

// Computed properties
const filteredLeagues = computed(() => {
    if (!form.sport_id) return [];
    return props.leagues.filter(league => league.sport_id === form.sport_id);
});

const isParlay = computed(() => form.wager_type === 'parlay');

const teamOneLogo = computed(() => {
    if (props.bet.teamOne?.logo_url) {
        return `/storage/${props.bet.teamOne.logo_url}`;
    }
    return null;
});

const teamTwoLogo = computed(() => {
    if (props.bet.teamTwo?.logo_url) {
        return `/storage/${props.bet.teamTwo.logo_url}`;
    }
    return null;
});

// Watch for sport changes to update the sport name and reset league
watch(() => form.sport_id, (newSportId) => {
    if (newSportId) {
        const sport = props.sports.find(s => s.id === newSportId);
        if (sport) {
            form.sports = sport.name;
        }
    }
    // Reset league if it doesn't belong to the new sport
    if (form.league_id) {
        const league = props.leagues.find(l => l.id === form.league_id);
        if (!league || league.sport_id !== newSportId) {
            form.league_id = null;
            form.league = '';
        }
    }
});

// Watch for league changes to update the league name
watch(() => form.league_id, (newLeagueId) => {
    if (newLeagueId) {
        const league = props.leagues.find(l => l.id === newLeagueId);
        if (league) {
            form.league = league.name;
        }
    }
});

// Calculate potential win when odds or stake change
watch([() => form.wager_odds, () => form.wager_amount], () => {
    calculatePotentialWin();
});

// Calculate profit when status changes
watch(() => form.status, () => {
    calculateProfit();
});

function calculatePotentialWin() {
    const odds = typeof form.wager_odds === 'string' ? parseFloat(form.wager_odds) : form.wager_odds;
    const stake = Number(form.wager_amount);
    
    if (odds && stake) {
        if (odds > 0) {
            // Positive American odds
            form.winning_amount = stake + (stake * odds / 100);
        } else {
            // Negative American odds
            form.winning_amount = stake + (stake * 100 / Math.abs(odds));
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
    } else if (form.status === 'lost') {
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
                return {
                    results: data.results
                };
            },
            cache: true
        },
        placeholder: `Search for ${teamType === 'one' ? 'team one' : 'team two'}...`,
        minimumInputLength: 2,
        templateResult: formatTeam,
        templateSelection: formatTeamSelection,
        allowClear: true,
        dropdownParent: window.$('.modal').length ? window.$('.modal') : window.$('body')
    });
    
    // Handle selection
    $element.on('select2:select', function (e: any) {
        const data = e.params.data;
        if (teamType === 'one') {
            form.team_one_id = data.id;
            form.team_one = data.name;
        } else {
            form.team_two_id = data.id;
            form.team_two = data.name;
        }
    });
    
    // Handle clearing
    $element.on('select2:clear', function () {
        if (teamType === 'one') {
            form.team_one_id = null;
            form.team_one = '';
        } else {
            form.team_two_id = null;
            form.team_two = '';
        }
    });
    
    // Set initial value if team is already selected
    if (teamType === 'one' && form.team_one_id) {
        // Get team name from various sources
        let teamName = '';
        let teamId = form.team_one_id;
        
        // First try the team relationship
        if (props.bet.teamOne && typeof props.bet.teamOne === 'object' && props.bet.teamOne.name) {
            teamName = String(props.bet.teamOne.name);
        } 
        // Fall back to the text field
        else if (form.team_one) {
            teamName = String(form.team_one);
        }
        
        if (teamName && teamId) {
            // Use jQuery to create the option properly
            const $option = window.$('<option></option>')
                .attr('value', teamId.toString())
                .text(teamName)
                .prop('selected', true);
            
            $element.append($option);
            
            // Trigger change event to update Select2
            $element.trigger('change');
        }
    } else if (teamType === 'two' && form.team_two_id) {
        // Get team name from various sources
        let teamName = '';
        let teamId = form.team_two_id;
        
        // First try the team relationship
        if (props.bet.teamTwo && typeof props.bet.teamTwo === 'object' && props.bet.teamTwo.name) {
            teamName = String(props.bet.teamTwo.name);
        } 
        // Fall back to the text field
        else if (form.team_two) {
            teamName = String(form.team_two);
        }
        
        if (teamName && teamId) {
            // Use jQuery to create the option properly
            const $option = window.$('<option></option>')
                .attr('value', teamId.toString())
                .text(teamName)
                .prop('selected', true);
            
            $element.append($option);
            
            // Trigger change event to update Select2
            $element.trigger('change');
        }
    }
}

function formatTeam(team: any) {
    if (!team.id) return team.text;
    
    const $container = window.$('<div class="d-flex align-items-center">');
    
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
    return $container;
}

function formatTeamSelection(team: any) {
    // Ensure we always return a string
    if (team.name) {
        return team.name;
    }
    if (team.text) {
        return team.text;
    }
    // If team is a string, return it
    if (typeof team === 'string') {
        return team;
    }
    return '';
}

function openLogoModal(teamType: 'one' | 'two') {
    logoModalTeam.value = teamType;
    
    // Get the selected team
    if (teamType === 'one' && form.team_one_id) {
        // Create a clean team object
        const teamOne = props.bet.teamOne;
        selectedTeamForLogo.value = teamOne ? {
            id: teamOne.id,
            name: teamOne.name,
            sport_id: teamOne.sport_id,
            league_id: teamOne.league_id,
            logo_url: teamOne.logo_url
        } : { 
            id: form.team_one_id, 
            name: form.team_one,
            sport_id: form.sport_id!,
            league_id: form.league_id
        };
    } else if (teamType === 'two' && form.team_two_id) {
        // Create a clean team object
        const teamTwo = props.bet.teamTwo;
        selectedTeamForLogo.value = teamTwo ? {
            id: teamTwo.id,
            name: teamTwo.name,
            sport_id: teamTwo.sport_id,
            league_id: teamTwo.league_id,
            logo_url: teamTwo.logo_url
        } : { 
            id: form.team_two_id, 
            name: form.team_two,
            sport_id: form.sport_id!,
            league_id: form.league_id
        };
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
        const response = await axios.post(
            route('admin.api.teams.update-logo', selectedTeamForLogo.value.id),
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data'
                }
            }
        );
        
        if (response.data.success) {
            // Update the team logo in the bet data
            if (logoModalTeam.value === 'one' && props.bet.teamOne) {
                props.bet.teamOne.logo_url = response.data.logo_url.replace('/storage/', '');
            } else if (logoModalTeam.value === 'two' && props.bet.teamTwo) {
                props.bet.teamTwo.logo_url = response.data.logo_url.replace('/storage/', '');
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
        logo_url: null
    });
}

function removeParlayTeam(index: number) {
    form.parlay_teams.splice(index, 1);
}

function submit() {
    form.put(route('admin.bets.update', props.bet.id));
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
        <Head title="Edit Bet" />
        
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
                                    <li class="breadcrumb-item active">Edit</li>
                                </ol>
                            </nav>
                            <h1 class="h2 mb-0 text-dark">Edit Bet #{{ bet.id }}</h1>
                        </div>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
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
                                    <option value="">Select wager type...</option>
                                    <option v-for="(label, value) in wagerTypes" :key="value" :value="value">
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
                                <!-- Team One -->
                                <div class="col-md-6">
                                    <label for="team_one_select" class="form-label">Team One / Player</label>
                                    <div class="d-flex gap-2">
                                        <select
                                            id="team_one_select"
                                            class="form-control flex-grow-1"
                                        >
                                        </select>
                                        <button
                                            v-if="teamOneLogo || form.team_one_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('one')"
                                            title="View/Update Logo"
                                            style="width: 42px;"
                                        >
                                            <img v-if="teamOneLogo" :src="teamOneLogo" alt="Logo" style="width: 20px; height: 20px; object-fit: contain;">
                                            <i v-else class="bi bi-image"></i>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.team_one || form.errors.team_one_id" class="invalid-feedback d-block">
                                        {{ form.errors.team_one || form.errors.team_one_id }}
                                    </div>
                                </div>

                                <!-- Team Two -->
                                <div class="col-md-6">
                                    <label for="team_two_select" class="form-label">Team Two</label>
                                    <div class="d-flex gap-2">
                                        <select
                                            id="team_two_select"
                                            class="form-control flex-grow-1"
                                        >
                                        </select>
                                        <button
                                            v-if="teamTwoLogo || form.team_two_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('two')"
                                            title="View/Update Logo"
                                            style="width: 42px;"
                                        >
                                            <img v-if="teamTwoLogo" :src="teamTwoLogo" alt="Logo" style="width: 20px; height: 20px; object-fit: contain;">
                                            <i v-else class="bi bi-image"></i>
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
                                            <input
                                                v-model="team.name"
                                                type="text"
                                                class="form-control"
                                                :placeholder="`Team ${index + 1}`"
                                            />
                                            <button
                                                type="button"
                                                class="btn btn-outline-danger"
                                                @click="removeParlayTeam(index)"
                                            >
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="btn btn-outline-primary btn-sm"
                                        @click="addParlayTeam"
                                    >
                                        <i class="bi bi-plus-circle me-1"></i> Add Team
                                    </button>
                                </div>
                            </template>

                            <!-- User -->
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">User</label>
                                <select
                                    id="user_id"
                                    v-model="form.user_id"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.user_id }"
                                >
                                    <option :value="null">Select a user...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <div v-if="form.errors.user_id" class="invalid-feedback">
                                    {{ form.errors.user_id }}
                                </div>
                            </div>

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
                                <label for="markets" class="form-label">Markets</label>
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
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="form-select"
                                    :class="{ 'is-invalid': form.errors.status }"
                                    required
                                >
                                    <option value="">Select status...</option>
                                    <option value="pending">Pending</option>
                                    <option value="won">Won</option>
                                    <option value="lost">Lost</option>
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

                            <!-- Place Fraction -->
                            <div class="col-md-6">
                                <label for="place_fraction" class="form-label">Place Fraction (Each Way)</label>
                                <input
                                    id="place_fraction"
                                    v-model.number="form.place_fraction"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="1"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.place_fraction }"
                                    placeholder="0.25"
                                />
                                <div v-if="form.errors.place_fraction" class="invalid-feedback">
                                    {{ form.errors.place_fraction }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="d-flex justify-content-end gap-2">
                    <Link
                        :href="route('admin.bets.index')"
                        class="btn btn-secondary"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="spinner-border spinner-border-sm me-2"></span>
                        {{ form.processing ? 'Updating...' : 'Update Bet' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Logo Upload Modal -->
        <div v-if="showLogoModal" class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Team Logo</h5>
                        <button type="button" class="btn-close" @click="showLogoModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div v-if="selectedTeamForLogo">
                            <p>Update logo for: <strong>{{ selectedTeamForLogo.name || 'Team' }}</strong></p>
                            
                            <div v-if="selectedTeamForLogo.logo_url" class="text-center mb-3">
                                <img 
                                    :src="`/storage/${selectedTeamForLogo.logo_url}`" 
                                    :alt="selectedTeamForLogo.name"
                                    style="max-width: 200px; max-height: 200px;"
                                />
                            </div>
                            
                            <div class="mb-3">
                                <label for="logoFile" class="form-label">Choose new logo</label>
                                <input 
                                    type="file" 
                                    id="logoFile" 
                                    class="form-control" 
                                    accept="image/*"
                                    @change="handleLogoFileChange"
                                />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showLogoModal = false">Cancel</button>
                        <button 
                            type="button" 
                            class="btn btn-primary" 
                            @click="uploadLogo"
                            :disabled="!logoFile"
                        >
                            Upload Logo
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
/* Select2 Bootstrap 5 Theme */
.select2-container--default .select2-selection--single {
    height: calc(1.5em + .75rem + 2px);
    padding: .375rem .75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: .25rem;
    transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #212529;
    padding-left: 0;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: calc(1.5em + .75rem);
    top: 1px;
    right: 10px;
}

.select2-dropdown {
    border: 1px solid #ced4da;
    border-radius: .25rem;
}

.select2-search--dropdown .select2-search__field {
    padding: .375rem .75rem;
    border: 1px solid #ced4da;
    border-radius: .25rem;
}

.select2-results__option--highlighted[aria-selected] {
    background-color: #0d6efd;
}
</style>