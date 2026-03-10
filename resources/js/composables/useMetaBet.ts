import { nextTick, onMounted, onUpdated } from 'vue';

declare global {
    interface Window {
        mb_initializeProducts?: () => void;
        metabetInitialized?: boolean;
    }
}

/**
 * Composable to handle MetaBet widget initialization
 * Call this in components that render MetaBet widgets
 */
export function useMetaBet() {
    const initializeMetaBet = () => {
        // Wait for next tick to ensure DOM is updated
        nextTick(() => {
            // Small delay to ensure MetaBet script is loaded
            setTimeout(() => {
                if (typeof window !== 'undefined' && window.mb_initializeProducts) {
                    console.log('[MetaBet] Re-initializing widgets');
                    window.mb_initializeProducts();
                }
            }, 100);
        });
    };

    // Initialize on mount and update
    onMounted(() => {
        initializeMetaBet();
    });

    onUpdated(() => {
        initializeMetaBet();
    });

    return {
        initializeMetaBet,
    };
}

/**
 * Manual initialization function - can be called directly
 */
export function reinitializeMetaBet() {
    if (typeof window !== 'undefined' && window.mb_initializeProducts) {
        console.log('[MetaBet] Manual re-initialization');
        window.mb_initializeProducts();
    }
}
