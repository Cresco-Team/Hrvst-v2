<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/components/ui/alert-dialog'
import { XCircle } from 'lucide-vue-next'

interface Planting {
    id: number
    variety: {
        name: string
    }
    date_planted: string
}

defineProps<{
    open: boolean
    planting: Planting | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    confirm: []
}>()

function handleConfirm() {
    emit('confirm')
}

function handleCancel() {
    emit('update:open', false)
}
</script>

<template>
    <AlertDialog :open="open" @update:open="handleCancel">
        <AlertDialogContent>
            <AlertDialogHeader>
                <div class="flex items-center gap-2">
                    <div class="flex size-10 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-500">
                        <XCircle class="size-5" />
                    </div>
                    <AlertDialogTitle>Cancel Planting</AlertDialogTitle>
                </div>
                <AlertDialogDescription>
                    Are you sure you want to cancel the planting for 
                    <strong>{{ planting?.variety.name }}</strong> 
                    planted on {{ planting?.date_planted }}?
                    <br><br>
                    This will mark the planting as cancelled and it will no longer appear in your active plantings.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="handleCancel">Keep Planting</AlertDialogCancel>
                <AlertDialogAction
                    @click="handleConfirm"
                    class="bg-orange-600 hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-700"
                >
                    Cancel Planting
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
