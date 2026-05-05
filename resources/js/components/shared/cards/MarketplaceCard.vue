<script setup lang="ts">
import axios from 'axios'
import { AlarmClockCheck, Calendar, Heart } from 'lucide-vue-next'
import { ref } from 'vue'
import { toggle as togglePostHeart } from '@/actions/App/Http/Controllers/PostHeartController'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { cn } from '@/lib/utils'
import type { DealerDemandResource, FarmerSupplyResource } from '@/types'

type PostItem = FarmerSupplyResource | DealerDemandResource

const { post } = defineProps<{ post: PostItem }>()

const localHearted = ref(post.is_hearted)
const localCount = ref(post.hearts_count)
const isPending = ref(false)

const displayImageUrl = 'image_url' in post ? post.image_url : undefined

const totalKg = post.items?.reduce((sum, i) => sum + i.quantity_kg, 0) ?? null

async function toggleHeart(event: MouseEvent): Promise<void> {
	event.stopPropagation()
	if (isPending.value) return

	const wasHearted = localHearted.value
	localHearted.value = !wasHearted
	localCount.value += wasHearted ? -1 : 1
	isPending.value = true

	try {
		const { data } = await axios.post<{ hearted: boolean; hearts_count: number }>(
			togglePostHeart(post.id).url,
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
</script>

<template>
	<Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
		<AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
			<img v-if="displayImageUrl" :src="displayImageUrl" :alt="post.vegetable?.name" />
			<img v-else-if="post.vegetable?.image_url" :src="post.vegetable.image_url" :alt="post.vegetable?.name" />

			<div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
				<TooltipProvider :delay-duration="200">
					<Tooltip>
						<TooltipTrigger as-child>
							<div class="cursor-help">{{ post.created_at_human }}</div>
						</TooltipTrigger>
						<TooltipContent>{{ post.created_at }}</TooltipContent>
					</Tooltip>
				</TooltipProvider>
			</div>
		</AspectRatio>

		<CardHeader class="p-5 py-2">
			<CardTitle class="line-clamp-1">{{ post.vegetable?.name }}</CardTitle>
			<CardDescription class="ml-2 line-clamp-1">{{ post.vegetable?.category }}</CardDescription>
		</CardHeader>

		<div class="px-7"><Separator /></div>

		<CardContent class="p-5 pt-2 grid gap-2">
			<!-- Total weight -->
			<div v-if="totalKg !== null" class="bg-primary/10 p-3 rounded-md">
				<span class="text-xs tracking-wider block mb-1">TOTAL</span>
				<span class="font-semibold text-primary">{{ totalKg }} kg</span>
			</div>

			<!-- Variety breakdown -->
			<div v-if="post.items?.length" class="text-xs text-muted-foreground space-y-0.5">
				<div v-for="item in post.items" :key="item.id" class="flex justify-between">
					<span>{{ item.variety_name ?? `Variety #${item.variety_id}` }}</span>
					<span class="tabular-nums">{{ item.quantity_kg }} kg</span>
				</div>
			</div>

			<!-- Scheduled date — Bug #3 fix: was 'scheduled_at', correct key is 'scheduled_date' -->
			<div v-if="'scheduled_date' in post && post.scheduled_date" class="flex items-center gap-2">
				<Calendar :size="20" class="text-muted-foreground" />
				<span class="text-xs">{{ post.scheduled_date }}</span>
			</div>

			<!-- Time slot -->
			<div v-if="'time_slot_label' in post && post.time_slot_label" class="flex items-center gap-2">
				<AlarmClockCheck :size="20" class="text-muted-foreground" />
				<span class="text-xs">{{ post.time_slot_label }}</span>
			</div>

			<!-- Heart -->
			<div class="flex items-center justify-end pt-1">
				<button
					class="flex items-center gap-1.5 text-sm text-muted-foreground cursor-pointer transition-colors hover:text-rose-500 disabled:pointer-events-none disabled:opacity-50"
					:disabled="isPending"
					@click="toggleHeart"
				>
					<Heart
						class="size-4 transition-all"
						:class="cn(localHearted ? 'fill-rose-500 text-rose-500 scale-110' : 'fill-none')"
					/>
					<span class="tabular-nums">{{ localCount }}</span>
				</button>
			</div>
		</CardContent>
	</Card>
</template>
