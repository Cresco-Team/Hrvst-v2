<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import {
    ChevronDownIcon,
    ChevronRightIcon,
    ClipboardList,
    Mail,
    MapPin,
    Package,
    Phone,
} from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { getInitials } from '@/composables/useInitials'
import type { FarmerResource, Paginated } from '@/types'

defineProps<{
    farmers: Paginated<FarmerResource>
    searchQuery?: string
}>()

defineEmits<{
    'view-farmer': [farmer: FarmerResource]
    'page-change': [page: number]
    search: [query: string]
}>()

const columns: ColumnDef<FarmerResource>[] = [
    { id: 'expander', header: '' },
    {
        id: 'farmer',
        header: 'Farmer',
        accessorFn: (row) => row.user?.name ?? '',
        enableSorting: true,
    },
    {
        id: 'ongoing_supplies_count',
        header: 'Supplies',
        accessorFn: (row) => row.ongoing_supplies_count ?? 0,
    },
    {
        id: 'location',
        header: 'Address',
        accessorFn: (row) =>
            `${row.location?.barangay}, ${row.location?.municipality}`,
        enableSorting: true,
    },
    {
        id: 'joined',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
    },
    { id: 'actions', header: 'Actions', enableSorting: false },
]
</script>

<template>
    <DataTable
        :data="farmers"
        :columns="columns"
        :search-query="searchQuery"
        search-placeholder="Search farmers..."
        entity-name="farmers"
        empty-message="No farmers found"
        enable-expand
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
    >
        <template #cell-expander="{ row, cell }">
            <Button
                v-if="(row.ongoing_supplies_count ?? 0) > 0"
                variant="ghost"
                size="icon-sm"
                class="text-muted-foreground"
                @click="cell.row.toggleExpanded()"
            >
                <ChevronDownIcon
                    v-if="cell.row.getIsExpanded()"
                    class="size-4"
                />
                <ChevronRightIcon v-else class="size-4" />
            </Button>
        </template>

        <template #cell-farmer="{ row }">
            <div class="flex items-center gap-3">
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ row.user?.name }}</span>
                    <div
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center gap-1">
                            <Mail class="size-3" />
                            {{ row.user?.email }}
                        </div>
                        <div class="flex items-center gap-1">
                            <Phone class="size-3" />
                            {{ row.user?.phone_number }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #cell-ongoing_supplies_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">{{
                    row.ongoing_supplies_count ?? 0
                }}</span>
            </div>
        </template>

        <template #cell-location="{ row }">
            <div class="flex items-start gap-2">
                <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{
                        row.location?.barangay
                    }}</span>
                    <span class="text-xs text-muted-foreground">
                        {{ row.location?.municipality }},
                        {{ row.location?.province }}
                    </span>
                </div>
            </div>
        </template>

        <template #cell-joined="{ row }">
            <TooltipProvider :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="cursor-help text-sm">
                            {{ row.joined_at_human }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="text-xs">Joined on {{ row.joined_at }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
        </template>

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
                                <ClipboardList class="size-4" />
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>
                            <p class="text-xs">View details</p>
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </template>

        <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-4 py-4">
                    <div class="ml-12">
                        <h4 class="mb-3 text-sm font-medium">
                            Available Supplies ({{
                                row.ongoing_supplies_count ?? 0
                            }})
                        </h4>
                        <div
                            class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4"
                        >
                            <Item
                                v-for="item in row.supply_items"
                                :key="item.id"
                                variant="outline"
                            >
                                <ItemMedia variant="image"
                                    >\
                                    <img
                                        v-if="item"
                                        :src="item.image_url"
                                        :alt="item.name"
                                    />
                                </ItemMedia>
                                <ItemContent>
                                    <ItemTitle class="line-clamp-1">
                                        {{ item.name }}
                                    </ItemTitle>
                                    <ItemDescription
                                        >{{
                                            item.quantity_kg.toFixed(2)
                                        }}
                                        kg</ItemDescription
                                    >
                                </ItemContent>
                            </Item>
                        </div>
                    </div>
                </td>
            </tr>
        </template>
    </DataTable>
</template>
