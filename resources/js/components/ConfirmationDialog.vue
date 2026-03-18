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
} from './ui/alert-dialog'
import { buttonVariants } from './ui/button'

type ButtonVariant = 'default' | 'destructive'

type Props = {
	title: string
	description: string
	actionName?: string
	variant?: ButtonVariant
}

const props = withDefaults(defineProps<Props>(), {
	actionName: 'Confirm',
	variant: 'default',
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
        <AlertDialogAction @click="handleAction" :class="buttonVariants({ variant: props.variant })">
          {{ actionName }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>