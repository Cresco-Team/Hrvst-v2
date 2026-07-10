import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import type { FlashMessage } from '@/types'

export function useFlash() {
    const page = usePage()

    const flash = computed(() => {
        const shared = page.props as Record<string, unknown>
        return (shared.flash as FlashMessage | undefined) ?? null
    })

    return { flash }
}
