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

    /**
     * X hashes email_address / phone_number in the browser, so they have to be
     * normalized the same way the server normalizes them before hashing for the
     * Conversion API — otherwise the two events hash to different values and
     * describe two different people. Phone must be E.164 with a leading "+";
     * a bare 10-digit number is assumed US.
     */
    const normalizeParameters = (parameters?: Record<string, any>) => {
        const normalized: Record<string, any> = { ...(parameters || {}) };

        const email = typeof normalized.email_address === 'string' ? normalized.email_address.trim().toLowerCase() : '';
        if (email) {
            normalized.email_address = email;
        } else {
            delete normalized.email_address;
        }

        const digits = typeof normalized.phone_number === 'string' ? normalized.phone_number.replace(/\D/g, '') : '';
        if (digits.length >= 10) {
            normalized.phone_number = `+${digits.length === 10 ? `1${digits}` : digits}`;
        } else {
            delete normalized.phone_number;
        }

        return normalized;
    };

    const track = (eventId: string | undefined, parameters?: Record<string, any>) => {
        if (!eventId) return;

        const payload = normalizeParameters(parameters);

        if (!isEnabled) {
            console.log('[X Pixel Debug] Event:', eventId, payload);
            return;
        }

        if (typeof window !== 'undefined' && typeof window.twq === 'function') {
            window.twq('event', eventId, payload);
        }
    };

    const trackContentView = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_CONTENT_VIEW, parameters);

    const trackCheckoutInitiated = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_CHECKOUT_INITIATED, parameters);

    const trackSignup = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_SIGNUP, parameters);

    const trackPurchase = (parameters?: Record<string, any>) => track(env.TWITTER_EVENT_PURCHASE, parameters);

    return {
        isEnabled,
        track,
        trackContentView,
        trackCheckoutInitiated,
        trackSignup,
        trackPurchase,
    };
}
