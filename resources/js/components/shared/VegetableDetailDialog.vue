<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight, Leaf, TrendingDown, TrendingUp, Minus } from 'lucide-vue-next'
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog'
import { Separator } from '@/components/ui/separator'
import type { CatalogVariety } from '@/types/shared/vegetables'

const props = defineProps<{
  open: boolean
  variety: CatalogVariety | null
  /** Role-specific URL to the full detail page. */
  viewHref?: string
}>()

defineEmits<{
  'update:open': [value: boolean]
}>()

const freshnessConfig = {
  recent: {
    label: 'Recently Updated',
    class: 'bg-green-500/10 text-green-700 dark:text-green-400 border-green-500/20',
  },
  stable: {
    label: 'Stable',
    class: 'bg-blue-500/10 text-blue-700 dark:text-blue-400 border-blue-500/20',
  },
  'very stable': {
    label: 'Older Price',
    class: 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border-amber-500/20',
  },
  stale: {
    label: 'Stale Price',
    class: 'bg-red-500/10 text-red-700 dark:text-red-400 border-red-500/20',
  },
} as const

const trendConfig = computed(() => {
  switch (props.variety?.price_trend) {
    case 'up':
      return { icon: TrendingUp, label: 'Price trending up', class: 'text-red-500 dark:text-red-400' }
    case 'down':
      return { icon: TrendingDown, label: 'Price trending down', class: 'text-green-600 dark:text-green-400' }
    case 'flat':
      return { icon: Minus, label: 'Price stable', class: 'text-muted-foreground' }
    default:
      return null
  }
})
</script>

<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="w-full max-w-sm p-0 overflow-hidden">

      <!-- Hero image -->
      <div class="relative h-36 w-full overflow-hidden bg-muted">
        <img v-if="variety?.image_url" :src="variety.image_url" :alt="`${variety.vegetable.name} ${variety.name}`"
          class="size-full object-cover" />
        <div v-else class="flex size-full items-center justify-center">
          <Leaf class="size-12 text-muted-foreground/20" />
        </div>
        <div class="absolute inset-0 bg-linear-to-t from-background/90 via-background/20 to-transparent" />

        <!-- Freshness badge -->
        <div class="absolute bottom-3 left-4 flex items-center gap-2">
          <Badge v-if="variety?.latest_price" variant="outline" class="text-xs"
            :class="freshnessConfig[variety.latest_price.freshness]?.class">
            {{ freshnessConfig[variety.latest_price.freshness]?.label }}
          </Badge>
        </div>
      </div>

      <!-- Header -->
      <div class="px-5 pt-2 pb-0">
        <DialogTitle class="text-base font-bold leading-tight">
          {{ variety?.vegetable.name }}
          <span class="text-primary">{{ variety?.name }}</span>
        </DialogTitle>
        <DialogDescription class="text-xs text-muted-foreground">
          {{ variety?.vegetable.category.name }}
        </DialogDescription>
      </div>

      <Separator class="mx-5" style="width: auto;" />

      <!-- KPI grid -->
      <div v-if="variety" class="grid grid-cols-2 gap-3 px-5 pb-1">

        <!-- Current price -->
        <div class="rounded-lg border bg-muted/30 p-3">
          <p class="text-xs text-muted-foreground mb-1">Market Price</p>
          <div v-if="variety.latest_price" class="flex items-baseline gap-1">
            <span class="font-mono text-sm font-semibold text-green-600 dark:text-green-400">
              ₱{{ Number(variety.latest_price.price_min).toFixed(2) }}
            </span>
            <span class="text-xs text-muted-foreground">–</span>
            <span class="font-mono text-sm font-semibold text-indigo-600 dark:text-indigo-400">
              ₱{{ Number(variety.latest_price.price_max).toFixed(2) }}
            </span>
          </div>
          <p v-else class="text-sm text-muted-foreground">No data</p>
        </div>

        <!-- Price trend -->
        <div class="rounded-lg border bg-muted/30 p-3">
          <p class="text-xs text-muted-foreground mb-1">Price Trend</p>
          <div v-if="trendConfig" class="flex items-center gap-1.5">
            <component :is="trendConfig.icon" class="size-4" :class="trendConfig.class" />
            <span class="text-sm font-medium" :class="trendConfig.class">
              {{ variety.price_trend === 'up' ? 'Rising' : variety.price_trend === 'down' ? 'Falling' : 'Stable' }}
            </span>
          </div>
          <p v-else class="text-sm text-muted-foreground">Not enough data</p>
        </div>

        <!-- Active supplies -->
        <div class="rounded-lg border bg-muted/30 p-3">
          <p class="text-xs text-muted-foreground mb-1">Active Supplies</p>
          <p class="text-2xl font-bold tabular-nums">{{ variety.supply_count }}</p>
        </div>

        <!-- Active demands -->
        <div class="rounded-lg border bg-muted/30 p-3">
          <p class="text-xs text-muted-foreground mb-1">Active Demands</p>
          <p class="text-2xl font-bold tabular-nums">{{ variety.demand_count }}</p>
        </div>

      </div>

      <!-- Last updated + hearts footer -->
      <div v-if="variety" class="flex items-center justify-between px-5 pb-1 text-xs text-muted-foreground">
        <span v-if="variety.price_updated_human">
          Price updated {{ variety.price_updated_human }}
        </span>
        <span v-else>No price on record</span>
        <span>{{ variety.hearts_count }} interested</span>
      </div>

      <!-- CTA -->
      <div v-if="viewHref" class="px-5 pb-5">
        <Button as-child class="w-full gap-2">
          <Link :href="viewHref">
            View Full Details
            <ArrowRight class="size-4" />
          </Link>
        </Button>
      </div>

    </DialogContent>
  </Dialog>
</template>
