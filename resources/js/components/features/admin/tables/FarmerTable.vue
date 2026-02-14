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
    ChevronDownIcon,
    ChevronRightIcon,
    MapPin,
    Phone,
    Mail,
    Eye,
} from 'lucide-vue-next'
import { Farmer, PaginatedData } from '@/types/admin/farmers'

/* -- types -- */


/* -- props / emits -- */
defineProps<{
    farmers: PaginatedData
}>()

defineEmits<{
    'view-farmer': [farmer: Farmer]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
const columns: ColumnDef<Farmer>[] = [
    {
        id: 'farmer',
        header: 'Farmer',
        accessorFn: (row) => row.user.name,
        enableSorting: true,
    },
    {
        id: 'location',
        header: 'Location',
        accessorFn: (row) => `${row.location.barangay}, ${row.location.municipality}`,
        enableSorting: true,
    },
    {
        id: 'available_plantings',
        header: 'Available Vegetables',
        accessorFn: (row) => row.available_offerings_count,
        enableSorting: true,
    },
    {
        id: 'joined',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
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
        :data="farmers"
        :columns="columns"
        search-placeholder="Search farmers..."
        empty-message="No farmers found."
        entity-name="farmers"
        enable-expand
        @page-change="$emit('page-change', $event)"
    >
        <!-- Custom Cell: Expander -->
        <template #cell-expander="{ row, cell }">
            <button
                v-if="row.available_offerings_count > 0"
                @click="cell.row.getToggleExpandedHandler()()"
                class="p-1 hover:bg-accent rounded transition-colors"
            >
                <ChevronDownIcon 
                    v-if="cell.row.getIsExpanded()" 
                    class="size-4" 
                />
                <ChevronRightIcon 
                    v-else 
                    class="size-4" 
                />
            </button>
        </template>

        <!-- Custom Cell: Farmer -->
        <template #cell-farmer="{ row }">
            <div class="flex items-center gap-3">
                <Avatar class="size-10 rounded-md">
                    <AvatarImage 
                        v-if="row.user.image_path"
                        :src="row.user.image_path" 
                        :alt="row.user.name"
                        class="object-cover"
                    />
                    <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                        {{ row.user.name.charAt(0) }}
                    </AvatarFallback>
                </Avatar>
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ row.user.name }}</span>
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <Mail class="size-3" />
                            {{ row.user.email }}
                        </div>
                        <div class="flex items-center gap-1">
                            <Phone class="size-3" />
                            {{ row.user.phone_number }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Custom Cell: Location -->
        <template #cell-location="{ row }">
            <div class="flex items-start gap-2">
                <MapPin class="size-4 mt-0.5 text-muted-foreground shrink-0" />
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ row.location.barangay }}</span>
                    <span class="text-xs text-muted-foreground">
                        {{ row.location.municipality }}, {{ row.location.province }}
                    </span>
                </div>
            </div>
        </template>

        <!-- Custom Cell: Active Plantings -->
        <template #cell-active_plantings="{ row }">
            <Badge variant="secondary" class="font-mono">
                {{ row.available_offerings_count }} active
            </Badge>
        </template>

        <!-- Custom Cell: Joined -->
        <template #cell-joined="{ row }">
            <TooltipProvider :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="text-sm cursor-help">
                            {{ row.joined_at_human }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="text-xs">Joined on {{ row.joined_at }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
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
                                @click="$emit('view-farmer', row)"
                            >
                                <Eye class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">View details</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>

        <!-- Expanded Row: Plantings -->
        <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-4 py-4">
                    <div class="ml-12">
                        <h4 class="text-sm font-medium mb-3">Available Offerings ({{ row.available_offerings_count }})</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div
                                v-for="offering in row.available_offerings"
                                :key="offering.id"
                                class="flex items-start gap-3 p-3 rounded-lg border bg-card hover:shadow-sm transition-shadow"
                            >
                                <Avatar class="size-12 rounded-md shrink-0">
                                    <AvatarImage 
                                        :src="offering.variety.image_path" 
                                        :alt="offering.variety.name"
                                        class="object-cover"
                                    />
                                    <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold text-xs">
                                        {{ offering.variety.name.charAt(0) }}
                                    </AvatarFallback>
                                </Avatar>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </template>
    </DataTable>
</template>
