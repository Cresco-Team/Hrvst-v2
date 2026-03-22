<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { ShoppingBag } from 'lucide-vue-next'
import type { AcceptableValue } from 'reka-ui'
import { computed, ref, watch } from 'vue'
import { store, update } from '@/actions/App/Http/Controllers/Dealer/DemandController'
import DialogForm from '@/components/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
  Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import type {
  DealerDemandResource, DemandVarietyOption, PostTimeSlot, VarietyOptionsByCategory,
} from '@/types'

interface Props {
  open: boolean
  demand?: DealerDemandResource | null
  varietyOptions?: VarietyOptionsByCategory<DemandVarietyOption>
}

const props = withDefaults(defineProps<Props>(), {
  demand: null,
})

const emit = defineEmits<{
  'update:open': [value: boolean]
}>()

const TIME_SLOT_OPTIONS: { value: PostTimeSlot; label: string }[] = [
  { value: 'morning', label: 'Morning (6 AM – 12 PM)' },
  { value: 'afternoon', label: 'Afternoon (12 PM – 6 PM)' },
  { value: 'evening', label: 'Evening (6 PM – 10 PM)' },
]

const form = useForm({
  variety_id: '',
  quantity_kg: '',
  offered_price: '',
  scheduled_date: '',
  time_slot: 'morning' as PostTimeSlot | '',
})

const isEditMode = computed(() => !!props.demand)

// Derive current price hint for the selected variety — only available on
// DemandVarietyOption which carries current_price from DemandService::varietyOptions()
const selectedVarietyPrice = ref<DemandVarietyOption['current_price'] | null>(null)

function updatePriceHint(varietyId: string) {
  if (!props.varietyOptions) {
    selectedVarietyPrice.value = null
    return
  }

  for (const varieties of Object.values(props.varietyOptions)) {
    const found = varieties.find((v) => String(v.id) === varietyId)
    if (found) {
      selectedVarietyPrice.value = found.current_price
      return
    }
  }

  selectedVarietyPrice.value = null
}

function handleVarietyChange(value: AcceptableValue) {
  const id = String(value ?? '')
  form.variety_id = id
  updatePriceHint(id)
}

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
  const routeData = props.demand ? update(props.demand.id) : store()

  form
    .transform((data) => {
      if (props.demand) return { ...data, _method: 'PUT' }
      return data
    })
    .post(routeData.url, {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
        form.reset()
        selectedVarietyPrice.value = null
      },
    })
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      const d = props.demand
      form.variety_id = String(d?.variety?.id ?? '')
      form.quantity_kg = String(d?.quantity_kg ?? '')
      form.offered_price = String(d?.offered_price ?? '')
      form.scheduled_date = d?.scheduled_date ?? ''
      form.time_slot = (d?.time_slot ?? 'morning') as PostTimeSlot | ''
      form.clearErrors()

      if (d?.variety?.id) {
        updatePriceHint(String(d.variety.id))
      } else {
        selectedVarietyPrice.value = null
      }
    } else {
      form.reset()
      form.clearErrors()
      selectedVarietyPrice.value = null
    }
  },
)
</script>

<template>
  <DialogForm :open="open" :title="isEditMode ? 'Edit Request' : 'Create Request'"
    :description="isEditMode ? 'Update your demand details' : 'Post a new purchase request for farmers'"
    :is-submitting="form.processing" :submit-label="isEditMode ? 'Update Request' : 'Post Request'" max-width="2xl"
    @update:open="emit('update:open', $event)" @submit="handleSubmit">
    <template #icon>
      <ShoppingBag class="size-5 text-primary" />
    </template>

    <div class="space-y-6">

      <!-- Variety Select -->
      <div class="space-y-2">
        <Label for="variety" class="flex items-center gap-1.5">
          Variety
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Select :model-value="form.variety_id" :disabled="isEditMode" @update:model-value="handleVarietyChange">
          <SelectTrigger id="variety" :class="{ 'border-destructive': form.errors.variety_id }">
            <SelectValue placeholder="Select a variety..." />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup v-for="(varieties, category) in varietyOptions" :key="category">
              <SelectLabel>{{ category }}</SelectLabel>
              <SelectItem v-for="variety in varieties" :key="variety.id" :value="String(variety.id)">
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

        <!-- Current market price hint — only shows when DemandVarietyOption has price data -->
        <div v-if="selectedVarietyPrice"
          class="flex items-center gap-1.5 rounded-md border border-dashed bg-muted/40 px-3 py-2 text-xs text-muted-foreground">
          <span>Current market price:</span>
          <span class="font-mono font-semibold text-foreground">
            ₱{{ selectedVarietyPrice.min.toFixed(2) }} – ₱{{ selectedVarietyPrice.max.toFixed(2) }}
          </span>
          <span>/ kg</span>
        </div>
      </div>

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
          Enter the quantity you need in kilograms
        </p>
      </div>

      <!-- Offered Price -->
      <div class="space-y-2">
        <Label for="price" class="flex items-center gap-1.5">
          Offered Price (₱/kg)
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input id="price" v-model.number="form.offered_price" type="number" step="0.01" min="0" max="9999.99"
          placeholder="0.00" :class="{ 'border-destructive': form.errors.offered_price }" />
        <p v-if="form.errors.offered_price" class="text-xs text-destructive">
          {{ form.errors.offered_price }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          The price you're willing to pay per kilogram
        </p>
      </div>

      <!-- Transaction Date -->
      <div class="space-y-2">
        <Label for="scheduled" class="flex items-center gap-1.5">
          Transaction Date
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input id="scheduled" v-model="form.scheduled_date" type="date" :min="minDate" :max="maxDate"
          :class="{ 'border-destructive': form.errors.scheduled_date }" />
        <p v-if="form.errors.scheduled_date" class="text-xs text-destructive">
          {{ form.errors.scheduled_date }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Post will auto-archive after this date (max 3 months)
        </p>
      </div>

      <!-- Time Slot -->
      <div class="space-y-2">
        <Label for="time_slot" class="flex items-center gap-1.5">
          Preferred Time Slot
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Select v-model="form.time_slot">
          <SelectTrigger id="time_slot" :class="{ 'border-destructive': form.errors.time_slot }">
            <SelectValue placeholder="Select a time slot..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in TIME_SLOT_OPTIONS" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p v-if="form.errors.time_slot" class="text-xs text-destructive">
          {{ form.errors.time_slot }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          When are you available for pickup or delivery?
        </p>
      </div>

    </div>
  </DialogForm>
</template>
