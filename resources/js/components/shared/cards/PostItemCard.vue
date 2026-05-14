<script setup lang="ts">
import axios from 'axios'
import {
	AlarmClockCheck,
	Archive,
	Calendar,
	Heart,
	MapPin,
	MoreVertical,
	PackageCheck,
	Pencil,
	Trash,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { toggle as togglePostHeart } from '@/actions/App/Http/Controllers/PostHeartController'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { cn } from '@/lib/utils'
import type { DealerPostItemResource } from '@/types'

type Action = 'edit' | 'fulfill' | 'archive' | 'delete'

const props = defineProps<{
	item: DealerPostItemResource
	mode?: 'supply' | 'demand'
	actions?: Action[]
}>()

const emit = defineEmits<{
	edit: []
	fulfill: []
	archive: []
	delete: []
}>()

const mode = computed(() => props.mode ?? 'supply')
const hasActions = computed(() => (props.actions?.length ?? 0) > 0)
const canEdit = computed(() => props.actions?.includes('edit') ?? false)
const canFulfill = computed(() => props.actions?.includes('fulfill') ?? false)
const canArchive = computed(() => props.actions?.includes('archive') ?? false)
const canDelete = computed(() => props.actions?.includes('delete') ?? false)

const localHearted = ref(props.item.is_hearted)
const localCount = ref(props.item.hearts_count)
const isPending = ref(false)

const priceFlagClass: Record<string, string> = {
	Low: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
	Fair: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
	High: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
}

const qtyLabel = computed(() => (mode.value === 'supply' ? 'AVAILABLE' : 'NEEDED'))
const qtyClass = computed(() =>
	mode.value === 'supply'
		? 'bg-primary/10 text-primary'
		: 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-300',
)

async function toggleHeart(event: MouseEvent): Promise<void> {
	event.stopPropagation()
	if (isPending.value) return

	const wasHearted = localHearted.value
	localHearted.value = !wasHearted
	localCount.value += wasHearted ? -1 : 1
	isPending.value = true

	try {
		const { data } = await axios.post<{ hearted: boolean; hearts_count: number }>(
			togglePostHeart(props.item.post_id).url,
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
	<Card class="py-0 gap-0 overflow-hidden transition-all hover:shadow-lg hover:-translate-y-0.5">

		<AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10">
			<img
				v-if="item.variety_image_url"
				:src="item.variety_image_url"
				:alt="`${item.vegetable_name} ${item.variety_name}`"
				class="absolute inset-0 h-full w-full object-cover"
			/>

			<!-- Days until badge — top left -->
			<div
				v-if="item.days_until_transaction !== null"
				class="absolute top-2 left-2 z-10 rounded-full bg-black/60 px-2.5 py-0.5 text-xs font-semibold text-white backdrop-blur-sm"
			>
				{{ item.days_until_transaction <= 0 ? 'Today' : `${item.days_until_transaction}d away` }}
			</div>

			<!-- Actions dropdown — top right -->
			<div v-if="hasActions" class="absolute top-2 right-2 z-10">
				<DropdownMenu>
					<DropdownMenuTrigger as-child>
						<Button variant="outline" size="icon" class="size-7">
							<MoreVertical class="size-3.5" />
						</Button>
					</DropdownMenuTrigger>
					<DropdownMenuContent align="end">
						<DropdownMenuItem
							v-if="canEdit"
							@click="emit('edit')"
						>
							<Pencil class="mr-2 size-4" />
							Edit
						</DropdownMenuItem>
						<DropdownMenuSeparator v-if="canEdit && (canFulfill || canArchive || canDelete)" />
						<DropdownMenuItem
							v-if="canFulfill"
							class="text-green-600 dark:text-green-400"
							@click="emit('fulfill')"
						>
							<PackageCheck class="mr-2 size-4" />
							Fulfill
						</DropdownMenuItem>
						<DropdownMenuItem
							v-if="canArchive"
							@click="emit('archive')"
						>
							<Archive class="mr-2 size-4" />
							Archive
						</DropdownMenuItem>
						<DropdownMenuSeparator v-if="canFulfill || canArchive" />
						<DropdownMenuItem
							v-if="canDelete"
							class="text-destructive"
							@click="emit('delete')"
						>
							<Trash class="mr-2 size-4" />
							Delete
						</DropdownMenuItem>
					</DropdownMenuContent>
				</DropdownMenu>
			</div>

			<!-- Price flag — top right when no actions -->
			<div v-else-if="item.price_flag" class="absolute top-2 right-2 z-10">
				<Badge :class="priceFlagClass[item.price_flag]">{{ item.price_flag }}</Badge>
			</div>

			<!-- Price flag bottom-left when actions present -->
			<div v-if="hasActions && item.price_flag" class="absolute bottom-8 left-2 z-10">
				<Badge :class="priceFlagClass[item.price_flag]">{{ item.price_flag }}</Badge>
			</div>

			<!-- Timestamp — bottom right -->
			<div class="absolute bottom-0 right-0 z-10 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
				<TooltipProvider :delay-duration="200">
					<Tooltip>
						<TooltipTrigger as-child>
							<div class="cursor-help">{{ item.created_at_human }}</div>
						</TooltipTrigger>
						<TooltipContent>{{ item.created_at }}</TooltipContent>
					</Tooltip>
				</TooltipProvider>
			</div>
		</AspectRatio>

		<CardHeader class="px-4 pt-3 pb-1">
			<CardTitle class="text-sm leading-tight sm:text-base line-clamp-1">
				{{ item.vegetable_name }} — {{ item.variety_name }}
			</CardTitle>
			<CardDescription class="text-xs">{{ item.category_name }}</CardDescription>
		</CardHeader>

		<div class="px-4"><Separator /></div>

		<CardContent class="px-4 py-3 flex flex-col gap-2">
			<!-- Quantity + price -->
			<div :class="cn('rounded-md p-3', qtyClass)">
				<span class="text-xs tracking-wider block mb-1">{{ qtyLabel }}</span>
				<span class="font-semibold text-sm">
					{{ item.quantity_kg.toLocaleString('en-PH') }} kg
				</span>
				<span v-if="item.unit_price" class="ml-2 text-xs font-mono opacity-80">
					@ ₱{{ item.unit_price.toFixed(2) }}/kg
				</span>
			</div>

			<div v-if="item.scheduled_date" class="flex items-center gap-2 text-xs text-muted-foreground">
				<Calendar class="size-3.5 shrink-0" />
				<span>{{ item.scheduled_date }}</span>
			</div>

			<div v-if="item.time_slot_label" class="flex items-center gap-2 text-xs text-muted-foreground">
				<AlarmClockCheck class="size-3.5 shrink-0" />
				<span>{{ item.time_slot_label }}</span>
			</div>

			<div v-if="item.municipality" class="flex items-center gap-2 text-xs text-muted-foreground">
				<MapPin class="size-3.5 shrink-0" />
				<span>{{ item.municipality }}</span>
			</div>

			<!-- Heart — hidden on own posts (actions present) -->
			<div v-if="!hasActions" class="flex items-center justify-end pt-1">
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
