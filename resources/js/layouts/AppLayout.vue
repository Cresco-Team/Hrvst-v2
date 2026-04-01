<script setup lang="ts">
import { watch } from 'vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import { useFlash } from '@/composables/useFlash'
import AppLayout from '@/layouts/app/AppHeaderLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
  breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
})

const { flash } = useFlash()

watch(
  () => flash.value,
  (newFlash) => {
    // 'pin' type is handled by the page that triggers it — never toast a PIN
    if (!newFlash?.message || newFlash.type === 'pin') return

    if (newFlash.type === 'error') {
      toast.error(newFlash.message)
    } else {
      toast.success(newFlash.message)
    }
  },
  { deep: true, immediate: true },
)
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <slot />
    <Toaster richColors position="top-right" />
  </AppLayout>
</template>
