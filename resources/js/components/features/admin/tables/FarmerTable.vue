<script setup lang="ts">

import type { ColumnDef } from '@tanstack/vue-table'
import { ChevronDownIcon, ChevronRightIcon, MapPin, Phone, Mail, Eye, Package } from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { getInitials } from '@/composables/useInitials'
import type { Farmer } from '@/types/admin/farmers'
import type { PaginatedResponse } from '@/types/pagination'

defineProps<{
    farmers: PaginatedResponse<Farmer>
}>()

defineEmits<{
    'view-farmer': [farmer: Farmer]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
const columns: ColumnDef<Farmer>[] = [
    {
        id: 'expander',
        header: () => null,
        cell: () => null,
    }, {
        id: 'farmer',
        header: 'Farmer',
        accessorFn: (row) => row.user.name,
        enableSorting: true,
    }, {
        id: 'ongoing_supplies_count',
        header: 'Supplies',
        accessorFn: (row) => row.ongoing_supplies_count
    }, {
        id: 'location',
        header: 'Address',
        accessorFn: (row) => `${row.location.barangay}, ${row.location.municipality}`,
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
    <DataTable :data="farmers" :columns="columns" search-placeholder="Search farmers..."
        empty-message="No farmers found." entity-name="farmers" enable-expand
        @page-change="$emit('page-change', $event)">
        <!-- Custom Cell: Expander -->
        <template #cell-expander="{ row, cell }">
            <button v-if="row.ongoing_supplies.length > 0" @click="cell.row.toggleExpanded()"
                class="p-1 hover:bg-accent rounded transition-colors">
                <ChevronDownIcon v-if="cell.row.getIsExpanded()" class="size-4" />
                <ChevronRightIcon v-else class="size-4" />
            </button>
        </template>

        <!-- Custom Cell: Farmer -->
        <template #cell-farmer="{ row }">
            <div class="flex items-center gap-3">
                <Avatar class="size-10 rounded-md">
                    <AvatarImage v-if="row.user.image_url" :src="row.user.image_url" :alt="row.user.name"
                        class="object-cover" />
                    <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                        {{ getInitials(row.user.name) }}
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

        <template #cell-ongoing_supplies_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">
                    {{ row.ongoing_supplies_count }}
                </span>
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
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-foreground"
                                @click="$emit('view-farmer', row)">
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

        <!-- Expanded Row: Supplies -->
        <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-4 py-4">
                    <div class="ml-12">
                        <h4 class="text-sm font-medium mb-3">Available Supplies ({{ row.ongoing_supplies_count }})
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                            <Item v-for="supply in row.ongoing_supplies" :key="supply.id" variant="outline">
                                <ItemMedia variant="image">
                                    <img
                                        v-if="supply.image_url"
                                        :src="supply.image_url"
                                        :alt="supply.variety.name">
                                </ItemMedia>

                                <ItemContent>
                                    <ItemTitle class="line-clamp-1">
                                        {{ supply.variety.name }} - <span class="text-muted-foreground">{{ supply.variety.category }}</span>
                                    </ItemTitle>
                                    <ItemDescription>{{ supply.quantity_kg.toFixed(2) }} kg</ItemDescription>
                                </ItemContent>
                            </Item>
                        </div>
                    </div>
                </td>
            </tr>
        </template>
    </DataTable>
</template>
