<script setup lang="ts">
import BetCalculator from '@/components/BetCalculator.vue';
import MetaBetLookupModal from '@/components/MetaBetLookupModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

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
    premium_notes?: string;
    premium_notes_enabled?: boolean;
    premium_notes_heading?: string;
    betting_date: string;
    game_date: string;
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
    each_way_stake?: number;
    place_payout?: number;
    user_id?: number;
    is_parlay?: boolean;
    // New fields for position and dead heat
    finishing_position?: string;
    places_paid?: number;
    position_numeric?: number;
    is_dead_heat?: boolean;
    dead_heat_players?: number;
    dead_heat_spots?: number;
    bet_result_type?: string;
    place_terms_denominator?: number;
    golf_place?: boolean;
    golf_place_fraction?: number;
    // MetaBet integration
    metabet_query_id?: string | null;
    metabet_game_name?: string | null;
    metabet_linked_at?: string | null;
    // MetaBet Prop Tile
    metabet_prop_query_id?: string | null;
    metabet_prop_name?: string | null;
    metabet_prop_size?: string | null;
    // MetaBet Parlay Tile
    metabet_parlay_query_id?: string | null;
    metabet_parlay_name?: string | null;
    metabet_parlay_size?: string | null;
    // Widget type preference
    metabet_widget_type?: string | null;
}

interface Props {
    bet: Bet;
    users: User[];
    sports: Sport[];
    leagues: League[];
    betTypes: Record<string, string>;
    teams: Array<{ id: number; text: string }>;
}

const props = defineProps<Props>();

// Helper function to normalize wager type
const normalizeWagerType = (wagerType: string): string => {
    // Convert "Single Wager" to "single_wager" for the form value
    if (wagerType === 'Single Wager') {
        return 'single_wager';
    }
    return wagerType;
};

// Helper function to extract base team name (removes pitcher info in parentheses)
const extractBaseTeamName = (teamString: string): string => {
    if (!teamString) return '';
    // Remove content in parentheses (e.g., "(G Kirby)")
    const baseTeam = teamString.replace(/\s*\([^)]*\)/g, '').trim();
    return baseTeam;
};

// Helper function to normalize membership tier (handles legacy Bronze/Silver → Free)
const normalizeMembership = (membership: string | undefined | null): string => {
    if (!membership) return 'Free';
    const lower = membership.toLowerCase();
    // Map legacy tiers to new structure
    if (lower === 'bronze' || lower === 'silver' || lower === 'free') {
        return 'Free';
    }
    if (lower === 'gold') {
        return 'Gold';
    }
    if (lower === 'platinum') {
        return 'Platinum';
    }
    // Return capitalized version as fallback
    return membership.charAt(0).toUpperCase() + membership.slice(1).toLowerCase();
};

// Modal state
const showLogoModal = ref(false);
const logoModalTeam = ref<'one' | 'two'>('one');
const selectedTeamForLogo = ref<Team | null>(null);
const logoFile = ref<File | null>(null);

// MetaBet modal state
const showMetaBetModal = ref(false);

const form = useForm({
    wager_type: normalizeWagerType(props.bet.wager_type || props.bet.markets || ''),
    sports: props.bet.sports || '',
    sport_id: null as number | null,
    league: props.bet.league || '',
    league_id: null as number | null,
    month: props.bet.month || '',
    matches: props.bet.matches || '',
    markets: props.bet.markets || '',
    team_one: typeof props.bet.team_one === 'string' ? props.bet.team_one : '',
    team_one_id: props.bet.team_one_id || null,
    team_two: typeof props.bet.team_two === 'string' ? props.bet.team_two : '',
    team_two_id: props.bet.team_two_id || null,
    parlay_teams:
        props.bet.parlayTeams?.map((pt) => ({
            id: pt.team.id,
            name: pt.team.name,
            sport_id: pt.team.sport_id,
            league_id: pt.team.league_id,
            logo_url: pt.team.logo_url,
        })) || [],
    tips: props.bet.tips || '',
    premium_notes: props.bet.premium_notes || '',
    premium_notes_enabled: props.bet.premium_notes_enabled ?? false,
    premium_notes_heading: props.bet.premium_notes_heading || '',
    betting_date: props.bet.betting_date ? new Date(props.bet.betting_date).toISOString().slice(0, 16) : '',
    game_date: props.bet.game_date ? new Date(props.bet.game_date).toISOString().slice(0, 16) : '',
    wager_odds: props.bet.wager_odds || '',
    membership: normalizeMembership(props.bet.membership),
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
    golf_place: props.bet.golf_place || false,
    golf_place_fraction: props.bet.golf_place_fraction || null,
    // MetaBet Game Tile
    metabet_query_id: props.bet.metabet_query_id || null,
    metabet_game_name: props.bet.metabet_game_name || null,
    // MetaBet Prop Tile
    metabet_prop_query_id: props.bet.metabet_prop_query_id || null,
    metabet_prop_name: props.bet.metabet_prop_name || null,
    metabet_prop_size: props.bet.metabet_prop_size || '320x50',
    // MetaBet Parlay Tile
    metabet_parlay_query_id: props.bet.metabet_parlay_query_id || null,
    metabet_parlay_name: props.bet.metabet_parlay_name || null,
    metabet_parlay_size: props.bet.metabet_parlay_size || '350x350',
    // MetaBet Widget Type
    metabet_widget_type: props.bet.metabet_widget_type || 'game',
    place_bet_url: props.bet.place_bet_url || '',
});

// Initialize sport_id and league_id from existing data
onMounted(() => {
    // Try to find sport by name (case-insensitive comparison)
    const sport = props.sports.find((s) => s.name.toLowerCase() === props.bet.sports?.toLowerCase());
    if (sport) {
        form.sport_id = sport.id;
    } else if (props.bet.sport_id) {
        // If we have sport_id directly, use it
        form.sport_id = props.bet.sport_id;
    }

    // Try to find league by name and sport
    if (form.sport_id && props.bet.league) {
        const league = props.leagues.find((l) => l.name.toLowerCase() === props.bet.league.toLowerCase() && l.sport_id === form.sport_id);
        if (league) {
            form.league_id = league.id;
        }
    }

    // Initialize Select2 after component is mounted
    nextTick(() => {
        console.log('Bet data:', {
            team_one: form.team_one,
            team_one_id: form.team_one_id,
            team_two: form.team_two,
            team_two_id: form.team_two_id,
            teams: props.teams,
        });
        initializeSelect2();
    });
});

// Computed properties
const filteredLeagues = computed(() => {
    if (!form.sport_id) return [];
    return props.leagues.filter((league) => league.sport_id === form.sport_id);
});

const isParlay = computed(() => form.wager_type === 'parlay');

const isIndividualSport = computed(() => {
    if (!form.sports) return false;
    const individualSports = ['golf', 'tennis', 'boxing', 'mma', 'ufc', 'racing', 'nascar'];
    return individualSports.includes(form.sports.toLowerCase());
});

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
watch(
    () => form.sport_id,
    (newSportId) => {
        if (newSportId) {
            const sport = props.sports.find((s) => s.id === newSportId);
            if (sport) {
                form.sports = sport.name;
            }
        }
        // Reset league if it doesn't belong to the new sport
        if (form.league_id) {
            const league = props.leagues.find((l) => l.id === form.league_id);
            if (!league || league.sport_id !== newSportId) {
                form.league_id = null;
                form.league = '';
            }
        }
    },
);

// Watch for teams data changes to re-initialize Select2
watch(
    () => props.teams,
    (newTeams) => {
        if (newTeams && newTeams.length > 0) {
            console.log('Teams loaded:', newTeams.length);
            // Re-initialize Select2 with new teams
            nextTick(() => {
                initializeSelect2();
            });
        }
    },
    { immediate: true },
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

// Watch for bet type changes to reset golf_place
watch(
    () => form.wager_type,
    () => {
        // Reset golf_place if bet type is not each_way
        if (form.wager_type !== 'each_way') {
            form.golf_place = false;
            form.golf_place_fraction = null;
        }
    },
);

// Computed property for golf place fraction display
const golfPlaceFractionDisplay = computed({
    get: () => {
        if (form.golf_place_fraction === null || form.golf_place_fraction === undefined) {
            return '';
        }
        return decimalToFraction(form.golf_place_fraction);
    },
    set: (value: string) => {
        // This will be handled by updateGolfPlaceFraction
    },
});

// Update golf place fraction from input
function updateGolfPlaceFraction(event: Event) {
    const input = event.target as HTMLInputElement;
    const value = input.value.trim();

    if (!value) {
        form.golf_place_fraction = null;
        return;
    }

    // Check if it's a fraction
    if (value.includes('/')) {
        form.golf_place_fraction = fractionToDecimal(value);
    } else {
        // Try to parse as decimal
        const decimal = parseFloat(value);
        if (!isNaN(decimal)) {
            form.golf_place_fraction = decimal;
        }
    }
}

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
        // Initialize Select2 and set initial values after Vue has rendered
        nextTick(() => {
            setTimeout(() => {
                console.log('Initializing Select2 with:', {
                    team_one_id: form.team_one_id,
                    team_two_id: form.team_two_id,
                    teams_count: props.teams.length,
                });
                initTeamSelect('team_one_select', 'one');
                initTeamSelect('team_two_select', 'two');
            }, 100);
        });
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

    // Clear and populate with teams
    $element.empty();
    $element.append('<option value="">Select a team...</option>');

    // Add all teams as options
    props.teams.forEach((team) => {
        const isSelected = (teamType === 'one' && form.team_one_id === team.id) || (teamType === 'two' && form.team_two_id === team.id);
        const option = new Option(team.text, team.id.toString(), isSelected, isSelected);
        $element.append(option);
    });

    // Initialize Select2 with simple configuration
    $element.select2({
        placeholder: `Select ${teamType === 'one' ? 'team one' : 'team two'}...`,
        allowClear: true,
        tags: true,
        createTag: function (params: any) {
            const term = params.term?.trim();
            if (!term) {
                return null;
            }
            return {
                id: term,
                text: term,
                newTag: true,
            };
        },
        dropdownParent: window.$('.modal').length ? window.$('.modal') : window.$('body'),
    });

    // Force set the value after Select2 initialization
    const valueToSet = teamType === 'one' ? form.team_one_id : form.team_two_id;
    if (valueToSet) {
        console.log(`Setting ${teamType} team value to:`, valueToSet);
        $element.val(valueToSet.toString()).trigger('change.select2');

        // Double-check the value was set
        setTimeout(() => {
            const currentVal = $element.val();
            console.log(`${teamType} team value after set:`, currentVal);
            if (currentVal !== valueToSet.toString()) {
                console.warn(`Failed to set ${teamType} team value, trying again...`);
                $element.val(valueToSet.toString()).trigger('change.select2');
            }
        }, 100);
    }

    // Handle selection
    $element.on('select2:select', function (e: any) {
        const data = e.params.data;
        if (teamType === 'one') {
            // For new tags (text-only teams), ID will be the same as text
            form.team_one_id = data.newTag ? null : parseInt(data.id);
            form.team_one = data.text || data.name;
        } else {
            // For new tags (text-only teams), ID will be the same as text
            form.team_two_id = data.newTag ? null : parseInt(data.id);
            form.team_two = data.text || data.name;
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
}

// Search for a team by name and set it if found
async function searchAndSetTeam(teamType: 'one' | 'two', searchTerm: string) {
    if (!searchTerm) return;

    try {
        const response = await axios.get(route('admin.api.teams.search'), {
            params: {
                q: searchTerm,
                sport_id: form.sport_id,
                league_id: form.league_id,
            },
        });

        if (response.data.results && response.data.results.length > 0) {
            // Find exact match or closest match
            const exactMatch = response.data.results.find((team: any) => team.name.toLowerCase() === searchTerm.toLowerCase());

            const teamToUse = exactMatch || response.data.results[0];

            if (teamToUse) {
                // Update form with team ID
                if (teamType === 'one') {
                    form.team_one_id = teamToUse.id;
                    // Keep the original full name with pitcher info
                    // form.team_one remains unchanged
                } else {
                    form.team_two_id = teamToUse.id;
                    // Keep the original full name with pitcher info
                    // form.team_two remains unchanged
                }

                // Update Select2 dropdown
                const elementId = teamType === 'one' ? 'team_one_select' : 'team_two_select';
                const $element = window.$(`#${elementId}`);

                if ($element.length) {
                    // Clear existing option
                    $element.empty();

                    // Add the found team as selected option
                    const $option = window
                        .$('<option></option>')
                        .attr('value', teamToUse.id)
                        .text(form[teamType === 'one' ? 'team_one' : 'team_two']) // Use original name with pitcher
                        .prop('selected', true);

                    $element.append($option);
                    $element.trigger('change');
                }
            }
        }
    } catch (error) {
        console.error(`Error searching for team ${teamType}:`, error);
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
    // Handle the case where team might be the element itself during initialization
    if (!team || !team.id) {
        return '';
    }

    // Get the text from the element if it's a DOM element
    if (team.element) {
        return team.text || '';
    }

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
    return team.toString();
}

function openLogoModal(teamType: 'one' | 'two') {
    logoModalTeam.value = teamType;

    // Get the selected team
    if (teamType === 'one' && form.team_one_id) {
        // Create a clean team object
        const teamOne = props.bet.teamOne;
        selectedTeamForLogo.value = teamOne
            ? {
                  id: teamOne.id,
                  name: teamOne.name,
                  sport_id: teamOne.sport_id,
                  league_id: teamOne.league_id,
                  logo_url: teamOne.logo_url,
              }
            : {
                  id: form.team_one_id,
                  name: form.team_one,
                  sport_id: form.sport_id!,
                  league_id: form.league_id,
              };
    } else if (teamType === 'two' && form.team_two_id) {
        // Create a clean team object
        const teamTwo = props.bet.teamTwo;
        selectedTeamForLogo.value = teamTwo
            ? {
                  id: teamTwo.id,
                  name: teamTwo.name,
                  sport_id: teamTwo.sport_id,
                  league_id: teamTwo.league_id,
                  logo_url: teamTwo.logo_url,
              }
            : {
                  id: form.team_two_id,
                  name: form.team_two,
                  sport_id: form.sport_id!,
                  league_id: form.league_id,
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
        const response = await axios.post(route('admin.api.teams.update-logo', selectedTeamForLogo.value.id), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

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
        errors.wager_type = 'The wager type field is required.';
        isValid = false;
    }

    if (!form.sports || !form.sports.trim()) {
        errors.sports = 'The sports field is required.';
        isValid = false;
    } else if (form.sports.length > 255) {
        errors.sports = 'The sports may not be greater than 255 characters.';
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

    if (!form.wager_odds && form.wager_odds !== 0) {
        errors.wager_odds = 'The wager odds field is required.';
        isValid = false;
    } else if (isNaN(Number(form.wager_odds))) {
        errors.wager_odds = 'The wager odds must be a number.';
        isValid = false;
    }

    if (form.wager_amount === null || form.wager_amount === undefined || form.wager_amount === '') {
        errors.wager_amount = 'The wager amount field is required.';
        isValid = false;
    } else if (form.wager_amount < 0) {
        errors.wager_amount = 'The wager amount must be at least 0.';
        isValid = false;
    }

    if (!form.status) {
        errors.status = 'The status field is required.';
        isValid = false;
    } else if (!['pending', 'won', 'loss', 'void', 'push'].includes(form.status)) {
        errors.status = 'The selected status is invalid.';
        isValid = false;
    }

    if (!form.membership) {
        errors.membership = 'The membership field is required.';
        isValid = false;
    } else if (!['free', 'Free', 'gold', 'Gold', 'platinum', 'Platinum', 'bronze', 'silver', 'Bronze', 'Silver'].includes(form.membership)) {
        errors.membership = 'The selected membership is invalid.';
        isValid = false;
    }

    // Optional fields validation
    if (form.league && form.league.length > 255) {
        errors.league = 'The league may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.month && form.month.length > 50) {
        errors.month = 'The month may not be greater than 50 characters.';
        isValid = false;
    }

    if (form.matches && form.matches.length > 500) {
        errors.matches = 'The matches may not be greater than 500 characters.';
        isValid = false;
    }

    if (form.markets && form.markets.length > 255) {
        errors.markets = 'The markets may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.team_one && form.team_one.length > 255) {
        errors.team_one = 'The team one may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.team_two && form.team_two.length > 255) {
        errors.team_two = 'The team two may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.tips && form.tips.length > 500) {
        errors.tips = 'The tips may not be greater than 500 characters.';
        isValid = false;
    }

    if (form.level && form.level.length > 50) {
        errors.level = 'The level may not be greater than 50 characters.';
        isValid = false;
    }

    if (form.code && form.code.length > 255) {
        errors.code = 'The code may not be greater than 255 characters.';
        isValid = false;
    }

    if (form.referrer && form.referrer.length > 255) {
        errors.referrer = 'The referrer may not be greater than 255 characters.';
        isValid = false;
    }

    // Remove validation for negative winning amounts - they are allowed for losses

    if (form.place_fraction !== null && form.place_fraction !== undefined) {
        if (form.place_fraction < 0 || form.place_fraction > 1) {
            errors.place_fraction = 'The place fraction must be between 0 and 1.';
            isValid = false;
        }
    }

    // Position and dead heat validation
    if (form.finishing_position && form.finishing_position.length > 20) {
        errors.finishing_position = 'The finishing position may not be greater than 20 characters.';
        isValid = false;
    }

    if (form.places_paid !== null && form.places_paid !== undefined) {
        if (form.places_paid < 1 || form.places_paid > 50) {
            errors.places_paid = 'The places paid must be between 1 and 50.';
            isValid = false;
        }
    }

    if (form.is_dead_heat) {
        if (!form.dead_heat_players || form.dead_heat_players < 2) {
            errors.dead_heat_players = 'Dead heat must have at least 2 players tied.';
            isValid = false;
        }

        if (form.dead_heat_spots === null || form.dead_heat_spots === undefined || form.dead_heat_spots <= 0) {
            errors.dead_heat_spots = 'Dead heat must have available spots specified.';
            isValid = false;
        }
    }

    // Parlay teams validation
    if (form.parlay_teams && form.parlay_teams.length > 0) {
        for (let i = 0; i < form.parlay_teams.length; i++) {
            if (form.parlay_teams[i].name && form.parlay_teams[i].name.length > 255) {
                errors[`parlay_teams.${i}.name`] = 'Each team name may not be greater than 255 characters.';
                isValid = false;
            }
        }
    }

    // Set errors if any
    if (!isValid) {
        form.setError(errors);
    }

    return isValid;
}

function openMetaBetModal() {
    showMetaBetModal.value = true;
}

function handleMetaBetLinked(payload: { queryId: string; gameName: string }) {
    form.metabet_query_id = payload.queryId || null;
    form.metabet_game_name = payload.gameName || null;
    showMetaBetModal.value = false;

    // Show success message
    if (payload.queryId) {
        alert(`MetaBet linked successfully!\nQuery ID: ${payload.queryId}`);
    } else {
        alert('MetaBet link removed successfully!');
    }
}

// MetaBet - check if any ID is configured
const hasAnyMetabetId = computed(() => {
    return form.metabet_query_id || form.metabet_prop_query_id || form.metabet_parlay_query_id;
});

function submit() {
    if (!validateForm()) {
        return;
    }

    // Set is_each_way based on wager_type
    const data = {
        ...form.data(),
        is_each_way: form.wager_type === 'each_way',
    };

    form.transform(() => data).put(route('admin.bets.update', props.bet.id));
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

// Convert decimal to fraction
function decimalToFraction(decimal: number): string {
    if (!decimal || decimal === 0) return '0';

    // Common fractions
    const commonFractions: { [key: number]: string } = {
        0.2: '1/5',
        0.25: '1/4',
        0.333: '1/3',
        0.3333: '1/3',
        0.5: '1/2',
        0.666: '2/3',
        0.6667: '2/3',
        0.75: '3/4',
        0.8: '4/5',
    };

    // Check if it's a common fraction
    for (const [dec, frac] of Object.entries(commonFractions)) {
        if (Math.abs(decimal - parseFloat(dec)) < 0.001) {
            return frac;
        }
    }

    // Otherwise, convert using algorithm
    const tolerance = 1.0e-6;
    let h1 = 1,
        h2 = 0,
        k1 = 0,
        k2 = 1;
    let b = decimal;
    do {
        const a = Math.floor(b);
        let aux = h1;
        h1 = a * h1 + h2;
        h2 = aux;
        aux = k1;
        k1 = a * k1 + k2;
        k2 = aux;
        b = 1 / (b - a);
    } while (Math.abs(decimal - h1 / k1) > decimal * tolerance);

    return `${h1}/${k1}`;
}

// Convert fraction to decimal
function fractionToDecimal(fraction: string): number {
    if (!fraction || fraction === '0') return 0;

    const parts = fraction.split('/');
    if (parts.length === 2) {
        const numerator = parseFloat(parts[0]);
        const denominator = parseFloat(parts[1]);
        if (denominator !== 0) {
            return numerator / denominator;
        }
    }

    return parseFloat(fraction) || 0;
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
                                <!-- Team One -->
                                <div class="col-md-6">
                                    <label for="team_one_select" class="form-label">Team One / Player <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-2">
                                        <select id="team_one_select" class="form-control flex-grow-1"></select>
                                        <button
                                            v-if="teamOneLogo || form.team_one_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('one')"
                                            title="View/Update Logo"
                                            style="width: 42px"
                                        >
                                            <img
                                                v-if="teamOneLogo"
                                                :src="teamOneLogo"
                                                alt="Logo"
                                                style="width: 20px; height: 20px; object-fit: contain"
                                            />
                                            <i v-else class="bi bi-image"></i>
                                        </button>
                                    </div>
                                    <div v-if="form.errors.team_one || form.errors.team_one_id" class="invalid-feedback d-block">
                                        {{ form.errors.team_one || form.errors.team_one_id }}
                                    </div>
                                </div>

                                <!-- Team Two -->
                                <div class="col-md-6">
                                    <label for="team_two_select" class="form-label"
                                        >Team Two <span v-if="!isIndividualSport" class="text-danger">*</span></label
                                    >
                                    <div class="d-flex gap-2">
                                        <select id="team_two_select" class="form-control flex-grow-1"></select>
                                        <button
                                            v-if="teamTwoLogo || form.team_two_id"
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            @click="openLogoModal('two')"
                                            title="View/Update Logo"
                                            style="width: 42px"
                                        >
                                            <img
                                                v-if="teamTwoLogo"
                                                :src="teamTwoLogo"
                                                alt="Logo"
                                                style="width: 20px; height: 20px; object-fit: contain"
                                            />
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

                            <!-- User -->
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">User</label>
                                <select id="user_id" v-model="form.user_id" class="form-select" :class="{ 'is-invalid': form.errors.user_id }">
                                    <option :value="null">Select a user...</option>
                                    <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
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

                            <!-- Golf Only: Place Fraction -->
                            <div v-if="form.wager_type === 'each_way' && form.golf_place" class="col-md-6 mt-3">
                                <label for="golf_place_fraction" class="form-label">Golf Only: Place Fraction</label>
                                <input
                                    id="golf_place_fraction"
                                    v-model="golfPlaceFractionDisplay"
                                    type="text"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.golf_place_fraction }"
                                    placeholder="e.g., 1/5"
                                    @input="updateGolfPlaceFraction"
                                />
                                <small class="text-muted">Enter as fraction (e.g., 1/5) or decimal (e.g., 0.2)</small>
                                <div v-if="form.errors.golf_place_fraction" class="invalid-feedback">
                                    {{ form.errors.golf_place_fraction }}
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
                                    <option value="Free">Free</option>
                                    <option value="Gold">Gold</option>
                                    <option value="Platinum">Platinum</option>
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
                                <small class="text-muted">Bet confidence/priority level (separate from membership tier)</small>
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

                <!-- MetaBet Integration Section -->
                <div class="card mt-4">
                    <div class="card-header bg-info bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="bi bi-graph-up-arrow text-info me-2"></i>
                            MetaBet Live Odds Integration
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-4">
                            Link this bet to MetaBet.io widgets to display live, real-time odds from major sportsbooks. Choose which widget types to
                            display.
                        </p>

                        <!-- Widget Type Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Display Widget Type</label>
                            <div class="btn-group d-flex flex-wrap gap-2" role="group">
                                <input type="radio" class="btn-check" id="widget_game" v-model="form.metabet_widget_type" value="game" />
                                <label class="btn btn-outline-primary" for="widget_game"> <i class="bi bi-grid-3x3-gap me-1"></i> Game Tile </label>

                                <input type="radio" class="btn-check" id="widget_prop" v-model="form.metabet_widget_type" value="prop" />
                                <label class="btn btn-outline-success" for="widget_prop"> <i class="bi bi-person-badge me-1"></i> Prop Tile </label>

                                <input type="radio" class="btn-check" id="widget_parlay" v-model="form.metabet_widget_type" value="parlay" />
                                <label class="btn btn-outline-warning" for="widget_parlay"> <i class="bi bi-layers me-1"></i> Parlay Tile </label>

                                <input type="radio" class="btn-check" id="widget_game_prop" v-model="form.metabet_widget_type" value="game_prop" />
                                <label class="btn btn-outline-info" for="widget_game_prop"> <i class="bi bi-collection me-1"></i> Game + Prop </label>
                            </div>
                        </div>

                        <div class="row g-4">
                            <!-- Game Tile Section -->
                            <div
                                class="col-lg-4"
                                v-if="
                                    form.metabet_widget_type === 'game' ||
                                    form.metabet_widget_type === 'game_prop' ||
                                    form.metabet_widget_type === 'all'
                                "
                            >
                                <div class="card h-100 border-primary">
                                    <div class="card-header bg-primary bg-opacity-10">
                                        <h6 class="mb-0">
                                            <i class="bi bi-grid-3x3-gap text-primary me-2"></i>
                                            Game Tile
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Query ID</label>
                                            <input
                                                v-model="form.metabet_query_id"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., 594584"
                                            />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Game Name (Optional)</label>
                                            <input
                                                v-model="form.metabet_game_name"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., Lakers vs Celtics"
                                            />
                                        </div>
                                        <div v-if="form.metabet_query_id" class="alert alert-success py-2 mb-0">
                                            <small><i class="bi bi-check-circle me-1"></i> Linked</small>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary w-100" @click="openMetaBetModal">
                                            <i class="bi bi-search me-1"></i>
                                            {{ form.metabet_query_id ? 'Update' : 'Find Game' }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Prop Tile Section -->
                            <div
                                class="col-lg-4"
                                v-if="
                                    form.metabet_widget_type === 'prop' ||
                                    form.metabet_widget_type === 'game_prop' ||
                                    form.metabet_widget_type === 'all'
                                "
                            >
                                <div class="card h-100 border-success">
                                    <div class="card-header bg-success bg-opacity-10">
                                        <h6 class="mb-0">
                                            <i class="bi bi-person-badge text-success me-2"></i>
                                            Prop Tile
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Prop Query ID</label>
                                            <input
                                                v-model="form.metabet_prop_query_id"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., nba/lebron_james or player ID"
                                            />
                                            <small class="text-muted">Format: league/player_name or numeric ID</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Prop Name (Optional)</label>
                                            <input
                                                v-model="form.metabet_prop_name"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., LeBron Points O/U"
                                            />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Size</label>
                                            <select v-model="form.metabet_prop_size" class="form-select form-select-sm">
                                                <option value="320x50">320x50 (Banner)</option>
                                                <option value="300x250">300x250 (Medium Rectangle)</option>
                                                <option value="336x280">336x280 (Large Rectangle)</option>
                                                <option value="100%x100">100% Width x 100px</option>
                                            </select>
                                        </div>
                                        <div v-if="form.metabet_prop_query_id" class="alert alert-success py-2 mb-0">
                                            <small><i class="bi bi-check-circle me-1"></i> Linked</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Parlay Tile Section -->
                            <div class="col-lg-4" v-if="form.metabet_widget_type === 'parlay' || form.metabet_widget_type === 'all'">
                                <div class="card h-100 border-warning">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0">
                                            <i class="bi bi-layers text-warning me-2"></i>
                                            Parlay Tile
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label">Parlay Hash/Query ID</label>
                                            <input
                                                v-model="form.metabet_parlay_query_id"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., 23f4290n2346385n2181034n"
                                            />
                                            <small class="text-muted">Use hash from Bet Hunter or comma-separated IDs</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Parlay Name (Optional)</label>
                                            <input
                                                v-model="form.metabet_parlay_name"
                                                type="text"
                                                class="form-control form-control-sm"
                                                placeholder="e.g., 3-Leg NFL Parlay"
                                            />
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Size</label>
                                            <select v-model="form.metabet_parlay_size" class="form-select form-select-sm">
                                                <option value="350x350">350x350 (Square)</option>
                                                <option value="300x400">300x400 (Portrait)</option>
                                                <option value="400x300">400x300 (Landscape)</option>
                                                <option value="100%x400">100% Width x 400px</option>
                                            </select>
                                        </div>
                                        <div v-if="form.metabet_parlay_query_id" class="alert alert-success py-2 mb-0">
                                            <small><i class="bi bi-check-circle me-1"></i> Linked</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Help Section -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6 class="mb-2"><i class="bi bi-info-circle me-1"></i> MetaBet Documentation</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <a
                                        href="https://www.metabet.io/products/prop-tiles?siteID=wewingames"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-success w-100 mb-2"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Prop Tiles Guide
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a
                                        href="https://www.metabet.io/products/parlay-tiles?siteID=wewingames"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-warning w-100 mb-2"
                                    >
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Parlay Tiles Guide
                                    </a>
                                </div>
                                <div class="col-md-4">
                                    <a href="https://www.metabet.io/documentation" target="_blank" class="btn btn-sm btn-outline-info w-100 mb-2">
                                        <i class="bi bi-book me-1"></i> Full Documentation
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Query ID Format Note -->
                        <div v-if="hasAnyMetabetId" class="mt-4 alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> Query IDs must be <strong>numeric</strong> (e.g., 615429). Get them from
                            <a href="https://www.metabet.io/products/prop-tiles?siteID=wewingames" target="_blank">MetaBet.io</a>. Test on the
                            frontend after saving.
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

                <!-- Place Bet URL Section -->
                <div class="card mt-4">
                    <div class="card-header bg-info bg-opacity-10">
                        <h5 class="mb-0">
                            <i class="bi bi-box-arrow-up-right me-2"></i>
                            External Betting Link
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <label for="place_bet_url" class="form-label">Place a Bet URL</label>
                                <input
                                    id="place_bet_url"
                                    v-model="form.place_bet_url"
                                    type="url"
                                    class="form-control"
                                    :class="{ 'is-invalid': form.errors.place_bet_url }"
                                    placeholder="https://example.com/bet"
                                />
                                <div class="form-text">
                                    Optional: Add a link to an external betting site where users can place this bet. The button will only show if this
                                    URL is provided.
                                </div>
                                <div v-if="form.errors.place_bet_url" class="invalid-feedback">
                                    {{ form.errors.place_bet_url }}
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
                        {{ form.processing ? 'Updating...' : 'Update Bet' }}
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
                                Update logo for: <strong>{{ selectedTeamForLogo.name || 'Team' }}</strong>
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

        <!-- MetaBet Lookup Modal -->
        <MetaBetLookupModal
            :show="showMetaBetModal"
            :bet-id="bet.id"
            :current-query-id="form.metabet_query_id"
            :current-game-name="form.metabet_game_name"
            :team-one="form.team_one"
            :team-two="form.team_two"
            :sport="form.sports"
            @close="showMetaBetModal = false"
            @linked="handleMetaBetLinked"
        />
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

/* Fix grey text color in Select2 dropdowns */
.select2-selection__rendered {
    color: #212529 !important; /* Bootstrap's default text color */
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #212529 !important;
}

.select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #6c757d !important; /* Keep placeholder slightly grey */
}
</style>
