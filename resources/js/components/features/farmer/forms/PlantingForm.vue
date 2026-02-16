<script setup lang="ts">
import { computed, watch } from 'vue'
import type { InertiaForm } from '@inertiajs/vue3'
import DialogForm from '@/components/DialogForm.vue'
import ImageUpload from '@/components/shared/media/ImageUpload.vue'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Sprout, Weight, Calendar, Info, DollarSign } from 'lucide-vue-next'
import type { VarietyOptionsByCategory } from '@/types/farmer/garden'

interface Offering {
    id: number
    farmer: {
        id: number
        name: string
    }
    variety: {
        id: number
        name: string
        category: string
    }
    image_url: string
    weight_kg: string
    asking_price: number
    expiration_date: string
    days_until_expiration: string
    status: string
    created_at_human: string
}

interface OfferingFormData {
    variety_id: string
    weight_kg: string
    asking_price: string
    expiration_date: string
    image: File | null
}

const props = defineProps<{
    open: boolean
    offering: Offering | null
    varietyOptions: VarietyOptionsByCategory
    form: InertiaForm<OfferingFormData>
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    submit: []
}>()

const isEditMode = computed(() => !!props.offering)

const title = computed(() => isEditMode.value ? 'Edit Offering' : 'Post New Offering')
const description = computed(() => 
    isEditMode.value
        ? 'Update the details of your offering.'
        : 'Create a new offering for the marketplace.'
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

// Client-side validation - run before submit
function validateForm(): boolean {
    // Clear existing errors first
    props.form.clearErrors()
    
    const errors: Partial<Record<keyof OfferingFormData, string>> = {}
    
    if (!isEditMode.value && !props.form.variety_id) {
        errors.variety_id = 'Please select a variety'
    }
    
    if (!props.form.weight_kg) {
        errors.weight_kg = 'Weight is required'
    } else {
        const weight = parseFloat(props.form.weight_kg)
        if (isNaN(weight) || weight < 0.1) {
            errors.weight_kg = 'Weight must be at least 0.1 kg'
        } else if (weight > 99999) {
            errors.weight_kg = 'Weight cannot exceed 99,999 kg'
        }
    }

    if (!props.form.asking_price) {
        errors.asking_price = 'Asking price is required'
    } else {
        const price = parseFloat(props.form.asking_price)
        if (isNaN(price) || price < 0) {
            errors.asking_price = 'Price must be at least ₱0.00'
        } else if (price > 999.99) {
            errors.asking_price = 'Price cannot exceed ₱999.99'
        }
    }

    if (!props.form.expiration_date) {
        errors.expiration_date = 'Expiration date is required'
    } else {
        const expiration = new Date(props.form.expiration_date)
        const today = new Date()
        today.setHours(0, 0, 0, 0)
        
        if (expiration <= today) {
            errors.expiration_date = 'Expiration date must be in the future'
        }
    }

    if (!isEditMode.value && !props.form.image) {
        errors.image = 'Image is required'
    }
    
    // Set errors on form if any exist
    if (Object.keys(errors).length > 0) {
        props.form.setError(errors)
        return false
    }
    
    return true
}

function handleSubmit() {
    if (!validateForm()) {
        return
    }
    
    emit('submit')
}
</script>

<template>
    <DialogForm
        :open="open"
        :title="title"
        :description="description"
        :is-submitting="form.processing"
        :submit-label="isEditMode ? 'Save Changes' : 'Post Offering'"
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
                                :value="variety.id.toString()"
                            >
                                {{ variety.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <p v-if="form.errors.variety_id" class="text-xs text-destructive">
                    {{ form.errors.variety_id }}
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
                    :class="{ 'border-destructive': form.errors.weight_kg }"
                />
                <p v-if="form.errors.weight_kg" class="text-xs text-destructive">
                    {{ form.errors.weight_kg }}
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
                    :class="{ 'border-destructive': form.errors.asking_price }"
                />
                <p v-if="form.errors.asking_price" class="text-xs text-destructive">
                    {{ form.errors.asking_price }}
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
                    :class="{ 'border-destructive': form.errors.expiration_date }"
                />
                <p v-if="form.errors.expiration_date" class="text-xs text-destructive">
                    {{ form.errors.expiration_date }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    When will this planting expire?
                </p>
            </div>

            <!-- Image Upload -->
            <ImageUpload
                v-model="form.image"
                :existing-image-url="offering?.image_url"
                :error="form.errors.image"
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
