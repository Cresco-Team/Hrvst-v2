<script setup lang="ts">
import { MapPin, Package, Weight } from 'lucide-vue-next'
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import {
	Dialog,
	DialogDescription,
	DialogHeader,
	DialogScrollContent,
	DialogTitle,
} from '@/components/ui/dialog'
import { Separator } from '@/components/ui/separator'
import type { BarangayMarker } from '@/types/supply-map'

const props = defineProps<{
	open: boolean
	marker: BarangayMarker | null
}>()

const emit = defineEmits<{
	'update:open': [value: boolean]
}>()

const sortedBreakdown = computed(() => {
	if (!props.marker) return []
	return [...props.marker.supply_breakdown].sort((a, b) => b.count - a.count)
})
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogScrollContent class="sm:max-w-md">
      <DialogHeader>
        <div class="flex items-center gap-2">
          <MapPin class="size-4 text-primary shrink-0" />
          <DialogTitle>{{ marker?.barangay }}</DialogTitle>
        </div>
        <DialogDescription>{{ marker?.municipality }}</DialogDescription>
      </DialogHeader>

      <template v-if="marker">
        <!-- Stats row -->
        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-lg border bg-muted/30 p-3 space-y-1">
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <Package class="size-3.5" />
              <span class="text-xs">Active Supplies</span>
            </div>
            <p class="text-2xl font-bold tabular-nums">{{ marker.supply_count }}</p>
          </div>
          <div class="rounded-lg border bg-muted/30 p-3 space-y-1">
            <div class="flex items-center gap-1.5 text-muted-foreground">
              <Weight class="size-3.5" />
              <span class="text-xs">Total Volume</span>
            </div>
            <p class="text-2xl font-bold tabular-nums">
              {{ marker.total_quantity_kg.toLocaleString() }}
              <span class="text-sm font-normal text-muted-foreground">kg</span>
            </p>
          </div>
        </div>

        <Separator />

        <!-- Breakdown -->
        <div class="space-y-2">
          <p class="text-xs font-semibold text-muted-foreground uppercase tracking-wide">
            Breakdown by Vegetable
          </p>

          <div
            v-for="item in sortedBreakdown"
            :key="item.vegetable"
            class="rounded-lg border bg-muted/30 p-3 space-y-2"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2 min-w-0">
                <span class="text-sm font-medium truncate">{{ item.vegetable }}</span>
                <Badge variant="secondary" class="text-xs shrink-0">{{ item.category }}</Badge>
              </div>
              <span class="text-sm font-semibold tabular-nums shrink-0">
                {{ item.total_quantity_kg.toLocaleString() }} kg
              </span>
            </div>

            <p class="text-xs text-muted-foreground">
              {{ item.count }} {{ item.count === 1 ? 'listing' : 'listings' }}
            </p>

            <div v-if="item.varieties.length" class="flex flex-wrap gap-1">
              <Badge
                v-for="variety in item.varieties"
                :key="variety"
                variant="outline"
                class="text-xs font-normal"
              >
                {{ variety }}
              </Badge>
            </div>
          </div>

          <p
            v-if="!sortedBreakdown.length"
            class="py-4 text-center text-sm text-muted-foreground"
          >
            No active supplies in this area.
          </p>
        </div>
      </template>
    </DialogScrollContent>
  </Dialog>
</template>
