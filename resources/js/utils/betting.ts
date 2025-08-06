// Betting utility functions

/**
 * Format a decimal place fraction to human-readable fraction string
 * @param fraction - Decimal representation of the fraction (e.g., 0.2 for 1/5)
 * @returns Human-readable fraction string (e.g., "1/5")
 */
export function formatPlaceFraction(fraction: number): string {
    if (!fraction) return '';

    // Common Each Way place fractions with their decimal values
    const fractionMap: Record<number, string> = {
        0.125: '1/8',
        0.167: '1/6',
        0.2: '1/5',
        0.25: '1/4',
        0.333: '1/3',
        0.5: '1/2',
    };

    // Check for exact matches first
    if (fractionMap[fraction]) {
        return fractionMap[fraction];
    }

    // Check for close matches (to handle floating point precision issues)
    for (const [decimal, fractionStr] of Object.entries(fractionMap)) {
        if (Math.abs(fraction - parseFloat(decimal)) < 0.001) {
            return fractionStr;
        }
    }

    // If no match found, return the decimal value
    return fraction.toFixed(3);
}

/**
 * Convert fraction string to decimal
 * @param fractionStr - Fraction string (e.g., "1/5")
 * @returns Decimal representation (e.g., 0.2)
 */
export function fractionToDecimal(fractionStr: string): number {
    if (!fractionStr) return 0;

    const fractionMap: Record<string, number> = {
        '1/8': 0.125,
        '1/6': 0.167,
        '1/5': 0.2,
        '1/4': 0.25,
        '1/3': 0.333,
        '1/2': 0.5,
    };

    return fractionMap[fractionStr] || 0;
}

/**
 * Get all available place fractions for dropdowns
 * @returns Array of fraction options with labels and values
 */
export function getPlaceFractionOptions() {
    return [
        { label: '1/5', value: 0.2 },
        { label: '1/4', value: 0.25 },
        { label: '1/3', value: 0.333 },
        { label: '1/2', value: 0.5 },
    ];
}

/**
 * Format American odds with proper + or - sign
 * @param odds - American odds value
 * @returns Formatted odds string
 */
export function formatOdds(odds: number): string {
    if (!odds) return '';
    return odds > 0 ? `+${odds}` : odds.toString();
}
