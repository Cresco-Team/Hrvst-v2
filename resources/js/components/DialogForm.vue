<script setup lang="ts" generic="TData">

import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import { Spinner } from '@/components/ui/spinner'

interface Props {
    open: boolean
    title: string
    description?: string
    isSubmitting?: boolean
    submitLabel?: string
    cancelLabel?: string
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
    showFooter?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    isSubmitting: false,
    submitLabel: 'Submit',
    cancelLabel: 'Cancel',
    maxWidth: 'lg',
    showFooter: true,
})

const emit = defineEmits<{
    'update:open': [value: boolean]
    submit: []
}>()

function handleClose() {
    if (props.isSubmitting) return
    emit('update:open', false)
}

function handleSubmit() {
    emit('submit')
}

const maxWidthClass = computed(() => {
    const widths = {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }
    return widths[props.maxWidth]
})
</script>

<template>
    <Dialog :open="open" @update:open="handleClose">
        <DialogContent 
            class="flex max-h-[85vh] flex-col gap-0 p-0"
            :class="maxWidthClass"
        >
            <!-- Fixed Header -->
            <DialogHeader class="space-y-2 border-b px-6 py-4">
                <DialogTitle class="flex items-center gap-2">
                    <slot name="icon" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription v-if="description || $slots.description">
                    <slot name="description">
                        {{ description }}
                    </slot>
                </DialogDescription>
            </DialogHeader>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
                <slot />
            </div>

            <!-- Fixed Footer -->
            <DialogFooter v-if="showFooter" class="border-t px-6 py-4">
                <div class="flex w-full gap-2 sm:justify-end">
                    <slot name="footer-actions">
                        <Button 
                            variant="outline" 
                            :disabled="isSubmitting"
                            @click="handleClose"
                        >
                            {{ cancelLabel }}
                        </Button>
                        <Button 
                            :disabled="isSubmitting"
                            @click="handleSubmit"
                        >
                            <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                            {{ submitLabel }}
                        </Button>
                    </slot>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
