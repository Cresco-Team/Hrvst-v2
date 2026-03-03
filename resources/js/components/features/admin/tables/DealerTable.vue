<script setup lang="ts">

import type { ColumnDef } from '@tanstack/vue-table'
import { Eye, Phone, Mail, Package, ChevronDownIcon, ChevronRightIcon } from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { getInitials } from '@/composables/useInitials'
import type { Dealer } from '@/types/admin/dealers'
import type { PaginatedResponse } from '@/types/pagination'

/* -- props / emits -- */
defineProps<{
    dealers: PaginatedResponse<Dealer>
}>()

defineEmits<{
    'view-dealer': [dealer: Dealer]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
const columns: ColumnDef<Dealer>[] = [
    {
        id: 'expander',
        header: () => null,
        cell: () => null,
    }, {
        id: 'dealer',
        header: 'Dealer',
        accessorFn: (row) => row.user.name,
        enableSorting: true,
    },
    {
        id: 'ongoing_demands_count',
        header: 'Total Open Requests',
        accessorFn: (row) => row.ongoing_demands_count,
        enableSorting: true,
    },
    {
        id: 'joined_at',
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
    <DataTable :data="dealers" :columns="columns" search-placeholder="Search dealers..."
        empty-message="No dealers found." entity-name="dealers" @page-change="$emit('page-change', $event)" enable-expand>
        <!-- Custom Cell: Expander -->
         <template #cell-expander="{ row, cell }">
            <button v-if="row.ongoing_demands.length > 0" @click="cell.row.toggleExpanded()"
                class="p-1 hover:bg-accent rounded transition-colors">
                <ChevronDownIcon v-if="cell.row.getIsExpanded()" class="size-4" />
                <ChevronRightIcon v-else class="size-4" />
            </button>
        </template>

        <!-- Custom Cell: Dealer -->
        <template #cell-dealer="{ row }">
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

        <!-- Custom Cell: Open Requests -->
        <template #cell-ongoing_demands_count="{ row }">
            <div class="flex items-center gap-2">
                <Package class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">
                    {{ row.ongoing_demands_count }}
                </span>
            </div>
        </template>

        <!-- Custom Cell: Joined -->
        <template #cell-joined_at="{ row }">
            <TooltipProvider v-if="row.joined_at" :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="text-sm cursor-help">
                            {{ row.joined_at_human }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="text-xs">Joined on: {{ row.joined_at }}</p>
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
                                @click="$emit('view-dealer', row)">
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

        <!-- Expanded Row: Demands -->
         <template #expanded-row="{ row, colspan }">
            <tr class="bg-muted/20">
                <td :colspan="colspan" class="px-4 py-4">
                    <div class="ml-12">
                        <h4 class="text-sm font-medium mb-3">Available Demands ({{ row.ongoing_demands.length }})
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <div v-for="demand in row.ongoing_demands" :key="demand.id"
                                class="flex items-start gap-3 p-3 rounded-lg border bg-card hover:shadow-sm transition-shadow">
                                <Avatar class="size-12 rounded-md shrink-0">
                                    <AvatarImage :src="demand.variety.image_url" :alt="demand.variety.name"
                                        class="object-cover" />
                                    <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold text-xs">
                                        {{ demand.variety.name.charAt(0) }}
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
