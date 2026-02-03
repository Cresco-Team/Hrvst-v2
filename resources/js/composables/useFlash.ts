import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useFlash() {
    const page = usePage()

    const flash = computed(() => {
        const shared = page.props as Record<string, unknown>
        return shared.flash as { type?: 'success' | 'error', message?: string } | undefined
    })

    return { flash }
}
