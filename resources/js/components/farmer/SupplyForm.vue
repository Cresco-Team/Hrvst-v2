<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Sprout } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { store, update } from '@/actions/App/Http/Controllers/Farmer/SupplyController'
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
import type { Supply, VarietyOption } from '@/types/marketplace'

interface Props {
	open: boolean
	supply?: Supply | null
	varietyOptions?: Record<string, VarietyOption[]>
}

const props = withDefaults(defineProps<Props>(), {
	supply: null,
})

const emit = defineEmits<{
	'update:open': [value: boolean]
	submit: []
}>()

const form = useForm({
	variety_id: '',
	quantity_kg: '',
	offered_price: '',
	scheduled_date: '',
	image: null as File | null,
})

watch(
	() => props.supply,
	(s) => {
		form.variety_id = String(s?.variety?.id ?? '')
		form.quantity_kg = String(s?.quantity_kg ?? '')
		form.offered_price = String(s?.offered_price ?? '')
		form.scheduled_date = s?.scheduled_date ?? ''
		form.image = null
	},
	{ immediate: true },
)

const isEditMode = computed(() => !!props.supply)

const minDate = computed(() => {
	const tomorrow = new Date()
	tomorrow.setDate(tomorrow.getDate() + 1)
	return tomorrow.toISOString().split('T')[0]
})

const maxDate = computed(() => {
	const threeMonths = new Date()
	threeMonths.setMonth(threeMonths.getMonth() + 3)
	return threeMonths.toISOString().split('T')[0]
})

function handleSubmit() {
	const routeData = props.supply ? update(props.supply.id) : store()

	form
		.transform((data) => {
			const payload: Record<string, unknown> = { ...data }

			if (props.supply) {
				payload._method = 'PUT'
				if (!payload.image) {
					delete payload.image
				}
			}

			return payload
		})
		.post(routeData.url, {
			forceFormData: true,
			preserveScroll: true,
			onSuccess: () => {
				emit('update:open', false)
				form.reset()
			},
		})
}

// Reset form when dialog closes
watch(
	() => props.open,
	(isOpen) => {
		if (!isOpen) {
			form.reset()
			form.clearErrors()
		}
	},
)
</script>

<template>
  <DialogForm :open="open" :title="isEditMode ? 'Edit Offering' : 'Create Offering'"
    :description="isEditMode ? 'Update your supply details' : 'Post a new supply for dealers'"
    :is-submitting="form.processing" :submit-label="isEditMode ? 'Update Supply' : 'Post Supply'" max-width="2xl"
    @update:open="emit('update:open', $event)" @submit=handleSubmit>
    <template #icon>
      <Sprout class="size-5 text-primary" />
    </template>

    <div class="space-y-6">

      <!-- Variety Select -->
      <div class="space-y-2">
        <Label for="variety" class="flex items-center gap-1.5">
          Variety
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Select v-model="form.variety_id" :disabled="isEditMode">
          <SelectTrigger id="variety" :class="{ 'border-destructive': form.errors.variety_id }">
            <SelectValue placeholder="Select a variety..." />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup v-for="(varieties, category) in varietyOptions" :key="category">
              <SelectLabel>{{ category }}</SelectLabel>
              <SelectItem v-for="variety in varieties" :key="variety.id" :value="variety.id">
                {{ variety.name }}
              </SelectItem>
            </SelectGroup>
          </SelectContent>
        </Select>
        <p v-if="form.errors.variety_id" class="text-xs text-destructive">
          {{ form.errors.variety_id }}
        </p>
        <p v-else-if="isEditMode" class="text-xs text-muted-foreground">
          Variety cannot be changed after creation
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Choose the variety you're offering
        </p>
      </div>

      <!-- Image Upload -->
      <ImageUpload v-model="form.image" :existing-image-url="supply?.image_url" :error="form.errors.image"
        :required="!isEditMode" />

      <!-- Quantity -->
      <div class="space-y-2">
        <Label for="quantity" class="flex items-center gap-1.5">
          Quantity (kg)
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input id="quantity" v-model.number="form.quantity_kg" type="number" step="0.1" min="0.1" max="99999"
          placeholder="0.0" :class="{ 'border-destructive': form.errors.quantity_kg }" />
        <p v-if="form.errors.quantity_kg" class="text-xs text-destructive">
          {{ form.errors.quantity_kg }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Enter the available quantity in kilograms
        </p>
      </div>

      <!-- Asking Price -->
      <div class="space-y-2">
        <Label for="price" class="flex items-center gap-1.5">
          Asking Price (₱/kg)
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input id="price" v-model.number="form.offered_price" type="number" step="0.01" min="0" max="9999.99"
          placeholder="0.00" :class="{ 'border-destructive': form.errors.offered_price }" />
        <p v-if="form.errors.offered_price" class="text-xs text-destructive">
          {{ form.errors.offered_price }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Set your asking price per kilogram
        </p>
      </div>

      <!-- Scheduled Date -->
      <div class="space-y-2">
        <Label for="scheduled" class="flex items-center gap-1.5">
          Scheduled Date
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input id="scheduled" v-model="form.scheduled_date" type="date" :min="minDate" :max="maxDate"
          :class="{ 'border-destructive': form.errors.scheduled_date }" />
        <p v-if="form.errors.scheduled_date" class="text-xs text-destructive">
          {{ form.errors.scheduled_date }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Post will auto-archived after this date (max 3 months)
        </p>
      </div>
    </div>
  </DialogForm>
</template>
