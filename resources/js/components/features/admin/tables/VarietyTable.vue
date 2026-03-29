<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import type { ColumnDef } from '@tanstack/vue-table'
import {
	ChevronDown,
	ChevronRight,
	ClipboardList,
	ClipboardPenLine,
	ClipboardPlus,
	ClipboardX,
	Clock,
	MapPin,
	Plus,
} from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { show as varietyShow } from '@/routes/admin/vegetables'
import type { Paginated, VarietyResource } from '@/types'

defineProps<{
	varieties: Paginated<VarietyResource>
	searchQuery?: string
}>()

defineEmits<{
	'open-create': []
	'open-edit': [variety: VarietyResource]
	'open-delete': [variety: VarietyResource]
	'open-update-price': [variety: VarietyResource]
	'page-change': [page: number]
	search: [query: string]
}>()

const columns: ColumnDef<VarietyResource>[] = [
	{ id: 'expand', header: '' },
	{ id: 'image', header: 'Image', enableSorting: false },
	{
		id: 'name',
		header: 'Variety',
		accessorFn: (row) => `${row.vegetable?.name ?? ''} ${row.name}`,
		enableSorting: true,
	},
	{
		id: 'price_range',
		header: 'Price Range',
		accessorFn: (row) =>
			row.latest_price
				? `₱${row.latest_price.price_min} – ₱${row.latest_price.price_max}`
				: 'No price data',
		enableSorting: false,
	},
	{ id: 'activity', header: 'Activity', enableSorting: false },
	{ id: 'actions', header: 'Actions', enableSorting: false, enableHiding: true },
]
</script>

<template>
    <DataTable :data="varieties" :columns="columns" :enable-expand="true" search-placeholder="Search varieties…"
        entity-name="varieties" @page-change="$emit('page-change', $event)" @search="$emit('search', $event)">
        <template #toolbar-actions>
            <Button class="gap-1.5" @click="$emit('open-create')">
                <Plus class="size-4" />
                Add Variety
            </Button>
        </template>

        <!-- Expand toggle -->
        <template #cell-expand="{ cell }">
            <Button variant="ghost" size="icon-sm" class="text-muted-foreground"
                @click="cell.getContext().row.toggleExpanded()">
                <ChevronDown v-if="cell.getContext().row.getIsExpanded()" class="size-4 transition-transform" />
                <ChevronRight v-else class="size-4 transition-transform" />
            </Button>
        </template>

        <!-- Image -->
        <template #cell-image="{ row }">
            <Avatar class="size-12 rounded-md">
                <AvatarImage v-if="row.image_url" :src="row.image_url" :alt="row.name" class="object-cover" />
                <AvatarFallback class="rounded-md bg-primary/10 font-semibold text-primary">
                    {{ (row.vegetable?.name?.charAt(0) ?? '') }}{{ row.name.charAt(0) }}
                </AvatarFallback>
            </Avatar>
        </template>

        <!-- Name + category -->
        <template #cell-name="{ row }">
            <div class="flex flex-col gap-0.5">
                <span class="font-medium">{{ row.vegetable?.name }}: {{ row.name }}</span>
                <span class="text-xs text-muted-foreground">{{ row.vegetable?.category?.name }}</span>
            </div>
        </template>

        <!-- Price range + freshness dot -->
        <template #cell-price_range="{ row }">
            <div v-if="row.latest_price" class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="w-fit font-mono">
                        ₱{{ row.latest_price.price_min }} – ₱{{ row.latest_price.price_max }}
                    </Badge>
                    <TooltipProvider :delay-duration="200">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <div class="size-2 cursor-help rounded-full" :class="{
                                    'bg-amber-400': row.latest_price.freshness === 'recent',
                                    'bg-green-400': row.latest_price.freshness === 'stable',
                                    'bg-sky-500': row.latest_price.freshness === 'very stable',
                                    'bg-gray-500': row.latest_price.freshness === 'stale',
                                }" />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-xs capitalize">{{ row.latest_price.freshness }}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
                <div v-if="row.price_updated_human" class="flex items-center gap-1 text-xs text-muted-foreground">
                    <Clock class="size-3" />
                    {{ row.price_updated_human }}
                </div>
            </div>
            <span v-else class="text-xs text-muted-foreground">No price data</span>
        </template>

        <!-- Supply / demand counts -->
        <template #cell-activity="{ row }">
            <div class="flex flex-col gap-1.5">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div class="flex w-fit items-center gap-1.5 text-sm">
                                <span class="size-2 rounded-full bg-green-500" />
                                <span class="font-medium tabular-nums">{{ row.supply_count ?? 0 }}</span>
                                <span class="text-xs text-muted-foreground">supplies</span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Ongoing farmer supply listings</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div class="flex w-fit items-center gap-1.5 text-sm">
                                <span class="size-2 rounded-full bg-blue-500" />
                                <span class="font-medium tabular-nums">{{ row.demand_count ?? 0 }}</span>
                                <span class="text-xs text-muted-foreground">demands</span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Ongoing dealer demand listings</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-foreground"
                                as-child>
                                <Link :href="varietyShow(row.id).url">
                                    <ClipboardList class="size-4" />
                                </Link>
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">View details</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-primary"
                                @click="$emit('open-update-price', row)">
                                <ClipboardPlus class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Update price</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-foreground"
                                @click="$emit('open-edit', row)">
                                <ClipboardPenLine class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Edit variety</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-destructive"
                                @click="$emit('open-delete', row)">
                                <ClipboardX class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Delete variety</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>

        <!-- Expanded municipality breakdown -->
        <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-6 py-4">
                    <div class="flex items-start gap-3">
                        <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                        <div class="flex-1">
                            <p class="mb-3 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                Supply by Municipality — {{ row.vegetable?.name }} {{ row.name }}
                            </p>

                            <div v-if="row.supply_municipalities?.length"
                                class="grid grid-cols-2 gap-x-8 gap-y-1.5 sm:grid-cols-3 lg:grid-cols-4">
                                <div v-for="entry in row.supply_municipalities" :key="entry.name"
                                    class="flex items-center justify-between gap-4 rounded-md border bg-background px-3 py-2">
                                    <span class="truncate text-sm font-medium">{{ entry.name }}</span>
                                    <Badge variant="secondary" class="shrink-0 font-mono text-xs">
                                        {{ entry.total_kg.toLocaleString() }} kg
                                    </Badge>
                                </div>
                            </div>

                            <p v-else class="text-sm text-muted-foreground">
                                No ongoing supplies for this variety.
                            </p>
                        </div>
                    </div>
                </td>
            </tr>
        </template>
    </DataTable>
</template>
