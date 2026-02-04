<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import { Badge } from '@/components/ui/badge'
import { Leaf, Clock } from 'lucide-vue-next'
import ImageUpload from '@/components/shared/media/ImageUpload.vue'

interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_url?: string
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
    submit: [payload: FormData]
}>()

/* ── local form state ── */
const form = ref({
    vegetable_id: '',
    name: '',
    image: null as File | null,
    weeks_to_harvest: 8,
})

const errors = ref<{
    vegetable_id?: string
    name?: string
    image?: string
    weeks_to_harvest?: string
}>({})

/* reset form whenever the modal opens or the target variety changes */
watch(
    () => [props.open, props.variety],
    () => {
        form.value = {
            vegetable_id: props.variety?.vegetable_id?.toString() ?? '',
            name: props.variety?.name ?? '',
            image: null,
            weeks_to_harvest: props.variety?.weeks_to_harvest ?? 8,
        }
        errors.value = {}
    },
)

/* ── computed ── */
const isEditMode = computed(() => !!props.variety)
const title = computed(() => isEditMode.value ? 'Edit Variety' : 'Add New Variety')
const description = computed(() => 
    isEditMode.value
        ? 'Update the details of this variety.'
        : 'Create a new variety for a vegetable type.'
)

const selectedVegetableName = computed(() => {
    if (!form.value.vegetable_id) return null
    
    for (const [category, vegetables] of Object.entries(props.vegetableOptions)) {
        const vegName = vegetables[Number(form.value.vegetable_id)]
        if (vegName) return vegName
    }
    return null
})

const existingImageUrl = computed(() => props.variety?.image_url ?? null)

/* ── actions ── */
function validate(): boolean {
    errors.value = {}

    if (!form.value.name.trim()) {
        errors.value.name = 'Variety name is required'
    }
    if (!form.value.vegetable_id) {
        errors.value.vegetable_id = 'Please select a parent vegetable'
    }
    if (!isEditMode.value && !form.value.image) {
        errors.value.image = 'Image is required for new varieties'
    }
    if (form.value.weeks_to_harvest < 1 || form.value.weeks_to_harvest > 52) {
        errors.value.weeks_to_harvest = 'Must be between 1 and 52 weeks'
    }

    return Object.keys(errors.value).length === 0
}

function handleSubmit() {
    if (!validate()) return

    // Create FormData for file upload
    const formData = new FormData()
    formData.append('vegetable_id', form.value.vegetable_id)
    formData.append('name', form.value.name.trim())
    formData.append('weeks_to_harvest', form.value.weeks_to_harvest.toString())
    
    if (form.value.image) {
        formData.append('image', form.value.image)
    }

    emit('submit', formData)
}

function close() {
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Leaf class="size-5 text-primary" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="flex flex-col gap-5 py-4">
                <!-- Parent Vegetable Selection -->
                <div class="flex flex-col gap-2">
                    <Label for="vegetable_id" class="flex items-center gap-1.5">
                        Parent Vegetable
                        <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Select v-model="form.vegetable_id">
                        <SelectTrigger 
                            id="vegetable_id"
                            :class="{ 'border-destructive': errors.vegetable_id }"
                        >
                            <SelectValue placeholder="Select the vegetable type..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup 
                                v-for="(vegetables, category) in vegetableOptions" 
                                :key="category"
                            >
                                <SelectLabel>{{ category }}</SelectLabel>
                                <SelectItem
                                    v-for="(name, id) in vegetables"
                                    :key="id"
                                    :value="id.toString()"
                                >
                                    {{ name }}
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.vegetable_id" class="text-xs text-destructive">
                        {{ errors.vegetable_id }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Choose the vegetable this variety belongs to
                    </p>
                </div>

                <!-- Variety Name -->
                <div class="flex flex-col gap-2">
                    <Label for="variety_name" class="flex items-center gap-1.5">
                        Variety Name
                        <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Input
                        id="variety_name"
                        v-model="form.name"
                        placeholder="e.g. Cherry, Beefsteak, Romaine..."
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <p v-if="errors.name" class="text-xs text-destructive">
                        {{ errors.name }}
                    </p>
                    <p v-else-if="selectedVegetableName && form.name" class="text-xs text-muted-foreground">
                        Full name will be: <span class="font-medium">{{ selectedVegetableName }} {{ form.name }}</span>
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        The specific type or cultivar name
                    </p>
                </div>

                <!-- Image Upload -->
                <ImageUpload
                    v-model="form.image"
                    :existing-image-url="existingImageUrl"
                    :error="errors.image"
                    :required="!isEditMode"
                />

                <!-- Weeks to Harvest -->
                <div class="flex flex-col gap-2">
                    <Label for="weeks_to_harvest" class="flex items-center gap-1.5">
                        <Clock class="size-3.5" />
                        Weeks to Harvest
                    </Label>
                    <div class="flex items-center gap-3">
                        <Input
                            id="weeks_to_harvest"
                            v-model.number="form.weeks_to_harvest"
                            type="number"
                            min="1"
                            max="52"
                            class="max-w-[120px]"
                            :class="{ 'border-destructive': errors.weeks_to_harvest }"
                        />
                        <span class="text-sm text-muted-foreground">
                            {{ form.weeks_to_harvest }} week{{ form.weeks_to_harvest !== 1 ? 's' : '' }}
                            ({{ Math.round(form.weeks_to_harvest * 7) }} days)
                        </span>
                    </div>
                    <p v-if="errors.weeks_to_harvest" class="text-xs text-destructive">
                        {{ errors.weeks_to_harvest }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                        Average time from planting to harvest (1-52 weeks)
                    </p>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="close" :disabled="isSubmitting">
                    Cancel
                </Button>
                <Button @click="handleSubmit" :disabled="isSubmitting">
                    <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                    {{ isEditMode ? 'Save Changes' : 'Create Variety' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>