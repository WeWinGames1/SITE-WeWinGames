import axios from 'axios';
import { onMounted, onUnmounted } from 'vue';

export function useSessionKeepAlive(intervalMinutes: number = 30) {
    let intervalId: NodeJS.Timeout | null = null;
    let lastActivity = Date.now();
    
    // Track user activity
    const updateActivity = () => {
        lastActivity = Date.now();
    };
    
    // Ping the server to keep session alive
    const pingSession = async () => {
        try {
            // Only ping if there was recent activity (within the last hour)
            const timeSinceActivity = Date.now() - lastActivity;
            if (timeSinceActivity < 60 * 60 * 1000) { // 1 hour
                await axios.get('/api/session/ping', {
                    headers: {
                        'X-Session-Keep-Alive': 'true'
                    }
                });
                console.log('Session kept alive');
            }
        } catch (error) {
            console.error('Failed to keep session alive:', error);
        }
    };
    
    const start = () => {
        // Set up activity listeners
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        events.forEach(event => {
            document.addEventListener(event, updateActivity, { passive: true });
        });
        
        // Initial ping
        pingSession();
        
        // Set up periodic ping
        intervalId = setInterval(pingSession, intervalMinutes * 60 * 1000);
    };
    
    const stop = () => {
        if (intervalId) {
            clearInterval(intervalId);
            intervalId = null;
        }
        
        // Remove activity listeners
        const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
        events.forEach(event => {
            document.removeEventListener(event, updateActivity);
        });
    };
    
    // Auto start/stop with component lifecycle
    onMounted(() => {
        start();
    });
    
    onUnmounted(() => {
        stop();
    });
    
    return { start, stop, pingSession };
}