<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import {
    ChevronDownIcon,
    ChevronRightIcon,
    ClipboardList,
    Mail,
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
import type { DealerResource, Paginated } from '@/types'

defineProps<{
    dealers: Paginated<DealerResource>
    searchQuery?: string
}>()

defineEmits<{
    'view-dealer': [dealer: DealerResource]
    'page-change': [page: number]
    search: [query: string]
}>()

const columns: ColumnDef<DealerResource>[] = [
    { id: 'expander', header: '' },
    {
        id: 'dealer',
        header: 'Dealer',
        accessorFn: (row) => row.user?.name ?? '',
        enableSorting: true,
    },
    {
        id: 'ongoing_demands_count',
        header: 'Demands',
        accessorFn: (row) => row.ongoing_demands_count ?? 0,
        enableSorting: true,
    },
    {
        id: 'joined_at',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
    },
    { id: 'actions', header: 'Actions', enableSorting: false },
]
</script>

<template>
    <DataTable
        :data="dealers"
        :columns="columns"
        :search-query="searchQuery"
        search-placeholder="Search dealers..."
        entity-name="dealers"
        empty-message="No dealers found."
        enable-expand
        @page-change="$emit('page-change', $event)"
        @search="$emit('search', $event)"
    >
        <template #cell-expander="{ row, cell }">
            <Button
                v-if="(row.ongoing_demands_count ?? 0) > 0"
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

        <template #cell-dealer="{ row }">
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
        </template>

        <template #cell-ongoing_demands_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">{{
                    row.ongoing_demands_count ?? 0
                }}</span>
            </div>
        </template>

        <template #cell-joined_at="{ row }">
            <TooltipProvider v-if="row.joined_at" :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="cursor-help text-sm">
                            {{ row.joined_at_human }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="text-xs">Joined on: {{ row.joined_at }}</p>
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
                                @click="$emit('view-dealer', row)"
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
                            Ongoing Demands ({{
                                row.demand_items?.length ?? 0
                            }})
                        </h4>
                        <div
                            class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4"
                        >
                            <Item
                                v-for="demand in row.demand_items"
                                :key="demand.id"
                                variant="outline"
                            >
                                <ItemMedia variant="image">
                                    <img
                                        :src="demand.image_url"
                                        :alt="demand.name"
                                    />
                                </ItemMedia>
                                <ItemContent>
                                    <ItemTitle class="line-clamp-1">
                                        {{ demand.name }}
                                    </ItemTitle>
                                    <ItemDescription
                                        >{{
                                            demand.quantity_kg.toFixed(2)
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
