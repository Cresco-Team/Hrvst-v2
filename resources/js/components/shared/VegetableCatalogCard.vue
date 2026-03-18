<script setup lang="ts">
import { Clock, TrendingUp } from 'lucide-vue-next'
import {
	Card,
	CardContent,
	CardDescription,
	CardHeader,
	CardTitle,
} from '@/components/ui/card'
import type { CatalogVariety } from '@/types/shared/vegetables'
import { AspectRatio } from '../ui/aspect-ratio'
import { Separator } from '../ui/separator'

defineProps<{
	variety: CatalogVariety
}>()

defineEmits<{
	select: [variety: CatalogVariety]
}>()

const freshnessConfig = {
	recent: {
		label: 'Updated',
		class: 'bg-green-500 dark:text-green-400 border-green-500/20',
	},
	stable: {
		label: 'Stable',
		class: 'bg-blue-400  dark:text-blue-400 border-blue-500/20',
	},
	'very stable': {
		label: 'Older',
		class: 'bg-amber-400 dark:text-amber-400 border-amber-500/20',
	},
	stale: {
		label: 'Stale',
		class: 'bg-red-400 dark:text-red-400 border-red-500/20',
	},
} as const
</script>

<template>
  <Card
    class="group py-0 gap-2 cursor-pointer overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5"
    @click="$emit('select', variety)"
  >
    <!-- Image -->
    <AspectRatio :ratio="16/9" class="relative overflow-hidden bg-primary/10">
      <img
        v-if="variety.image_url"
        :src="variety.image_url"
        :alt="`${variety.vegetable.name} ${variety.name}`"
        class="size-full object-cover"
      />

      <div
        v-if="variety.latest_price"
        class="absolute bottom-0 right-0 rounded-tl-lg px-3 py-1 text-xs font-medium text-white"
        :class="freshnessConfig[variety.latest_price.freshness]?.class"
      >
        {{ freshnessConfig[variety.latest_price.freshness]?.label }}
      </div>
    </AspectRatio>

    <CardHeader class="p-5 py-2">
      <CardTitle>
        {{ variety.vegetable.name }} {{ variety.name }}
      </CardTitle>
      <CardDescription>
        {{ variety.vegetable.category.name }}
      </CardDescription>
      <Separator />
    </CardHeader>

    <CardContent class="flex flex-col gap-3 p-4">
      <div class="gap-3">
        <p class="text-xs text-muted-foreground">Price Range</p>
        <p class="font-mono text-sm font-semibold">
          ₱{{ variety.latest_price?.price_min.toFixed(2) }} – ₱{{ variety.latest_price?.price_max.toFixed(2) }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
