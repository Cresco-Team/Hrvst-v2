<script setup lang="ts">
import {
    type ColumnDef,
    FlexRender,
    getCoreRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import {
    ClipboardList,
    ClipboardPenLine,
    ClipboardX,
    Leaf,
    Search,
} from 'lucide-vue-next'
import { ref } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import type { VegetableTableRow } from '@/types/resources/product'

const props = defineProps<{
    vegetables: VegetableTableRow[]
    searchQuery?: string
}>()

const emit = defineEmits<{
    'open-edit-vegetable': [row: VegetableTableRow]
    'open-delete-vegetable': [row: VegetableTableRow]
    'open-vegetable-details': [row: VegetableTableRow]
    search: [query: string]
}>()

const columns: ColumnDef<VegetableTableRow>[] = [
    { id: 'name', header: 'Name', enableSorting: false },
    { id: 'meta', header: 'Category', enableSorting: false },
    { id: 'activity', header: '', enableSorting: false },
    { id: 'actions', header: '', enableSorting: false },
]

const table = useVueTable({
    get data() {
        return props.vegetables
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
    manualFiltering: true,
})

const localSearch = ref(props.searchQuery ?? '')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput(): void {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => emit('search', localSearch.value), 300)
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <InputGroup class="max-w-xs">
                <InputGroupInput
                    v-model="localSearch"
                    placeholder="Search vegetables or varieties…"
                    @input="handleSearchInput"
                />
                <InputGroupAddon>
                    <Search class="size-4" />
                </InputGroupAddon>
            </InputGroup>
            <slot name="toolbar-actions" />
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-3 py-3 text-left text-xs font-medium text-muted-foreground"
                        >
                            <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-muted-foreground">
                            No vegetables found.
                        </td>
                    </tr>

                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="border-t font-medium transition-colors hover:bg-muted/30"
                    >
                        <td class="px-3 py-2.5">
                            <div class="flex items-center gap-2">
                                <Avatar class="size-8 shrink-0 rounded-md">
                                    <AvatarImage
                                        v-if="row.original.image_url"
                                        :src="row.original.image_url"
                                        :alt="row.original.name"
                                        class="object-cover"
                                    />
                                    <AvatarFallback class="rounded-md bg-primary/10 text-xs font-semibold text-primary">
                                        <Leaf class="size-4" />
                                    </AvatarFallback>
                                </Avatar>
                                <div class="flex flex-col">
                                    <span class="font-semibold">{{ row.original.vegetable_name }}</span>
                                    <span v-if="row.original.variety_name" class="text-xs font-normal text-muted-foreground">
                                        {{ row.original.variety_name }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <td class="px-3 py-2.5">
                            <Badge variant="secondary">{{ row.original.category?.name }}</Badge>
                        </td>

                        <td class="px-3 py-2.5">
                            <div class="flex flex-col gap-1">
                                <div class="flex w-fit items-center gap-1.5 text-xs">
                                    <span class="size-1.5 rounded-full bg-green-500" />
                                    <span class="font-medium tabular-nums">{{ row.original.supply_count ?? 0 }}</span>
                                    <span class="text-muted-foreground">supplies</span>
                                </div>
                                <div class="flex w-fit items-center gap-1.5 text-xs">
                                    <span class="size-1.5 rounded-full bg-orange-500" />
                                    <span class="font-medium tabular-nums">{{ row.original.demand_count ?? 0 }}</span>
                                    <span class="text-muted-foreground">demands</span>
                                </div>
                            </div>
                        </td>

                        <td class="px-3 py-2.5 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <TooltipProvider :delay-duration="200">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-muted-foreground hover:text-foreground"
                                                @click="emit('open-vegetable-details', row.original)"
                                            >
                                                <ClipboardList class="size-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p class="text-xs">View details</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="200">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-muted-foreground hover:text-foreground"
                                                @click="emit('open-edit-vegetable', row.original)"
                                            >
                                                <ClipboardPenLine class="size-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p class="text-xs">Edit</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                                <TooltipProvider :delay-duration="200">
                                    <Tooltip>
                                        <TooltipTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="icon-sm"
                                                class="text-muted-foreground hover:text-destructive"
                                                @click="emit('open-delete-vegetable', row.original)"
                                            >
                                                <ClipboardX class="size-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent><p class="text-xs">Delete</p></TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
