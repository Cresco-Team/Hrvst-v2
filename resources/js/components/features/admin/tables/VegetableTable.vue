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
import {
    ChevronLeft,
    ChevronRight,
    ChevronsUpDown,
    ChevronUp,
    ChevronDown,
    Pencil,
    Trash2,
    Plus,
} from 'lucide-vue-next'

/* -- types -- */
interface Vegetable {
    id: number
    category_id: number
    name: string
    varieties_count: number
    category: { id: number, name: string }
}

interface PaginatedData {
    data: Vegetable[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

/* -- props / emits -- */
const props = defineProps<{
    vegetables: PaginatedData
}>()

defineEmits<{
    'open-create': []
    'open-edit': [vegetable: Vegetable]
    'open-delete': [vegetable: Vegetable]
    'page-change': [page: number]
}>()

/* -- local state -- */
const globalFilter = ref('')

/* -- column definitions -- */
const columns: ColumnDef<Vegetable>[] = [
    {
        accessorKey: 'name',
        header: 'Vegetable',
        enableSorting: true,
    },
    {
        id: 'category_name',
        accessorKey: 'category.name',
        header: 'Category',
        cell: ({ row }) => row.original.category?.name ?? '—',
        enableSorting: true,
    },
    {
        accessorKey: 'varieties_count',
        header: 'Varieties',
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
        return props.vegetables.data
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
const hasPrevPage = computed(() => props.vegetables.current_page > 1)
const hasNextPage = computed(() => props.vegetables.current_page < props.vegetables.last_page)

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
                placeholder="Search vegetables…"
                class="max-w-xs"
            />
            <Button @click="$emit('open-create')" class="gap-1.5">
                <Plus class="size-4" />
                Add Vegetable
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
                            <template v-if="cell.column.id === 'actions'">
                                <div class="flex items-center gap-1.5">
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-muted-foreground hover:text-foreground"
                                        @click="$emit('open-edit', row.original)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon-sm"
                                        class="text-muted-foreground hover:text-destructive"
                                        @click="$emit('open-delete', row.original)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </template>

                            <template v-else-if="cell.column.id === 'varieties_count'">
                                <Badge variant="secondary">
                                    {{ cell.getValue() }}
                                </Badge>
                            </template>

                            <template v-else-if="cell.column.id === 'category_name'">
                                <Badge variant="outline">
                                    {{ cell.getValue() }}
                                </Badge>
                            </template>

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
                            No vegetables found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- pagination -->
        <div class="flex items-center justify-between text-sm text-muted-foreground">
            <span>
                Showing
                <strong>{{ (vegetables.current_page - 1) * vegetables.per_page + 1 }}</strong>–<strong>{{ Math.min(vegetables.current_page * vegetables.per_page, vegetables.total) }}</strong>
                of <strong>{{ vegetables.total }}</strong> vegetables
            </span>

            <div class="flex items-center gap-1.5">
                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasPrevPage"
                    @click="$emit('page-change', vegetables.current_page - 1)"
                >
                    <ChevronLeft class="size-4" />
                </Button>

                <span class="px-2 font-medium text-foreground">
                    {{ vegetables.current_page }} / {{ vegetables.last_page }}
                </span>

                <Button
                    variant="outline"
                    size="icon-sm"
                    :disabled="!hasNextPage"
                    @click="$emit('page-change', vegetables.current_page + 1)"
                >
                    <ChevronRight class="size-4" />
                </Button>
            </div>
        </div>
    </div>
</template>