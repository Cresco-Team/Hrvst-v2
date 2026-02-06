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
import { Trash2, AlertTriangle } from 'lucide-vue-next'

interface Planting {
    id: number
    variety: {
        name: string
    }
    weight_kg: number
    date_planted: string
    can_delete: boolean
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
                    <div class="flex size-10 items-center justify-center rounded-lg bg-destructive/10 text-destructive">
                        <Trash2 class="size-5" />
                    </div>
                    <AlertDialogTitle>Delete Planting</AlertDialogTitle>
                </div>
                <AlertDialogDescription class="space-y-3">
                    <p>
                        Are you sure you want to permanently delete the planting for 
                        <strong>{{ planting?.variety.name }}</strong> 
                        ({{ planting?.weight_kg }} kg) planted on {{ planting?.date_planted }}?
                    </p>
                    
                    <div class="flex items-start gap-2 rounded-lg border border-destructive/20 bg-destructive/5 p-3">
                        <AlertTriangle class="size-4 shrink-0 text-destructive mt-0.5" />
                        <div class="text-xs text-destructive">
                            <p class="font-medium">This action cannot be undone</p>
                            <p class="mt-1">All data related to this planting will be permanently removed from the system.</p>
                        </div>
                    </div>
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="handleCancel">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    @click="handleConfirm"
                    class="bg-destructive hover:bg-destructive/90 dark:bg-destructive dark:hover:bg-destructive/90"
                >
                    Delete Permanently
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
