<script setup lang="ts">
import axios from 'axios';
import { Heart } from 'lucide-vue-next';
import { ref } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { cn } from '@/lib/utils';
import type { CatalogVariety } from '@/types/shared/vegetables'
import { AspectRatio } from '../ui/aspect-ratio'
import { Separator } from '../ui/separator'

const props = defineProps<{
  variety: CatalogVariety
}>()

defineEmits<{
  select: [variety: CatalogVariety]
}>()

const localHearted = ref(props.variety.is_hearted)
const localCount = ref(props.variety.hearts_count)
const isPending = ref(false)

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

async function toggleHeart(event: MouseEvent): Promise<void> {
  event.stopPropagation()

  if (isPending.value) return

  // Optimistic update
  const wasHearted = localHearted.value
  localHearted.value = !wasHearted
  localCount.value += wasHearted ? -1 : 1
  isPending.value = true

  try {
    const { data } = await axios.post<{ hearted: boolean; hearts_count: number }>(
      `/varieties/${props.variety.id}/heart`
    )
    localHearted.value = data.hearted
    localCount.value = data.hearts_count
  } catch {
    // Revert on failure
    localHearted.value = wasHearted
    localCount.value += wasHearted ? 1 : -1
  } finally {
    isPending.value = false
  }
}
</script>

<template>
  <Card class="py-0 gap-2 cursor-pointer transition-all hover:shadow-lg hover:-translate-y-0.5"
    @click="$emit('select', variety)">
    <!-- Image -->
    <AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
      <img v-if="variety.image_url" :src="variety.image_url" :alt="`${variety.vegetable.name} ${variety.name} image`" />

      <div v-if="variety.latest_price"
        class="absolute bottom-0 right-0 rounded-tl-lg px-3 py-1 text-xs font-medium text-white"
        :class="freshnessConfig[variety.latest_price.freshness]?.class">
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

      <div class="flex items-center justify-between pt-1">
        <button
          class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-rose-500 disabled:pointer-events-none disabled:opacity-50"
          :disabled="isPending" @click="toggleHeart">
          <Heart class="size-4 transition-all" :class="cn(
            localHearted
              ? 'fill-rose-500 text-rose-500 scale-110'
              : 'fill-none'
          )" />
          <span class="tabular-nums">{{ localCount }}</span>
        </button>
      </div>
    </CardContent>
  </Card>
</template>
