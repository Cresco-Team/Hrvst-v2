<script setup lang="ts">

import { Leaf, Clock, TrendingUp, AlertCircle } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import type { CatalogVariety } from '@/types/shared/vegetables'

defineProps<{
  variety: CatalogVariety
}>()

defineEmits<{
  select: [variety: CatalogVariety]
}>()

const freshnessConfig = {
  recent: { label: 'Updated', class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20' },
  stable: { label: 'Stable', class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20' },
  'very stable': { label: 'Older', class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20' },
  stale: { label: 'Stale', class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20' },
} as const
</script>

<template>
  <Card
    class="group cursor-pointer overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
    @click="$emit('select', variety)"
  >
    <!-- Image -->
    <div class="relative aspect-4/3 overflow-hidden bg-muted">
      <img
        v-if="variety.image_url"
        :src="variety.image_url"
        :alt="`${variety.vegetable.name} ${variety.name}`"
        class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
      />
      <div v-else class="flex size-full items-center justify-center">
        <Leaf class="size-12 text-muted-foreground/30" />
      </div>

      <!-- Category badge overlay -->
      <div class="absolute left-2 top-2">
        <Badge variant="secondary" class="text-xs backdrop-blur-sm bg-background/80">
          {{ variety.vegetable.category.name }}
        </Badge>
      </div>
    </div>

    <CardContent class="flex flex-col gap-3 p-4">
      <!-- Name -->
      <div>
        <p class="text-xs text-muted-foreground">{{ variety.vegetable.name }}</p>
        <h3 class="font-semibold leading-tight">{{ variety.name }}</h3>
      </div>

      <!-- Price -->
      <div v-if="variety.latest_price" class="flex items-start justify-between gap-2">
        <div>
          <p class="text-xs text-muted-foreground">Price range</p>
          <p class="font-mono text-sm font-semibold text-foreground">
            ₱{{ variety.latest_price.price_min.toFixed(2) }} – ₱{{ variety.latest_price.price_max.toFixed(2) }}
          </p>
          <p class="mt-0.5 text-xs text-muted-foreground">
            {{ variety.latest_price.recorded_at }}
          </p>
        </div>
        <Badge
          variant="outline"
          class="shrink-0 text-xs"
          :class="freshnessConfig[variety.latest_price.freshness]?.class"
        >
          {{ freshnessConfig[variety.latest_price.freshness]?.label }}
        </Badge>
      </div>

      <div v-else class="flex items-center gap-1.5 text-xs text-muted-foreground">
        <AlertCircle class="size-3.5" />
        No price data yet
      </div>

      <!-- Weeks to harvest -->
      <div class="flex items-center gap-1.5 border-t pt-3 text-xs text-muted-foreground">
        <Clock class="size-3.5 shrink-0" />
        <span>{{ variety.weeks_to_harvest }} weeks to harvest</span>
        <TrendingUp class="ml-auto size-3.5 text-muted-foreground/50 transition-colors group-hover:text-primary" />
      </div>
    </CardContent>
  </Card>
</template>
