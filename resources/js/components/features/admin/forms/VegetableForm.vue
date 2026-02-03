<script setup lang="ts">
import { ref, watch } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'

interface Vegetable {
    id: number
    category_id: number
    name: string
}

interface CategoryOption {
    [id: number]: string
}

const props = defineProps<{
    open: boolean
    vegetable: Vegetable | null          // null → create mode
    categoryOptions: CategoryOption
    isSubmitting: boolean
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    submit: [payload: { category_id: number, name: string }]
}>()

/* ── local form state ── */
const form = ref({
    category_id: props.vegetable?.category_id ?? Object.keys(props.categoryOptions)[0]?.valueOf() ?? 0,
    name: props.vegetable?.name ?? '',
})

const errors = ref<{ category_id?: string, name?: string }>({})

/* reset form whenever the modal opens or the target vegetable changes */
watch(
    () => [props.open, props.vegetable],
    () => {
        form.value = {
            category_id: props.vegetable?.category_id ?? Number(Object.keys(props.categoryOptions)[0]) ?? 0,
            name: props.vegetable?.name ?? '',
        }
        errors.value = {}
    },
)

/* ── computed ── */
const isEditMode = !!props.vegetable
const title = isEditMode ? 'Edit Vegetable' : 'Add Vegetable'
const description = isEditMode
    ? 'Update the name or category of this vegetable.'
    : 'Fill in the details to register a new vegetable.'

/* ── actions ── */
function validate(): boolean {
    errors.value = {}

    if (!form.value.name.trim()) {
        errors.value.name = 'Name is required.'
    }
    if (!form.value.category_id) {
        errors.value.category_id = 'Please select a category.'
    }

    return Object.keys(errors.value).length === 0
}

function handleSubmit() {
    if (!validate()) return

    emit('submit', {
        category_id: Number(form.value.category_id),
        name: form.value.name.trim(),
    })
}

function close() {
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-4 py-2">
                <!-- Category -->
                <div class="flex flex-col gap-1.5">
                    <Label for="category_id">Category</Label>
                    <select
                        id="category_id"
                        v-model="form.category_id"
                        class="border-input bg-background placeholder:text-muted-foreground h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option disabled value="">Select a category</option>
                        <option
                            v-for="(name, id) in categoryOptions"
                            :key="id"
                            :value="id"
                        >
                            {{ name }}
                        </option>
                    </select>
                    <p v-if="errors.category_id" class="text-xs text-destructive">
                        {{ errors.category_id }}
                    </p>
                </div>

                <!-- Name -->
                <div class="flex flex-col gap-1.5">
                    <Label for="veg_name">Vegetable Name</Label>
                    <Input
                        id="veg_name"
                        v-model="form.name"
                        placeholder="e.g. Tomato"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <p v-if="errors.name" class="text-xs text-destructive">
                        {{ errors.name }}
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close">Cancel</Button>
                <Button @click="handleSubmit" :disabled="isSubmitting">
                    <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                    {{ isEditMode ? 'Save Changes' : 'Create' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
