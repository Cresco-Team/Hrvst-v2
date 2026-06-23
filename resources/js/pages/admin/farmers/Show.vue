<script setup lang="ts">
import { Deferred, Head } from '@inertiajs/vue3'
import {
    Archive,
    CalendarDays,
    Mail,
    MapPin,
    Package,
    PackageCheck,
    Phone,
    Sprout,
} from 'lucide-vue-next'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import LeafletMap from '@/components/LeafletMap.vue'
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
import type { BreadcrumbItem, PostItemSnapshot, FarmerResource } from '@/types'

const props = defineProps<{
    farmer?: FarmerResource
}>()

const ongoingItems = computed<PostItemSnapshot[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'ongoing') ?? [],
)
const archivedItems = computed<PostItemSnapshot[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'unsettled') ??
        [],
)
const fulfilledItems = computed<PostItemSnapshot[]>(
    () =>
        props.farmer?.supply_items?.filter((i) => i.status === 'fulfilled') ??
        [],
)
const totalItems = computed(() => props.farmer?.supply_items?.length ?? 0)
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
            <Heading
                :title="farmer?.user?.name ?? 'Farmer'"
                description="Farmer profile and supply history"
            />

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

                <div v-if="farmer" class="grid grid-cols-12 gap-5">
                    <!-- Sidebar -->
                    <div class="col-span-12 lg:col-span-3">
                        <Card class="space-y-5 p-5">
                            <div class="flex flex-col items-start gap-3">
                                <Avatar class="size-16">
                                    <AvatarImage
                                        v-if="farmer.user?.avatar_url"
                                        :src="farmer.user.avatar_url"
                                        :alt="farmer.user.name"
                                    />
                                    <AvatarFallback
                                        class="bg-primary/10 text-base font-semibold text-primary"
                                    >
                                        {{ getInitials(farmer.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                                <div>
                                    <p
                                        class="text-sm leading-snug font-semibold"
                                    >
                                        {{ farmer.user?.name }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ farmer.location?.full_address }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="space-y-2.5 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-2">
                                    <Mail class="size-4 shrink-0" /><span
                                        class="truncate"
                                        >{{ farmer.user?.email }}</span
                                    >
                                </div>
                                <div class="flex items-center gap-2">
                                    <Phone class="size-4 shrink-0" /><span>{{
                                        farmer.user?.phone_number
                                    }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CalendarDays
                                        class="size-4 shrink-0"
                                    /><span
                                        >Joined
                                        {{ farmer.joined_at_human }}</span
                                    >
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Main -->
                    <div class="col-span-12 space-y-4 lg:col-span-9">
                        <!-- Map -->
                        <Card class="gap-0 overflow-hidden py-0">
                            <CardContent
                                class="flex items-center gap-2 border-b px-4 py-3"
                            >
                                <MapPin class="size-4 text-primary" />
                                <p class="text-sm font-medium">Location</p>
                            </CardContent>
                            <LeafletMap
                                v-if="farmer.location"
                                :lat="farmer.location.coordinates.lat"
                                :lng="farmer.location.coordinates.lng"
                                :markers="[
                                    {
                                        lat: farmer.location.coordinates.lat,
                                        lng: farmer.location.coordinates.lng,
                                        popup: farmer.location.full_address,
                                    },
                                ]"
                            />
                        </Card>

                        <!-- Stats -->
                        <div class="hidden gap-3 sm:grid sm:grid-cols-3">
                            <SmallCard
                                title="Total Items"
                                :value="totalItems"
                            />
                            <SmallCard
                                title="Total Quantity"
                                :value="totalQuantity"
                                subtext="kg"
                            />
                            <SmallCard
                                title="Ongoing Items"
                                :value="ongoingItems.length"
                            />
                        </div>

                        <!-- Supply Items (PostItem-level tabs) -->
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
                                                }}</Badge
                                            >
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="unsettled"
                                            class="gap-1.5"
                                        >
                                            <Archive class="size-4" />Unsettled
                                            <Badge
                                                variant="secondary"
                                                class="ml-1 px-1.5 py-0 text-xs"
                                                >{{
                                                    archivedItems.length
                                                }}</Badge
                                            >
                                        </TabsTrigger>
                                        <TabsTrigger
                                            value="fulfilled"
                                            class="gap-1.5"
                                        >
                                            <PackageCheck
                                                class="size-4"
                                            />Fulfilled
                                            <Badge
                                                variant="secondary"
                                                class="ml-1 px-1.5 py-0 text-xs"
                                                >{{
                                                    fulfilledItems.length
                                                }}</Badge
                                            >
                                        </TabsTrigger>
                                    </TabsList>

                                    <template
                                        v-for="(items, tab) in {
                                            ongoing: ongoingItems,
                                            unsettled: archivedItems,
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
                                                            :src="
                                                                item.image_url
                                                            "
                                                            :alt="item.name"
                                                        />
                                                    </ItemMedia>
                                                    <ItemContent
                                                        class="min-w-0"
                                                    >
                                                        <ItemTitle
                                                            class="line-clamp-1 text-sm"
                                                        >
                                                            {{ item.name }}
                                                        </ItemTitle>
                                                        <ItemDescription
                                                            class="mt-0.5 flex items-center gap-1.5"
                                                        >
                                                            <span
                                                                class="font-mono text-sm font-medium text-foreground"
                                                            >
                                                                {{
                                                                    item.quantity_kg.toFixed(
                                                                        2,
                                                                    )
                                                                }}
                                                                kg
                                                            </span>
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
