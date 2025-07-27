import '../css/app.css';
import 'bootstrap';
import * as bootstrap from 'bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { initializeTheme } from './composables/useAppearance';
import { useGoogleAnalytics } from './composables/useGoogleAnalytics';
import axios from 'axios';

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
    axios.interceptors.response.use(
        response => response,
        error => {
            if (error.response?.status === 422) {
                console.error('Validation Error Details:', {
                    url: error.config?.url,
                    method: error.config?.method,
                    data: error.config?.data,
                    errors: error.response?.data?.errors,
                    message: error.response?.data?.message
                });
            }
            return Promise.reject(error);
        }
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
        alert('Your session has expired. The page will refresh to restore your session.');
        
        // Prevent the default error modal
        event.preventDefault();
        
        // Reload the page to get a fresh CSRF token
        setTimeout(() => {
            window.location.reload();
        }, 1000);
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