<script setup lang="ts">
import {
	AlarmClockCheck, Calendar, MoreVertical, PackageCheck, Pencil, Trash,
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

const { post } = defineProps<{
	post: PostItem
}>()

const emit = defineEmits<{
	edit: [post: PostItem]
	archive: [post: PostItem]
	fulfill: [post: PostItem]
	delete: [post: PostItem]
}>()

const isOngoing = computed(() => post.status === 'Ongoing')
const isArchived = computed(() => post.status === 'Archived')

const imageUrl = computed(() => ('image_url' in post ? post.image_url : undefined))
</script>

<template>
	<Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
		<AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
			<img v-if="imageUrl" :src="imageUrl" :alt="`${post.vegetable?.name} image`" />
			<img v-else-if="post.vegetable?.image_url" :src="post.vegetable.image_url"
				:alt="`${post.vegetable?.name} image`" />

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

			<Badge class="absolute top-2 left-4 tracking-wider font-mono font-semibold">
				{{ post.quantity_kg }} kg
			</Badge>

			<DropdownMenu>
				<DropdownMenuTrigger as-child>
					<Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
						<MoreVertical class="size-4" />
					</Button>
				</DropdownMenuTrigger>
				<DropdownMenuContent align="end">
					<DropdownMenuItem v-if="isOngoing" @click="emit('edit', post)">
						<Pencil class="mr-2 size-4" />
						Edit Details
					</DropdownMenuItem>
					<DropdownMenuSeparator v-if="isOngoing" />
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

		<div class="px-7">
			<Separator />
		</div>

		<CardContent class="p-5 pt-2 grid gap-2">
			<div class="bg-primary/10 p-3 rounded-md">
				<span class="text-xs tracking-wider block mb-1">QUANTITY</span>
				<span class="font-body font-semibold text-primary">{{ post.quantity_kg }} kg</span>
			</div>

			<div class="flex items-center gap-2">
				<Calendar :size="20" class="text-muted-foreground" />
				<span class="text-xs">{{ post.scheduled_date }}</span>
			</div>

			<div v-if="post.time_slot" class="flex items-center gap-2">
				<AlarmClockCheck :size="20" class="text-muted-foreground" />
				<span class="text-xs">{{ post.time_slot_label }}</span>
			</div>
		</CardContent>
	</Card>
</template>
