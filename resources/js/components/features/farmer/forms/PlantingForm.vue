<script setup lang="ts">
import { computed, watch } from 'vue'
import DialogForm from '@/components/shared/forms/DialogForm.vue'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Sprout, Weight, Calendar, Info, CalendarClock } from 'lucide-vue-next'
import { useDialogForm } from '@/composables/useDialogForm'

interface Planting {
    id: number
    variety: {
        id: number
    }
    weight_kg: number
    date_planted: string
    expected_harvest_date: string
}

interface VarietyOption {
    id: number
    name: string
    weeks_to_harvest: number
}

interface VarietyOptions {
    [categoryName: string]: VarietyOption[]
}

interface PlantingFormData {
    variety_id: string
    weight_kg: string
    date_planted: string
    expected_harvest_date: string
}

const props = defineProps<{
    open: boolean
    planting: Planting | null
    varietyOptions: VarietyOptions
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
        variety_id: planting?.variety.id?.toString() ?? '',
        weight_kg: planting?.weight_kg?.toString() ?? '',
        date_planted: planting?.date_planted ?? '',
        expected_harvest_date: planting?.expected_harvest_date ?? '',
    }),
    validate: (form) => {
        const errors: Record<string, string> = {}
        
        if (!isEditMode.value) {
            if (!form.variety_id) {
                errors.variety_id = 'Please select a variety'
            }
            if (!form.date_planted) {
                errors.date_planted = 'Planting date is required'
            }
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

        if (!form.expected_harvest_date) {
            errors.expected_harvest_date = 'Expected harvest date is required'
        } else if (form.date_planted) {
            const planted = new Date(form.date_planted)
            const harvest = new Date(form.expected_harvest_date)
            
            if (harvest <= planted) {
                errors.expected_harvest_date = 'Harvest date must be after planting date'
            }
        }
        
        return errors
    },
})

const title = computed(() => isEditMode.value ? 'Edit Planting' : 'Add New Planting')
const description = computed(() => 
    isEditMode.value
        ? 'Update the weight and expected harvest date of your planting.'
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

// Auto-fill harvest date when variety + planting date change
watch(
    [() => form.value.variety_id, () => form.value.date_planted],
    ([varietyId, datePlanted]) => {
        if (isEditMode.value) return
        if (!varietyId || !datePlanted) return
        
        const variety = selectedVariety.value
        if (!variety) return
        
        const planted = new Date(datePlanted)
        if (isNaN(planted.getTime())) return
        
        const harvestDate = new Date(planted)
        harvestDate.setDate(harvestDate.getDate() + (variety.weeks_to_harvest * 7))
        
        form.value.expected_harvest_date = harvestDate.toISOString().split('T')[0]
    },
)

const maxDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

const minDate = computed(() => {
    const oneYearAgo = new Date()
    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1)
    return oneYearAgo.toISOString().split('T')[0]
})

const minHarvestDate = computed(() => {
    if (!form.value.date_planted) return undefined
    
    const planted = new Date(form.value.date_planted)
    planted.setDate(planted.getDate() + 1)
    return planted.toISOString().split('T')[0]
})

function handleSubmit() {
    if (!validateForm()) return

    const formData = new FormData()
    formData.append('weight_kg', form.value.weight_kg)
    formData.append('expected_harvest_date', form.value.expected_harvest_date)
    
    if (!isEditMode.value) {
        formData.append('variety_id', form.value.variety_id)
        formData.append('date_planted', form.value.date_planted)
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
                        <SelectValue placeholder="Select a variety to plant..." />
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
                    Enter the total weight being planted
                </p>
            </div>

            <!-- Date Planted (create only) -->
            <div v-if="!isEditMode" class="flex flex-col gap-2">
                <Label for="date_planted" class="flex items-center gap-1.5">
                    <Calendar class="size-3.5" />
                    Date Planted
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Input
                    id="date_planted"
                    v-model="form.date_planted"
                    type="date"
                    :max="maxDate"
                    :min="minDate"
                    :class="{ 'border-destructive': errors.date_planted }"
                />
                <p v-if="errors.date_planted" class="text-xs text-destructive">
                    {{ errors.date_planted }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    When did you plant this?
                </p>
            </div>

            <!-- Expected Harvest Date -->
            <div class="flex flex-col gap-2">
                <Label for="expected_harvest_date" class="flex items-center gap-1.5">
                    <CalendarClock class="size-3.5" />
                    Expected Harvest Date
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                </Label>
                <Input
                    id="expected_harvest_date"
                    v-model="form.expected_harvest_date"
                    type="date"
                    :min="minHarvestDate"
                    :class="{ 'border-destructive': errors.expected_harvest_date }"
                />
                <p v-if="errors.expected_harvest_date" class="text-xs text-destructive">
                    {{ errors.expected_harvest_date }}
                </p>
                <p v-else-if="!isEditMode && selectedVariety" class="text-xs text-muted-foreground">
                    Auto-filled based on {{ selectedVariety.weeks_to_harvest }}-week growth period. You can adjust this.
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Adjust the harvest date based on actual conditions
                </p>
            </div>

            <!-- Info Box -->
            <div 
                v-if="!isEditMode && form.expected_harvest_date" 
                class="rounded-lg border border-primary/20 bg-primary/5 p-4"
            >
                <div class="flex items-start gap-3">
                    <Info class="size-5 shrink-0 text-primary mt-0.5" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Expected Harvest</p>
                        <p class="mt-1 text-lg font-bold text-primary">
                            {{ new Date(form.expected_harvest_date).toLocaleDateString('en-US', { 
                                year: 'numeric', 
                                month: 'short', 
                                day: 'numeric' 
                            }) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            You can change this date if needed based on actual growth conditions.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </DialogForm>
</template>
