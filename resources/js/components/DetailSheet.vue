<script setup lang="ts">

import { ScrollArea } from '@/components/ui/scroll-area'
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetDescription } from '@/components/ui/sheet'

withDefaults(defineProps<{
    open: boolean
    title: string
    description?: string
    side?: 'right' | 'left' | 'top' | 'bottom'
}>(), {
    side: 'right',
})

defineEmits<{
    'update:open': [value: boolean]
}>()
</script>

<template>
    <Sheet :open="open" @update:open="$emit('update:open', $event)">
        <SheetContent :side="side" class="flex flex-col gap-0 p-0 sm:max-w-[480px]">
            <SheetHeader class="border-b px-6 py-4">
                <SheetTitle>{{ title }}</SheetTitle>
                <SheetDescription v-if="description">
                    {{ description }}
                </SheetDescription>
            </SheetHeader>

            <ScrollArea class="flex-1 min-h-0">
                <div class="px-6 py-4">
                    <slot />
                </div>
            </ScrollArea>

            <div v-if="$slots.footer" class="border-t px-6 py-4">
                <slot name="footer" />
            </div>
        </SheetContent>
    </Sheet>
</template>
