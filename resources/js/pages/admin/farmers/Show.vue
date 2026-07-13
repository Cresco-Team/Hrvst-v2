<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import { ChevronDown } from '@lucide/vue'
import {
    Archive,
    Mail,
    MapPin,
    Package,
    PackageCheck,
    Phone,
} from 'lucide-vue-next'
import { computed } from 'vue'
import UserTeaser from '@/components/features/admin/charts/UserTeaser.vue'
import LeafletMap from '@/components/LeafletMap.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemGroup,
    ItemHeader,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import type { BreadcrumbItem, FarmerResource } from '@/types'

const props = defineProps<{
    farmer?: FarmerResource
}>()

const ongoingItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'ongoing') ?? [],
)
const archivedItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'expired') ?? [],
)
const fulfilledItems = computed<App.Data.PostItem.PostItemData[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'fulfilled') ??
        [],
)
const totalQuantity = computed(
    () =>
        props.farmer?.supply_items?.reduce(
            (sum, i) => sum + i.quantity_kg,
            0,
        ) ?? 0,
)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Farmers', href: admin.farmers.index().url },
    ...(props.farmer
        ? [
              {
                  title: props.farmer.user?.name ?? 'Farmer',
                  href: admin.farmers.show(props.farmer.id).url,
              },
          ]
        : []),
])
</script>

<template>
    <Head :title="farmer?.user?.name ?? 'Farmer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">

            <Deferred data="farmer">
                <template #fallback>
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 lg:col-span-3">
                            <Card class="space-y-5 p-5">
                                <Skeleton class="size-16 rounded-full" />
                                <Skeleton class="h-4 w-full" />
                                <Skeleton class="h-4 w-3/4" />
                            </Card>
                        </div>
                        <div class="col-span-12 space-y-4 lg:col-span-9">
                            <Skeleton class="h-52 w-full rounded-xl" />
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <Skeleton
                                    v-for="i in 4"
                                    :key="i"
                                    class="h-20 rounded-xl"
                                />
                            </div>
                            <Skeleton class="h-64 w-full rounded-xl" />
                        </div>
                    </div>
                </template>

                <div
                    v-if="farmer"
                    class="grid grid-cols-12 gap-5"
                >
                    <!-- Sidebar -->
                    <Item class="sticky top-6 col-span-12 lg:col-span-3">
                        <ItemHeader>
                            <Avatar class="size-16">
                                <AvatarImage
                                    v-if="farmer.user?.avatar_url"
                                    :src="farmer.user?.avatar_url"
                                    :alt="farmer.user.name"
                                />

                                <AvatarFallback class="bg-primary/10 text0base font-semibold text-primary">
                                    {{ getInitials(farmer.user?.name) }}
                                </AvatarFallback>
                            </Avatar>
                        </ItemHeader>

                        <ItemContent>
                            <ItemTitle>{{ farmer.user?.name }}</ItemTitle>
                            <ItemDescription>
                                <div class="flex items-center gap-2">
                                    <Phone class="size-4 shrink-0" /><span>{{
                                        farmer.user?.phone_number
                                    }}</span>
                                </div>
                                <div
                                    v-if="farmer.user?.email"
                                    class="flex items-center gap-2"
                                >
                                    <Mail class="size-4 shrink-0" /><span class="truncate">{{ farmer.user?.email }}</span>
                                </div>
                            </ItemDescription>
                        </ItemContent>
                    </Item>

                    <!-- Main -->
                    <div class="col-span-12 space-y-4 lg:col-span-9">
                        <!-- Map -->
                        <Card class="gap-0 overflow-hidden py-0">
                            <CardContent class="flex items-center gap-2 border-b px-4 py-3">
                                <MapPin class="size-4 text-primary" />
                                <p class="text-sm font-medium">Location</p>
                            </CardContent>
                            <LeafletMap
                                v-if="farmer.coordinates"
                                :lat="farmer.coordinates.lat"
                                :lng="farmer.coordinates.lng"
                                :markers="[
                                    {
                                        lat: farmer.coordinates.lat,
                                        lng: farmer.coordinates.lng,
                                        popup: farmer.full_address,
                                    },
                                ]"
                            />
                        </Card>

                        <UserTeaser
                            v-if="farmer.insights"
                            :insights="farmer.insights"
                            :locked="farmer.analytics_locked"
                            :total-quantity="totalQuantity"
                            feature-label="Platform Analytics License"
                            waste-title="Most Supplied Varieties"
                            waste-description="By total kilograms supplied"
                            waste-unit-label="kg supplied"
                            waste-guide-question="What does this farmer grow most?"
                            volume-title="6-Month Supply Volume"
                        />

                        <Collapsible :default-open="false">
                            <CollapsibleTrigger class="flex w-full items-center justify-between rounded-lg border bg-muted/20 px-4 py-2.5 text-sm font-medium hover:bg-muted/40">
                                Full Post History
                                <ChevronDown class="size-4 text-muted-foreground transition-transform duration-200 data-[state=open]:rotate-180" />
                            </CollapsibleTrigger>
                            <CollapsibleContent class="pt-4">
                                <Card>
                                    <CardContent class="pt-4">
                                        <Tabs default-value="ongoing">
                                            <TabsList class="mb-4">
                                                <TabsTrigger
                                                    value="ongoing"
                                                    class="gap-1.5"
                                                >
                                                    <Package class="size-4" />Ongoing
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        ongoingItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                                <TabsTrigger
                                                    value="expired"
                                                    class="gap-1.5"
                                                >
                                                    <Archive class="size-4" />Expired
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        archivedItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                                <TabsTrigger
                                                    value="fulfilled"
                                                    class="gap-1.5"
                                                >
                                                    <PackageCheck class="size-4"/>Fulfilled
                                                    <Badge
                                                        variant="secondary"
                                                        class="ml-1 px-1.5 py-0 text-xs"
                                                    >{{
                                                        fulfilledItems.length
                                                    }}</Badge>
                                                </TabsTrigger>
                                            </TabsList>

                                            <template
                                                v-for="(items, tab) in {
                                                    ongoing: ongoingItems,
                                                    expired: archivedItems,
                                                    fulfilled: fulfilledItems,
                                                }"
                                                :key="tab"
                                            >
                                                <TabsContent :value="tab">
                                                    <div
                                                        v-if="items.length === 0"
                                                        class="flex h-24 items-center justify-center text-sm text-muted-foreground"
                                                    >
                                                        No {{ tab }} items
                                                    </div>
                                                    <ItemGroup
                                                        v-else
                                                        class="grid grid-cols-1 gap-3 sm:grid-cols-2"
                                                    >
                                                        <Item
                                                            v-for="item in items"
                                                            :key="item.id"
                                                            variant="outline"
                                                        >
                                                            <ItemMedia variant="image">
                                                                <img
                                                                    :src="item.image_url"
                                                                    :alt="item.display_name"
                                                                />
                                                            </ItemMedia>
                                                            <ItemContent class="min-w-0">
                                                                <ItemTitle class="line-clamp-1 text-sm">
                                                                    {{ item.display_name }}
                                                                </ItemTitle>
                                                                <ItemDescription class="mt-0.5 flex items-center gap-1.5">
                                                                    <span class="text-sm font-medium text-foreground">
                                                                        {{ item.scheduled_date }}
                                                                    </span>
                                                                </ItemDescription>
                                                            </ItemContent>

                                                            <ItemActions>
                                                                <span class="font-mono">{{ item.quantity_kg.toLocaleString() }} </span>kg
                                                            </ItemActions>
                                                        </Item>
                                                    </ItemGroup>
                                                </TabsContent>
                                            </template>
                                        </Tabs>
                                    </CardContent>
                                </Card>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>
