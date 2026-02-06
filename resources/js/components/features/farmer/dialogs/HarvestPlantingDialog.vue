<script setup lang="ts">
import { ref, watch } from 'vue'
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
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { CheckCircle } from 'lucide-vue-next'

interface Planting {
    id: number
    variety: {
        name: string
    }
    weight_kg: number
}

const props = defineProps<{
    open: boolean
    planting: Planting | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    confirm: [formData: FormData]
}>()

const actualWeight = ref('')
const error = ref('')

watch(() => [props.open, props.planting], () => {
    if (props.open && props.planting) {
        actualWeight.value = props.planting.weight_kg.toString()
    } else {
        actualWeight.value = ''
    }
    error.value = ''
})

function handleConfirm() {
    error.value = ''

    if (actualWeight.value) {
        const weight = parseFloat(actualWeight.value)
        if (isNaN(weight) || weight < 0.1) {
            error.value = 'Weight must be at least 0.1 kg'
            return
        } else if (weight > 99999) {
            error.value = 'Weight cannot exceed 99,999 kg'
            return
        }
    }

    const formData = new FormData()
    if (actualWeight.value) {
        formData.append('actual_weight', actualWeight.value)
    }

    emit('confirm', formData)
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
                    <div class="flex size-10 items-center justify-center rounded-lg bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-500">
                        <CheckCircle class="size-5" />
                    </div>
                    <AlertDialogTitle>Mark as Harvested</AlertDialogTitle>
                </div>
                <AlertDialogDescription>
                    Record the harvest for <strong>{{ planting?.variety.name }}</strong>. 
                    You can update the actual harvested weight if it differs from the planted weight.
                </AlertDialogDescription>
            </AlertDialogHeader>

            <div class="flex flex-col gap-2 py-4">
                <Label for="actual_weight">Actual Harvested Weight (kg)</Label>
                <Input
                    id="actual_weight"
                    v-model="actualWeight"
                    type="number"
                    step="0.1"
                    min="0.1"
                    max="99999"
                    placeholder="Optional - leave blank to use planted weight"
                    :class="{ 'border-destructive': error }"
                />
                <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
                <p v-else class="text-xs text-muted-foreground">
                    Planted weight: {{ planting?.weight_kg }} kg
                </p>
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel @click="handleCancel">Cancel</AlertDialogCancel>
                <AlertDialogAction
                    @click="handleConfirm"
                    class="bg-green-600 hover:bg-green-700 dark:bg-green-600 dark:hover:bg-green-700"
                >
                    Confirm Harvest
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
