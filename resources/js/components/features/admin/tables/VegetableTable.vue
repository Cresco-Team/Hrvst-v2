<script setup lang="ts">
import {
    type ColumnDef,
    type ExpandedState,
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    type Updater,
    useVueTable,
} from '@tanstack/vue-table'
import {
    ChevronDown,
    ChevronRight,
    ClipboardList,
    ClipboardPenLine,
    ClipboardPlus,
    ClipboardX,
    Clock,
    Leaf,
    Minus,
    Plus,
    Search,
    TrendingDown,
    TrendingUp,
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
import type { VarietyTableRow } from '@/types/resources/product'
import AppTooltip from '@/components/templates/AppTooltip.vue'

// ── Props & emits ──────────────────────────────────────────────────────────────

const props = defineProps<{
    vegetables: VarietyTableRow[]
    searchQuery?: string
}>()

const emit = defineEmits<{
    'open-edit-vegetable': [row: VarietyTableRow]
    'open-delete-vegetable': [row: VarietyTableRow]
    'open-create-variety': [parentRow: VarietyTableRow]
    'open-edit-variety': [row: VarietyTableRow]
    'open-delete-variety': [row: VarietyTableRow]
    'open-update-price': [row: VarietyTableRow]
    'open-variety-details': [row: VarietyTableRow]
    search: [query: string]
}>()

// ── Expand state ───────────────────────────────────────────────────────────────

const expanded = ref<ExpandedState>({})

// ── Column defs ────────────────────────────────────────────────────────────────

const columns: ColumnDef<VarietyTableRow>[] = [
    { id: 'expand', header: '', size: 32 },
    { id: 'name', header: 'Name', enableSorting: false },
    { id: 'meta', header: 'Category / Price', enableSorting: false },
    { id: 'activity', header: 'Activity', enableSorting: false },
    { id: 'actions', header: '', enableSorting: false },
]

// ── Table instance ─────────────────────────────────────────────────────────────

const table = useVueTable({
    get data() {
        return props.vegetables
    },
    columns,
    state: {
        get expanded() {
            return expanded.value
        },
    },
    onExpandedChange: (updaterOrValue: Updater<ExpandedState>) => {
        expanded.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(expanded.value)
                : updaterOrValue
    },
    getSubRows: (row) => row.varieties ?? [],
    getCoreRowModel: getCoreRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    manualPagination: true,
    manualFiltering: true,
})

// ── Search debounce ────────────────────────────────────────────────────────────

const localSearch = ref(props.searchQuery ?? '')
let searchTimeout: ReturnType<typeof setTimeout> | null = null

function handleSearchInput(): void {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => emit('search', localSearch.value), 300)
}

// ── Freshness config ───────────────────────────────────────────────────────────

const freshnessClass: Record<string, string> = {
    recent: 'bg-amber-400',
    stable: 'bg-green-400',
    'very stable': 'bg-sky-500',
    stale: 'bg-gray-500',
}

// ── Trend icon ─────────────────────────────────────────────────────────────────

const trendIcon = {
    up: TrendingUp,
    down: TrendingDown,
    flat: Minus,
} as const
</script>

<template>
    <div class="flex flex-col gap-4">
        <!-- Toolbar -->
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

        <!-- Table -->
        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/60">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            class="px-3 py-3 text-left text-xs font-medium text-muted-foreground"
                        >
                            <FlexRender
                                :render="header.column.columnDef.header"
                                :props="header.getContext()"
                            />
                        </th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="table.getRowModel().rows.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-10 text-center text-sm text-muted-foreground"
                        >
                            No vegetables found.
                        </td>
                    </tr>

                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        class="border-t transition-colors"
                        :class="
                            row.depth === 0
                                ? 'font-medium hover:bg-muted/30'
                                : 'bg-green-50/60 hover:bg-green-100/60 dark:bg-green-950/20 dark:hover:bg-green-950/40'
                        "
                    >
                        <!-- expand toggle -->
                        <td class="w-8 px-2 py-2">
                            <template v-if="row.depth === 0">
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-muted-foreground"
                                    @click="row.toggleExpanded()"
                                >
                                    <ChevronDown
                                        v-if="row.getIsExpanded()"
                                        class="size-4"
                                    />
                                    <ChevronRight v-else class="size-4" />
                                </Button>
                            </template>
                        </td>

                        <!-- name column -->
                        <td class="px-3 py-2.5">
                            <!-- vegetable parent row -->
                            <template v-if="row.depth === 0">
                                <div class="flex items-center gap-2">
                                    <Avatar class="size-8 shrink-0 rounded-md">
                                        <AvatarImage
                                            v-if="row.original.image_url"
                                            :src="row.original.image_url"
                                            :alt="row.original.name"
                                            class="object-cover"
                                        />
                                        <AvatarFallback
                                            class="rounded-md bg-primary/10 text-xs font-semibold text-primary"
                                        >
                                            <Leaf class="size-4" />
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="font-semibold">{{
                                        row.original.name
                                    }}</span>
                                    <AppTooltip content="Variety">
                                        <Badge class="cursor-help">
                                            {{
                                                row.original.varieties
                                                    ?.length ?? 0
                                            }}
                                        </Badge>
                                    </AppTooltip>
                                </div>
                            </template>

                            <!-- variety child row -->
                            <template v-else>
                                <div class="flex items-center gap-2 pl-6">
                                    <Avatar class="size-8 shrink-0 rounded-md">
                                        <AvatarImage
                                            v-if="row.original.image_url"
                                            :src="row.original.image_url"
                                            :alt="row.original.name"
                                            class="object-cover"
                                        />
                                        <AvatarFallback
                                            class="rounded-md bg-primary/10 text-xs font-semibold text-primary"
                                        >
                                            {{ row.original.name.charAt(0) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="font-medium">{{
                                        row.original.name
                                    }}</span>
                                </div>
                            </template>
                        </td>

                        <!-- meta column: category (parent) / price (child) -->
                        <td class="px-3 py-2.5">
                            <template v-if="row.depth === 0">
                                <Badge variant="secondary">{{
                                    row.original.category?.name
                                }}</Badge>
                            </template>
                            <template v-else>
                                <div
                                    v-if="row.original.latest_price"
                                    class="flex flex-col gap-1"
                                >
                                    <div class="flex items-center gap-2">
                                        <component
                                            :is="
                                                row.original.price_trend
                                                    ? trendIcon[
                                                          row.original
                                                              .price_trend
                                                      ]
                                                    : Minus
                                            "
                                            class="size-3.5 shrink-0 text-muted-foreground"
                                        />
                                        <Badge
                                            variant="secondary"
                                            class="font-mono text-xs"
                                        >
                                            ₱{{
                                                row.original.latest_price.price_min.toFixed(
                                                    2,
                                                )
                                            }}
                                            – ₱{{
                                                row.original.latest_price.price_max.toFixed(
                                                    2,
                                                )
                                            }}
                                        </Badge>
                                        <TooltipProvider :delay-duration="200">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <div
                                                        class="size-2 shrink-0 cursor-help rounded-full"
                                                        :class="
                                                            freshnessClass[
                                                                row.original
                                                                    .latest_price
                                                                    .freshness ??
                                                                    ''
                                                            ] ?? 'bg-gray-400'
                                                        "
                                                    />
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p
                                                        class="text-xs capitalize"
                                                    >
                                                        {{
                                                            row.original
                                                                .latest_price
                                                                .freshness
                                                        }}
                                                    </p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                    <div
                                        v-if="row.original.price_updated_human"
                                        class="flex items-center gap-1 text-xs text-muted-foreground"
                                    >
                                        <Clock class="size-3 shrink-0" />
                                        {{ row.original.price_updated_human }}
                                    </div>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >No price data</span
                                >
                            </template>
                        </td>

                        <!-- activity column -->
                        <td class="px-3 py-2.5">
                            <template v-if="row.depth === 1">
                                <div class="flex flex-col gap-1">
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <div
                                                    class="flex w-fit cursor-help items-center gap-1.5 text-xs"
                                                >
                                                    <span
                                                        class="size-1.5 rounded-full bg-green-500"
                                                    />
                                                    <span
                                                        class="font-medium tabular-nums"
                                                        >{{
                                                            row.original
                                                                .supply_count ??
                                                            0
                                                        }}</span
                                                    >
                                                    <span
                                                        class="text-muted-foreground"
                                                        >supplies</span
                                                    >
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Ongoing farmer supply
                                                    listings
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <div
                                                    class="flex w-fit cursor-help items-center gap-1.5 text-xs"
                                                >
                                                    <span
                                                        class="size-1.5 rounded-full bg-blue-500"
                                                    />
                                                    <span
                                                        class="font-medium tabular-nums"
                                                        >{{
                                                            row.original
                                                                .demand_count ??
                                                            0
                                                        }}</span
                                                    >
                                                    <span
                                                        class="text-muted-foreground"
                                                        >demands</span
                                                    >
                                                </div>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Ongoing dealer demand
                                                    listings
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </template>
                        </td>

                        <!-- actions column -->
                        <td class="px-3 py-2.5 text-right">
                            <!-- vegetable parent actions -->
                            <template v-if="row.depth === 0">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-primary"
                                                    @click="
                                                        emit(
                                                            'open-create-variety',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <Plus class="size-4" />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Add variety
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-foreground"
                                                    @click="
                                                        emit(
                                                            'open-edit-vegetable',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardPenLine
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Edit vegetable
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-destructive"
                                                    @click="
                                                        emit(
                                                            'open-delete-vegetable',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardX
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Delete vegetable
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </template>

                            <!-- variety child actions -->
                            <template v-else>
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-foreground"
                                                    @click="
                                                        emit(
                                                            'open-variety-details',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardList
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    View details
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-primary"
                                                    @click="
                                                        emit(
                                                            'open-update-price',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardPlus
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Update price
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-foreground"
                                                    @click="
                                                        emit(
                                                            'open-edit-variety',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardPenLine
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Edit variety
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                    <TooltipProvider :delay-duration="200">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    class="text-muted-foreground hover:text-destructive"
                                                    @click="
                                                        emit(
                                                            'open-delete-variety',
                                                            row.original,
                                                        )
                                                    "
                                                >
                                                    <ClipboardX
                                                        class="size-4"
                                                    />
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent
                                                ><p class="text-xs">
                                                    Delete variety
                                                </p></TooltipContent
                                            >
                                        </Tooltip>
                                    </TooltipProvider>
                                </div>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
