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
    Eye,
    Phone,
    Mail,
    MessageSquare,
    Activity,
} from 'lucide-vue-next'
import { Dealer, PaginatedData } from '@/types/admin/dealers'

/* -- props / emits -- */
defineProps<{
    dealers: PaginatedData
}>()

defineEmits<{
    'view-dealer': [dealer: Dealer]
    'page-change': [page: number]
}>()

/* -- column definitions -- */
const columns: ColumnDef<Dealer>[] = [
    {
        id: 'dealer',
        header: 'Dealer',
        accessorFn: (row) => row.user.name,
        enableSorting: true,
    },
    {
        id: 'open_requests_count',
        header: 'Total Open Requests',
        accessorFn: (row) => row.open_requests_count,
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
    <DataTable
        :data="dealers"
        :columns="columns"
        search-placeholder="Search dealers..."
        empty-message="No dealers found."
        entity-name="dealers"
        @page-change="$emit('page-change', $event)"
    >
        <!-- Custom Cell: Dealer -->
        <template #cell-dealer="{ row }">
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

        <!-- Custom Cell: Open Requests -->
        <template #cell-open_requests_count="{ row }">
            <div class="flex items-center gap-2">
                <MessageSquare class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">
                    {{ row.open_requests_count }}
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
                            <Button
                                variant="ghost"
                                size="icon-sm"
                                class="text-muted-foreground hover:text-foreground"
                                @click="$emit('view-dealer', row)"
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
    </DataTable>
</template>
