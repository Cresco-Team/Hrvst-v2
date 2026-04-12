<script setup lang="ts">
import { computed } from 'vue'
import { Minus, TrendingDown, TrendingUp } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import type { VarietyAnalytics } from '@/types/resources/product'

const props = defineProps<{
  analytics: VarietyAnalytics
}>()

// ── Market balance ────────────────────────────────────────────────────────────

const bandConfig = computed(() => {
  switch (props.analytics.imbalance_band) {
    case 'oversupply':
      return {
        label: 'Oversupply',
        valueClass: 'text-red-600 dark:text-red-400',
        borderClass: 'border-l-red-500',
      }
    case 'undersupply':
      return {
        label: 'Undersupply',
        valueClass: 'text-amber-600 dark:text-amber-400',
        borderClass: 'border-l-amber-500',
      }
    default:
      return {
        label: 'Balanced',
        valueClass: 'text-green-600 dark:text-green-400',
        borderClass: 'border-l-green-500',
      }
  }
})

const ratioLabel = computed(() => {
  const r = props.analytics.supply_demand_ratio
  const pct = Math.round(Math.abs(r) * 100)
  if (r > 0) return `+${pct}% excess supply`
  if (r < 0) return `${pct}% excess demand`
  return 'supply meets demand'
})

// ── Fulfillment rates ─────────────────────────────────────────────────────────

function fulfillmentClasses(rate: number | null): string {
  if (rate === null) return 'text-muted-foreground'
  if (rate >= 0.7) return 'text-green-600 dark:text-green-400'
  if (rate >= 0.5) return 'text-amber-600 dark:text-amber-400'
  return 'text-red-600 dark:text-red-400'
}

function fulfillmentBorderClass(rate: number | null): string {
  if (rate === null) return 'border-l-border'
  if (rate >= 0.7) return 'border-l-green-500'
  if (rate >= 0.5) return 'border-l-amber-500'
  return 'border-l-red-500'
}

function formatRate(rate: number | null): string {
  return rate !== null ? `${Math.round(rate * 100)}%` : '—'
}

// ── Price momentum ────────────────────────────────────────────────────────────

const momentumConfig = computed(() => {
  const pct = props.analytics.price_momentum_pct

  if (pct === null) {
    return {
      label: '—',
      valueClass: 'text-muted-foreground',
      borderClass: 'border-l-border',
      icon: Minus,
    }
  }

  const flat = Math.abs(pct) < 1

  return {
    label: flat ? 'Flat' : `${pct > 0 ? '+' : ''}${pct.toFixed(1)}%`,
    valueClass: flat
      ? 'text-muted-foreground'
      : pct > 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400',
    borderClass: flat
      ? 'border-l-border'
      : pct > 0
        ? 'border-l-green-500'
        : 'border-l-red-500',
    icon: flat ? Minus : pct > 0 ? TrendingUp : TrendingDown,
  }
})

const isStale = computed(
  () => props.analytics.price_weeks_stale !== null && props.analytics.price_weeks_stale > 4,
)
</script>

<template>
  <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">

    <!-- Market Balance -->
    <Card :class="['gap-0 py-4 overflow-hidden border-l-4', bandConfig.borderClass]">
      <CardContent class="px-4">
        <p class="text-xs text-muted-foreground line-clamp-1">Market Balance</p>
      </CardContent>
      <CardContent class="px-4 pt-1">
        <p :class="['text-xl font-bold font-mono', bandConfig.valueClass]">
          {{ bandConfig.label }}
        </p>
        <p class="text-xs text-muted-foreground truncate mt-0.5">{{ ratioLabel }}</p>
      </CardContent>
    </Card>

    <!-- Supply Fulfillment -->
    <Card :class="['gap-0 py-4 overflow-hidden border-l-4', fulfillmentBorderClass(analytics.supply_fulfillment_rate)]">
      <CardContent class="px-4">
        <p class="text-xs text-muted-foreground">Supply Fulfillment</p>
      </CardContent>
      <CardContent class="px-4 pt-1">
        <p :class="['text-xl font-bold font-mono', fulfillmentClasses(analytics.supply_fulfillment_rate)]">
          {{ formatRate(analytics.supply_fulfillment_rate) }}
        </p>
        <p class="text-xs text-muted-foreground mt-0.5">3-month avg</p>
      </CardContent>
    </Card>

    <!-- Demand Fulfillment -->
    <Card :class="['gap-0 py-4 overflow-hidden border-l-4', fulfillmentBorderClass(analytics.demand_fulfillment_rate)]">
      <CardContent class="px-4">
        <p class="text-xs text-muted-foreground">Demand Fulfillment</p>
      </CardContent>
      <CardContent class="px-4 pt-1">
        <p :class="['text-xl font-bold font-mono', fulfillmentClasses(analytics.demand_fulfillment_rate)]">
          {{ formatRate(analytics.demand_fulfillment_rate) }}
        </p>
        <p class="text-xs text-muted-foreground mt-0.5">3-month avg</p>
      </CardContent>
    </Card>

    <!-- Price Momentum -->
    <Card :class="['gap-0 py-4 overflow-hidden border-l-4', momentumConfig.borderClass]">
      <CardContent class="px-4">
        <p class="text-xs text-muted-foreground">Price Momentum</p>
      </CardContent>
      <CardContent class="px-4 pt-1">
        <div class="flex items-center gap-1.5">
          <component
            :is="momentumConfig.icon"
            class="size-4 shrink-0"
            :class="momentumConfig.valueClass"
          />
          <p :class="['text-xl font-bold font-mono', momentumConfig.valueClass]">
            {{ momentumConfig.label }}
          </p>
        </div>
        <div class="flex items-center gap-1.5 mt-0.5">
          <p class="text-xs text-muted-foreground">over recorded history</p>
          <Badge
            v-if="isStale"
            variant="secondary"
            class="text-[10px] px-1.5 py-0 h-4"
          >
            {{ Math.round(analytics.price_weeks_stale!) }}w stale
          </Badge>
        </div>
      </CardContent>
    </Card>

  </div>
</template>
