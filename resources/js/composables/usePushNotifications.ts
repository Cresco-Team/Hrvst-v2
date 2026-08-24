import { push } from "@/routes/subscription"

export function usePushNotifications() {
    async function subscribe(): Promise<boolean> {
        const permission = await Notification.requestPermission()
        if (permission !== 'granted') return false

        const registration = await navigator.serviceWorker.ready
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: import.meta.env.VITE_VAPID_PUBLIC_KEY,
        })

        await push('/push-subscriptions', subscription.toJSON())
        return true
    }

    return { subscribe }
}