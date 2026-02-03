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

interface Vegetable {
    id: number
    name: string
    varieties_count: number
}

defineProps<{
    open: boolean
    vegetable: Vegetable | null
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
                <DialogTitle>Delete Vegetable</DialogTitle>
                <DialogDescription>
                    <template v-if="vegetable?.varieties_count && vegetable.varieties_count > 0">
                        <span class="text-destructive font-medium">
                            "{{ vegetable?.name }}" still has {{ vegetable?.varieties_count }} variety(ies).
                        </span>
                        Deletion will be blocked on the server. Remove all varieties first.
                    </template>
                    <template v-else>
                        Are you sure you want to delete
                        <span class="font-semibold">"{{ vegetable?.name }}"</span>?
                        This action cannot be undone.
                    </template>
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
