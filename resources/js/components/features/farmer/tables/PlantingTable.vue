<script setup lang="ts">
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
import {
    Pencil,
    Trash2,
    Plus,
    CheckCircle,
    XCircle,
} from 'lucide-vue-next'

/* -- types -- */
interface Planting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: number
    date_planted: string
    date_planted_human: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status: 'active' | 'harvested' | 'expired' | 'cancelled'
    status_badge: string
    can_edit: boolean
    can_delete: boolean
    can_harvest: boolean
    can_cancel: boolean
}

interface PaginatedData {
    data: Planting[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

/* -- props / emits -- */
defineProps<{
    plantings: PaginatedData
}>()

defineEmits<{
    'open-create': []
    'open-edit': [planting: Planting]
    'open-harvest': [planting: Planting]
    'open-cancel': [planting: Planting]
    'open-delete': [planting: Planting]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
const columns: ColumnDef<Planting>[] = [
    {
        id: 'image',
        header: 'Image',
        enableSorting: false,
    },
    {
        id: 'variety',
        header: 'Variety',
        accessorFn: (row) => row.variety.name,
        enableSorting: true,
    },
    {
        id: 'weight',
        header: 'Weight',
        accessorFn: (row) => row.weight_kg,
        enableSorting: true,
    },
    {
        id: 'dates',
        header: 'Dates',
        enableSorting: false,
    },
    {
        id: 'harvest_countdown',
        header: 'Days Until Harvest',
        accessorFn: (row) => row.days_until_harvest,
        enableSorting: true,
    },
    {
        id: 'status',
        header: 'Status',
        accessorFn: (row) => row.status_badge,
        enableSorting: true,
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
    },
]

/* -- status badge color helper -- */
function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        Growing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        Overdue: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        Harvested: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        Expired: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        Cancelled: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}
</script>

<template>
    <DataTable
        :data="plantings"
        :columns="columns"
        search-placeholder="Search plantings..."
        empty-message="No plantings found."
        entity-name="plantings"
        @page-change="$emit('page-change', $event)"
    >
        <!-- Toolbar Actions -->
        <template #toolbar-actions>
            <Button @click="$emit('open-create')" class="gap-1.5">
                <Plus class="size-4" />
                Add Planting
            </Button>
        </template>

        <!-- Custom Cell: Image -->
        <template #cell-image="{ row }">
            <Avatar class="size-12 rounded-md">
                <AvatarImage 
                    :src="row.variety.image_path" 
                    :alt="row.variety.name"
                    class="object-cover"
                />
                <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                    {{ row.variety.name.charAt(0) }}
                </AvatarFallback>
            </Avatar>
        </template>

        <!-- Custom Cell: Variety -->
        <template #cell-variety="{ row }">
            <div class="flex flex-col gap-0.5">
                <span class="font-medium">{{ row.variety.name }}</span>
                <span class="text-xs text-muted-foreground">
                    {{ row.variety.category }}
                </span>
            </div>
        </template>

        <!-- Custom Cell: Weight -->
        <template #cell-weight="{ row }">
            <Badge variant="secondary" class="font-mono">
                {{ row.weight_kg }} kg
            </Badge>
        </template>

        <!-- Custom Cell: Dates -->
        <template #cell-dates="{ row }">
            <div class="flex flex-col gap-1 text-xs">
                <div>
                    <span class="text-muted-foreground">Planted:</span>
                    <span class="ml-1 font-medium">{{ row.date_planted }}</span>
                </div>
                <div>
                    <span class="text-muted-foreground">Harvest:</span>
                    <span class="ml-1 font-medium">{{ row.expected_harvest_date }}</span>
                </div>
            </div>
        </template>

        <!-- Custom Cell: Harvest Countdown -->
        <template #cell-harvest_countdown="{ row }">
            <div v-if="row.days_until_harvest !== null" class="text-sm">
                <span v-if="row.days_until_harvest > 0" class="font-medium">
                    {{ row.days_until_harvest }} days
                </span>
                <span v-else-if="row.days_until_harvest === 0" class="font-medium text-orange-600 dark:text-orange-500">
                    Today
                </span>
                <span v-else class="font-medium text-red-600 dark:text-red-500">
                    {{ Math.abs(row.days_until_harvest) }} days overdue
                </span>
            </div>
            <span v-else class="text-xs text-muted-foreground">N/A</span>
        </template>

        <!-- Custom Cell: Status -->
        <template #cell-status="{ row }">
            <Badge 
                :class="getStatusColor(row.status_badge)"
                class="text-xs"
            >
                {{ row.status_badge }}
            </Badge>
        </template>

        <!-- Custom Cell: Actions -->
        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1.5">
                <!-- Edit (only if can_edit) -->
                <TooltipProvider v-if="row.can_edit" :delay-duration="200">
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
                            <p class="text-xs">Edit planting</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <!-- Harvest (only if can_harvest) -->
                <TooltipProvider v-if="row.can_harvest" :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-green-600 dark:hover:text-green-500"
                                @click="$emit('open-harvest', row)"
                            >
                                <CheckCircle class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Mark as harvested</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <!-- Cancel (only if can_cancel) -->
                <TooltipProvider v-if="row.can_cancel" :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-orange-600 dark:hover:text-orange-500"
                                @click="$emit('open-cancel', row)"
                            >
                                <XCircle class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">Cancel planting</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>

                <!-- Delete (only if can_delete) -->
                <TooltipProvider v-if="row.can_delete" :delay-duration="200">
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
                            <p class="text-xs">Delete planting</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>
    </DataTable>
</template>
