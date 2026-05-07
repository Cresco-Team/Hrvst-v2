<script setup lang="ts">
import { Calendar, AlarmClockCheck, Leaf, MoreVertical, Pencil, Trash } from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
	DropdownMenu, DropdownMenuContent, DropdownMenuItem,
	DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import type { FarmerSupplyResource } from '@/types'

// MyPostCard is now growing-only — Post has two stages: growing | harvested.
// Post-level actions: harvest, edit, delete. Fulfill/archive live on PostItem.
const { post } = defineProps<{ post: FarmerSupplyResource }>()

const emit = defineEmits<{
	edit: [post: FarmerSupplyResource]
	harvest: [post: FarmerSupplyResource]
	delete: [post: FarmerSupplyResource]
}>()
</script>

<template>
	<Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
		<AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
			<img v-if="post.image_url" :src="post.image_url" :alt="post.vegetable?.name" />
			<img v-else-if="post.vegetable?.image_url" :src="post.vegetable.image_url" :alt="post.vegetable?.name" />

			<!-- Status badge -->
			<span class="absolute top-2 left-4 rounded-full px-2.5 py-0.5 text-xs font-semibold bg-lime-100 text-lime-700 dark:bg-lime-900/40 dark:text-lime-300">
				Growing
			</span>

			<!-- Timestamp -->
			<div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
				<TooltipProvider :delay-duration="200">
					<Tooltip>
						<TooltipTrigger as-child>
							<div class="cursor-help">{{ post.created_at_human }}</div>
						</TooltipTrigger>
						<TooltipContent>Posted on: {{ post.created_at }}</TooltipContent>
					</Tooltip>
				</TooltipProvider>
			</div>

			<!-- Actions -->
			<DropdownMenu>
				<DropdownMenuTrigger as-child>
					<Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
						<MoreVertical class="size-4" />
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end">
					<DropdownMenuItem @click="emit('harvest', post)">
						<Leaf class="mr-2 size-4 text-lime-600" />
						Record Harvest
					</DropdownMenuItem>
					<DropdownMenuItem @click="emit('edit', post)">
						<Pencil class="mr-2 size-4" />
						Edit Details
					</DropdownMenuItem>
					<DropdownMenuSeparator />
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
			<div class="bg-lime-50 dark:bg-lime-900/20 p-3 rounded-md">
				<span class="text-xs tracking-wider block mb-1 text-lime-700 dark:text-lime-300">TARGET MONTH</span>
				<span class="font-semibold text-lime-700 dark:text-lime-300">{{ post.target_month }}</span>
			</div>
			<div class="bg-primary/10 p-3 rounded-md">
				<span class="text-xs tracking-wider block mb-1">EST. WEIGHT</span>
				<span class="font-semibold text-primary">{{ post.estimated_total_weight }} kg</span>
			</div>
		</CardContent>
	</Card>
</template>
