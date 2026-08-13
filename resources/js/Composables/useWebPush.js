import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

export function useWebPush() {
    const page = usePage();

    /**
     * Request notification permission and subscribe to Web Push if granted.
     */
    const initWebPush = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Web Push is not supported in this browser.');
            return false;
        }

        const permission = await Notification.requestPermission();
        
        if (permission !== 'granted') {
            console.log('Notification permission denied.');
            return false;
        }

        try {
            await subscribeToPush();
            return true;
        } catch (error) {
            console.error('Failed to subscribe to Web Push:', error);
            return false;
        }
    };

    const subscribeToPush = async () => {
        const registration = await navigator.serviceWorker.ready;
        
        const vapidPublicKey = page.props.vapidPublicKey;
        
        if (!vapidPublicKey) {
            throw new Error('VAPID public key not found in Inertia props.');
        }

        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey)
        });

        await sendSubscriptionToServer(subscription);
    };

    const sendSubscriptionToServer = async (subscription) => {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        
        const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

        await axios.post('/api/push-subscriptions', {
            endpoint: subscription.endpoint,
            keys: {
                p256dh: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                auth: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
            },
            contentEncoding
        });
    };

    /**
     * Helper to convert VAPID key
     */
    const urlBase64ToUint8Array = (base64String) => {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    };

    return {
        initWebPush,
    };
}
