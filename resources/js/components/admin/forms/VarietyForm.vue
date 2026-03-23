<script setup lang="ts">
import { DollarSign, Leaf } from 'lucide-vue-next'
import { computed } from 'vue'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import ImageUpload from '@/components/shared/media/ImageUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { useDialogForm } from '@/composables/useDialogForm'
import type { VarietyResource, VegetableOptions } from '@/types/resources/product'

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
  vegetableOptions: VegetableOptions
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  submit: [payload: FormData]
}>()

const { form, errors, isEditMode, validateForm } = useDialogForm<VarietyResource, VarietyFormData>({
  item: () => props.variety,
  open: () => props.open,
  mapToForm: (variety) => ({
    vegetable_id: variety?.vegetable?.id?.toString() ?? '',
    name: variety?.name ?? '',
    image: null,
    price_min: '',
    price_max: '',
  }),
  validate: (data) => {
    const errs: Record<string, string> = {}

    if (!data.name.trim()) {
      errs.name = 'Variety name is required'
    }
    if (!data.vegetable_id) {
      errs.vegetable_id = 'Please select a parent vegetable'
    }
    if (!isEditMode.value && !data.image) {
      errs.image = 'Image is required for new varieties'
    }

    if (!isEditMode.value) {
      const min = parseFloat(data.price_min)
      const max = parseFloat(data.price_max)

      if (!data.price_min || isNaN(min)) {
        errs.price_min = 'Minimum price is required'
      } else if (min < 0) {
        errs.price_min = 'Price cannot be negative'
      } else if (min > 9999.99) {
        errs.price_min = 'Price cannot exceed ₱9,999.99'
      }

      if (!data.price_max || isNaN(max)) {
        errs.price_max = 'Maximum price is required'
      } else if (max < 0) {
        errs.price_max = 'Price cannot be negative'
      } else if (max > 9999.99) {
        errs.price_max = 'Price cannot exceed ₱9,999.99'
      }

      if (!errs.price_min && !errs.price_max && max < min) {
        errs.price_max = 'Maximum price must be greater than or equal to minimum price'
      }
    }

    return errs
  },
})

const title = computed(() => (isEditMode.value ? 'Edit Variety' : 'Add New Variety'))
const description = computed(() =>
  isEditMode.value
    ? 'Update the variety details. Use the "Update Price" action to record a new price.'
    : 'Create a new variety for a vegetable type.',
)

const selectedVegetableName = computed(() => {
  if (!form.value.vegetable_id) return null
  for (const vegetables of Object.values(props.vegetableOptions)) {
    const name = vegetables[form.value.vegetable_id]
    if (name) return name
  }
  return null
})

const existingImageUrl = computed(() => props.variety?.image_url ?? null)

const priceRange = computed(() => {
  const min = parseFloat(form.value.price_min)
  const max = parseFloat(form.value.price_max)
  if (isNaN(min) || isNaN(max)) return null
  return `₱${min.toFixed(2)} – ₱${max.toFixed(2)} (avg: ₱${((min + max) / 2).toFixed(2)})`
})

function handleSubmit() {
  if (!validateForm()) return

  const payload = new FormData()
  payload.append('vegetable_id', form.value.vegetable_id)
  payload.append('name', form.value.name.trim())

  if (form.value.image) {
    payload.append('image', form.value.image)
  }

  if (!isEditMode.value) {
    payload.append('price_min', parseFloat(form.value.price_min).toFixed(2))
    payload.append('price_max', parseFloat(form.value.price_max).toFixed(2))
  }

  emit('submit', payload)
}
</script>

<template>
  <DialogForm :open="open" :title="title" :description="description" :is-submitting="isSubmitting"
    :submit-label="isEditMode ? 'Save Changes' : 'Create Variety'" @update:open="$emit('update:open', $event)"
    @submit="handleSubmit">
    <template #icon>
      <Leaf class="size-5 text-primary" />
    </template>

    <div class="flex flex-col gap-5">
      <!-- Parent Vegetable -->
      <div class="flex flex-col gap-2">
        <Label for="vegetable_id" class="flex items-center gap-1.5">
          Parent Vegetable
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Select v-model="form.vegetable_id">
          <SelectTrigger id="vegetable_id" :class="{ 'border-destructive': errors.vegetable_id }">
            <SelectValue placeholder="Select the vegetable type…" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup v-for="(vegetables, category) in vegetableOptions" :key="category">
              <SelectLabel>{{ category }}</SelectLabel>
              <SelectItem v-for="(name, id) in vegetables" :key="id" :value="String(id)">
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
        <Input id="variety_name" v-model="form.name" placeholder="e.g. Cherry, Beefsteak, Romaine…"
          :class="{ 'border-destructive': errors.name }" />
        <p v-if="errors.name" class="text-xs text-destructive">{{ errors.name }}</p>
        <p v-else-if="selectedVegetableName && form.name" class="text-xs text-muted-foreground">
          Full name: <span class="font-medium">{{ selectedVegetableName }} {{ form.name }}</span>
        </p>
        <p v-else class="text-xs text-muted-foreground">The specific type or cultivar name</p>
      </div>

      <!-- Image -->
      <ImageUpload v-model="form.image" :existing-image-url="existingImageUrl" :error="errors.image"
        :required="!isEditMode" />

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
            <Input id="price_min" v-model="form.price_min" type="number" step="0.01" min="0" max="9999.99"
              placeholder="0.00" :class="{ 'border-destructive': errors.price_min }" />
            <p v-if="errors.price_min" class="text-xs text-destructive">{{ errors.price_min }}</p>
          </div>

          <div class="flex flex-col gap-2">
            <Label for="price_max" class="text-xs text-muted-foreground">Maximum (₱)</Label>
            <Input id="price_max" v-model="form.price_max" type="number" step="0.01" min="0" max="9999.99"
              placeholder="0.00" :class="{ 'border-destructive': errors.price_max }" />
            <p v-if="errors.price_max" class="text-xs text-destructive">{{ errors.price_max }}</p>
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
  </DialogForm>
</template>
