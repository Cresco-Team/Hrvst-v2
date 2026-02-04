<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Badge } from '@/components/ui/badge'
import { MapPin, Sprout, X } from 'lucide-vue-next'

interface Municipality {
    id: number
    name: string
    province: string
    label: string
}

interface PlantingOption {
    id: number
    name: string
    category: string
}

interface PlantingsByCategory {
    [category: string]: PlantingOption[]
}

const props = defineProps<{
    municipalities: Municipality[]
    plantings: PlantingsByCategory
    selectedMunicipality: string | null
    selectedVariety: string | null
}>()

const emit = defineEmits<{
    'update:selectedMunicipality': [value: string | null]
    'update:selectedVariety': [value: string | null]
    'clear': []
}>()

const hasActiveFilters = computed(() => 
    props.selectedMunicipality !== null || props.selectedVariety !== null
)

const activeFilterCount = computed(() => {
    let count = 0
    if (props.selectedMunicipality) count++
    if (props.selectedVariety) count++
    return count
})

const handleMunicipalityChange = (value: any) => {
    emit('update:selectedMunicipality', value === 'all' ? null : value)
}

const handleVarietyChange = (value: any) => {
    emit('update:selectedVariety', value === 'all' ? null : value)
}

const clearFilters = () => {
    emit('clear')
}
</script>

<template>
    <div class="flex flex-col gap-3 rounded-lg border bg-card p-4 shadow-sm sm:flex-row sm:items-center sm:gap-3">
        <!-- Municipality Filter -->
        <div class="flex flex-1 flex-col gap-1.5">
            <label class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <MapPin class="size-3.5" />
                Municipality
            </label>
            <Select 
                :model-value="selectedMunicipality || 'all'" 
                @update:model-value="handleMunicipalityChange"
            >
                <SelectTrigger size="sm">
                    <SelectValue placeholder="All municipalities" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All municipalities</SelectItem>
                    <SelectItem
                        v-for="municipality in municipalities"
                        :key="municipality.id"
                        :value="municipality.id.toString()"
                    >
                        {{ municipality.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Variety Filter -->
        <div class="flex flex-1 flex-col gap-1.5">
            <label class="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                <Sprout class="size-3.5" />
                Planting/Variety
            </label>
            <Select 
                :model-value="selectedVariety || 'all'" 
                @update:model-value="handleVarietyChange"
            >
                <SelectTrigger size="sm">
                    <SelectValue placeholder="All varieties" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All varieties</SelectItem>
                    <SelectGroup 
                        v-for="(varieties, category) in plantings" 
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
        </div>

        <!-- Clear Filters Button -->
        <div class="flex items-end sm:pt-5">
            <Button
                v-if="hasActiveFilters"
                variant="outline"
                size="sm"
                class="w-full gap-1.5 sm:w-auto"
                @click="clearFilters"
            >
                <X class="size-4" />
                Clear
                <Badge variant="secondary" class="ml-1 px-1.5 py-0.5">
                    {{ activeFilterCount }}
                </Badge>
            </Button>
        </div>
    </div>
</template>
