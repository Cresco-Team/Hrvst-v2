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
    Item, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle,
} from '@/components/ui/item'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem, DealerDemandResource, DealerResource, PostPriceFlag } from '@/types'

const props = defineProps<{
    dealer?: DealerResource
}>()

const ongoingDemands = computed<DealerDemandResource[]>(
    () => props.dealer?.demands?.filter((d) => d.status === 'Ongoing') ?? [],
)
const archivedDemands = computed<DealerDemandResource[]>(
    () => props.dealer?.demands?.filter((d) => d.status === 'Archived') ?? [],
)
const fulfilledDemands = computed<DealerDemandResource[]>(
    () => props.dealer?.demands?.filter((d) => d.status === 'Fulfilled') ?? [],
)

const totalDemands = computed(() => props.dealer?.demands?.length ?? 0)
const totalQuantity = computed(
    () => props.dealer?.demands?.reduce((sum, d) => sum + d.quantity_kg, 0) ?? 0,
)
const totalOngoing = computed(() => ongoingDemands.value.length)
const totalOngoingQuantity = computed(() =>
    ongoingDemands.value.reduce((sum, d) => sum + d.quantity_kg, 0),
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Dealers', href: admin.dealers.index().url },
    ...(props.dealer
        ? [{
            title: props.dealer.user?.name ?? 'Dealer',
            href: admin.dealers.show(props.dealer.id).url,
        }]
        : []),
])

function priceFlagVariant(flag: PostPriceFlag | undefined) {
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
                    <div />
                </template>

                <div v-if="dealer" class="grid grid-cols-12 gap-5">
                    <div class="col-span-12 lg:col-span-3 space-y-4">
                        <Card class="p-5 space-y-5">
                            <div class="flex flex-col items-start gap-3">
                                <Avatar class="size-16">
                                    <AvatarImage v-if="dealer.user?.avatar_url" :src="dealer.user.avatar_url"
                                        :alt="dealer.user.name" />
                                    <AvatarFallback class="bg-primary/10 text-base font-semibold text-primary">
                                        {{ getInitials(dealer.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <div>
                                    <p class="text-sm font-semibold leading-snug">{{ dealer.user?.name }}</p>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-sm text-muted-foreground">
                                <div class="flex items-center gap-2">
                                    <Mail class="size-4 shrink-0" />
                                    <span class="truncate">{{ dealer.user?.email }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Phone class="size-4 shrink-0" />
                                    <span>{{ dealer.user?.phone_number }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CalendarDays class="size-4 shrink-0" />
                                    <span>Joined {{ dealer.joined_at_human }}</span>
                                </div>
                            </div>
                        </Card>

                        <Card v-if="dealer.document_url" class="p-0 aspect-video overflow-hidden">
                            <img :src="dealer.document_url" alt="Business document" class="size-full object-cover" />
                        </Card>
                    </div>

                    <!-- Main Content -->
                    <div class="col-span-12 lg:col-span-9 space-y-4">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <SmallCard title="Total Demands" :value="totalDemands" />
                            <SmallCard title="Total Quantity" :value="totalQuantity" subtext="kg" />
                            <SmallCard title="Ongoing Demands" :value="totalOngoing" />
                            <SmallCard title="Ongoing Quantity" :value="totalOngoingQuantity" subtext="kg" />
                        </div>

                        <Card>
                            <CardContent class="pt-4">
                                <Tabs default-value="ongoing">
                                    <TabsList class="mb-4">
                                        <TabsTrigger value="ongoing" class="gap-1.5">
                                            <Package class="size-4" />
                                            Ongoing
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ ongoingDemands.length }}
                                            </Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="archived" class="gap-1.5">
                                            <Archive class="size-4" />
                                            Archived
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ archivedDemands.length }}
                                            </Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="fulfilled" class="gap-1.5">
                                            <PackageCheck class="size-4" />
                                            Fulfilled
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ fulfilledDemands.length }}
                                            </Badge>
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent value="ongoing">
                                        <div v-if="ongoingDemands.length === 0"
                                            class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No ongoing demands
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="demand in ongoingDemands" :key="demand.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="demand.variety?.image_url" :alt="demand.variety?.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">
                                                        {{ demand.variety?.name }} — {{ demand.variety?.category }}
                                                    </ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">
                                                            ₱{{ demand.offered_price.toFixed(2) }}
                                                        </span>
                                                        <Badge :variant="priceFlagVariant(demand.price_flag)"
                                                            class="text-xs px-1.5 py-0">
                                                            {{ demand.price_flag }}
                                                        </Badge>
                                                    </ItemDescription>
                                                </ItemContent>
                                            </Item>
                                        </ItemGroup>
                                    </TabsContent>

                                    <TabsContent value="archived">
                                        <div v-if="archivedDemands.length === 0"
                                            class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No archived demands
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="demand in archivedDemands" :key="demand.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="demand.variety?.image_url" :alt="demand.variety?.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">
                                                        {{ demand.variety?.name }} — {{ demand.variety?.category }}
                                                    </ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">
                                                            ₱{{ demand.offered_price.toFixed(2) }}
                                                        </span>
                                                        <Badge :variant="priceFlagVariant(demand.price_flag)"
                                                            class="text-xs px-1.5 py-0">
                                                            {{ demand.price_flag }}
                                                        </Badge>
                                                    </ItemDescription>
                                                </ItemContent>
                                            </Item>
                                        </ItemGroup>
                                    </TabsContent>

                                    <TabsContent value="fulfilled">
                                        <div v-if="fulfilledDemands.length === 0"
                                            class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No fulfilled demands
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="demand in fulfilledDemands" :key="demand.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="demand.variety?.image_url" :alt="demand.variety?.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">
                                                        {{ demand.variety?.name }} — {{ demand.variety?.category }}
                                                    </ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">
                                                            ₱{{ demand.offered_price.toFixed(2) }}
                                                        </span>
                                                        <Badge :variant="priceFlagVariant(demand.price_flag)"
                                                            class="text-xs px-1.5 py-0">
                                                            {{ demand.price_flag }}
                                                        </Badge>
                                                    </ItemDescription>
                                                </ItemContent>
                                            </Item>
                                        </ItemGroup>
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>
