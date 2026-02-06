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

/* -- types -- */
interface Dealer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    activity: {
        total_conversations: number
        active_conversations: number
        last_activity_at: string | null
        last_activity_human: string | null
        status: 'active' | 'moderate' | 'inactive'
    }
    document_image: string | null
    joined_at: string
    joined_at_human: string
}

interface PaginatedData {
    data: Dealer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

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
        id: 'total_conversations',
        header: 'Total Conversations',
        accessorFn: (row) => row.activity.total_conversations,
        enableSorting: true,
    },
    {
        id: 'active_conversations',
        header: 'Active Conversations',
        accessorFn: (row) => row.activity.active_conversations,
        enableSorting: true,
    },
    {
        id: 'last_activity',
        header: 'Last Activity',
        accessorFn: (row) => row.activity.last_activity_at,
        enableSorting: true,
    },
    {
        id: 'status',
        header: 'Status',
        accessorFn: (row) => row.activity.status,
        enableSorting: true,
    },
    {
        id: 'actions',
        header: 'Actions',
        enableSorting: false,
    },
]

/* -- status badge helpers -- */
function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        active: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        moderate: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        inactive: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}

function getStatusLabel(status: string) {
    const labels: Record<string, string> = {
        active: 'Active',
        moderate: 'Moderate',
        inactive: 'Inactive',
    }
    return labels[status] || 'Unknown'
}
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
                        v-if="row.user.user_image"
                        :src="row.user.user_image" 
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

        <!-- Custom Cell: Total Conversations -->
        <template #cell-total_conversations="{ row }">
            <div class="flex items-center gap-2">
                <MessageSquare class="size-4 text-muted-foreground" />
                <span class="font-mono font-medium">
                    {{ row.activity.total_conversations }}
                </span>
            </div>
        </template>

        <!-- Custom Cell: Active Conversations -->
        <template #cell-active_conversations="{ row }">
            <div class="flex items-center gap-2">
                <Activity class="size-4 text-primary" />
                <span class="font-mono font-medium">
                    {{ row.activity.active_conversations }}
                </span>
                <span class="text-xs text-muted-foreground">(30d)</span>
            </div>
        </template>

        <!-- Custom Cell: Last Activity -->
        <template #cell-last_activity="{ row }">
            <TooltipProvider v-if="row.activity.last_activity_human" :delay-duration="200">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <div class="text-sm cursor-help">
                            {{ row.activity.last_activity_human }}
                        </div>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p class="text-xs">Last active: {{ row.activity.last_activity_at }}</p>
                    </TooltipContent>
                </Tooltip>
            </TooltipProvider>
            <span v-else class="text-sm text-muted-foreground">Never</span>
        </template>

        <!-- Custom Cell: Status -->
        <template #cell-status="{ row }">
            <Badge 
                :class="getStatusColor(row.activity.status)"
                class="text-xs"
            >
                {{ getStatusLabel(row.activity.status) }}
            </Badge>
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
