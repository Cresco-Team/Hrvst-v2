<script setup lang="ts">

import { Toaster } from '@/components/ui/sonner'
import { useFlash } from '@/composables/useFlash'
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
  breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
  breadcrumbs: () => [],
})

useFlash()
</script>

<template>
  <AppHeaderLayout :breadcrumbs="breadcrumbs">
    <slot />
    <Toaster richColors position="top-right" />
  </AppHeaderLayout>
</template>
