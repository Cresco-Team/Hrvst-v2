<script setup lang="ts">
import { Deferred, Head, router, usePage } from '@inertiajs/vue3'
import { Search } from 'lucide-vue-next'
import type { AcceptableValue } from 'reka-ui'
import { computed, ref } from 'vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import VegetableCard from '@/components/shared/cards/VegetableCard.vue'
import { Button } from '@/components/ui/button'
import {
	Carousel,
	CarouselContent,
	CarouselItem,
	CarouselNext,
	CarouselPrevious,
} from '@/components/ui/carousel'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import categories from '@/routes/categories'
import dealer from '@/routes/dealer'
import farmer from '@/routes/farmer'
import type { BreadcrumbItem, SharedCategoryProps } from '@/types'
import type { Paginated } from '@/types/index'
import type { VegetableResource } from '@/types/resources/product'

interface VegetablesFilters {
	search: string | null
}

interface Props {
	category: SharedCategoryProps
	filters: VegetablesFilters
	vegetables?: Paginated<VegetableResource> // Inertia::defer — varieties eager-loaded
}

const props = defineProps<Props>()

// ─── State ────────────────────────────────────────────────────────────────────
const searchQuery = ref(props.filters.search ?? '')
let searchDebounce: ReturnType<typeof setTimeout> | null = null

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────
const page = usePage()
const isFarmer = page.props.auth.user.roles.includes('farmer')

const backHref = isFarmer ? farmer.supplies.index().url : dealer.demands.index().url
const indexHref = categories.vegetables.index

const breadcrumbs: BreadcrumbItem[] = [
	{ title: isFarmer ? 'Farmer' : 'Dealer', href: backHref },
	{ title: 'Vegetables', href: categories.vegetables.index({ category: props.category.slug }).url },
]

// ─── Derived ─────────────────────────────────────────────────────────────────

// Only render vegetable groups that actually have varieties.
const vegetableGroups = computed(() =>
	(props.vegetables?.data ?? []).filter((veg) => (veg.varieties?.length ?? 0) > 0),
)

// Flat variety count across all visible groups.
const totalVarieties = computed(() =>
	vegetableGroups.value.reduce((sum, veg) => sum + (veg.varieties?.length ?? 0), 0),
)

// ─── Handlers ─────────────────────────────────────────────────────────────────
function handleSearch() {
	if (searchDebounce) clearTimeout(searchDebounce)

	searchDebounce = setTimeout(() => {
		router.visit(categories.vegetables.index({ category: props.category }), {
			data: {
				search: searchQuery.value || undefined,
			},
			preserveState: true,
			preserveScroll: true,
			only: ['vegetables'],
		})
	}, 300)
}

function handlePageChange(page: number) {
	router.visit(categories.vegetables.index({ category: props.category }), {
		data: {
			page,
			search: props.filters.search || undefined,
		},
		preserveScroll: true,
	})
}
</script>

<template>
    <Head title="Vegetables" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-6 p-4 lg:p-6">

            <!-- Header -->
            <Heading title="Vegetables"
                description="Browse all available vegetables and their current market prices." />

            <!-- Filters -->
            <div class="flex flex-wrap gap-3">
                <InputGroup class="w-full sm:max-w-xs">
                    <InputGroupAddon>
                        <Search class="size-4 text-muted-foreground" />
                    </InputGroupAddon>
                    <InputGroupInput v-model="searchQuery" placeholder="Search vegetables..."
                        @input="handleSearch" />
                    <InputGroupAddon align="inline-end">
                        {{ totalVarieties }} results
                    </InputGroupAddon>
                </InputGroup>
            </div>

            <!-- Grouped carousels -->
            <Deferred data="vegetables">
                <template #fallback>
                    <div class="flex flex-col gap-8">
                        <div v-for="g in 2" :key="g" class="flex flex-col gap-3">
                            <Skeleton class="h-5 w-32" />
                            <!-- Mirror carousel skeleton: same basis breakpoints -->
                            <div class="flex gap-4 overflow-hidden">
                                <Skeleton
                                    v-for="i in 4" :key="i"
                                    class="aspect-3/4 shrink-0 rounded-xl
                                           basis-4/5 sm:basis-1/2 md:basis-1/3 lg:basis-1/4" />
                            </div>
                        </div>
                    </div>
                </template>

                <EmptyState v-if="!vegetableGroups.length" title="No varieties found"
                    description="Try adjusting your search or category filter." />

                <div v-else class="flex flex-col gap-8">
                    <section v-for="vegetable in vegetableGroups" :key="vegetable.id">
                        <!-- Vegetable group header -->
                        <div class="mb-3 flex items-baseline gap-2">
                            <h2 class="text-base font-semibold">{{ vegetable.name }}</h2>
                            <span class="text-xs text-muted-foreground">
                                {{ vegetable.varieties!.length }}
                                {{ vegetable.varieties!.length === 1 ? 'variety' : 'varieties' }}
                            </span>
                        </div>

                        <!--
                            Carousel per vegetable group.
                            - Mobile  : ~1 card visible  (basis-4/5)
                            - sm      : ~2 cards          (basis-1/2)
                            - md      : ~3 cards          (basis-1/3)
                            - lg+     : ~4 cards          (basis-1/4)
                            pl-4 on content + ml-4 on item keeps the prev arrow
                            from being clipped by the carousel overflow boundary.
                        -->
                        <Carousel
                            :opts="{ align: 'start', loop: false }"
                            class="relative"
                        >
                            <CarouselContent class="-ml-3">
                                <CarouselItem
                                    v-for="variety in vegetable.varieties"
                                    :key="variety.id"
                                    class="pl-3 basis-4/5 sm:basis-1/2 md:basis-1/3 lg:basis-1/4"
                                >
                                    <VegetableCard
                                        :variety="variety"
                                        :href="categories.vegetables.show({ category: props.category.slug, variety: variety.id }).url"
                                    />
                                </CarouselItem>
                            </CarouselContent>

                            <!-- Navigation arrows — hidden on touch-only widths, visible md+ -->
                            <CarouselPrevious class="hidden md:flex -left-4" />
                            <CarouselNext class="hidden md:flex -right-4" />
                        </Carousel>
                    </section>
                </div>
            </Deferred>

            <!-- Pagination -->
            <div v-if="vegetables && vegetables.meta.last_page > 1"
                class="flex items-center justify-between border-t pt-4">
                <Button variant="outline" size="sm"
                    :disabled="vegetables.meta.current_page === 1"
                    @click="handlePageChange(vegetables.meta.current_page - 1)">
                    Previous
                </Button>
                <span class="text-sm text-muted-foreground">
                    Page {{ vegetables.meta.current_page }} of {{ vegetables.meta.last_page }}
                </span>
                <Button variant="outline" size="sm"
                    :disabled="vegetables.meta.current_page === vegetables.meta.last_page"
                    @click="handlePageChange(vegetables.meta.current_page + 1)">
                    Next
                </Button>
            </div>

        </div>
    </AppLayout>
</template>
