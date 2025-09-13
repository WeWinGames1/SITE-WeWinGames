import axios from 'axios';

let isRefreshing = false;
let refreshPromise: Promise<string> | null = null;

export function useCsrf() {
    const refreshCsrfToken = async (): Promise<string> => {
        // If already refreshing, wait for that promise
        if (isRefreshing && refreshPromise) {
            return refreshPromise;
        }

        isRefreshing = true;
        refreshPromise = (async () => {
            try {
                // First try to get a new CSRF cookie from Laravel Sanctum
                await axios.get('/sanctum/csrf-cookie');
                
                // Then get the new token
                const response = await axios.get('/csrf-token');
                const newToken = response.data.token;
                
                // Update axios default headers
                if (newToken) {
                    axios.defaults.headers.common['X-CSRF-TOKEN'] = newToken;
                    
                    // Update meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', newToken);
                    }
                }
                
                return newToken;
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
                throw error;
            } finally {
                isRefreshing = false;
                refreshPromise = null;
            }
        })();

        return refreshPromise;
    };

    const getCsrfToken = (): string => {
        // Try to get from meta tag first
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content') || '';
        }
        
        // Fallback to axios headers
        return axios.defaults.headers.common['X-CSRF-TOKEN'] as string || '';
    };

    return {
        refreshCsrfToken,
        getCsrfToken,
    };
}