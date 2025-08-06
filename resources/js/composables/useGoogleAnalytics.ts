import { usePage } from '@inertiajs/vue3';

declare global {
    interface Window {
        gtag: (...args: any[]) => void;
        dataLayer: any[];
    }
}

export function useGoogleAnalytics() {
    const page = usePage();
    const tagId = page.props.env?.GOOGLE_ANALYTICS_TAG_ID;
    const isProduction = page.props.env?.APP_ENV === 'production';
    const isEnabled = !!tagId && isProduction;

    const gtag = (...args: any[]) => {
        if (typeof window !== 'undefined' && window.gtag && isEnabled) {
            window.gtag(...args);
        }
    };

    const trackEvent = (eventName: string, parameters?: Record<string, any>) => {
        if (!isEnabled) {
            console.log('[GA Debug] Event:', eventName, parameters);
            return;
        }
        gtag('event', eventName, parameters);
    };

    const trackPageView = (path?: string) => {
        if (!isEnabled) {
            console.log('[GA Debug] Page view:', path || window.location.pathname);
            return;
        }
        gtag('config', tagId, {
            page_path: path || window.location.pathname + window.location.search,
        });
    };

    const trackEcommerce = (event: string, parameters: Record<string, any>) => {
        if (!isEnabled) {
            console.log('[GA Debug] Ecommerce:', event, parameters);
            return;
        }
        gtag('event', event, parameters);
    };

    return {
        gtag,
        trackEvent,
        trackPageView,
        trackEcommerce,
        isEnabled,
    };
}
