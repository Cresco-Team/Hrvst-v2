<script setup lang="ts">
import AppLayout from '@/layouts/app/AppHeaderLayout.vue'
import { Toaster } from '@/components/ui/sonner'
import { toast } from 'vue-sonner' // Import the trigger function
import { useFlash } from '@/composables/useFlash' // Your existing composable
import { watch } from 'vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
    breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

const { flash } = useFlash()

watch(() => flash.value, (newFlash) => {
    if (newFlash?.message) {
        if (newFlash.type === 'error') {
            toast.error(newFlash.message)
        } else {
            toast.success(newFlash.message)
        }
    }
}, { deep: true, immediate: true })
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
        <Toaster richColors position="top-right" />
    </AppLayout>
</template>
