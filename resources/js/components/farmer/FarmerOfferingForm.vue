<script setup lang="ts">
import { ref, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Upload } from 'lucide-vue-next'
import type { FarmerOffering, VarietyOption } from '@/types/announcement'

interface Props {
  open: boolean
  offering?: FarmerOffering | null
  varietyOptions: Record<string, VarietyOption[]>
  isSubmitting?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  offering: null,
  isSubmitting: false
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'submit': [formData: FormData]
}>()

const form = useForm({
  variety_id: props.offering?.variety.id || 0,
  quantity_kg: props.offering?.quantity_kg || 0,
  price_asking: props.offering?.price_asking || 0,
  expiration_date: props.offering?.expiration_date || '',
  image: null as File | null
})

const imagePreview = ref<string | null>(props.offering?.image_url || null)
const fileInput = ref<HTMLInputElement>()

function handleImageSelect(event: Event) {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    form.image = file
    
    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

function handleSubmit() {
  const formData = new FormData()
  formData.append('variety_id', String(form.variety_id))
  formData.append('quantity_kg', String(form.quantity_kg))
  formData.append('price_asking', String(form.price_asking))
  formData.append('expiration_date', form.expiration_date)
  
  if (form.image) {
    formData.append('image', form.image)
  }

  emit('submit', formData)
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
</script>

<template>
  <Dialog :open="open" @update:open="(val) => emit('update:open', val)">
    <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
      <DialogHeader>
        <DialogTitle>
          {{ offering ? 'Edit Offering' : 'Create Offering' }}
        </DialogTitle>
        <DialogDescription>
          {{ offering ? 'Update your offering details' : 'Post a new offering for dealers' }}
        </DialogDescription>
      </DialogHeader>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Variety Select -->
        <div class="space-y-2">
          <Label for="variety">Variety *</Label>
          <Select v-model="form.variety_id" :disabled="!!offering">
            <SelectTrigger id="variety">
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
          <p v-if="offering" class="text-xs text-muted-foreground">
            Variety cannot be changed after creation
          </p>
        </div>

        <!-- Image Upload -->
        <div class="space-y-2">
          <Label for="image">Image {{ offering ? '' : '*' }}</Label>
          <div class="flex items-center gap-4">
            <Button
              type="button"
              variant="outline"
              @click="fileInput?.click()"
              class="gap-2"
            >
              <Upload class="size-4" />
              {{ imagePreview ? 'Change Image' : 'Upload Image' }}
            </Button>
            <input
              ref="fileInput"
              type="file"
              accept="image/jpeg,image/jpg,image/png,image/webp"
              class="hidden"
              @change="handleImageSelect"
            />
            <span v-if="form.image" class="text-sm text-muted-foreground">
              {{ form.image.name }}
            </span>
          </div>
          <div v-if="imagePreview" class="mt-2">
            <img
              :src="imagePreview"
              alt="Preview"
              class="h-32 w-32 rounded-lg border object-cover"
            />
          </div>
          <p class="text-xs text-muted-foreground">
            Max 5MB. Formats: JPEG, PNG, WebP
          </p>
        </div>

        <!-- Quantity -->
        <div class="space-y-2">
          <Label for="quantity">Quantity (kg) *</Label>
          <Input
            id="quantity"
            v-model.number="form.quantity_kg"
            type="number"
            step="0.1"
            min="0.1"
            max="99999"
            required
          />
        </div>

        <!-- Price Asking -->
        <div class="space-y-2">
          <Label for="price">Asking Price (₱/kg) *</Label>
          <Input
            id="price"
            v-model.number="form.price_asking"
            type="number"
            step="0.01"
            min="0"
            max="9999.99"
            required
          />
        </div>

        <!-- Expiration Date -->
        <div class="space-y-2">
          <Label for="expiration">Expiration Date *</Label>
          <Input
            id="expiration"
            v-model="form.expiration_date"
            type="date"
            :min="minDate"
            :max="maxDate"
            required
          />
          <p class="text-xs text-muted-foreground">
            Offering will auto-expire after this date (max 3 months)
          </p>
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
            {{ isSubmitting ? 'Saving...' : (offering ? 'Update Offering' : 'Post Offering') }}
          </Button>
        </div>
      </form>
    </DialogContent>
  </Dialog>
</template>
