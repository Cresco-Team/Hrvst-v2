<script setup lang="ts">
import type { ColumnDef } from '@tanstack/vue-table'
import { ClipboardList, Mail, MapPin, Phone } from 'lucide-vue-next'
import DataTable from '@/components/shared/tables/DataTable.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import { getInitials } from '@/composables/useInitials'
import type { AdminFarmerTable, Paginated } from '@/types'

defineProps<{
    farmers: Paginated<AdminFarmerTable>
    searchQuery?: string
}>()

defineEmits<{
    'view-farmer': [farmer: AdminFarmerTable]
    'page-change': [page: number]
    search: [query: string]
}>()

const columns: ColumnDef<AdminFarmerTable>[] = [
    {
        id: 'expander',
        header: '',
        size: 10,
        enableSorting: false,
    },
    {
        id: 'farmer',
        header: 'Farmer',
        accessorFn: (row) => row.user?.name ?? '',
        enableSorting: true,
    },
    {
        id: 'location',
        header: 'Address',
        accessorFn: (row) =>
            `${row.location?.barangay}, ${row.location?.municipality}`,
        enableSorting: true,
        meta: { hideOnMobile: true },
    },
    {
        id: 'joined',
        header: 'Joined',
        accessorFn: (row) => row.joined_at,
        enableSorting: true,
        meta: { hideOnMobile: true },
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
        size: 80,
    },
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
        <template #cell-farmer="{ row }">
            <div class="flex items-center gap-1 sm:gap-3">
                <Avatar>
                    <AvatarImage :src="row.user.avatar_url!" />
                    <AvatarFallback>
                        {{ getInitials(row.user.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ row.user?.name }}</span>
                    <div class="text-xs text-muted-foreground">
                        <div class="flex items-center gap-1">
                            <Phone class="size-3" />
                            {{ row.user?.phone_number }}
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <template #cell-location="{ row }">
            <div class="flex items-center gap-2">
                <MapPin
                    :size="20"
                    class="mt-0.5 shrink-0 text-muted-foreground"
                />
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
    </DataTable>
</template>
