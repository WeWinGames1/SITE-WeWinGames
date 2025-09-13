import { router, useForm as useInertiaForm } from '@inertiajs/vue3';
import { useCsrf } from './useCsrf';

export function useForm<T extends Record<string, any>>(data: T) {
    const form = useInertiaForm(data);
    const { refreshCsrfToken } = useCsrf();
    
    // Store the original methods
    const originalPost = form.post.bind(form);
    const originalPut = form.put.bind(form);
    const originalPatch = form.patch.bind(form);
    const originalDelete = form.delete.bind(form);
    
    // Override methods to refresh CSRF token before submission
    form.post = async (url: string, options?: any) => {
        try {
            await refreshCsrfToken();
        } catch (error) {
            console.warn('Failed to refresh CSRF token before form submission:', error);
        }
        return originalPost(url, options);
    };
    
    form.put = async (url: string, options?: any) => {
        try {
            await refreshCsrfToken();
        } catch (error) {
            console.warn('Failed to refresh CSRF token before form submission:', error);
        }
        return originalPut(url, options);
    };
    
    form.patch = async (url: string, options?: any) => {
        try {
            await refreshCsrfToken();
        } catch (error) {
            console.warn('Failed to refresh CSRF token before form submission:', error);
        }
        return originalPatch(url, options);
    };
    
    form.delete = async (url: string, options?: any) => {
        try {
            await refreshCsrfToken();
        } catch (error) {
            console.warn('Failed to refresh CSRF token before form submission:', error);
        }
        return originalDelete(url, options);
    };
    
    return form;
}

// Also add a helper to refresh CSRF token periodically for long forms
export function usePeriodicCsrfRefresh(intervalMinutes: number = 15) {
    const { refreshCsrfToken } = useCsrf();
    let intervalId: NodeJS.Timeout | null = null;
    
    const start = () => {
        // Initial refresh
        refreshCsrfToken().catch(console.error);
        
        // Set up periodic refresh
        intervalId = setInterval(() => {
            refreshCsrfToken().catch(console.error);
        }, intervalMinutes * 60 * 1000);
    };
    
    const stop = () => {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
    };
    
    // Auto cleanup on page navigation
    router.on('before', () => {
        stop();
    });
    
    return { start, stop };
}