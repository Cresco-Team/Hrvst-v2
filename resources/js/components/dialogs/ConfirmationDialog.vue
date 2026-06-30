<script setup lang="ts">
import {
	AlertDialog,
	AlertDialogAction,
	AlertDialogCancel,
	AlertDialogContent,
	AlertDialogDescription,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { Button } from '@/components/ui/button'

type ButtonVariant = 'default' | 'destructive'

type Props = {
	title: string
	description: string
	actionName?: string
	variant?: ButtonVariant
	processing?: boolean
}

const props = withDefaults(defineProps<Props>(), {
	actionName: 'Confirm',
	variant: 'default',
	processing: false,
})

const open = defineModel<boolean>('open', { default: false })

const emit = defineEmits<{
	action: []
}>()

const handleAction = () => {
	emit('action')
	open.value = false
}
</script>

<template>
	<AlertDialog v-model:open="open">
		<AlertDialogContent>
			<AlertDialogHeader>
				<AlertDialogTitle>{{ title }}</AlertDialogTitle>
				<AlertDialogDescription>{{ description }}</AlertDialogDescription>
			</AlertDialogHeader>

			<AlertDialogFooter>
				<AlertDialogCancel>Cancel</AlertDialogCancel>
				<AlertDialogAction @click="handleAction" :variant="variant" :disabled="props.processing">
					{{ actionName }}
				</AlertDialogAction>
			</AlertDialogFooter>
		</AlertDialogContent>
	</AlertDialog>
</template>
