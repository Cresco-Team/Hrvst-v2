<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

interface Variety {
    id: number
    name: string
    vegetable: {
        name: string
    }
}

defineProps<{
    open: boolean
    variety: Variety | null
}>()

defineEmits<{
    'update:open': [value: boolean]
    confirm: []
}>()
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Delete Variety</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete
                    <span class="font-semibold">"{{ variety?.vegetable.name }} {{ variety?.name }}"</span>?
                    This action cannot be undone and will remove all associated price history.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter>
                <Button variant="outline" @click="$emit('update:open', false)">Cancel</Button>
                <Button variant="destructive" @click="$emit('confirm')">
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>