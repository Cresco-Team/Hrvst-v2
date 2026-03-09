<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table';
import { Phone, Mail, Package, ChevronDownIcon, ChevronRightIcon } from 'lucide-vue-next';
import DataTable from '@/components/shared/tables/DataTable.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { getInitials } from '@/composables/useInitials';
import type { Dealer } from '@/types/admin/dealers';
import type { PaginatedResponse } from '@/types/pagination';

defineProps<{
    dealers: PaginatedResponse<Dealer>;
    searchQuery?: string;
}>();

defineEmits<{
    'view-dealer': [dealer: Dealer];
    'page-change': [page: number];
    search: [query: string];
}>();

/* -- column definitions -- */
const columns: ColumnDef<Dealer>[] = [
    {
        id: 'expander',
        header: '',
    }, {
        id: 'image',
        header: 'Image',
    }, {
        id: 'dealer',
        header: 'Dealer',
        accessorFn: (row) => row.user.name,
        enableSorting: true,
    }, {
        id: 'ongoing_demands_count',
        header: 'Total Open Requests',
        accessorFn: (row) => row.ongoing_demands_count,
        enableSorting: true,
    }, {
        id: 'joined_at',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
    }, {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
    },
];
</script>

<template>
    <DataTable :data="dealers" :columns="columns" :search-query="searchQuery" search-placeholder="Search dealers..."
        entity-name="dealers" empty-message="No dealers found." enable-expand
        @page-change="$emit('page-change', $event)" @search="$emit('search', $event)">
        <!-- Expander -->
        <template #cell-expander="{ row, cell }">
            <Button v-if="row.ongoing_demands.length > 0" @click="cell.row.toggleExpanded()"
                variant="ghost" size="icon-sm" class="text-muted-foreground">
                <ChevronDownIcon v-if="cell.row.getIsExpanded()" class="size-4" />
                <ChevronRightIcon v-else class="size-4" />
            </Button>
        </template>

        <!-- Image -->
        <template #cell-image="{ row }">
            <Avatar class="size-12 rounded-md">
                <AvatarImage v-if="row.document_image" :src="row.document_image" :alt="row.user.name" />
                <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                    {{ getInitials(row.user.name) }}
                </AvatarFallback>
            </Avatar>
        </template>

        <!-- Dealer -->
        <template #cell-dealer="{ row }">
            <div class="flex items-center gap-3">
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

        <!-- Custom Cell: Ongoing Demands -->
        <template #cell-ongoing_demands_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">
                    {{ row.ongoing_demands_count }}
                </span>
            </div>
        </template>

        <!-- Joined -->
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

        <!-- Actions -->
        <template #cell-actions="{ row }">
            <div class="flex items-center gap-1.5">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button variant="ghost" size="icon-sm" class="text-muted-foreground hover:text-foreground"
                                @click="$emit('view-dealer', row)">
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

        <!-- Demands -->
        <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-4 py-4">
                    <div class="ml-12">
                        <h4 class="mb-3 text-sm font-medium">
                            Ongoing Demands ({{ row.ongoing_demands.length }})
                        </h4>
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <Item v-for="demand in row.ongoing_demands" :key="demand.id" variant="outline">
                                <ItemMedia variant="image">
                                    <img
                                        v-if="demand.variety.image_url"
                                        :src="demand.variety.image_url"
                                        :alt="demand.variety.name"
                                    >
                                </ItemMedia>

                                <ItemContent>
                                    <ItemTitle class="line-clamp-1">
                                        {{ demand.variety.name }} - <span class="text-muted-foreground">{{ demand.variety.category }}</span>
                                    </ItemTitle>
                                    <ItemDescription>{{ demand.quantity_kg.toFixed(2) }} kg</ItemDescription>
                                </ItemContent>
                            </Item>
                        </div>
                    </div>
                </td>
            </tr>
        </template>
    </DataTable>
</template>
