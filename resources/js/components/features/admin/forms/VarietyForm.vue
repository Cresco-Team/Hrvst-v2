<script setup lang="ts">
import { DollarSign, Leaf } from 'lucide-vue-next'
import { computed } from 'vue'
import DialogForm from '@/components/DialogForm.vue'
import ImageUpload from '@/components/shared/media/ImageUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
	Select,
	SelectContent,
	SelectGroup,
	SelectItem,
	SelectLabel,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select'
import { useDialogForm } from '@/composables/useDialogForm'

interface Variety {
	id: number
	vegetable_id: number
	name: string
	image_url?: string
	latest_price?: {
		price_min: string
		price_max: string
	} | null
}

interface VegetableOptions {
	[categoryName: string]: {
		[vegetableId: number]: string
	}
}

interface VarietyFormData {
	vegetable_id: string
	name: string
	image: File | null
	price_min: string
	price_max: string
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

// Use the composable for form state management
const { form, errors, isEditMode, validateForm } = useDialogForm<
	Variety,
	VarietyFormData
>({
	item: () => props.variety,
	open: () => props.open,
	mapToForm: (variety) => ({
		vegetable_id: variety?.vegetable_id?.toString() ?? '',
		name: variety?.name ?? '',
		image: null,
		price_min: variety?.latest_price?.price_min ?? '',
		price_max: variety?.latest_price?.price_max ?? '',
	}),
	validate: (form) => {
		const errors: Record<string, string> = {}

		if (!form.name.trim()) {
			errors.name = 'Variety name is required'
		}
		if (!form.vegetable_id) {
			errors.vegetable_id = 'Please select a parent vegetable'
		}
		if (!isEditMode.value && !form.image) {
			errors.image = 'Image is required for new varieties'
		}

		const priceMin = parseFloat(form.price_min)
		const priceMax = parseFloat(form.price_max)

		if (!form.price_min || isNaN(priceMin)) {
			errors.price_min = 'Minimum price is required'
		} else if (priceMin < 0) {
			errors.price_min = 'Price cannot be negative'
		} else if (priceMin > 9999.99) {
			errors.price_min = 'Price cannot exceed ₱9,999.99'
		}

		if (!form.price_max || isNaN(priceMax)) {
			errors.price_max = 'Maximum price is required'
		} else if (priceMax < 0) {
			errors.price_max = 'Price cannot be negative'
		} else if (priceMax > 9999.99) {
			errors.price_max = 'Price cannot exceed ₱9,999.99'
		}

		if (!errors.price_min && !errors.price_max && priceMax < priceMin) {
			errors.price_max =
				'Maximum price must be greater than or equal to minimum price'
		}

		return errors
	},
})

const title = computed(() =>
	isEditMode.value ? 'Edit Variety' : 'Add New Variety',
)
const description = computed(() =>
	isEditMode.value
		? 'Update the details of this variety.'
		: 'Create a new variety for a vegetable type.',
)

const selectedVegetableName = computed(() => {
	if (!form.value.vegetable_id) return null

	for (const [vegetables] of Object.entries(props.vegetableOptions)) {
		const vegName = vegetables[Number(form.value.vegetable_id)]
		if (vegName) return vegName
	}
	return null
})

const existingImageUrl = computed(() => props.variety?.image_url ?? null)

const priceRange = computed(() => {
	const min = parseFloat(form.value.price_min)
	const max = parseFloat(form.value.price_max)

	if (isNaN(min) || isNaN(max)) return null

	const avg = ((min + max) / 2).toFixed(2)
	return `₱${min} - ₱${max} (avg: ₱${avg})`
})

function handleSubmit() {
	if (!validateForm()) return

	const formData = new FormData()
	formData.append('vegetable_id', form.value.vegetable_id)
	formData.append('name', form.value.name.trim())
	formData.append('price_min', parseFloat(form.value.price_min).toFixed(2))
	formData.append('price_max', parseFloat(form.value.price_max).toFixed(2))

	if (form.value.image) {
		formData.append('image', form.value.image)
	}

	emit('submit', formData)
}
</script>

<template>
    <DialogForm
        :open="open"
        :title="title"
        :description="description"
        :is-submitting="isSubmitting"
        :submit-label="isEditMode ? 'Save Changes' : 'Create Variety'"
        @update:open="$emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Leaf class="size-5 text-primary" />
        </template>

        <div class="flex flex-col gap-5">
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

            <!-- Price Range Section -->
            <div class="flex flex-col gap-3 rounded-lg border p-4 bg-muted/30">
                <Label class="flex items-center gap-1.5">
                    <DollarSign class="size-3.5" />
                    Current Market Price
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex flex-col gap-2">
                        <Label for="price_min" class="text-xs text-muted-foreground">
                            Minimum (₱)
                        </Label>
                        <Input
                            id="price_min"
                            v-model="form.price_min"
                            type="number"
                            step="0.01"
                            min="0"
                            max="9999.99"
                            placeholder="0.00"
                            :class="{ 'border-destructive': errors.price_min }"
                        />
                        <p v-if="errors.price_min" class="text-xs text-destructive">
                            {{ errors.price_min }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Label for="price_max" class="text-xs text-muted-foreground">
                            Maximum (₱)
                        </Label>
                        <Input
                            id="price_max"
                            v-model="form.price_max"
                            type="number"
                            step="0.01"
                            min="0"
                            max="9999.99"
                            placeholder="0.00"
                            :class="{ 'border-destructive': errors.price_max }"
                        />
                        <p v-if="errors.price_max" class="text-xs text-destructive">
                            {{ errors.price_max }}
                        </p>
                    </div>
                </div>

                <div v-if="priceRange" class="flex items-center gap-2 pt-1">
                    <Badge variant="secondary" class="font-mono text-xs">
                        {{ priceRange }}
                    </Badge>
                </div>
                <p v-else class="text-xs text-muted-foreground">
                    Enter the current market price range per kilogram
                </p>
            </div>
        </div>
    </DialogForm>
</template>
