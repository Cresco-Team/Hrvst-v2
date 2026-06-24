<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { DollarSign, Leaf } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { VarietyResource } from '@/types/resources/product'
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Admin/Vegetable/VarietyController'

interface VarietyFormData {
    vegetable_id: string
    name: string
}

const props = defineProps<{
    open: boolean
    variety: VarietyResource | null
    parentVegetable: { id: number; name: string } | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    success: []
}>()

const isEditMode = computed(() => props.variety !== null)

const form = useForm<VarietyFormData>({
    vegetable_id: '',
    name: '',
})

watch(
    () => [props.variety, props.open, props.parentVegetable],
    () => {
        if (!props.open) return
        form.vegetable_id = props.parentVegetable?.id.toString() ?? ''
        form.name = props.variety?.name ?? ''
        form.clearErrors()
    },
)

const title = computed(() =>
    isEditMode.value ? 'Edit Variety' : 'Add New Variety',
)
const description = computed(() =>
    isEditMode.value
        ? 'Update the variety details.'
        : 'Create a new variety for a vegetable type.',
)

function handleSubmit(): void {
    if (isEditMode.value) {
        form.transform((data) => ({
            vegetable_id: data.vegetable_id,
            name: data.name,
        }))

        form.put(update({ variety: props.variety!.id }).url!, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => emit('success'),
        })
    } else {
        form.transform((data) => ({
            vegetable_id: data.vegetable_id,
            name: data.name,
        }))

        form.post(store().url, {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => emit('success'),
        })
    }
}
</script>

<template>
    <DialogForm
        :open="open"
        :form="form"
        :title="title"
        :description="description"
        :submit-label="isEditMode ? 'Save Changes' : 'Create Variety'"
        @update:open="$emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Leaf class="size-5 text-primary" />
        </template>

        <template #default>
            <div class="flex flex-col gap-5">
                <!-- Parent Vegetable — locked, derived from table context -->
                <div class="flex flex-col gap-2">
                    <Label class="flex items-center gap-1.5"
                        >Parent Vegetable</Label
                    >

                    <div
                        class="flex h-9 items-center rounded-md border border-input bg-muted/50 px-3 text-sm text-muted-foreground"
                    >
                        {{ parentVegetable?.name ?? '—' }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{
                            isEditMode
                                ? 'Parent vegetable cannot be changed after creation.'
                                : 'Variety will be created under this vegetable.'
                        }}
                    </p>
                </div>

                <!-- Variety Name -->
                <div class="flex flex-col gap-2">
                    <Label for="variety_name" class="flex items-center gap-1.5">
                        Variety Name
                        <Badge variant="secondary" class="text-xs font-normal"
                            >Required</Badge
                        >
                    </Label>
                    <Input
                        id="variety_name"
                        v-model="form.name"
                        placeholder="e.g. Cherry, Beefsteak, Romaine…"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                    <p
                        v-else-if="parentVegetable && form.name"
                        class="text-xs text-muted-foreground"
                    >
                        Full name:
                        <span class="font-medium"
                            >{{ parentVegetable.name }} {{ form.name }}</span
                        >
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        The specific type or cultivar name
                    </p>
                </div>
            </div>
        </template>
    </DialogForm>
</template>
