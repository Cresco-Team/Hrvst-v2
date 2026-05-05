<script setup lang="ts">
import {
	AlarmClockCheck, Calendar, Leaf, MoreVertical, PackageCheck, Pencil, Trash,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
	DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import type { DealerDemandResource, FarmerSupplyResource } from '@/types'

type PostItem = FarmerSupplyResource | DealerDemandResource

const { post } = defineProps<{ post: PostItem }>()

const emit = defineEmits<{
	edit: [post: PostItem]
	harvest: [post: PostItem]
	archive: [post: PostItem]
	fulfill: [post: PostItem]
	delete: [post: PostItem]
}>()

const isGrowing = computed(() => post.status === 'growing')
const isOngoing = computed(() => post.status === 'ongoing')
const isArchived = computed(() => post.status === 'archived')

const imageUrl = computed(() => ('image_url' in post ? post.image_url : undefined))

const statusBadgeClass = computed(() => ({
	growing: 'bg-lime-100 text-lime-700 dark:bg-lime-900/40 dark:text-lime-300',
	ongoing: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
	archived: 'bg-neutral-100 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400',
	fulfilled: 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
}[post.status] ?? ''))

const totalKg = computed(() => {
	if (!post.items?.length) return null
	return post.items.reduce((sum, i) => sum + i.quantity_kg, 0)
})
</script>

<template>
	<Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
		<AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
			<img v-if="imageUrl" :src="imageUrl" :alt="post.vegetable?.name" />
			<img v-else-if="post.vegetable?.image_url" :src="post.vegetable.image_url" :alt="post.vegetable?.name" />

			<!-- Status badge -->
			<span class="absolute top-2 left-4 rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
				:class="statusBadgeClass">
				{{ post.status }}
			</span>

			<!-- Timestamp -->
			<div
				class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
				<TooltipProvider :delay-duration="200">
					<Tooltip>
						<TooltipTrigger as-child>
							<div class="cursor-help">{{ post.created_at_human }}</div>
						</TooltipTrigger>
						<TooltipContent>Posted on: {{ post.created_at }}</TooltipContent>
					</Tooltip>
				</TooltipProvider>
			</div>

			<!-- Actions dropdown -->
			<DropdownMenu>
				<DropdownMenuTrigger as-child>
					<Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
						<MoreVertical class="size-4" />
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end">

					<!-- Harvest — only for growing supply posts -->
					<DropdownMenuItem v-if="isGrowing" @click="emit('harvest', post)">
						<Leaf class="mr-2 size-4 text-lime-600" />
						Record Harvest
					</DropdownMenuItem>

					<!-- Edit — growing (pre-harvest details) or ongoing (demand) -->
					<DropdownMenuItem v-if="isGrowing || isOngoing" @click="emit('edit', post)">
						<Pencil class="mr-2 size-4" />
						Edit Details
					</DropdownMenuItem>

					<DropdownMenuSeparator v-if="isGrowing || isOngoing" />

					<DropdownMenuItem v-if="isOngoing || isArchived" class="text-green-500 dark:text-green-400"
						@click="emit('fulfill', post)">
						<PackageCheck class="mr-2 size-4" />
						Fulfill
					</DropdownMenuItem>

					<DropdownMenuItem class="text-destructive" @click="emit('delete', post)">
						<Trash class="mr-2 size-4" />
						Delete
					</DropdownMenuItem>

				</DropdownMenuContent>
			</DropdownMenu>
		</AspectRatio>

		<CardHeader class="p-5 py-2">
			<CardTitle class="line-clamp-1">{{ post.vegetable?.name }}</CardTitle>
			<CardDescription class="ml-2 line-clamp-1">{{ post.vegetable?.category }}</CardDescription>
		</CardHeader>

		<div class="px-7"><Separator /></div>

		<CardContent class="p-5 pt-2 grid gap-2">

			<!-- Growing: target month + estimated weight -->
			<template v-if="isGrowing && 'target_month' in post">
				<div class="bg-lime-50 dark:bg-lime-900/20 p-3 rounded-md">
					<span class="text-xs tracking-wider block mb-1 text-lime-700 dark:text-lime-300">TARGET MONTH</span>
					<span class="font-semibold text-lime-700 dark:text-lime-300">{{ post.target_month }}</span>
				</div>
				<div class="bg-primary/10 p-3 rounded-md">
					<span class="text-xs tracking-wider block mb-1">EST. WEIGHT</span>
					<span class="font-semibold text-primary">{{ post.estimated_total_weight }} kg</span>
				</div>
			</template>

			<!-- Post-harvest / demand: items breakdown + schedule -->
			<template v-else>
				<div v-if="totalKg !== null" class="bg-primary/10 p-3 rounded-md">
					<span class="text-xs tracking-wider block mb-1">TOTAL WEIGHT</span>
					<span class="font-semibold text-primary">{{ totalKg }} kg</span>
				</div>

				<div v-if="post.items?.length" class="text-xs text-muted-foreground space-y-0.5">
					<div v-for="item in post.items" :key="item.id" class="flex justify-between">
						<span>{{ item.variety_name ?? `Variety #${item.variety_id}` }}</span>
						<span class="tabular-nums">{{ item.quantity_kg }} kg</span>
					</div>
				</div>

				<div v-if="'scheduled_date' in post && post.scheduled_date" class="flex items-center gap-2">
					<Calendar :size="20" class="text-muted-foreground" />
					<span class="text-xs">{{ post.scheduled_date }}</span>
				</div>

				<div v-if="'time_slot_label' in post && post.time_slot_label" class="flex items-center gap-2">
					<AlarmClockCheck :size="20" class="text-muted-foreground" />
					<span class="text-xs">{{ post.time_slot_label }}</span>
				</div>
			</template>

		</CardContent>
	</Card>
</template>
