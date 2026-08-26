import axios from 'axios'
import { computed, onMounted, ref } from 'vue'

export type PushSupportState = 'unsupported' | 'default' | 'granted' | 'denied'

const READY_TIMEOUT_MS = 5000

function withTimeout<T>(promise: Promise<T>, ms: number, message: string): Promise<T> {
    return Promise.race([
        promise,
        new Promise<T>((_, reject) =>
            setTimeout(() => reject(new Error(message)), ms),
        ),
    ])
}

export function usePushNotifications() {
    const isSupported = 'serviceWorker' in navigator && 'PushManager' in window
    const permission = ref<NotificationPermission>(
        isSupported ? Notification.permission : 'denied',
    )
    const isSubscribed = ref(false)
    const loading = ref(true)
    const subscribing = ref(false)
    const error = ref<string | null>(null)

    const state = computed<PushSupportState>(() => {
        if (!isSupported) return 'unsupported'
        return permission.value as PushSupportState
    })

    function urlBase64ToUint8Array(base64String: string): Uint8Array {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/')
        const raw = window.atob(base64)
        return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)))
    }

    async function getReadyRegistration(): Promise<ServiceWorkerRegistration> {
        return withTimeout(
            navigator.serviceWorker.ready,
            READY_TIMEOUT_MS,
            'Service worker did not become ready in time',
        )
    }

    async function refreshSubscriptionState(): Promise<void> {
        if (!isSupported) {
            loading.value = false
            return
        }

        try {
            const registration = await getReadyRegistration()
            const subscription = await registration.pushManager.getSubscription()
            isSubscribed.value = subscription !== null
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to check subscription state'
            isSubscribed.value = false
        } finally {
            loading.value = false
        }
    }

    async function subscribe(): Promise<boolean> {
        if (!isSupported) return false

        subscribing.value = true
        error.value = null

        try {
            const result = await Notification.requestPermission()
            permission.value = result
            if (result !== 'granted') return false

            const registration = await getReadyRegistration()
            const vapidKey = import.meta.env.VITE_VAPID_PUBLIC_KEY as string

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            })

            await axios.post('/push-subscriptions', subscription.toJSON(), { timeout: 5000 })
            isSubscribed.value = true
            return true
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to subscribe'
            return false
        } finally {
            subscribing.value = false
        }
    }

    async function unsubscribe(): Promise<void> {
        if (!isSupported) return

        subscribing.value = true
        error.value = null

        try {
            const registration = await getReadyRegistration()
            const subscription = await registration.pushManager.getSubscription()
            if (!subscription) {
                isSubscribed.value = false
                return
            }

            await axios.delete('/push-subscriptions', {
                data: { endpoint: subscription.endpoint },
                timeout: 5000,
            })
            await subscription.unsubscribe()
            isSubscribed.value = false
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to unsubscribe'
        } finally {
            subscribing.value = false
        }
    }

    onMounted(refreshSubscriptionState)

    return { state, permission, isSubscribed, loading, subscribing, error, subscribe, unsubscribe }
}