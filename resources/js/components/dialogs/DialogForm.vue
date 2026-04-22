<script setup lang="ts" generic="TData extends Record<string, unknown>">
import { computed } from 'vue'
import type { InertiaForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog'
import { Spinner } from '@/components/ui/spinner'

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
	submit: [form: InertiaForm<TData>]
}>()

function handleClose() {
	if (props.form.processing) return
	if (props.resetOnClose) props.form.reset()
	props.form.clearErrors()
	emit('update:open', false)
}

function handleSubmit() {
	emit('submit', props.form)
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
		<DialogContent class="flex max-h-[85vh] flex-col gap-0 p-0" :class="maxWidthClass">
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

			<!-- Scrollable Content — exposes errors and processing to field components -->
			<div class="flex-1 overflow-y-auto px-6 py-4">
				<slot :errors="form.errors" :processing="form.processing" />
			</div>

			<!-- Fixed Footer -->
			<DialogFooter v-if="showFooter" class="border-t px-6 py-4">
				<div class="flex w-full gap-2 sm:justify-end">
					<slot name="footer-actions" :form="form">
						<Button variant="outline" :disabled="form.processing" @click="handleClose">
							{{ cancelLabel }}
						</Button>
						<Button :disabled="form.processing" @click="handleSubmit">
							<Spinner v-if="form.processing" class="mr-2 size-4" />
							{{ submitLabel }}
						</Button>
					</slot>
				</div>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
