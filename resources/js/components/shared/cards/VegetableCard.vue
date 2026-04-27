<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { CalendarSync, Heart, Minus, TrendingDown, TrendingUp } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import Button from '@/components/ui/button/Button.vue'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { cn } from '@/lib/utils'
import type { PriceTrend, VarietyResource } from '@/types'
import { toggle as toggleVarietyHeart } from '@/actions/App/Http/Controllers/VarietyHeartController'

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
            toggleVarietyHeart(props.variety.id).url,
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
	up: { icon: TrendingUp, label: 'Rising', class: 'text-green-600 dark:text-green-400' },
	down: { icon: TrendingDown, label: 'Falling', class: 'text-red-500 dark:text-red-400' },
	flat: { icon: Minus, label: 'Stable', class: 'text-muted-foreground' },
}

const trendConfig = computed<TrendConfig | null>(() => {
	const trend = props.variety.price_trend
	return trend ? (trendConfigMap[trend] ?? null) : null
})
</script>

<template>
    <Card as="div" class="py-0 gap-0 overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5 h-full">
        <Link :href="href" class="block">
            <AspectRatio :ratio="21 / 9" class="relative overflow-hidden bg-primary/10">
                <img
                    v-if="variety.image_url"
                    :src="variety.image_url"
                    :alt="`${variety.vegetable?.name} ${variety.name}`"
                    class="absolute inset-0 h-full w-full object-cover"
                />
            </AspectRatio>

            <!-- BUG FIX: was gap-2 in Card but header had no top padding — collapsed weirdly.
                 Use explicit compact padding that scales up at sm. -->
            <CardHeader class="px-3 pt-2 pb-1 sm:px-4 sm:pt-3">
                <CardTitle class="text-sm leading-tight sm:text-base">
                    {{ variety.vegetable?.name }} {{ variety.name }}
                </CardTitle>
                <CardDescription class="text-xs">
                    {{ variety.vegetable?.category?.name }}
                </CardDescription>
            </CardHeader>

            <div class="px-3 sm:px-4">
                <Separator />
            </div>
        </Link>

        <CardContent class="flex flex-col gap-1.5 px-3 py-2 sm:px-4 sm:py-3">
            <!-- Price row — BUG FIX: apply trendConfig.class to the icon, not hardcoded muted -->
            <div class="flex items-center gap-1.5 font-mono">
                <component
                    :is="trendConfig?.icon ?? Minus"
                    :size="16"
                    :class="trendConfig?.class ?? 'text-muted-foreground'"
                />
                <AppTooltip :content="`${variety.latest_price?.freshness} price since ${variety.price_updated_date}`">
                    <span class="text-xs sm:text-sm cursor-help">
                        ₱{{ variety.latest_price?.price_min.toFixed(2) }} –
                        ₱{{ variety.latest_price?.price_max.toFixed(2) }}
                    </span>
                </AppTooltip>
            </div>

            <!-- Date row -->
            <div class="flex items-center gap-1.5">
                <CalendarSync :size="16" class="shrink-0 text-muted-foreground" />
                <AppTooltip :content="variety.price_updated_date">
                    <span class="text-xs cursor-help">{{ variety.price_updated_human }}</span>
                </AppTooltip>
            </div>

            <!-- Heart row -->
            <div class="flex items-center justify-end pt-0.5">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-7 w-7 hover:text-rose-500 disabled:pointer-events-none disabled:opacity-50 cursor-pointer"
                    :disabled="isPending"
                    @click="toggleHeart"
                >
                    <Heart
                        class="size-3.5 transition-all"
                        :class="cn(localHearted ? 'fill-rose-500 text-rose-500 scale-110' : 'fill-none')"
                    />
                </Button>
                <AppTooltip content="Likes">
                    <span class="text-xs tabular-nums cursor-help">{{ localCount }}</span>
                </AppTooltip>
            </div>
        </CardContent>
    </Card>
</template>
