<script setup lang="ts">
import { ref, computed } from 'vue'
import {
    useVueTable,
    getCoreRowModel,
    getFilteredRowModel,
    getSortedRowModel,
    getExpandedRowModel,
    FlexRender,
    type ColumnDef,
    type ExpandedState,
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
    ChevronDownIcon,
    ChevronRightIcon,
    MapPin,
    Phone,
    Mail,
    Eye,
} from 'lucide-vue-next'

/* -- types -- */
interface Planting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: string
    date_planted: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status_badge: string
}

interface Farmer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    location: {
        province: string
        municipality: string
        barangay: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    active_plantings_count: number
    active_plantings: Planting[]
    joined_at: string
    joined_at_human: string
}

interface PaginatedData {
    data: Farmer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

/* -- props / emits -- */
const props = defineProps<{
    farmers: PaginatedData
}>()

defineEmits<{
    'view-farmer': [farmer: Farmer]
    'page-change': [page: number]
}>()

/* -- local state -- */
const globalFilter = ref('')
const expanded = ref<ExpandedState>({})

/* -- column definitions -- */
const columns: ColumnDef<Farmer>[] = [
    {
        id: 'expander',
        header: () => null,
        enableSorting: false,
    },
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
        id: 'active_plantings',
        header: 'Active Plantings',
        accessorFn: (row) => row.active_plantings_count,
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

/* -- table instance -- */
const table = useVueTable({
    get data() {
        return props.farmers.data
    },
    columns,
    state: {
        get globalFilter() {
            return globalFilter.value
        },
        set globalFilter(value) {
            globalFilter.value = value
        },
        get expanded() {
            return expanded.value
        },
        set expanded(value) {
            expanded.value = value
        },
    },
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    manualPagination: true,
    getRowCanExpand: (row) => row.original.active_plantings_count > 0,
})

/* -- pagination helpers -- */
const hasPrevPage = computed(() => props.farmers.current_page > 1)
const hasNextPage = computed(() => props.farmers.current_page < props.farmers.last_page)

/* -- sort icon helper -- */
function sortIcon(state: string | false) {
    if (state === 'asc') return ChevronUp
    if (state === 'desc') return ChevronDown
    return ChevronsUpDown
}

/* -- status badge color helper -- */
function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        Growing: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        Overdue: 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        Harvested: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        Expired: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    }
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- toolbar -->
        <div class="flex items-center justify-between">
            <Input
                v-model="globalFilter"
                placeholder="Search farmers..."
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
                    <template v-for="row in table.getRowModel().rows" :key="row.original.id">
                        <!-- Main row -->
                        <tr class="border-t hover:bg-muted/40 transition-colors">
                            <td
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                class="px-4 py-3"
                            >
                                <!-- Expander Column -->
                                <template v-if="cell.column.id === 'expander'">
                                    <button
                                        v-if="row.getCanExpand()"
                                        @click="row.getToggleExpandedHandler()()"
                                        class="p-1 hover:bg-accent rounded transition-colors"
                                    >
                                        <ChevronDownIcon 
                                            v-if="row.getIsExpanded()" 
                                            class="size-4" 
                                        />
                                        <ChevronRightIcon 
                                            v-else 
                                            class="size-4" 
                                        />
                                    </button>
                                </template>

                                <!-- Farmer Column -->
                                <template v-else-if="cell.column.id === 'farmer'">
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

                                <!-- Location Column -->
                                <template v-else-if="cell.column.id === 'location'">
                                    <div class="flex items-start gap-2">
                                        <MapPin class="size-4 mt-0.5 text-muted-foreground shrink-0" />
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-medium">{{ row.original.location.barangay }}</span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ row.original.location.municipality }}, {{ row.original.location.province }}
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                <!-- Active Plantings Column -->
                                <template v-else-if="cell.column.id === 'active_plantings'">
                                    <Badge variant="secondary" class="font-mono">
                                        {{ row.original.active_plantings_count }} active
                                    </Badge>
                                </template>

                                <!-- Joined Column -->
                                <template v-else-if="cell.column.id === 'joined'">
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <div class="text-sm cursor-help">
                                                    {{ row.original.joined_at_human }}
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p class="text-xs">Joined on {{ row.original.joined_at }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
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
                                                        @click="$emit('view-farmer', row.original)"
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

                        <!-- Expanded row with plantings -->
                        <tr v-if="row.getIsExpanded()" class="bg-muted/20">
                            <td :colspan="columns.length" class="px-4 py-4">
                                <div class="ml-12">
                                    <h4 class="text-sm font-medium mb-3">Active Plantings ({{ row.original.active_plantings_count }})</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <div
                                            v-for="planting in row.original.active_plantings"
                                            :key="planting.id"
                                            class="flex items-start gap-3 p-3 rounded-lg border bg-card hover:shadow-sm transition-shadow"
                                        >
                                            <Avatar class="size-12 rounded-md shrink-0">
                                                <AvatarImage 
                                                    :src="planting.variety.image_path" 
                                                    :alt="planting.variety.name"
                                                    class="object-cover"
                                                />
                                                <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold text-xs">
                                                    {{ planting.variety.name.charAt(0) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex flex-col gap-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-sm truncate">{{ planting.variety.name }}</span>
                                                    <Badge 
                                                        :class="getStatusColor(planting.status_badge)"
                                                        class="text-xs shrink-0"
                                                    >
                                                        {{ planting.status_badge }}
                                                    </Badge>
                                                </div>
                                                <span class="text-xs text-muted-foreground">{{ planting.variety.category }}</span>
                                                <div class="flex flex-col gap-0.5 text-xs text-muted-foreground mt-1">
                                                    <div>Weight: {{ planting.weight_kg }} kg</div>
                                                    <div>Planted: {{ planting.date_planted }}</div>
                                                    <div>Expected harvest: {{ planting.expected_harvest_date }}</div>
                                                    <div v-if="planting.days_until_harvest !== null">
                                                        <span v-if="planting.days_until_harvest > 0">
                                                            {{ planting.days_until_harvest }} days until harvest
                                                        </span>
                                                        <span v-else-if="planting.days_until_harvest === 0">
                                                            Harvest today
                                                        </span>
                                                        <span v-else class="text-orange-600 dark:text-orange-400 font-medium">
                                                            {{ Math.abs(planting.days_until_harvest) }} days overdue
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td :colspan="columns.length" class="px-4 py-8 text-center text-muted-foreground">
                            No farmers found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- pagination -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Showing
                <strong>{{ (farmers.current_page - 1) * farmers.per_page + 1 }}</strong>–<strong>{{ Math.min(farmers.current_page * farmers.per_page, farmers.total) }}</strong>
                of <strong>{{ farmers.total }}</strong> farmers
            </span>

            <div class="flex items-center gap-1.5">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', farmers.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-2 font-medium text-foreground">
                    {{ farmers.current_page }} / {{ farmers.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', farmers.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>