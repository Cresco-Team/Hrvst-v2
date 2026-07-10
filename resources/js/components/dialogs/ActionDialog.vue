<script setup lang="ts">
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog'

type Props = {
    open: boolean
    title: string
    description: string
}

defineProps<Props>()

defineEmits<{
    'update:open': [value: boolean]
}>()
</script>

<template>
    <Dialog
        :open="open"
        @update:open="$emit('update:open', $event)"
    >
        <DialogContent class="flex max-h-[85vh] flex-col gap-0 p-0">
            <DialogHeader class="space-y-2 border-b px-6 py-4">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <slot />
            </div>

            <DialogFooter class="border-t px-6 py-4">
                <div class="flex w-full gap-2 sm:justify-end">
                    <slot name="footer-actions" />
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>