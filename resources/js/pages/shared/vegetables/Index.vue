<script setup lang="ts">
import { Deferred, Head, Link, router, usePage } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import {
    InputGroup,
    InputGroupAddon,
    InputGroupInput,
} from '@/components/ui/input-group'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { categories, dashboard } from '@/routes'
import { index, show } from '@/routes/vegetables'
import type { BreadcrumbItem, SharedCategoryProps } from '@/types'
import type { Paginated } from '@/types/index'
import type { VegetableResource } from '@/types/resources/product'
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import VegetableItem from '@/components/shared/cards/VegetableItem.vue'

interface VegetablesFilters {
    search: string | null
}

interface Props {
    category: SharedCategoryProps
    filters: VegetablesFilters
    vegetables?: Paginated<VegetableResource>
}

const props = defineProps<Props>()

// ─── State ────────────────────────────────────────────────────────────────────
const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: useCapitalize(usePage().props.auth.user.roles[0]),
        href: dashboard().url,
    },
    { title: 'Vegetables', href: categories().url },
    {
        title: props.category.name,
        href: index({ query: { category: props.category.slug } }).url,
    },
]

// ─── Handlers ─────────────────────────────────────────────────────────────────
function handleSearch() {
    if (searchDebounce) clearTimeout(searchDebounce)

    searchDebounce = setTimeout(() => {
        router.visit(
            index({
                query: {
                    category: props.category.slug,
                    search: searchQuery.value || undefined,
                },
            }).url,
            {
                preserveState: true,
                preserveScroll: true,
                only: ['vegetables'],
            },
        )
    }, 300)
}

function handlePageChange(page: number) {
    router.visit(
        index({
            query: {
                category: props.category.slug,
                search: searchQuery.value || undefined,
                page,
            },
        }).url,
        {
            preserveScroll: true,
            only: ['vegetables'],
        },
    )
}
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Vegetables"
                description="Browse all available vegetables and varieties."
            />

            <div class="flex flex-wrap gap-3">
                <InputGroup class="w-full sm:max-w-xs">
                    <InputGroupAddon>
                        <Search class="size-4 text-muted-foreground" />
                    </InputGroupAddon>
                    <InputGroupInput
                        v-model="searchQuery"
                        placeholder="Search vegetables or varieties..."
                        @input="handleSearch"
                    />
                    <InputGroupAddon align="inline-end">
                        {{ vegetables?.total ?? 0 }} results
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <Deferred data="vegetables">
                <template #fallback>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <Skeleton v-for="i in 8" :key="i" class="aspect-3/4 rounded-xl" />
                    </div>
                </template>

                <EmptyState
                    v-if="!vegetables?.data.length"
                    title="No vegetables found"
                    description="Try adjusting your search or category filter."
                />

                <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <VegetableItem
                        v-for="vegetable in vegetables.data"
                        :key="vegetable.id"
                        :vegetable="vegetable"
                    />
                </div>
            </Deferred>

            <div
                v-if="vegetables && vegetables.last_page > 1"
                class="flex items-center justify-between border-t pt-4"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="vegetables.current_page === 1"
                    @click="handlePageChange(vegetables.current_page - 1)"
                >
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ vegetables.current_page }} of {{ vegetables.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="vegetables.current_page === vegetables.last_page"
                    @click="handlePageChange(vegetables.current_page + 1)"
                >
                    Next
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
