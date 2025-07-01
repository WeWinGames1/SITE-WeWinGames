import { usePage } from '@inertiajs/vue3';

declare global {
    interface Window {
        dataLayer: any[];
    }
}

export function useGoogleTagManager() {
    const page = usePage();
    const containerId = page.props.env?.GOOGLE_TAG_MANAGER_ID;

    const pushToDataLayer = (data: Record<string, any>) => {
        if (typeof window !== 'undefined' && window.dataLayer && containerId) {
            window.dataLayer.push(data);
        }
    };

    const trackEvent = (eventName: string, parameters?: Record<string, any>) => {
        pushToDataLayer({
            event: eventName,
            ...parameters,
        });
    };

    const trackEcommerce = (eventType: string, data: Record<string, any>) => {
        pushToDataLayer({
            event: eventType,
            ecommerce: data,
        });
    };

    const trackUserData = (userData: Record<string, any>) => {
        pushToDataLayer({
            event: 'user_data',
            user: userData,
        });
    };

    const trackPageView = (pageData?: Record<string, any>) => {
        pushToDataLayer({
            event: 'page_view',
            page: {
                path: window.location.pathname,
                url: window.location.href,
                title: document.title,
                ...pageData,
            },
        });
    };

    return {
        pushToDataLayer,
        trackEvent,
        trackEcommerce,
        trackUserData,
        trackPageView,
        isEnabled: !!containerId,
    };
}