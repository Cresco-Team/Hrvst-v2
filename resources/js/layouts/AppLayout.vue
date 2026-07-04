<script setup lang="ts">
import { watch } from 'vue'
import { toast } from 'vue-sonner'
import { Toaster } from '@/components/ui/sonner'
import OnboardingGuide from '@/components/OnboardingGuide.vue'
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
    <OnboardingGuide />
  </AppLayout>
</template>
