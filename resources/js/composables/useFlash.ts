import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function useFlash() {
	const page = usePage()

	const flash = computed(() => {
		const shared = page.props as Record<string, unknown>
		return shared.flash as { type?: 'success' | 'error'; message?: string } | undefined
	})

	return { flash }
}
