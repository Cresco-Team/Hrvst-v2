<script setup lang="ts">
import { computed, watch } from 'vue'
import DialogForm from '@/components/shared/forms/DialogForm.vue'
import ImageUpload from '@/components/shared/media/ImageUpload.vue'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Sprout, Weight, Calendar, Info, DollarSign } from 'lucide-vue-next'
import { useDialogForm } from '@/composables/useDialogForm'
import type { Planting, VarietyOptionsByCategory, VarietyOption } from '@/types/farmer/garden'

interface PlantingFormData {
    variety_id: string
    weight_kg: string
    asking_price: string
    expiration_date: string
    image: File | null
}

const props = defineProps<{
    open: boolean
    planting: Planting | null
    varietyOptions: VarietyOptionsByCategory
    isSubmitting: boolean
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    submit: [payload: FormData]
}>()

const { form, errors, isEditMode, validateForm } = useDialogForm<Planting, PlantingFormData>({
    item: () => props.planting,
    open: () => props.open,
    mapToForm: (planting) => ({
        variety_id: planting?.variety?.id?.toString() ?? '',
        weight_kg: planting?.weight_kg?.toString() ?? '',
        asking_price: planting?.asking_price?.toString() ?? '',
        expiration_date: planting?.expiration_date ? new Date(planting.expiration_date).toISOString().split('T')[0] : '',
        image: null,
    }),
    validate: (form) => {
        const errors: Record<string, string> = {}
        
        if (!isEditMode.value && !form.variety_id) {
            errors.variety_id = 'Please select a variety'
        }
        
        if (!form.weight_kg) {
            errors.weight_kg = 'Weight is required'
        } else {
            const weight = parseFloat(form.weight_kg)
            if (isNaN(weight) || weight < 0.1) {
                errors.weight_kg = 'Weight must be at least 0.1 kg'
            } else if (weight > 99999) {
                errors.weight_kg = 'Weight cannot exceed 99,999 kg'
            }
        }

        if (!form.asking_price) {
            errors.asking_price = 'Asking price is required'
        } else {
            const price = parseFloat(form.asking_price)
            if (isNaN(price) || price < 0) {
                errors.asking_price = 'Price must be at least ₱0.00'
            } else if (price > 999.99) {
                errors.asking_price = 'Price cannot exceed ₱999.99'
            }
        }

        if (!form.expiration_date) {
            errors.expiration_date = 'Expiration date is required'
        } else {
            const expiration = new Date(form.expiration_date)
            const today = new Date()
            today.setHours(0, 0, 0, 0)
            
            if (expiration <= today) {
                errors.expiration_date = 'Expiration date must be in the future'
            }
        }

        if (!isEditMode.value && !form.image) {
            errors.image = 'Image is required'
        }
        
        return errors
    },
})

const title = computed(() => isEditMode.value ? 'Edit Planting' : 'Add New Planting')
const description = computed(() => 
    isEditMode.value
        ? 'Update the details of your planting.'
        : 'Record a new planting in your garden.'
)

const selectedVariety = computed<VarietyOption | null>(() => {
    if (!form.value.variety_id) return null
    
    for (const [_, varieties] of Object.entries(props.varietyOptions)) {
        const found = varieties.find(v => v.id === Number(form.value.variety_id))
        if (found) return found
    }
    return null
})

// Auto-fill expiration date when variety is selected
watch(
    () => form.value.variety_id,
    (varietyId) => {
        if (isEditMode.value || !varietyId) return
        
        const variety = selectedVariety.value
        if (!variety) return
        
        const today = new Date()
        const expirationDate = new Date(today)
        expirationDate.setDate(expirationDate.getDate() + (variety.weeks_to_harvest * 7))
        
        form.value.expiration_date = expirationDate.toISOString().split('T')[0]
    },
)

const maxDate = computed(() => {
    const sixMonthsFromNow = new Date()
    sixMonthsFromNow.setMonth(sixMonthsFromNow.getMonth() + 6)
    return sixMonthsFromNow.toISOString().split('T')[0]
})

const minDate = computed(() => {
    const tomorrow = new Date()
    tomorrow.setDate(tomorrow.getDate() + 1)
    return tomorrow.toISOString().split('T')[0]
})

function handleSubmit() {
    if (!validateForm()) return

    const formData = new FormData()
    formData.append('weight_kg', form.value.weight_kg)
    formData.append('asking_price', form.value.asking_price)
    formData.append('expiration_date', form.value.expiration_date)
    
    if (form.value.image) {
        formData.append('image', form.value.image)
    }
    
    if (!isEditMode.value) {
        formData.append('variety_id', form.value.variety_id)
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
        :submit-label="isEditMode ? 'Save Changes' : 'Add Planting'"
        @update:open="$emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Sprout class="size-5 text-primary" />
        </template>

        <div class="flex flex-col gap-5">
            <!-- Variety Selection (create only) -->
            <div v-if="!isEditMode" class="flex flex-col gap-2">
                <Label for="variety_id" class="flex items-center gap-1.5">
                    Variety
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Select v-model="form.variety_id">
                    <SelectTrigger 
                        id="variety_id"
                        :class="{ 'border-destructive': errors.variety_id }"
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
                                :value="variety.id.toString()"
                            >
                                {{ variety.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <p v-if="errors.variety_id" class="text-xs text-destructive">
                    {{ errors.variety_id }}
                </p>
                <p v-else-if="selectedVariety" class="text-xs text-muted-foreground">
                    Harvest time: {{ selectedVariety.weeks_to_harvest }} weeks
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Choose the variety you're planting
                </p>
            </div>

            <!-- Weight Input -->
            <div class="flex flex-col gap-2">
                <Label for="weight_kg" class="flex items-center gap-1.5">
                    <Weight class="size-3.5" />
                    Weight (kg)
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Input
                    id="weight_kg"
                    v-model="form.weight_kg"
                    type="number"
                    step="0.1"
                    min="0.1"
                    max="99999"
                    placeholder="0.0"
                    :class="{ 'border-destructive': errors.weight_kg }"
                />
                <p v-if="errors.weight_kg" class="text-xs text-destructive">
                    {{ errors.weight_kg }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Enter the total weight available
                </p>
            </div>

            <!-- Asking Price -->
            <div class="flex flex-col gap-2">
                <Label for="asking_price" class="flex items-center gap-1.5">
                    <DollarSign class="size-3.5" />
                    Asking Price (₱/kg)
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Input
                    id="asking_price"
                    v-model="form.asking_price"
                    type="number"
                    step="0.01"
                    min="0"
                    max="999.99"
                    placeholder="0.00"
                    :class="{ 'border-destructive': errors.asking_price }"
                />
                <p v-if="errors.asking_price" class="text-xs text-destructive">
                    {{ errors.asking_price }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Set your asking price per kilogram
                </p>
            </div>

            <!-- Expiration Date -->
            <div class="flex flex-col gap-2">
                <Label for="expiration_date" class="flex items-center gap-1.5">
                    <Calendar class="size-3.5" />
                    Expiration Date
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Input
                    id="expiration_date"
                    v-model="form.expiration_date"
                    type="date"
                    :min="minDate"
                    :max="maxDate"
                    :class="{ 'border-destructive': errors.expiration_date }"
                />
                <p v-if="errors.expiration_date" class="text-xs text-destructive">
                    {{ errors.expiration_date }}
                </p>
                <p v-else-if="!isEditMode && selectedVariety" class="text-xs text-muted-foreground">
                    Auto-filled based on {{ selectedVariety.weeks_to_harvest }}-week growth period
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    When will this planting expire?
                </p>
            </div>

            <!-- Image Upload -->
            <ImageUpload
                v-model="form.image"
                :existing-image-url="planting?.image_url"
                :error="errors.image"
                :required="!isEditMode"
            />

            <!-- Info Box -->
            <div 
                v-if="form.expiration_date" 
                class="rounded-lg border border-primary/20 bg-primary/5 p-4"
            >
                <div class="flex items-start gap-3">
                    <Info class="size-5 shrink-0 text-primary mt-0.5" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Expiration Date</p>
                        <p class="mt-1 text-lg font-bold text-primary">
                            {{ new Date(form.expiration_date).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            }) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            This planting will automatically be archived after this date
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </DialogForm>
</template>
