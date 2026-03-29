<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { CalendarSync, Heart, Minus, TrendingDown, TrendingUp } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { cn } from '@/lib/utils'
import type { PriceTrend, VarietyResource } from '@/types'

const props = defineProps<{
	variety: VarietyResource
	href: string
}>()

const localHearted = ref(props.variety.is_hearted)
const localCount = ref(props.variety.hearts_count)
const isPending = ref(false)

async function toggleHeart(event: MouseEvent): Promise<void> {
	event.stopPropagation()

	if (isPending.value) return

	const wasHearted = localHearted.value
	localHearted.value = !wasHearted
	localCount.value += wasHearted ? -1 : 1
	isPending.value = true

	try {
		const { data } = await axios.post<{ hearted: boolean; hearts_count: number }>(
			`/varieties/${props.variety.id}/heart`,
		)
		localHearted.value = data.hearted
		localCount.value = data.hearts_count
	} catch {
		localHearted.value = wasHearted
		localCount.value += wasHearted ? 1 : -1
	} finally {
		isPending.value = false
	}
}

type TrendConfig = { icon: typeof TrendingUp; label: string; class: string }

const trendConfigMap: Record<NonNullable<PriceTrend>, TrendConfig> = {
	up: { icon: TrendingUp, label: 'Rising', class: 'text-red-500 dark:text-red-400' },
	down: { icon: TrendingDown, label: 'Falling', class: 'text-green-600 dark:text-green-400' },
	flat: { icon: Minus, label: 'Stable', class: 'text-muted-foreground' },
}

const trendConfig = computed<TrendConfig | null>(() => {
	const trend = props.variety.price_trend
	return trend ? (trendConfigMap[trend] ?? null) : null
})
</script>

<template>
  <Card as="div" class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5">
    <Link :href="href" class="block">
    <AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
      <img v-if="variety.image_url" :src="variety.image_url"
        :alt="`${variety.vegetable?.name} ${variety.name} image`" />
    </AspectRatio>

    <CardHeader class="p-5 py-2">
      <CardTitle>
        {{ variety.vegetable?.name }} {{ variety.name }}
      </CardTitle>
      <CardDescription>
        {{ variety.vegetable?.category?.name }}
      </CardDescription>
    </CardHeader>

    <div class="px-7">
          <Separator />
      </div>
    </Link>

    <CardContent class="flex flex-col gap-2 p-4">
      <div class="flex items-center gap-2 font-mono">
        <component :is="trendConfig?.icon" :size="20" class="text-muted-foreground" />
        <AppTooltip :content="variety.latest_price?.freshness">
          <span class="text-sm">₱{{ variety.latest_price?.price_min.toFixed(2) }} - ₱{{ variety.latest_price?.price_max.toFixed(2) }}</span>
        </AppTooltip>
      </div>

      <div class="flex items-center gap-2">
        <CalendarSync :size="20" class="text-muted-foreground" />
        <AppTooltip :content="variety.price_updated_date">
          <span class="text-xs">{{ variety.price_updated_human }}</span>          
        </AppTooltip>
      </div>

      <div class="flex items-center justify-between pt-1">
        <button
          class="flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-rose-500 disabled:pointer-events-none disabled:opacity-50"
          :disabled="isPending" @click="toggleHeart">
          <Heart class="size-4 transition-all"
            :class="cn(localHearted ? 'fill-rose-500 text-rose-500 scale-110' : 'fill-none')" />
          <span class="tabular-nums">{{ localCount }}</span>
        </button>
      </div>
    </CardContent>
  </Card>
</template>
