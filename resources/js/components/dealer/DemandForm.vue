<script setup lang="ts">

import { useForm } from '@inertiajs/vue3'
import { Sprout } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import { store, update } from '@/routes/dealer/demands'
import type { Demand } from '@/types/dealer/demands'
import type { VarietyOption } from '@/types/product/variety'
import DialogForm from '../DialogForm.vue'
import { Badge } from '../ui/badge'

interface Props {
  open: boolean
  demand?: Demand | null
  varietyOptions: Record<string, VarietyOption[]>
}

const props = withDefaults(defineProps<Props>(), {
  demand: null,
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'submit': []
}>()

const form = useForm({
  variety_id: null as number | null,
  quantity_kg: 0,
  offered_price: 0,
  transaction_date: '',
})

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

const isEditMode = computed(() => !!props.demand)

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    if (props.demand) {
      form.variety_id = props.demand.variety.id
      form.quantity_kg = props.demand.quantity_kg
      form.offered_price = props.demand.offered_price
      form.transaction_date = props.demand.transaction_date
    } else {
      form.reset()
    }
    form.clearErrors()
  }
})

const handleSubmit = () => {
  if (props.demand) {
    form.put(update(props.demand.id).url, {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
      }
    })
  } else {
    form.post(store().url, {
      preserveScroll: true,
      onSuccess: () => {
        emit('update:open', false)
      }
    })
  }
}
</script>

<template>
  <DialogForm
    :open="open"
    :title="isEditMode ? 'Edit Post' : 'Create Post'"
    :description="isEditMode ? 'Update your post details' : 'Post a new requested vegetable'"
    :is-submitting="form.processing"
    :submit-label="isEditMode ? 'Update Post' : 'Post Vegetable Request'"
    max-width="2xl"
    @update:open="emit('update:open', $event)"
    @submit="handleSubmit"
  >
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
        <Select 
          v-model="form.variety_id" 
          :disabled="isEditMode"
        >
          <SelectTrigger 
            id="variety"
            :class="{ 'border-destructive': form.errors.variety_id }"
          >
            <SelectValue placeholder="Select a variety..." />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup 
              v-for="(varieties, category) in varietyOptions" 
              :key="category"
            >
              <SelectLabel>{{ category }}</SelectLabel>
              <SelectItem
                v-for="variety in varieties"
                :key="variety.id"
                :value="variety.id"
              >
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
          Choose the variety you're requesting
        </p>
      </div>
    </div>

    <div class="space-y-2">
        <Label for="quantity" class="flex items-center gap-1.5">
          Quantity (kg)
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input
          id="quantity"
          v-model.number="form.quantity_kg"
          type="number"
          step="0.1"
          min="0.1"
          max="99999"
          placeholder="0.0"
          :class="{ 'border-destructive': form.errors.quantity_kg }"
        />
        <p v-if="form.errors.quantity_kg" class="text-xs text-destructive">
          {{ form.errors.quantity_kg }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Enter the available quantity in kilograms
        </p>
      </div>

      <div class="space-y-2">
        <Label for="price" class="flex items-center gap-1.5">
          Price Offered (₱/kg)
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input
          id="price"
          v-model.number="form.offered_price"
          type="number"
          step="0.01"
          min="0"
          max="9999.99"
          placeholder="0.00"
          :class="{ 'border-destructive': form.errors.offered_price }"
        />
        <p v-if="form.errors.offered_price" class="text-xs text-destructive">
          {{ form.errors.offered_price }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Set your offered price per kilogram
        </p>
      </div>

      <div class="space-y-2">
        <Label for="transaction" class="flex items-center gap-1.5">
          Transaction Date
          <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>
        <Input
          id="expiration"
          v-model="form.transaction_date"
          type="date"
          :min="minDate"
          :max="maxDate"
          :class="{ 'border-destructive': form.errors.transaction_date }"
        />
        <p v-if="form.errors.transaction_date" class="text-xs text-destructive">
          {{ form.errors.transaction_date }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
          Offering will auto-expire after this date (max 3 months)
        </p>
      </div>
  </DialogForm>
</template>
