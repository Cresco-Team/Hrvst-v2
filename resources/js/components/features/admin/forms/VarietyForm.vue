<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { DollarSign, Leaf } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { VarietyResource } from '@/types/resources/product'
import { store, update } from '@/actions/App/Http/Controllers/Admin/Vegetable/VarietyController'

interface VarietyFormData {
	vegetable_id: string
	name: string
	image: File | null
	price_min: string
	price_max: string
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
	image: null,
	price_min: '',
	price_max: '',
})

watch(
	() => [props.variety, props.open, props.parentVegetable],
	() => {
		if (!props.open) return
		form.vegetable_id = props.parentVegetable?.id.toString() ?? ''
		form.name = props.variety?.name ?? ''
		form.image = null
		form.price_min = ''
		form.price_max = ''
		form.clearErrors()
	},
)

const title = computed(() => (isEditMode.value ? 'Edit Variety' : 'Add New Variety'))
const description = computed(() =>
	isEditMode.value
		? 'Update the variety details. Use the "Update Price" action to record a new price.'
		: 'Create a new variety for a vegetable type.',
)

const existingImageUrl = computed(() => props.variety?.image_url ?? null)

const priceRange = computed(() => {
	const min = parseFloat(form.price_min)
	const max = parseFloat(form.price_max)
	if (isNaN(min) || isNaN(max)) return null
	return `₱${min.toFixed(2)} – ₱${max.toFixed(2)} (avg: ₱${((min + max) / 2).toFixed(2)})`
})

function handleSubmit(): void {
	form.clearErrors()

	if (!form.name.trim()) form.setError('name', 'Variety name is required')
	if (!isEditMode.value && !form.image) form.setError('image', 'Image is required for new varieties')

	if (!isEditMode.value) {
		const min = parseFloat(form.price_min)
		const max = parseFloat(form.price_max)

		if (!form.price_min || isNaN(min)) form.setError('price_min', 'Minimum price is required')
		else if (min < 0) form.setError('price_min', 'Price cannot be negative')
		else if (min > 9999.99) form.setError('price_min', 'Price cannot exceed ₱9,999.99')

		if (!form.price_max || isNaN(max)) form.setError('price_max', 'Maximum price is required')
		else if (max < 0) form.setError('price_max', 'Price cannot be negative')
		else if (max > 9999.99) form.setError('price_max', 'Price cannot exceed ₱9,999.99')

		if (!form.errors.price_min && !form.errors.price_max && max < min) {
			form.setError('price_max', 'Maximum price must be greater than or equal to minimum price')
		}
	}

	if (Object.keys(form.errors).length > 0) return

	if (isEditMode.value) {
		form.transform((data) => ({
			vegetable_id: data.vegetable_id,
			name: data.name,
			...(data.image ? { image: data.image } : {}),
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
			image: data.image,
			price_min: data.price_min,
			price_max: data.price_max,
		}))

		form.post(store().url, {
			preserveScroll: true,
			preserveState: true,
			forceFormData: true,
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
          <Label class="flex items-center gap-1.5">Parent Vegetable</Label>
          <div class="flex h-9 items-center rounded-md border border-input bg-muted/50 px-3 text-sm text-muted-foreground">
            {{ parentVegetable?.name ?? '—' }}
          </div>
          <p class="text-xs text-muted-foreground">
            {{ isEditMode ? 'Parent vegetable cannot be changed after creation.' : 'Variety will be created under this vegetable.' }}
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
            placeholder="e.g. Cherry, Beefsteak, Romaine…"
            :class="{ 'border-destructive': form.errors.name }"
          />
          <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
          <p v-else-if="parentVegetable && form.name" class="text-xs text-muted-foreground">
            Full name: <span class="font-medium">{{ parentVegetable.name }} {{ form.name }}</span>
          </p>
          <p v-else class="text-xs text-muted-foreground">The specific type or cultivar name</p>
        </div>

        <!-- Image -->
        <ImageUpload
          v-model="form.image"
          :existing-image-url="existingImageUrl"
          :error="form.errors.image"
          :required="!isEditMode"
        />

        <!-- Price Range — create only -->
        <div v-if="!isEditMode" class="flex flex-col gap-3 rounded-lg border bg-muted/30 p-4">
          <Label class="flex items-center gap-1.5">
            <DollarSign class="size-3.5" />
            Current Market Price
            <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
          </Label>

          <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-2">
              <Label for="price_min" class="text-xs text-muted-foreground">Minimum (₱)</Label>
              <Input
                id="price_min"
                v-model="form.price_min"
                type="number"
                step="0.01"
                min="0"
                max="9999.99"
                placeholder="0.00"
                :class="{ 'border-destructive': form.errors.price_min }"
              />
              <p v-if="form.errors.price_min" class="text-xs text-destructive">{{ form.errors.price_min }}</p>
            </div>

            <div class="flex flex-col gap-2">
              <Label for="price_max" class="text-xs text-muted-foreground">Maximum (₱)</Label>
              <Input
                id="price_max"
                v-model="form.price_max"
                type="number"
                step="0.01"
                min="0"
                max="9999.99"
                placeholder="0.00"
                :class="{ 'border-destructive': form.errors.price_max }"
              />
              <p v-if="form.errors.price_max" class="text-xs text-destructive">{{ form.errors.price_max }}</p>
            </div>
          </div>

          <div v-if="priceRange" class="flex items-center gap-2 pt-1">
            <Badge variant="secondary" class="font-mono text-xs">{{ priceRange }}</Badge>
          </div>
          <p v-else class="text-xs text-muted-foreground">
            Enter the current market price range per kilogram
          </p>
        </div>

      </div>
    </template>
  </DialogForm>
</template>
