<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import { Pencil, Trash2, Plus, Clock, Eye } from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import type { PaginatedData, Variety } from '@/types/admin/vegetable-varieties'

defineProps<{
    varieties: PaginatedData
    searchQuery?: string
}>()

defineEmits<{
    'open-create': []
    'open-view': [variety: Variety]
    'open-edit': [variety: Variety]
    'open-delete': [variety: Variety]
    'page-change': [page: number]
    'search': [query: string]
}>()

const columns: ColumnDef<Variety>[] = [
    {
        id: 'image',
        header: 'Image',
        enableSorting: false,
    },
    {
        id: 'name',
        header: 'Variety',
        accessorFn: (row) => `${row.vegetable.name} ${row.name}`,
        enableSorting: true,
    },
    {
        id: 'price_range',
        header: 'Price Range',
        accessorFn: (row) => row.latest_price
            ? `₱${row.latest_price.price_min} - ₱${row.latest_price.price_max}`
            : 'No price data',
        enableSorting: false,
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
    },
]
</script>

<template>
    <DataTable
        :data="varieties"
        :columns="columns"
        :search-query="searchQuery"
        search-placeholder="Search varieties…"
        empty-message="No varieties found."
        entity-name="varieties"
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
    >
        <template #toolbar-actions>
            <Button @click="$emit('open-create')" class="gap-1.5">
                <Plus class="size-4" />
                Add Variety
            </Button>
        </template>

        <template #cell-image="{ row }">
            <Avatar class="size-12 rounded-md">
                <AvatarImage
                    :src="row.image_url"
                    :alt="row.name"
                    class="object-cover"
                />
                <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                    {{ row.name.charAt(0) }}
                </AvatarFallback>
            </Avatar>
        </template>

        <template #cell-name="{ row }">
            <div class="flex flex-col gap-0.5">
                <span class="font-medium">{{ row.vegetable.name }}: {{ row.name }}</span>
                <span class="text-xs text-muted-foreground">{{ row.vegetable.category.name }}</span>
            </div>
        </template>

        <template #cell-price_range="{ row }">
            <div v-if="row.latest_price" class="flex flex-col gap-1.5">
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="w-fit font-mono">
                        ₱{{ row.latest_price.price_min }} – ₱{{ row.latest_price.price_max }}
                    </Badge>
                    <TooltipProvider v-if="row.price_freshness" :delay-duration="200">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <div
                                    class="size-2 rounded-full cursor-help"
                                    :class="{
                                        'bg-amber-400': row.price_freshness === 'recent',
                                        'bg-green-400': row.price_freshness === 'stable',
                                        'bg-sky-500':   row.price_freshness === 'very stable',
                                        'bg-gray-500':  row.price_freshness === 'stale',
                                    }"
                                />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-xs capitalize">{{ row.price_freshness }}</p>
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

        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-foreground"
                                @click="$emit('open-view', row)"
                            >
                                <Eye class="size-4" />
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
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-foreground"
                                @click="$emit('open-edit', row)"
                            >
                                <Pencil class="size-4" />
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
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-destructive"
                                @click="$emit('open-delete', row)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Delete variety</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>
    </DataTable>
</template>
