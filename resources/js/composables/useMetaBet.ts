import { onMounted } from 'vue';

declare global {
    interface Window {
        mb_initializeProducts?: () => void;
        metabetInitialized?: boolean;
        metabetInitPending?: number | null;
    }
}

/**
 * Clear all MetaBet widget content to prevent duplicates
 * MetaBet's mb_initializeProducts() appends content, so we must clear first
 */
function clearMetaBetWidgets() {
    const widgets = document.querySelectorAll('[class*="metabet-sideoddstile"], [class*="metabet-gametile"]');
    widgets.forEach((widget) => {
        widget.innerHTML = '';
    });
}

/**
 * Composable to handle MetaBet widget initialization
 * Call this in components that render MetaBet widgets
 * Uses debouncing to prevent multiple rapid calls
 */
export function useMetaBet() {
    const initializeMetaBet = () => {
        if (typeof window === 'undefined') return;

        // Clear any pending initialization to debounce
        if (window.metabetInitPending) {
            clearTimeout(window.metabetInitPending);
        }

        // Debounce: wait 300ms after the last call before actually initializing
        window.metabetInitPending = window.setTimeout(() => {
            if (window.mb_initializeProducts) {
                // Clear existing content BEFORE initializing to prevent duplicates
                clearMetaBetWidgets();
                console.log('[MetaBet] Initializing widgets');
                window.mb_initializeProducts();
            }
            window.metabetInitPending = null;
        }, 300);
    };

    // Initialize only on mount - not on update to prevent duplicate content
    onMounted(() => {
        initializeMetaBet();
    });

    return {
        initializeMetaBet,
    };
}

/**
 * Manual initialization function - can be called directly
 * Clears existing widget content before re-initializing to prevent duplicates
 */
export function reinitializeMetaBet() {
    if (typeof window === 'undefined') return;

    // Clear any pending initialization
    if (window.metabetInitPending) {
        clearTimeout(window.metabetInitPending);
    }

    // Debounce the re-initialization
    window.metabetInitPending = window.setTimeout(() => {
        if (window.mb_initializeProducts) {
            // Clear existing content BEFORE initializing to prevent duplicates
            clearMetaBetWidgets();
            console.log('[MetaBet] Manual re-initialization');
            window.mb_initializeProducts();
        }
        window.metabetInitPending = null;
    }, 300);
}
