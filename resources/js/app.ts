import 'bootstrap';
import * as bootstrap from 'bootstrap';
import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/vue3';
import axios from 'axios';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { useGoogleAnalytics } from './composables/useGoogleAnalytics';
import { useCsrf } from './composables/useCsrf';

// Make Bootstrap available globally
window.bootstrap = bootstrap;

// Debug flag to verify JS is running
(window as any).appLoaded = true;
console.log('App.ts loaded successfully');

// Configure axios defaults
if (typeof window !== 'undefined') {
    axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
    axios.defaults.withCredentials = true;
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }

    // Add global error interceptor for debugging
    const { refreshCsrfToken } = useCsrf();
    
    axios.interceptors.response.use(
        (response) => response,
        async (error) => {
            if (error.response?.status === 419) {
                // CSRF token mismatch - refresh the token and retry

                // Check if we've already tried to refresh for this request
                const retryCount = error.config._retryCount || 0;

                // Don't retry for certain URLs that should just reload
                const noRetryUrls = ['/logout', '/admin/logout'];
                const shouldReload = noRetryUrls.some((url) => error.config?.url?.includes(url));

                if (shouldReload || retryCount >= 1) {
                    console.error(`CSRF token refresh failed${retryCount > 0 ? ' after ' + retryCount + ' attempts' : ''}`);
                    // For logout or after 1 attempt, just reload
                    if (!shouldReload) {
                        alert('Your session has expired. The page will refresh.');
                    }
                    window.location.reload();
                    return Promise.reject(error);
                }

                console.warn('CSRF token expired, refreshing...');

                // Mark that we're retrying
                error.config._retryCount = retryCount + 1;

                // Try to get a fresh CSRF token
                try {
                    const newToken = await refreshCsrfToken();
                    
                    // Update the failed request with new token
                    if (newToken) {
                        error.config.headers['X-CSRF-TOKEN'] = newToken;
                    }

                    // Retry the request
                    return axios.request(error.config);
                } catch (e) {
                    console.error('Failed to refresh CSRF token:', e);
                    // If refresh fails, reload the page
                    alert('Session refresh failed. The page will reload.');
                    window.location.reload();
                    return Promise.reject(error);
                }
            } else if (error.response?.status === 422) {
                console.error('Validation Error Details:', {
                    url: error.config?.url,
                    method: error.config?.method,
                    data: error.config?.data,
                    errors: error.response?.data?.errors,
                    message: error.response?.data?.message,
                });
            }
            return Promise.reject(error);
        },
    );
}

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

const appName = import.meta.env.VITE_APP_NAME || 'We Win Games';

console.log('Initializing Inertia app...');

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        console.log('Resolving page component:', name);
        return resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue'));
    },
    setup({ el, App, props, plugin }) {
        console.log('Setting up Vue app with props:', props);
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
        console.log('Vue app mounted successfully');
        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// Track page views with Google Analytics
router.on('navigate', () => {
    const { trackPageView } = useGoogleAnalytics();
    trackPageView();
});

// Handle Inertia errors globally
router.on('error', (event) => {
    const status = event.detail.response?.status;

    if (status === 419) {
        // CSRF token mismatch
        console.warn('Inertia 419 error - attempting to refresh CSRF token');
        
        // Prevent the default error modal
        event.preventDefault();
        
        // Try to refresh the token and retry
        const { refreshCsrfToken } = useCsrf();
        refreshCsrfToken().then(() => {
            // Show a message to the user
            const message = 'Your session was refreshed. Please try your action again.';
            
            // Create a Bootstrap toast or alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-warning alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }).catch(() => {
            // If refresh fails, reload the page
            alert('Your session has expired. The page will refresh to restore your session.');
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        });
    } else if (status === 429) {
        // Rate limit error
        const data = event.detail.response?.data;
        const message = data?.message || 'Too many requests. Please try again later.';
        const retryAfter = data?.retry_after || 60;

        // Show a nice alert or toast
        alert(`${message}\n\nYou can try again in ${retryAfter} seconds.`);

        // Prevent the default error modal
        event.preventDefault();
    } else if (status === 500) {
        // Server error
        const message = event.detail.response?.data?.message || 'An error occurred while processing your request.';

        // Show error message
        alert(`Server Error: ${message}`);

        // Prevent the default error modal
        event.preventDefault();
    }
});

if ('serviceWorker' in navigator) {
    console.log('Service Worker is supported');
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js');
    });
}
