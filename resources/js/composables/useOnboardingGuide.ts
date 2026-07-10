import { ref } from 'vue'

const isOpen = ref(false)

export function useOnboardingGuide() {
    const open = (): void => {
        isOpen.value = true
    }

    const close = (): void => {
        isOpen.value = false
    }

    return { isOpen, open, close }
}
