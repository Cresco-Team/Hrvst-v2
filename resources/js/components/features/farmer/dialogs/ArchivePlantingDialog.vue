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
import { Archive } from 'lucide-vue-next'
import type { Planting } from '@/types/farmer/garden'

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
                        <Archive class="size-5" />
                    </div>
                    <AlertDialogTitle>Archive Planting</AlertDialogTitle>
                </div>
                <AlertDialogDescription>
                    Are you sure you want to archive the planting for 
                    <strong>{{ planting?.variety.name }}</strong> 
                    ({{ planting?.weight_kg }} kg)?
                    <br><br>
                    Archived plantings can be deleted but cannot be edited.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <AlertDialogFooter>
                <AlertDialogCancel @click="handleCancel">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    @click="handleConfirm"
                    class="bg-orange-600 hover:bg-orange-700 dark:bg-orange-600 dark:hover:bg-orange-700"
                >
                    Archive Planting
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
