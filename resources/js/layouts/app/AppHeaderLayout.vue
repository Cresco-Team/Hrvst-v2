<script setup lang="ts">
import AppContent from '@/components/layout/AppContent.vue'
import AppHeader from '@/components/layout/AppHeader.vue'
import AppShell from '@/components/layout/AppShell.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
    breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})
</script>

<template>
    <AppShell class="flex-col">
        <AppHeader :breadcrumbs="breadcrumbs" />
        <AppContent>
            <slot />
        </AppContent>
    </AppShell>
</template>
