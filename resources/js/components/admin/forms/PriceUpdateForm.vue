<script setup lang="ts">

import { TrendingUp } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'
import DialogForm from '@/components/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'

interface Variety {
  id: number
  name: string
  vegetable: { name: string }
  latest_price: { price_min: string; price_max: string } | null
}

interface PriceForm {
  price_min: string
  price_max: string
}

const props = defineProps<{
  open: boolean
  variety: Variety | null
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  submit: [payload: FormData]
}>()

const form = ref<PriceForm>({ price_min: '', price_max: '' })
const errors = ref<Partial<PriceForm>>({})

// Seed fields with the current price when the dialog opens
watch(
  () => [props.open, props.variety] as const,
  ([open, variety]) => {
    if (!open) return
    form.value = {
      price_min: variety?.latest_price?.price_min ?? '',
      price_max: variety?.latest_price?.price_max ?? '',
    }
    errors.value = {}
  },
)

const priceRange = computed(() => {
  const min = parseFloat(form.value.price_min)
  const max = parseFloat(form.value.price_max)
  if (isNaN(min) || isNaN(max)) return null
  return `₱${min.toFixed(2)} – ₱${max.toFixed(2)} (avg: ₱${((min + max) / 2).toFixed(2)})`
})

function validate(): boolean {
  const e: Partial<PriceForm> = {}
  const min = parseFloat(form.value.price_min)
  const max = parseFloat(form.value.price_max)

  if (!form.value.price_min || isNaN(min)) {
    e.price_min = 'Minimum price is required'
  } else if (min < 0) {
    e.price_min = 'Price cannot be negative'
  } else if (min > 9999.99) {
    e.price_min = 'Price cannot exceed ₱9,999.99'
  }

  if (!form.value.price_max || isNaN(max)) {
    e.price_max = 'Maximum price is required'
  } else if (max < 0) {
    e.price_max = 'Price cannot be negative'
  } else if (max > 9999.99) {
    e.price_max = 'Price cannot exceed ₱9,999.99'
  }

  if (!e.price_min && !e.price_max && max < min) {
    e.price_max = 'Maximum price must be ≥ minimum price'
  }

  errors.value = e
  return Object.keys(e).length === 0
}

function handleSubmit() {
  if (!validate()) return

  const payload = new FormData()
  payload.append('price_min', parseFloat(form.value.price_min).toFixed(2))
  payload.append('price_max', parseFloat(form.value.price_max).toFixed(2))

  emit('submit', payload)
}
</script>

<template>
  <DialogForm
    :open="open"
    title="Update Price"
    :description="`Record today's market price for ${variety?.vegetable.name} ${variety?.name}.`"
    submit-label="Save Price"
    :is-submitting="isSubmitting"
    max-width="sm"
    @update:open="$emit('update:open', $event)"
    @submit="handleSubmit"
  >
    <template #icon>
      <TrendingUp class="size-5 text-primary" />
    </template>

    <div class="flex flex-col gap-5">
      <div class="grid grid-cols-2 gap-3">
        <!-- Min -->
        <div class="flex flex-col gap-2">
          <Label for="ph_price_min" class="flex items-center gap-1.5">
            Minimum (₱)
            <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
          </Label>
          <Input
            id="ph_price_min"
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

        <!-- Max -->
        <div class="flex flex-col gap-2">
          <Label for="ph_price_max" class="flex items-center gap-1.5">
            Maximum (₱)
            <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
          </Label>
          <Input
            id="ph_price_max"
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

      <div v-if="priceRange" class="flex items-center gap-2">
        <Badge variant="secondary" class="font-mono text-xs">{{ priceRange }}</Badge>
      </div>
      <p v-else class="text-xs text-muted-foreground">
        Enter today's market price range per kilogram.
      </p>
    </div>
  </DialogForm>
</template>
