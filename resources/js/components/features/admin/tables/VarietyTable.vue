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
    Pencil,
    Trash2,
    Plus,
    Clock,
} from 'lucide-vue-next'

/* -- types -- */
interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_path: string
    weeks_to_harvest: number
    vegetable: {
        id: number
        name: string
        category: {
            id: number
            name: string
        }
    }
    latest_price: {
        price_min: string
        price_max: string
    } | null
    price_updated_human?: string
    price_updated_date?: string
}

interface PaginatedData {
    data: Variety[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

/* -- props / emits -- */
const props = defineProps<{
    varieties: PaginatedData
}>()

defineEmits<{
    'open-create': []
    'open-edit': [variety: Variety]
    'open-delete': [variety: Variety]
    'page-change': [page: number]
}>()

/* -- local state -- */
const globalFilter = ref('')

/* -- column definitions -- */
const columns: ColumnDef<Variety>[] = [
    {
        id: 'image',
        header: 'Image',
        enableSorting: false,
    },
    {
        id: 'name',
        header: 'Variety',
        accessorFn: (row) => `${row.vegetable.name} ${row.name}`,
        enableSorting: true,
    },
    {
        id: 'price_range',
        header: 'Price Range',
        accessorFn: (row) => row.latest_price 
            ? `₱${row.latest_price.price_min} - ₱${row.latest_price.price_max}`
            : 'No price data',
        enableSorting: false,
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
        return props.varieties.data
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
const hasPrevPage = computed(() => props.varieties.current_page > 1)
const hasNextPage = computed(() => props.varieties.current_page < props.varieties.last_page)

/* -- sort icon helper -- */
function sortIcon(state: string | false) {
    if (state === 'asc') return ChevronUp
    if (state === 'desc') return ChevronDown
    return ChevronsUpDown
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- toolbar -->
        <div class="flex items-center justify-between">
            <Input
                v-model="globalFilter"
                placeholder="Search varieties…"
                class="max-w-xs"
            />
            <Button @click="$emit('open-create')" class="gap-1.5">
                <Plus class="size-4" />
                Add Variety
            </Button>
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
                            <!-- Image Column -->
                            <template v-if="cell.column.id === 'image'">
                                <Avatar class="size-12 rounded-md">
                                    <AvatarImage 
                                        :src="row.original.image_path" 
                                        :alt="row.original.name"
                                        class="object-cover"
                                    />
                                    <AvatarFallback class="rounded-md bg-primary/10 text-primary font-semibold">
                                        {{ row.original.name.charAt(0) }}
                                    </AvatarFallback>
                                </Avatar>
                            </template>

                            <!-- Name Column with vegetable + variety + category -->
                            <template v-else-if="cell.column.id === 'name'">
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium">
                                        {{ row.original.vegetable.name }} {{ row.original.name }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ row.original.vegetable.category.name }}
                                    </span>
                                </div>
                            </template>

                            <!-- Price Range Column with elegant timestamp -->
                            <template v-else-if="cell.column.id === 'price_range'">
                                <div v-if="row.original.latest_price" class="flex flex-col gap-1.5">
                                    <!-- Price Badge -->
                                    <Badge variant="secondary" class="w-fit font-mono">
                                        ₱{{ row.original.latest_price.price_min }} - ₱{{ row.original.latest_price.price_max }}
                                    </Badge>
                                    
                                    <!-- Updated timestamp with tooltip -->
                                    <TooltipProvider v-if="row.original.price_updated_human" :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <div class="flex items-center gap-1.5 text-xs text-muted-foreground cursor-help w-fit">
                                                    <Clock class="size-3" />
                                                    <span>{{ row.original.price_updated_human }}</span>
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p class="text-xs">Updated on {{ row.original.price_updated_date }}</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                                
                                <div v-else class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                    <Clock class="size-3 opacity-50" />
                                    <span>No price data</span>
                                </div>
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
                                                    @click="$emit('open-edit', row.original)"
                                                >
                                                    <Pencil class="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p class="text-xs">Edit variety</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>

                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-destructive"
                                                    @click="$emit('open-delete', row.original)"
                                                >
                                                    <Trash2 class="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                <p class="text-xs">Delete variety</p>
                                            </TooltipContent>
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </template>

                            <!-- Default rendering for other columns -->
                            <template v-else>
                                <FlexRender 
                                    :render="cell.column.columnDef.cell" 
                                    :props="cell.getContext()" 
                                />
                            </template>
                        </td>
                    </tr>

                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                            No varieties found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- pagination -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Showing
                <strong>{{ (varieties.current_page - 1) * varieties.per_page + 1 }}</strong>–<strong>{{ Math.min(varieties.current_page * varieties.per_page, varieties.total) }}</strong>
                of <strong>{{ varieties.total }}</strong> varieties
            </span>

            <div class="flex items-center gap-1.5">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', varieties.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-2 font-medium text-foreground">
                    {{ varieties.current_page }} / {{ varieties.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', varieties.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>