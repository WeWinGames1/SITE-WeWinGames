import { computed, onMounted, ref } from 'vue';

/**
 * Composable for processing Elfsight widget shortcodes in content.
 *
 * Transforms {elfsight UUID} shortcodes into proper Elfsight widget divs
 * and dynamically loads the Elfsight platform script when widgets are present.
 *
 * Usage in content: {elfsight 80550162-ade3-4967-9296-3ebecb119bd7}
 * Output: <div class="elfsight-app-80550162-ade3-4967-9296-3ebecb119bd7" data-elfsight-app-lazy></div>
 */
export function useElfsight(content: string | null | undefined) {
    const scriptLoaded = ref(false);

    // Regex to match {elfsight UUID} pattern
    // UUID format: 8-4-4-4-12 hexadecimal characters
    const elfsightPattern = /\{elfsight\s+([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\}/gi;

    // Check if content contains any elfsight shortcodes
    const hasElfsightWidgets = computed(() => {
        if (!content) return false;
        return elfsightPattern.test(content);
    });

    // Process content and replace shortcodes with actual widget divs
    const processedContent = computed(() => {
        if (!content) return '';

        return content.replace(elfsightPattern, (_match, uuid) => {
            return `<div class="elfsight-app-${uuid}" data-elfsight-app-lazy></div>`;
        });
    });

    // Load Elfsight platform script dynamically
    const loadElfsightScript = () => {
        if (scriptLoaded.value) return;
        if (typeof window === 'undefined') return;

        // Check if script already exists
        const existingScript = document.querySelector('script[src*="elfsightcdn.com/platform.js"]');
        if (existingScript) {
            scriptLoaded.value = true;
            return;
        }

        const script = document.createElement('script');
        script.src = 'https://static.elfsight.com/platform/platform.js';
        script.async = true;
        script.onload = () => {
            scriptLoaded.value = true;
        };
        document.head.appendChild(script);
    };

    // Automatically load script on mount if widgets are present
    onMounted(() => {
        // Re-test pattern since computed might have been evaluated
        if (content && elfsightPattern.test(content)) {
            loadElfsightScript();
        }
    });

    return {
        hasElfsightWidgets,
        processedContent,
        scriptLoaded,
        loadElfsightScript,
    };
}
