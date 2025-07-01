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

    const gtag = (...args: any[]) => {
        if (typeof window !== 'undefined' && window.gtag && tagId) {
            window.gtag(...args);
        }
    };

    const trackEvent = (eventName: string, parameters?: Record<string, any>) => {
        gtag('event', eventName, parameters);
    };

    const trackPageView = (path?: string) => {
        gtag('config', tagId, {
            page_path: path || window.location.pathname + window.location.search,
        });
    };

    const trackEcommerce = (event: string, parameters: Record<string, any>) => {
        gtag('event', event, parameters);
    };

    return {
        gtag,
        trackEvent,
        trackPageView,
        trackEcommerce,
        isEnabled: !!tagId,
    };
}