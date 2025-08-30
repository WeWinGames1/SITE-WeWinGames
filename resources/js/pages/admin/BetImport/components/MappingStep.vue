<template>
    <div>
        <h2 class="h3 mb-4">Map CSV Columns</h2>

        <p class="mb-4 text-dark">
            Map your CSV columns to the corresponding bet fields. We've detected some mappings automatically, but please review and adjust as needed.
        </p>

        <!-- Additional Help Text -->
        <div class="alert alert-light border mb-4">
            <h6 class="alert-heading"><i class="bi bi-lightbulb me-2"></i>Column Mapping Guide</h6>
            <ul class="mb-0 small">
                <li><strong>Sport:</strong> The sport being bet on (e.g., Baseball, Combat Sports, Golf)</li>
                <li><strong>League:</strong> The league or event name (e.g., MLB, UFC, PGA)</li>
                <li><strong>Month:</strong> Calendar month the bet is placed or settles</li>
                <li><strong>Betting Date:</strong> Date when the bet was placed (MM/DD/YYYY)</li>
                <li><strong>Game Date:</strong> Date of the actual game/event (MM/DD/YYYY)</li>
                <li><strong>Game:</strong> The specific matchup or contest (e.g., Yankees @ Red Sox, Dustin Poirier vs. Islam Makhachev)</li>
                <li><strong>Bet Type:</strong> General type of bet (Moneyline, Spread, Player Prop, etc)</li>
                <li><strong>Wager Type:</strong> Specific betting style (Straight, Outright, Each Way, Parlay)</li>
                <li>
                    <strong>Wager Name:</strong> Detailed description of the bet (e.g., "Chicago Cubs (S Imanaga) ML," "Ilia Topuria to win by KO")
                </li>
                <li><strong>odds:</strong> American odds (e.g., -120, +150)</li>
                <li><strong>membership:</strong> Membership tier (Bronze, Silver, Gold, Platinum)</li>
                <li><strong>code:</strong> Unique/internal code for tracking bet source, system, or capper (e.g., BB, TPP, Golf Brad)</li>
                <li><strong>Status:</strong> Outcome of the bet ("Won", "Lost", "Placed", "Pending")</li>
                <li><strong>ROI(net):</strong> Net Return on Investment as % of the stake</li>
                <li><strong>Wager:</strong> Dollar amount staked on the bet</li>
                <li><strong>Profits:</strong> Net gain or loss (USD) for the bet (can be negative)</li>
                <li><strong>Winning Amount:</strong> Total returned if the bet wins (Wager + Profits; $0 if lost)</li>
            </ul>
        </div>

        <!-- Special Game Column Notice -->
        <div v-if="gameColumnName" class="alert alert-info mb-4">
            <h5 class="alert-heading"><i class="bi bi-info-circle me-2"></i>{{ gameColumnName }} Column Detected</h5>
            <p class="mb-2">The "{{ gameColumnName }}" column contains the matchup information. We'll automatically parse teams from formats like:</p>
            <ul class="mb-0 small">
                <li><strong>Team Sports:</strong> "Yankees @ Red Sox" (Away @ Home)</li>
                <li><strong>Combat Sports:</strong> "Dustin Poirier vs. Islam Makhachev"</li>
                <li><strong>Individual Sports:</strong> Just the player name (e.g., "Tiger Woods")</li>
            </ul>
        </div>

        <!-- Column Mappings -->
        <div class="mb-4">
            <div class="card bg-light mb-4">
                <div class="card-body">
                    <h3 class="h5 mb-3">Required Fields</h3>
                    <div class="row g-3">
                        <div v-for="(description, field) in columnRequirements.required" :key="field" class="col-12">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label :for="`mapping-${field}`" class="form-label fw-medium">
                                        {{ formatFieldName(field) }}
                                        <span class="text-danger">*</span>
                                        <div class="small text-muted">{{ description }}</div>
                                    </label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <select
                                            :id="`mapping-${field}`"
                                            v-model="mappings[field]"
                                            class="form-select"
                                            :class="{ 'is-invalid': !mappings[field] && !staticValues[field] }"
                                        >
                                            <option value="">-- Select Column --</option>
                                            <option v-for="header in csvHeaders" :key="header" :value="header">
                                                {{ header }}
                                            </option>
                                        </select>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            :class="{ active: staticValueModes[field] }"
                                            @click="toggleStaticMode(field)"
                                            :title="staticValueModes[field] ? 'Switch to column mapping' : 'Use static value'"
                                        >
                                            <i class="bi" :class="staticValueModes[field] ? 'bi-x-lg' : 'bi-pencil-square'"></i>
                                        </button>
                                    </div>
                                    <div v-if="staticValueModes[field]" class="mt-2">
                                        <input
                                            type="text"
                                            v-model="staticValues[field]"
                                            class="form-control"
                                            :placeholder="`Enter static value for all ${formatFieldName(field)} entries`"
                                            :class="{ 'is-invalid': staticValueModes[field] && !staticValues[field] }"
                                        />
                                        <small class="text-muted">This value will be used for all rows</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="h5 mb-3">Optional Fields</h3>
                    <div class="row g-3">
                        <div v-for="(description, field) in columnRequirements.optional" :key="field" class="col-12">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label :for="`mapping-${field}`" class="form-label fw-medium">
                                        {{ formatFieldName(field) }}
                                        <div class="small text-muted">{{ description }}</div>
                                    </label>
                                </div>
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <select :id="`mapping-${field}`" v-model="mappings[field]" class="form-select">
                                            <option value="">-- Select Column --</option>
                                            <option v-for="header in csvHeaders" :key="header" :value="header">
                                                {{ header }}
                                            </option>
                                        </select>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            :class="{ active: staticValueModes[field] }"
                                            @click="toggleStaticMode(field)"
                                            :title="staticValueModes[field] ? 'Switch to column mapping' : 'Use static value'"
                                        >
                                            <i class="bi" :class="staticValueModes[field] ? 'bi-x-lg' : 'bi-pencil-square'"></i>
                                        </button>
                                    </div>
                                    <div v-if="staticValueModes[field]" class="mt-2">
                                        <input
                                            type="text"
                                            v-model="staticValues[field]"
                                            class="form-control"
                                            :placeholder="`Enter static value for all ${formatFieldName(field)} entries`"
                                        />
                                        <small class="text-muted">This value will be used for all rows</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Preview with Validation -->
        <div class="mb-4">
            <CsvPreview :headers="csvHeaders" :rows="sampleData" :mappings="transformedMappings" />
        </div>

        <!-- Validation Messages -->
        <div v-if="validationErrors.length > 0" class="alert alert-danger mb-4">
            <h4 class="alert-heading">Please fix the following issues:</h4>
            <ul class="mb-0">
                <li v-for="error in validationErrors" :key="error">{{ error }}</li>
            </ul>
        </div>

        <!-- Actions -->
        <div class="d-flex justify-content-between">
            <button @click="$emit('back')" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-2"></i>Back</button>

            <button @click="confirmMappings" :disabled="!isValid" class="btn btn-primary">
                Continue to Validation <i class="bi bi-arrow-right ms-2"></i>
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import CsvPreview from './CsvPreview.vue';

interface Props {
    csvHeaders: string[];
    detectedMappings: Record<string, string>;
    columnRequirements: {
        required: Record<string, string>;
        optional: Record<string, string>;
    };
    sampleData: any[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'mappings-confirmed': [mappings: Record<string, string>, staticValues: Record<string, string>];
    back: [];
}>();

const mappings = ref<Record<string, string>>({});
const staticValues = ref<Record<string, string>>({});
const staticValueModes = ref<Record<string, boolean>>({});

// Get the actual name of the game column if detected
const gameColumnName = computed(() => {
    // First check if there's a detected game mapping
    if (props.detectedMappings.game) {
        return props.detectedMappings.game;
    }

    // Otherwise look for a game-like column in the headers
    const gameColumn = props.csvHeaders.find((header) => {
        const normalized = header.toLowerCase().trim();
        return ['game', 'games', 'match', 'matchup', 'fixture', 'event', 'contest'].includes(normalized);
    });

    return gameColumn || null;
});

onMounted(() => {
    // Initialize with detected mappings, but filter out 'level' field
    const filteredMappings = { ...props.detectedMappings };

    // Remove 'level' field if it exists - we only use 'membership' now
    if ('level' in filteredMappings) {
        delete filteredMappings.level;
    }

    mappings.value = filteredMappings;

    // Ensure all required fields are initialized
    Object.keys(props.columnRequirements.required).forEach((field) => {
        if (!mappings.value[field]) {
            mappings.value[field] = '';
        }
    });

    // Initialize optional fields
    Object.keys(props.columnRequirements.optional).forEach((field) => {
        if (!mappings.value[field]) {
            mappings.value[field] = '';
        }
    });
});

// Transform mappings for CsvPreview component
const transformedMappings = computed(() => {
    // Create array-based mappings where index corresponds to CSV column index
    return props.csvHeaders.map((header) => {
        // Find if this header is mapped to any field
        const mappedField = Object.entries(mappings.value).find(([_, csvCol]) => csvCol === header)?.[0];
        return { field: mappedField || null };
    });
});

// Toggle between column mapping and static value mode
const toggleStaticMode = (field: string) => {
    staticValueModes.value[field] = !staticValueModes.value[field];

    // Clear the mapping when switching to static mode
    if (staticValueModes.value[field]) {
        mappings.value[field] = '';
    } else {
        // Clear static value when switching back to mapping mode
        staticValues.value[field] = '';
    }
};

// Format field names for display
const formatFieldName = (field: string): string => {
    const nameMap: Record<string, string> = {
        sport: 'Sport',
        league: 'League',
        month: 'Month',
        betting_date: 'Betting Date',
        game_date: 'Game Date',
        game: 'Game',
        home_team: 'Home Team',
        away_team: 'Away Team',
        wager_type: 'Wager Type',
        wager_name: 'Wager Name',
        odds: 'odds',
        membership: 'Membership',
        code: 'code',
        status: 'Status',
        roi: 'ROI(net)',
        wager: 'Wager',
        profits: 'Profits',
        winning_amount: 'Winning Amount',
        selection: 'Selection/Pick',
        stake: 'Stake/Wager Amount',
        operator: 'Sportsbook/Operator',
        description: 'Notes/Description',
        placed_at: 'Date Placed',
        referrer: 'Referrer/Source',
    };
    return nameMap[field] || field.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const validationErrors = computed(() => {
    const errors: string[] = [];

    // Check required fields - must have either mapping or static value
    Object.keys(props.columnRequirements.required).forEach((field) => {
        if (!mappings.value[field] && (!staticValueModes.value[field] || !staticValues.value[field])) {
            errors.push(`${formatFieldName(field)} is required (must map to a column or provide a static value)`);
        }
    });

    // Check for duplicate mappings
    const usedColumns = Object.entries(mappings.value)
        .filter(([field, value]) => value)
        .map(([field, value]) => ({ field, value }));

    const columnCounts = usedColumns.reduce(
        (acc, { field, value }) => {
            if (!acc[value]) acc[value] = [];
            acc[value].push(field);
            return acc;
        },
        {} as Record<string, string[]>,
    );

    Object.entries(columnCounts).forEach(([column, fields]) => {
        if (fields.length > 1) {
            errors.push(`Column "${column}" is mapped to multiple fields: ${fields.join(', ')}`);
        }
    });

    return errors;
});

const isValid = computed(() => validationErrors.value.length === 0);

const confirmMappings = () => {
    if (isValid.value) {
        // Only send mapped fields (exclude empty optional fields)
        const confirmedMappings = Object.entries(mappings.value)
            .filter(([_, value]) => value)
            .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {});

        // Collect static values
        const confirmedStaticValues = Object.entries(staticValues.value)
            .filter(([field, value]) => staticValueModes.value[field] && value)
            .reduce((acc, [key, value]) => ({ ...acc, [key]: value }), {});

        emit('mappings-confirmed', confirmedMappings, confirmedStaticValues);
    }
};
</script>
