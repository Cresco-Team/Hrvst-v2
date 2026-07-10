<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core'
import { computed } from 'vue'
import {
    Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog'
import {
    Drawer, DrawerContent, DrawerDescription, DrawerFooter, DrawerHeader, DrawerTitle, DrawerTrigger,
} from '@/components/ui/drawer'

interface Props {
    title?: string
    description?: string
    open?: boolean
}

const props = defineProps<Props>()
const emit = defineEmits(['update:open'])

const isDesktop = useMediaQuery('(min-width: 768px)')

const isOpen = computed({
    get: () => props.open,
    set: (val) => emit('update:open', val),
})

const Modal = computed(() => ({
    Root: isDesktop.value ? Dialog : Drawer,
    Trigger: isDesktop.value ? DialogTrigger : DrawerTrigger,
    Content: isDesktop.value ? DialogContent : DrawerContent,
    Header: isDesktop.value ? DialogHeader : DrawerHeader,
    Title: isDesktop.value ? DialogTitle : DrawerTitle,
    Description: isDesktop.value ? DialogDescription : DrawerDescription,
    Footer: isDesktop.value ? DialogFooter : DrawerFooter,
}))
</script>

<template>
    <component
        :is="Modal.Root"
        v-model:open="isOpen"
    >
        <component
            :is="Modal.Trigger"
            v-if="$slots.trigger"
            as-child
        >
            <slot name="trigger" />
        </component>

        <component
            :is="Modal.Content"
            class="sm:max-w-[425px]"
        >
            <component
                :is="Modal.Header"
                class="text-left"
            >
                <component
                    :is="Modal.Title"
                    v-if="title || $slots.title"
                >
                    <slot name="title">{{ title }}</slot>
                </component>
                <component
                    :is="Modal.Description"
                    v-if="description || $slots.description"
                >
                    <slot name="description">{{ description }}</slot>
                </component>
            </component>

            <slot />

            <component
                :is="Modal.Footer"
                v-if="$slots.footer"
                class="pt-2"
            >
                <slot name="footer" />
            </component>
        </component>
    </component>
</template>
