<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import { Archive, CalendarDays, Mail, Package, PackageCheck, Phone } from 'lucide-vue-next'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import {
	Item,
	ItemContent,
	ItemDescription,
	ItemGroup,
	ItemMedia,
	ItemTitle,
} from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem, DealerPostItemResource, DealerResource } from '@/types'

const props = defineProps<{
	dealer?: DealerResource
}>()

const ongoingItems = computed<DealerPostItemResource[]>(
	() => props.dealer?.demand_items?.filter((i) => i.status === 'ongoing') ?? [],
)
const archivedItems = computed<DealerPostItemResource[]>(
	() => props.dealer?.demand_items?.filter((i) => i.status === 'unsettled') ?? [],
)
const fulfilledItems = computed<DealerPostItemResource[]>(
	() => props.dealer?.demand_items?.filter((i) => i.status === 'fulfilled') ?? [],
)

const totalItems = computed(() => props.dealer?.demand_items?.length ?? 0)
const totalQuantity = computed(
	() => props.dealer?.demand_items?.reduce((sum, i) => sum + i.quantity_kg, 0) ?? 0,
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
	{ title: 'Admin', href: admin.dashboard().url },
	{ title: 'Dealers', href: admin.dealers.index().url },
	...(props.dealer
		? [
				{
					title: props.dealer.user?.name ?? 'Dealer',
					href: admin.dealers.show(props.dealer.id).url,
				},
			]
		: []),
])

function priceFlagVariant(flag?: string | null) {
	if (flag === 'Low') return 'secondary'
	if (flag === 'High') return 'destructive'
	return 'outline'
}
</script>

<template>
    <Head :title="dealer?.user?.name ?? 'Dealer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading :title="dealer?.user?.name ?? 'Dealer'" description="Dealer profile and demand history" />

            <Deferred data="dealer">
                <template #fallback>
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 lg:col-span-3">
                            <Card class="p-5 space-y-5">
                                <Skeleton class="size-16 rounded-full" />
                                <Skeleton class="h-4 w-full" />
                                <Skeleton class="h-4 w-3/4" />
                            </Card>
                        </div>
                        <div class="col-span-12 lg:col-span-9 space-y-4">
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <Skeleton v-for="i in 4" :key="i" class="h-20 rounded-xl" />
                            </div>
                            <Skeleton class="h-64 w-full rounded-xl" />
                        </div>
                    </div>
                </template>

                <div v-if="dealer" class="grid grid-cols-12 gap-5">

                    <!-- Sidebar -->
                    <div class="col-span-12 lg:col-span-3">
                        <Card class="p-5 space-y-5">
                            <div class="flex flex-col items-start gap-3">
                                <Avatar class="size-16">
                                    <AvatarImage v-if="dealer.user?.avatar_url" :src="dealer.user.avatar_url" :alt="dealer.user.name" />
                                    <AvatarFallback class="bg-primary/10 text-base font-semibold text-primary">
                                        {{ getInitials(dealer.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p class="text-sm font-semibold leading-snug">{{ dealer.user?.name }}</p>
                                </div>
                            </div>
                            <div class="space-y-2.5 text-sm text-muted-foreground">
                                <div class="flex items-center gap-2"><Mail class="size-4 shrink-0" /><span class="truncate">{{ dealer.user?.email }}</span></div>
                                <div class="flex items-center gap-2"><Phone class="size-4 shrink-0" /><span>{{ dealer.user?.phone_number }}</span></div>
                                <div class="flex items-center gap-2"><CalendarDays class="size-4 shrink-0" /><span>Joined {{ dealer.joined_at_human }}</span></div>
                            </div>
                        </Card>
                    </div>

                    <!-- Main -->
                    <div class="col-span-12 lg:col-span-9 space-y-4">

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <SmallCard title="Total Items"    :value="totalItems" />
                            <SmallCard title="Total Quantity" :value="totalQuantity" subtext="kg" />
                            <SmallCard title="Ongoing"        :value="ongoingItems.length" />
                            <SmallCard title="Fulfilled"      :value="fulfilledItems.length" />
                        </div>

                        <Card>
                            <CardContent class="pt-4">
                                <Tabs default-value="ongoing">
                                    <TabsList class="mb-4">
                                        <TabsTrigger value="ongoing" class="gap-1.5">
                                            <Package class="size-4" />Ongoing
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">{{ ongoingItems.length }}</Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="unsettled" class="gap-1.5">
                                            <Archive class="size-4" />Unsettled
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">{{ archivedItems.length }}</Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="fulfilled" class="gap-1.5">
                                            <PackageCheck class="size-4" />Fulfilled
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">{{ fulfilledItems.length }}</Badge>
                                        </TabsTrigger>
                                    </TabsList>

                                    <template v-for="(items, tab) in { ongoing: ongoingItems, unsettled: archivedItems, fulfilled: fulfilledItems }" :key="tab">
                                        <TabsContent :value="tab">
                                            <div v-if="items.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                                No {{ tab }} items
                                            </div>
                                            <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                                <Item v-for="item in items" :key="item.id" variant="outline">
                                                    <ItemMedia variant="image">
                                                        <img :src="item.variety_image_url ?? undefined" :alt="item.variety_name" />
                                                    </ItemMedia>
                                                    <ItemContent class="min-w-0">
                                                        <ItemTitle class="line-clamp-1 text-sm">
                                                            {{ item.vegetable_name }} — {{ item.variety_name }}
                                                        </ItemTitle>
                                                        <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                            <span class="text-sm font-medium text-foreground font-mono">
                                                                {{ item.quantity_kg.toFixed(2) }} kg
                                                            </span>
                                                            <span v-if="item.unit_price" class="text-xs text-muted-foreground">
                                                                @ ₱{{ item.unit_price.toFixed(2) }}
                                                            </span>
                                                            <Badge v-if="item.price_flag" :variant="priceFlagVariant(item.price_flag)" class="text-xs px-1.5 py-0">
                                                                {{ item.price_flag }}
                                                            </Badge>
                                                        </ItemDescription>
                                                    </ItemContent>
                                                </Item>
                                            </ItemGroup>
                                        </TabsContent>
                                    </template>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>
