<script setup lang="ts">
import { Filter, X } from 'lucide-vue-next'
import type { AcceptableValue } from 'reka-ui'
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import type { FilterOptions, MapFilters } from '@/types/supply-map'

const props = defineProps<{
  filters: MapFilters
  options: FilterOptions | null
  totalMarkers: number
  totalSupplies: number
}>()

const emit = defineEmits<{
  'update:filters': [filters: MapFilters]
  clear: []
}>()

const hasActiveFilters = computed(
  () => props.filters.category_id !== null || props.filters.variety_id !== null
)

function handleCategoryChange(value: AcceptableValue) {
  emit('update:filters', {
    category_id: value === 'all' ? null : Number(value),
    // reset variety when category changes
    variety_id: null,
  })
}

function handleVarietyChange(value: AcceptableValue) {
  emit('update:filters', {
    ...props.filters,
    variety_id: value === 'all' ? null : Number(value),
  })
}
</script>

<template>
  <div class="rounded-lg border bg-card shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3 border-b">
      <div class="flex items-center gap-2">
        <Filter class="size-4 text-primary" />
        <span class="text-sm font-semibold">Filters</span>
        <Badge v-if="hasActiveFilters" variant="default" class="text-xs h-5">Active</Badge>
      </div>
      <Button
        v-if="hasActiveFilters"
        variant="ghost"
        size="sm"
        class="h-7 gap-1 text-xs text-muted-foreground"
        @click="emit('clear')"
      >
        <X class="size-3" />
        Clear
      </Button>
    </div>

    <div class="p-4 space-y-4">
      <!-- Category -->
      <div class="space-y-1.5">
        <Label class="text-xs text-muted-foreground">Category</Label>
        <template v-if="!options">
          <Skeleton class="h-9 w-full" />
        </template>
        <Select
          v-else
          :model-value="filters.category_id?.toString() ?? 'all'"
          @update:model-value="handleCategoryChange"
        >
          <SelectTrigger class="h-9 text-sm">
            <SelectValue placeholder="All categories" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All categories</SelectItem>
            <SelectItem
              v-for="cat in options.categories"
              :key="cat.id"
              :value="cat.id.toString()"
            >
              {{ cat.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Variety -->
      <div class="space-y-1.5">
        <Label class="text-xs text-muted-foreground">Variety</Label>
        <template v-if="!options">
          <Skeleton class="h-9 w-full" />
        </template>
        <Select
          v-else
          :model-value="filters.variety_id?.toString() ?? 'all'"
          @update:model-value="handleVarietyChange"
        >
          <SelectTrigger class="h-9 text-sm">
            <SelectValue placeholder="All varieties" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All varieties</SelectItem>
            <SelectGroup
              v-for="(varieties, category) in options.varieties"
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

      <!-- Map stats -->
      <div class="rounded-md border bg-muted/30 p-3 space-y-2">
        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
          Visible on Map
        </p>
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted-foreground">Barangays</span>
          <span class="font-mono font-medium">{{ totalMarkers }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
          <span class="text-muted-foreground">Active supplies</span>
          <span class="font-mono font-medium">{{ totalSupplies }}</span>
        </div>
      </div>

      <!-- Legend -->
      <div class="space-y-2">
        <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
          Legend
        </p>
        <div class="space-y-1.5 text-xs text-muted-foreground">
          <div class="flex items-center gap-2">
            <div class="size-3 shrink-0 rounded-full bg-blue-500" />
            <span>Cluster (multiple barangays)</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="size-3 shrink-0 rounded-full bg-emerald-500" />
            <span>Leafy greens dominant</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="size-3 shrink-0 rounded-full bg-orange-500" />
            <span>Root vegetables dominant</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="size-3 shrink-0 rounded-full bg-teal-500" />
            <span>Brassicas dominant</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="size-3 shrink-0 rounded-full bg-yellow-500" />
            <span>Bulb vegetables dominant</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
