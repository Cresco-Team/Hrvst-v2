<script setup lang="ts">
import { ref, computed } from 'vue'
import {
    useVueTable,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    FlexRender,
    type ColumnDef,
} from '@tanstack/vue-table'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import {
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    ChevronDown,
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
const props = defineProps<{
    dealers: PaginatedData
}>()

defineEmits<{
    'view-dealer': [dealer: Dealer]
    'page-change': [page: number]
}>()

/* -- local state -- */
const globalFilter = ref('')

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

/* -- table instance -- */
const table = useVueTable({
    get data() {
        return props.dealers.data
    },
    columns,
    state: {
        get globalFilter() {
            return globalFilter.value
        },
        set globalFilter(value) {
            globalFilter.value = value
        },
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
})

/* -- pagination helpers -- */
const hasPrevPage = computed(() => props.dealers.current_page > 1)
const hasNextPage = computed(() => props.dealers.current_page < props.dealers.last_page)

/* -- sort icon helper -- */
function sortIcon(state: string | false) {
    if (state === 'asc') return ChevronUp
    if (state === 'desc') return ChevronDown
    return ChevronsUpDown
}

/* -- status badge color helper -- */
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
    <div class="flex flex-col gap-4">
        <!-- toolbar -->
        <div class="flex items-center justify-between">
            <Input
                v-model="globalFilter"
                placeholder="Search dealers..."
                class="max-w-xs"
            />
        </div>

        <!-- table -->
        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-4 py-3 text-left font-medium text-muted-foreground"
                        >
                            <template v-if="header.column.getCanSort()">
                                <button
                                    class="flex items-center gap-1 hover:text-foreground transition-colors"
                                    @click="header.column.toggleSorting(header.column.getIsSorted() === 'asc')"
                                >
                                    <template>
                                        <FlexRender 
                                            :render="header.column.columnDef.header"
                                            :props="header.getContext()"
                                        />
                                    </template>
                                    <component :is="sortIcon(header.column.getIsSorted())" class="size-3.5" />
                                </button>
                            </template>
                            <template v-else>
                                <template v-if="!header.isPlaceholder">
                                    <FlexRender 
                                        :render="header.column.columnDef.header"
                                        :props="header.getContext()"
                                    />
                                </template>
                            </template>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.original.id"
                        class="border-t hover:bg-muted/40 transition-colors"
                    >
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            class="px-4 py-3"
                        >
                            <!-- Dealer Column -->
                            <template v-if="cell.column.id === 'dealer'">
                                <div class="flex items-center gap-3">
                                    <Avatar class="size-10 rounded-md">
                                        <AvatarImage 
                                            v-if="row.original.user.user_image"
                                            :src="row.original.user.user_image" 
                                            :alt="row.original.user.name"
                                            class="object-cover"
                                        />
                                        <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                                            {{ row.original.user.name.charAt(0) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex flex-col gap-0.5">
                                        <span class="font-medium">{{ row.original.user.name }}</span>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <div class="flex items-center gap-1">
                                                <Mail class="size-3" />
                                                {{ row.original.user.email }}
                                            </div>
                                            <div class="flex items-center gap-1">
                                                <Phone class="size-3" />
                                                {{ row.original.user.phone_number }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Total Conversations Column -->
                            <template v-else-if="cell.column.id === 'total_conversations'">
                                <div class="flex items-center gap-2">
                                    <MessageSquare class="size-4 text-muted-foreground" />
                                    <span class="font-mono font-medium">
                                        {{ row.original.activity.total_conversations }}
                                    </span>
                                </div>
                            </template>

                            <!-- Active Conversations Column -->
                            <template v-else-if="cell.column.id === 'active_conversations'">
                                <div class="flex items-center gap-2">
                                    <Activity class="size-4 text-primary" />
                                    <span class="font-mono font-medium">
                                        {{ row.original.activity.active_conversations }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">(30d)</span>
                                </div>
                            </template>

                            <!-- Last Activity Column -->
                            <template v-else-if="cell.column.id === 'last_activity'">
                                <TooltipProvider v-if="row.original.activity.last_activity_human" :delay-duration="200">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <div class="text-sm cursor-help">
                                                {{ row.original.activity.last_activity_human }}
                                            </div>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p class="text-xs">Last active: {{ row.original.activity.last_activity_at }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <span v-else class="text-sm text-muted-foreground">Never</span>
                            </template>

                            <!-- Status Column -->
                            <template v-else-if="cell.column.id === 'status'">
                                <Badge 
                                    :class="getStatusColor(row.original.activity.status)"
                                    class="text-xs"
                                >
                                    {{ getStatusLabel(row.original.activity.status) }}
                                </Badge>
                            </template>

                            <!-- Actions Column -->
                            <template v-else-if="cell.column.id === 'actions'">
                                <div class="flex items-center gap-1.5">
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-foreground"
                                                    @click="$emit('view-dealer', row.original)"
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

                            <!-- Default rendering -->
                            <template v-else>
                                <FlexRender 
                                    :render="cell.column.columnDef.cell" 
                                    :props="cell.getContext()" 
                                />
                            </template>
                        </td>
                    </tr>

                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td :colspan="columns.length" class="px-4 py-8 text-center text-muted-foreground">
                            No dealers found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- pagination -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Showing
                <strong>{{ (dealers.current_page - 1) * dealers.per_page + 1 }}</strong>–<strong>{{ Math.min(dealers.current_page * dealers.per_page, dealers.total) }}</strong>
                of <strong>{{ dealers.total }}</strong> dealers
            </span>

            <div class="flex items-center gap-1.5">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', dealers.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-2 font-medium text-foreground">
                    {{ dealers.current_page }} / {{ dealers.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', dealers.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>
