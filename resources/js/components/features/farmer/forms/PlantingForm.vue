<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/components/ui/dialog'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import { Badge } from '@/components/ui/badge'
import { Sprout, Weight, Calendar, Info } from 'lucide-vue-next'

interface Planting {
    id: number
    variety: {
        id: number
    }
    weight_kg: number
    date_planted: string
}

interface VarietyOption {
    id: number
    name: string
    weeks_to_harvest: number
}

interface VarietyOptions {
    [categoryName: string]: VarietyOption[]
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

/* -- local form state -- */
const form = ref({
    variety_id: '',
    weight_kg: '',
    date_planted: '',
})

const errors = ref<{
    variety_id?: string
    weight_kg?: string
    date_planted?: string
}>({})

/* -- reset form on modal open/close or planting change -- */
watch(
    () => [props.open, props.planting],
    () => {
        form.value = {
            variety_id: props.planting?.variety.id?.toString() ?? '',
            weight_kg: props.planting?.weight_kg?.toString() ?? '',
            date_planted: props.planting?.date_planted ?? '',
        }
        errors.value = {}
    },
)

/* -- computed -- */
const isEditMode = computed(() => !!props.planting)
const title = computed(() => isEditMode.value ? 'Edit Planting' : 'Add New Planting')
const description = computed(() => 
    isEditMode.value
        ? 'Update the weight of your planting.'
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

const expectedHarvestDate = computed(() => {
    if (!selectedVariety.value || !form.value.date_planted) return null
    
    const planted = new Date(form.value.date_planted)
    if (isNaN(planted.getTime())) return null
    
    const harvestDate = new Date(planted)
    harvestDate.setDate(harvestDate.getDate() + (selectedVariety.value.weeks_to_harvest * 7))
    
    return harvestDate.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    })
})

const maxDate = computed(() => {
    const today = new Date()
    return today.toISOString().split('T')[0]
})

const minDate = computed(() => {
    const oneYearAgo = new Date()
    oneYearAgo.setFullYear(oneYearAgo.getFullYear() - 1)
    return oneYearAgo.toISOString().split('T')[0]
})

/* -- validation -- */
function validate(): boolean {
    errors.value = {}

    if (!form.value.variety_id) {
        errors.value.variety_id = 'Please select a variety'
    }
    
    if (!form.value.weight_kg) {
        errors.value.weight_kg = 'Weight is required'
    } else {
        const weight = parseFloat(form.value.weight_kg)
        if (isNaN(weight) || weight < 0.1) {
            errors.value.weight_kg = 'Weight must be at least 0.1 kg'
        } else if (weight > 99999) {
            errors.value.weight_kg = 'Weight cannot exceed 99,999 kg'
        }
    }
    
    if (!form.value.date_planted) {
        errors.value.date_planted = 'Planting date is required'
    }

    return Object.keys(errors.value).length === 0
}

function handleSubmit() {
    if (!validate()) return

    const formData = new FormData()
    
    if (!isEditMode.value) {
        formData.append('variety_id', form.value.variety_id)
        formData.append('date_planted', form.value.date_planted)
    }
    
    formData.append('weight_kg', form.value.weight_kg)

    emit('submit', formData)
}

function close() {
    emit('update:open', false)
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="flex max-h-[85vh] flex-col gap-0 p-0 sm:max-w-lg">
            <!-- Fixed Header -->
            <DialogHeader class="space-y-2 border-b px-6 py-4">
                <DialogTitle class="flex items-center gap-2">
                    <Sprout class="size-5 text-primary" />
                    {{ title }}
                </DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto px-6 py-4">
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

                    <!-- Expected Harvest Date Preview (create only) -->
                    <div 
                        v-if="!isEditMode && expectedHarvestDate" 
                        class="rounded-lg border border-primary/20 bg-primary/5 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <Info class="size-5 shrink-0 text-primary mt-0.5" />
                            <div>
                                <p class="text-sm font-medium">Expected Harvest Date</p>
                                <p class="mt-1 text-lg font-bold text-primary">
                                    {{ expectedHarvestDate }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Based on {{ selectedVariety?.weeks_to_harvest }} week growing period
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fixed Footer -->
            <DialogFooter class="border-t px-6 py-4">
                <div class="flex w-full gap-2 sm:justify-end">
                    <Button variant="outline" @click="close" :disabled="isSubmitting">
                        Cancel
                    </Button>
                    <Button @click="handleSubmit" :disabled="isSubmitting">
                        <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                        {{ isEditMode ? 'Save Changes' : 'Add Planting' }}
                    </Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
