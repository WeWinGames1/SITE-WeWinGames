<script setup lang="ts">
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';

const page = usePage();
const user = page.props.auth.user?.data || null;
const vapidPublicKey = page.props.env?.VAPID_PUBLIC_KEY;

const serviceWorkerStatus = ref('Checking...');
const notificationPermission = ref('Checking...');
const pushSubscription = ref<any>(null);
const subscriptionDetails = ref('');

onMounted(async () => {
    // Check service worker
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                serviceWorkerStatus.value = 'Registered ✓';
                
                // Check push subscription
                const subscription = await registration.pushManager.getSubscription();
                if (subscription) {
                    pushSubscription.value = subscription;
                    subscriptionDetails.value = JSON.stringify(subscription, null, 2);
                } else {
                    subscriptionDetails.value = 'No active push subscription';
                }
            } else {
                serviceWorkerStatus.value = 'Not registered ✗';
            }
        } catch (error) {
            serviceWorkerStatus.value = `Error: ${error}`;
        }
    } else {
        serviceWorkerStatus.value = 'Not supported in this browser';
    }
    
    // Check notification permission
    if ('Notification' in window) {
        notificationPermission.value = Notification.permission;
    } else {
        notificationPermission.value = 'Not supported';
    }
});

async function requestPermission() {
    try {
        console.log('Requesting notification permission...');
        
        if (!('Notification' in window)) {
            alert('This browser does not support desktop notifications');
            return;
        }
        
        const permission = await Notification.requestPermission();
        console.log('Permission result:', permission);
        notificationPermission.value = permission;
        
        if (permission === 'granted') {
            // Show a test notification to confirm it works
            new Notification('Notifications Enabled!', {
                body: 'You will now receive push notifications from WeWinGames',
                icon: '/images/icons/icon-192x192.png'
            });
        } else if (permission === 'denied') {
            alert('Notification permission denied. You will need to enable it in your browser settings.');
        }
    } catch (error) {
        console.error('Error requesting permission:', error);
        alert(`Error: ${error.message}`);
    }
}

async function subscribeToPush() {
    if (!vapidPublicKey) {
        alert('VAPID public key not configured!');
        return;
    }
    
    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });
        
        // Send to server
        const response = await fetch('/api/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify(subscription)
        });
        
        if (response.ok) {
            pushSubscription.value = subscription;
            subscriptionDetails.value = JSON.stringify(subscription, null, 2);
            alert('Successfully subscribed to push notifications!');
        } else {
            alert('Failed to save subscription on server');
        }
    } catch (error) {
        alert(`Subscribe error: ${error}`);
    }
}

function urlBase64ToUint8Array(base64String: string) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');
    const rawData = window.atob(base64);
    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

async function testLocalNotification() {
    try {
        if (!('Notification' in window)) {
            alert('This browser does not support notifications');
            return;
        }
        
        if (Notification.permission !== 'granted') {
            alert('Please grant notification permission first');
            return;
        }
        
        // Test basic notification
        const notification = new Notification('Test Notification', {
            body: 'This is a local test notification (not push)',
            icon: '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-96x96.png',
            vibrate: [100, 50, 100],
            requireInteraction: false
        });
        
        notification.onclick = () => {
            console.log('Notification clicked');
            window.focus();
            notification.close();
        };
    } catch (error) {
        console.error('Test notification error:', error);
        alert(`Error: ${error.message}`);
    }
}

async function testServiceWorkerNotification() {
    try {
        const registration = await navigator.serviceWorker.ready;
        await registration.showNotification('Service Worker Test', {
            body: 'This notification was shown by the service worker',
            icon: '/images/icons/icon-192x192.png',
            badge: '/images/icons/icon-96x96.png',
            vibrate: [100, 50, 100]
        });
    } catch (error) {
        console.error('Service worker notification error:', error);
        alert(`Error: ${error.message}`);
    }
}
</script>

<template>
    <AdminLayout>
        <Head title="Push Notification Debug" />
        
        <div class="container-fluid p-4">
            <h1 class="h2 mb-4">Push Notification Debug</h1>
            
            <div class="row">
                <div class="col-lg-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Configuration Status</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">VAPID Public Key:</dt>
                                <dd class="col-sm-7">
                                    <code class="small">{{ vapidPublicKey ? vapidPublicKey.substring(0, 20) + '...' : 'NOT CONFIGURED' }}</code>
                                </dd>
                                
                                <dt class="col-sm-5">Service Worker:</dt>
                                <dd class="col-sm-7">{{ serviceWorkerStatus }}</dd>
                                
                                <dt class="col-sm-5">Notification Permission:</dt>
                                <dd class="col-sm-7">
                                    <span :class="{
                                        'text-success': notificationPermission === 'granted',
                                        'text-warning': notificationPermission === 'default',
                                        'text-danger': notificationPermission === 'denied'
                                    }">
                                        {{ notificationPermission }}
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-5">User Push Preference:</dt>
                                <dd class="col-sm-7">
                                    {{ user?.notification_preferences?.push ? 'Enabled ✓' : 'Disabled ✗' }}
                                </dd>
                                
                                <dt class="col-sm-5">Browser:</dt>
                                <dd class="col-sm-7">{{ navigator.userAgent.substring(0, 50) }}...</dd>
                                
                                <dt class="col-sm-5">Protocol:</dt>
                                <dd class="col-sm-7">
                                    <span :class="{
                                        'text-success': location.protocol === 'https:',
                                        'text-warning': location.protocol === 'http:' && location.hostname === 'localhost',
                                        'text-danger': location.protocol === 'http:' && location.hostname !== 'localhost'
                                    }">
                                        {{ location.protocol }}//{{ location.hostname }}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button 
                                    @click="requestPermission"
                                    class="btn btn-primary"
                                    :disabled="notificationPermission === 'granted'"
                                >
                                    <i class="bi bi-shield-check me-2"></i>
                                    Request Notification Permission
                                </button>
                                
                                <button 
                                    @click="testLocalNotification"
                                    class="btn btn-warning"
                                    :disabled="notificationPermission !== 'granted'"
                                >
                                    <i class="bi bi-bell me-2"></i>
                                    Test Local Notification
                                </button>
                                
                                <button 
                                    @click="testServiceWorkerNotification"
                                    class="btn btn-info"
                                    :disabled="notificationPermission !== 'granted' || serviceWorkerStatus !== 'Registered ✓'"
                                >
                                    <i class="bi bi-gear me-2"></i>
                                    Test Service Worker Notification
                                </button>
                                
                                <button 
                                    @click="subscribeToPush"
                                    class="btn btn-success"
                                    :disabled="!vapidPublicKey || notificationPermission !== 'granted'"
                                >
                                    <i class="bi bi-broadcast me-2"></i>
                                    Subscribe to Push Notifications
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Push Subscription Details</h5>
                        </div>
                        <div class="card-body">
                            <pre class="bg-light p-3 rounded">{{ subscriptionDetails }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>