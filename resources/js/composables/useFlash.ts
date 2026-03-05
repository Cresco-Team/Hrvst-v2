import { usePage } from '@inertiajs/vue3'
import { watch } from 'vue'
import { toast } from 'vue-sonner'
import type { AppPageProps, FlashType } from '@/types'

const handlers: Record<FlashType, (message: string) => void> = {
  success: (message) => toast.success(message),
  error:   (message) => toast.error(message),
  warning: (message) => toast.warning(message),
  info:    (message) => toast.info(message),
}

export function useFlash(): void {
  const page = usePage<AppPageProps>()

  watch(
    () => page.props.flash,
    (flash) => {
      if (!flash) return

      const types = Object.keys(handlers) as FlashType[]

      for (const type of types) {
        const message = flash[type]
        if (message) {
          handlers[type](message)
        }
      }
    },
    { deep: true },
  )
}
