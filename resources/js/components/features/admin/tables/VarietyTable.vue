<script setup lang="ts">
import { computed } from 'vue'
import type { ColumnDef } from '@tanstack/vue-table'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { Pencil, Trash2, Plus, Clock } from 'lucide-vue-next'

/* -- types -- */
interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_path: string
    weeks_to_harvest: number
    vegetable: {
        id: number
        name: string
        category: {
            id: number
            name: string
        }
    }
    latest_price: {
        price_min: string
        price_max: string
    } | null
    price_updated_human?: string
    price_updated_date?: string
    price_freshness?: 'fresh' | 'recent' | 'okay' | 'aging' | 'stale'
}

interface PaginatedData {
    data: Variety[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

/* -- props / emits -- */
defineProps<{
    varieties: PaginatedData
}>()

defineEmits<{
    'open-create': []
    'open-edit': [variety: Variety]
    'open-delete': [variety: Variety]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
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
        search-placeholder="Search varieties…"
        empty-message="No varieties found."
        entity-name="varieties"
        @page-change="$emit('page-change', $event)"
    >
        <!-- Toolbar Actions -->
        <template #toolbar-actions>
            <Button @click="$emit('open-create')" class="gap-1.5">
                <Plus class="size-4" />
                Add Variety
            </Button>
        </template>

        <!-- Custom Cell: Image -->
        <template #cell-image="{ row }">
            <Avatar class="size-12 rounded-md">
                <AvatarImage 
                    :src="row.image_path" 
                    :alt="row.name"
                    class="object-cover"
                />
                <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                    {{ row.name.charAt(0) }}
                </AvatarFallback>
            </Avatar>
        </template>

        <!-- Custom Cell: Name -->
        <template #cell-name="{ row }">
            <div class="flex flex-col gap-0.5">
                <span class="font-medium">
                    {{ row.vegetable.name }} {{ row.name }}
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ row.vegetable.category.name }}
                </span>
            </div>
        </template>

        <!-- Custom Cell: Price Range -->
        <template #cell-price_range="{ row }">
            <div v-if="row.latest_price" class="flex flex-col gap-1.5">
                <!-- Price Badge with freshness indicator -->
                <div class="flex items-center gap-2">
                    <Badge variant="secondary" class="w-fit font-mono">
                        ₱{{ row.latest_price.price_min }} - ₱{{ row.latest_price.price_max }}
                    </Badge>
                    
                    <!-- Freshness dot indicator -->
                    <TooltipProvider v-if="row.price_freshness" :delay-duration="200">
                        <Tooltip>
                            <TooltipTrigger as-child>
                                <div 
                                    class="size-2 rounded-full cursor-help"
                                    :class="{
                                        'bg-green-500': row.price_freshness === 'fresh',
                                        'bg-blue-500': row.price_freshness === 'recent',
                                        'bg-yellow-500': row.price_freshness === 'okay',
                                        'bg-orange-400': row.price_freshness === 'aging',
                                        'bg-orange-600': row.price_freshness === 'stale',
                                    }"
                                />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p class="text-xs">
                                    {{ 
                                        row.price_freshness === 'fresh' ? '0-3 days old - Very fresh' :
                                        row.price_freshness === 'recent' ? '4-7 days old - Fresh' :
                                        row.price_freshness === 'okay' ? '8-14 days old - Good' :
                                        row.price_freshness === 'aging' ? '15-30 days old - Getting old' :
                                        '30+ days old - Needs update'
                                    }}
                                </p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
                
                <!-- Updated timestamp with tooltip -->
                <TooltipProvider v-if="row.price_updated_human" :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div class="flex items-center gap-1.5 text-xs text-muted-foreground cursor-help w-fit">
                                <Clock class="size-3" />
                                <span>{{ row.price_updated_human }}</span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Updated on {{ row.price_updated_date }}</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
            
            <div v-else class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <div class="size-2 rounded-full bg-red-500" />
                <span>No price data</span>
            </div>
        </template>

        <!-- Custom Cell: Actions -->
        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1.5">
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
