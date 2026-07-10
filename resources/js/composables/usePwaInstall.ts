import type { ComputedRef, Ref } from 'vue'
import { computed, onMounted, onUnmounted, ref } from 'vue'

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>
}

export type PwaInstallOutcome = 'accepted' | 'dismissed' | 'unsupported'

export type UsePwaInstallReturn = {
    canInstall: ComputedRef<boolean>
    isIos: boolean
    isInstalled: Ref<boolean>
    install: () => Promise<PwaInstallOutcome>
}

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null)
const isInstalled = ref(false)

const isIos =
    typeof window !== 'undefined' &&
    /iphone|ipad|ipod/.test(window.navigator.userAgent.toLowerCase())

function checkStandalone(): boolean {
    if (typeof window === 'undefined') return false

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as Navigator & { standalone?: boolean })
            .standalone === true
    )
}

function handleBeforeInstall(e: Event) {
    e.preventDefault()
    deferredPrompt.value = e as BeforeInstallPromptEvent
}

function handleInstalled() {
    isInstalled.value = true
    deferredPrompt.value = null
}

export function usePwaInstall(): UsePwaInstallReturn {
    const canInstall = computed(
        () => !isInstalled.value && (deferredPrompt.value !== null || isIos),
    )

    onMounted(() => {
        isInstalled.value = checkStandalone()
        window.addEventListener('beforeinstallprompt', handleBeforeInstall)
        window.addEventListener('appinstalled', handleInstalled)
    })

    onUnmounted(() => {
        window.removeEventListener('beforeinstallprompt', handleBeforeInstall)
        window.removeEventListener('appinstalled', handleInstalled)
    })

    async function install(): Promise<PwaInstallOutcome> {
        if (!deferredPrompt.value) return 'unsupported'

        await deferredPrompt.value.prompt()
        const { outcome } = await deferredPrompt.value.userChoice
        deferredPrompt.value = null

        return outcome
    }

    return { canInstall, isIos, isInstalled, install }
}
