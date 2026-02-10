<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Plus, X } from 'lucide-vue-next'
import type { DealerRequest, VarietyOption } from '@/types/announcement'

interface Props {
  open: boolean
  request?: DealerRequest | null
  varietyOptions: Record<string, VarietyOption[]>
  isSubmitting?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  request: null,
  isSubmitting: false
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'submit': [formData: FormData]
}>()

interface RequestItem {
  variety_id: number
  quantity_kg: number
  price_offered: number
}

const form = useForm({
  transaction_date: props.request?.transaction_date || '',
  items: props.request?.items.map(item => ({
    variety_id: item.variety.id,
    quantity_kg: item.quantity_kg,
    price_offered: item.price_offered
  })) || [{ variety_id: 0, quantity_kg: 0, price_offered: 0 }] as RequestItem[]
})

const allVarieties = computed(() => {
  return Object.values(props.varietyOptions).flat()
})

function addItem() {
  form.items.push({ variety_id: 0, quantity_kg: 0, price_offered: 0 })
}

function removeItem(index: number) {
  if (form.items.length > 1) {
    form.items.splice(index, 1)
  }
}

function handleSubmit() {
  const formData = new FormData()
  formData.append('transaction_date', form.transaction_date)
  
  form.items.forEach((item, index) => {
    formData.append(`items[${index}][variety_id]`, String(item.variety_id))
    formData.append(`items[${index}][quantity_kg]`, String(item.quantity_kg))
    formData.append(`items[${index}][price_offered]`, String(item.price_offered))
  })

  emit('submit', formData)
}

function getVarietyById(id: number) {
  return allVarieties.value.find(v => v.id === id)
}
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ request ? 'Edit Request' : 'Create Request' }}
        </DialogTitle>
        <DialogDescription>
          {{ request ? 'Update your purchase request details' : 'Post a new purchase request for farmers' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Transaction Date -->
        <div class="space-y-2">
          <Label for="transaction_date">Transaction Date *</Label>
          <Input
            id="transaction_date"
            v-model="form.transaction_date"
            type="date"
            :min="new Date().toISOString().split('T')[0]"
            required
          />
          <p class="text-xs text-muted-foreground">
            When you need these vegetables
          </p>
        </div>

        <!-- Items -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <Label>Varieties Needed *</Label>
            <Button
              type="button"
              variant="outline"
              size="sm"
              @click="addItem"
              class="gap-2"
            >
              <Plus class="size-4" />
              Add Variety
            </Button>
          </div>

          <div
            v-for="(item, index) in form.items"
            :key="index"
            class="space-y-4 rounded-lg border p-4"
          >
            <div class="flex items-start justify-between">
              <span class="text-sm font-medium">Item {{ index + 1 }}</span>
              <Button
                v-if="form.items.length > 1"
                type="button"
                variant="ghost"
                size="sm"
                @click="removeItem(index)"
              >
                <X class="size-4" />
              </Button>
            </div>

            <!-- Variety Select -->
            <div class="space-y-2">
              <Label :for="`variety_${index}`">Variety</Label>
              <Select v-model="form.items[index].variety_id">
                <SelectTrigger :id="`variety_${index}`">
                  <SelectValue placeholder="Select variety" />
                </SelectTrigger>
                <SelectContent>
                  <template v-for="(varieties, category) in varietyOptions" :key="category">
                    <div class="px-2 py-1.5 text-xs font-semibold text-muted-foreground">
                      {{ category }}
                    </div>
                    <SelectItem
                      v-for="variety in varieties"
                      :key="variety.id"
                      :value="variety.id"
                    >
                      {{ variety.name }}
                    </SelectItem>
                  </template>
                </SelectContent>
              </Select>
            </div>

            <!-- Quantity -->
            <div class="space-y-2">
              <Label :for="`quantity_${index}`">Quantity (kg)</Label>
              <Input
                :id="`quantity_${index}`"
                v-model.number="form.items[index].quantity_kg"
                type="number"
                step="0.1"
                min="0.1"
                max="99999"
                required
              />
            </div>

            <!-- Price Offered -->
            <div class="space-y-2">
              <Label :for="`price_${index}`">Price Offered (₱/kg)</Label>
              <Input
                :id="`price_${index}`"
                v-model.number="form.items[index].price_offered"
                type="number"
                step="0.01"
                min="0"
                max="9999.99"
                required
              />
              <p
                v-if="getVarietyById(form.items[index].variety_id)?.current_price"
                class="text-xs text-muted-foreground"
              >
                Market: ₱{{ getVarietyById(form.items[index].variety_id)?.current_price?.min }} - 
                ₱{{ getVarietyById(form.items[index].variety_id)?.current_price?.max }}
              </p>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3">
          <Button
            type="button"
            variant="outline"
            @click="emit('update:open', false)"
            :disabled="isSubmitting"
          >
            Cancel
          </Button>
          <Button type="submit" :disabled="isSubmitting">
            {{ isSubmitting ? 'Saving...' : (request ? 'Update Request' : 'Post Request') }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
