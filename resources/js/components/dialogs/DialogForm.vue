<script setup lang="ts" generic="TData extends object">
import type { InertiaForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { useResponsiveDialog } from '@/composables/useResponsiveDialog'

interface Props {
	open: boolean
	title: string
	description?: string
	form: InertiaForm<TData>
	submitLabel?: string
	cancelLabel?: string
	maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
	showFooter?: boolean
	resetOnClose?: boolean
}

const props = withDefaults(defineProps<Props>(), {
	submitLabel: 'Submit',
	cancelLabel: 'Cancel',
	maxWidth: 'lg',
	showFooter: true,
	resetOnClose: true,
})

const emit = defineEmits<{
	'update:open': [value: boolean]
	submit: []
}>()

const { isDesktop, Modal } = useResponsiveDialog()

const isOpen = computed({
	get: () => props.open,
	set: (val: boolean) => emit('update:open', val),
})

watch(isOpen, (open) => {
	if (open) return
	if (props.resetOnClose) props.form.reset()
	props.form.clearErrors()
})

function handleClose() {
	if (props.form.processing) return
	isOpen.value = false
}

function handleSubmit() {
	emit('submit')
}

const maxWidthClass = computed(() => {
	if (!isDesktop.value) return ''
	const widths = {
		sm: 'sm:max-w-sm', md: 'sm:max-w-md', lg: 'sm:max-w-lg', xl: 'sm:max-w-xl', '2xl': 'sm:max-w-2xl',
	}
	return widths[props.maxWidth]
})
</script>

<template>
    <component
        :is="Modal.Root"
        v-model:open="isOpen"
    >
        <component
            :is="Modal.Content"
            class="flex max-h-[85vh] flex-col gap-0 p-0"
            :class="maxWidthClass"
        >
            <component
                :is="Modal.Header"
                class="space-y-2 border-b px-6 py-4"
                :class="{ 'text-left': isDesktop }"
            >
                <component
                    :is="Modal.Title"
                    class="flex items-center gap-2"
                >
                    <slot name="icon" />
                    {{ title }}
                </component>
                <component
                    :is="Modal.Description"
                    v-if="description || $slots.description"
                >
                    <slot name="description">{{ description }}</slot>
                </component>
            </component>

            <div class="flex-1 overflow-y-auto px-6 py-4">
                <slot
                    :errors="form.errors"
                    :processing="form.processing"
                />
            </div>

            <component
                :is="Modal.Footer"
                v-if="showFooter"
                class="border-t px-6 py-4"
                :class="{ 'pt-2': !isDesktop }"
            >
                <div class="flex w-full gap-2 sm:justify-end">
                    <slot
                        name="footer-actions"
                        :form="form"
                    >
                        <Button
                            variant="outline"
                            :disabled="form.processing"
                            @click="handleClose"
                        >{{ cancelLabel }}</Button>
                        <Button
                            :disabled="form.processing"
                            @click="handleSubmit"
                        >
                            <Spinner
                                v-if="form.processing"
                                class="mr-2 size-4"
                            />
                            {{ submitLabel }}
                        </Button>
                    </slot>
                </div>
            </component>
        </component>
    </component>
</template>
