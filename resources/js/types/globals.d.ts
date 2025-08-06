import type { route as routeFn } from 'ziggy-js';

declare global {
    const route: typeof routeFn;

    interface TurnstileConfig {
        enabled: boolean;
        siteKey: string;
    }

    interface TurnstileInstance {
        render(
            element: string,
            options: {
                sitekey: string;
                callback?: (token: string) => void;
                'expired-callback'?: () => void;
                'error-callback'?: () => void;
                theme?: 'light' | 'dark' | 'auto';
                size?: 'normal' | 'compact';
            },
        ): string;
        reset(widgetId: string): void;
        remove(widgetId: string): void;
        getResponse(widgetId: string): string;
    }

    interface Window {
        turnstileConfig?: TurnstileConfig;
        turnstile?: TurnstileInstance;
    }
}
