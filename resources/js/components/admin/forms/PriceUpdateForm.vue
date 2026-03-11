<script setup lang="ts">

import { useForm } from '@inertiajs/vue3'
import { TrendingUp } from 'lucide-vue-next'
import { ref, watch, computed } from 'vue'
import DialogForm from '@/components/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { store } from '@/routes/admin/vegetables/prices'
import type { Variety } from '@/types/admin/vegetable-varieties'

interface PriceForm {
  price_min: number
  price_max: number
}

const props = defineProps<{
  open: boolean
  variety: Variety
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  submit: [payload: FormData]
}>()

const form = useForm({
  price_min: 0,
  price_max: 0,
})

const errors = ref<Partial<PriceForm>>({})

// Seed fields with the current price when the dialog opens
watch(() => [props.open, props.variety] as const, (isOpen) => {
    if (!isOpen) return
    
    form.price_min = props.variety?.latest_price?.price_min
    form.price_max = props.variety?.latest_price?.price_max

    form.clearErrors()
  },
)

const priceRange = computed(() => {
  const min = parseFloat(form.price_min)
  const max = parseFloat(form.price_max)
  if (isNaN(min) || isNaN(max)) return null
  return `₱${min.toFixed(2)} – ₱${max.toFixed(2)} (avg: ₱${((min + max) / 2).toFixed(2)})`
})

function handleSubmit() {
  form.post(store(props.variety.id).url, {
    preserveScroll: true,
    onSuccess: () => {
      emit('update:open', false)
    }
  })
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
