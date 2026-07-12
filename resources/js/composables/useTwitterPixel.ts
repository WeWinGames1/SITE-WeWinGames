import { usePage } from '@inertiajs/vue3';

declare global {
    interface Window {
        twq: (...args: any[]) => void;
    }
}

/**
 * Composable for firing X (Twitter) Ads conversion events.
 *
 * The base pixel is injected server-side in partials/tracking-head.blade.php
 * (production only). Event IDs are configured per conversion in the X Ads
 * Events Manager and surfaced here via the shared `env` Inertia prop.
 *
 * Usage:
 *   const { trackSignup, trackPurchase } = useTwitterPixel();
 *   trackPurchase({ value: 65, currency: 'USD' });
 */
export function useTwitterPixel() {
    const page = usePage();
    const env = (page.props.env ?? {}) as Record<string, string | undefined>;
    const pixelId = env.TWITTER_PIXEL_ID;
    const isProduction = env.APP_ENV === 'production';
    const isEnabled = !!pixelId && isProduction;

    const track = (eventId: string | undefined, parameters?: Record<string, any>) => {
        if (!eventId) return;

        if (!isEnabled) {
            console.log('[X Pixel Debug] Event:', eventId, parameters);
            return;
        }

        if (typeof window !== 'undefined' && typeof window.twq === 'function') {
            window.twq('event', eventId, parameters || {});
        }
    };

    const trackSignup = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_SIGNUP, parameters);

    const trackPurchase = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_PURCHASE, parameters);

    return {
        isEnabled,
        track,
        trackSignup,
        trackPurchase,
    };
}
