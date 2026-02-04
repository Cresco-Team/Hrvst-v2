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

interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_path: string
    weeks_to_harvest: number
}

interface VegetableOptions {
    [categoryName: string]: {
        [vegetableId: number]: string
    }
}

const props = defineProps<{
    open: boolean
    variety: Variety | null
    vegetableOptions: VegetableOptions
    isSubmitting: boolean
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    submit: [payload: {
        vegetable_id: number
        name: string
        image_path: string
        weeks_to_harvest: number
    }]
}>()

/* ── local form state ── */
const form = ref({
    vegetable_id: props.variety?.vegetable_id ?? 0,
    name: props.variety?.name ?? '',
    image_path: props.variety?.image_path ?? '',
    weeks_to_harvest: props.variety?.weeks_to_harvest ?? 8,
})

const errors = ref<{
    vegetable_id?: string
    name?: string
    image_path?: string
    weeks_to_harvest?: string
}>({})

/* reset form whenever the modal opens or the target variety changes */
watch(
    () => [props.open, props.variety],
    () => {
        form.value = {
            vegetable_id: props.variety?.vegetable_id ?? 0,
            name: props.variety?.name ?? '',
            image_path: props.variety?.image_path ?? '',
            weeks_to_harvest: props.variety?.weeks_to_harvest ?? 8,
        }
        errors.value = {}
    },
)

/* ── computed ── */
const isEditMode = !!props.variety
const title = isEditMode ? 'Edit Variety' : 'Add Variety'
const description = isEditMode
    ? 'Update the details of this variety.'
    : 'Fill in the details to register a new variety.'

/* ── actions ── */
function validate(): boolean {
    errors.value = {}

    if (!form.value.name.trim()) {
        errors.value.name = 'Name is required.'
    }
    if (!form.value.vegetable_id) {
        errors.value.vegetable_id = 'Please select a vegetable.'
    }
    if (!form.value.image_path.trim()) {
        errors.value.image_path = 'Image path is required.'
    }
    if (form.value.weeks_to_harvest < 1 || form.value.weeks_to_harvest > 52) {
        errors.value.weeks_to_harvest = 'Must be between 1 and 52 weeks.'
    }

    return Object.keys(errors.value).length === 0
}

function handleSubmit() {
    if (!validate()) return

    emit('submit', {
        vegetable_id: Number(form.value.vegetable_id),
        name: form.value.name.trim(),
        image_path: form.value.image_path.trim(),
        weeks_to_harvest: Number(form.value.weeks_to_harvest),
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
                <!-- Vegetable Selection -->
                <div class="flex flex-col gap-1.5">
                    <Label for="vegetable_id">Vegetable</Label>
                    <select
                        id="vegetable_id"
                        v-model="form.vegetable_id"
                        class="border-input bg-background placeholder:text-muted-foreground h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]"
                    >
                        <option disabled :value="0">Select a vegetable</option>
                        <optgroup 
                            v-for="(vegetables, category) in vegetableOptions" 
                            :key="category"
                            :label="category"
                        >
                            <option
                                v-for="(name, id) in vegetables"
                                :key="id"
                                :value="id"
                            >
                                {{ name }}
                            </option>
                        </optgroup>
                    </select>
                    <p v-if="errors.vegetable_id" class="text-xs text-destructive">
                        {{ errors.vegetable_id }}
                    </p>
                </div>

                <!-- Variety Name -->
                <div class="flex flex-col gap-1.5">
                    <Label for="variety_name">Variety Name</Label>
                    <Input
                        id="variety_name"
                        v-model="form.name"
                        placeholder="e.g. Cherry, Beefsteak"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <p v-if="errors.name" class="text-xs text-destructive">
                        {{ errors.name }}
                    </p>
                </div>

                <!-- Image Path -->
                <div class="flex flex-col gap-1.5">
                    <Label for="image_path">Image Path</Label>
                    <Input
                        id="image_path"
                        v-model="form.image_path"
                        placeholder="varieties/tomato-cherry.jpg"
                        :class="{ 'border-destructive': errors.image_path }"
                    />
                    <p v-if="errors.image_path" class="text-xs text-destructive">
                        {{ errors.image_path }}
                    </p>
                </div>

                <!-- Weeks to Harvest -->
                <div class="flex flex-col gap-1.5">
                    <Label for="weeks_to_harvest">Weeks to Harvest</Label>
                    <Input
                        id="weeks_to_harvest"
                        v-model.number="form.weeks_to_harvest"
                        type="number"
                        min="1"
                        max="52"
                        :class="{ 'border-destructive': errors.weeks_to_harvest }"
                    />
                    <p v-if="errors.weeks_to_harvest" class="text-xs text-destructive">
                        {{ errors.weeks_to_harvest }}
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