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
import type { FarmerSupply, ShowFarmer } from '@/types/admin/farmers'

interface Props {
	farmer: ShowFarmer | null
}

const props = defineProps<Props>()

/* ---------- Supply groups ---------- */
const ongoingSupplies = computed<FarmerSupply[]>(
	() => props.farmer?.supplies.filter((s) => s.status === 'Ongoing') ?? [],
)
const archivedSupplies = computed<FarmerSupply[]>(
	() => props.farmer?.supplies.filter((s) => s.status === 'Archived') ?? [],
)
const fulfilledSupplies = computed<FarmerSupply[]>(
	() => props.farmer?.supplies.filter((s) => s.status === 'Fulfilled') ?? [],
)

/* ---------- Stats ---------- */
const totalSupplies = computed(() => props.farmer?.supplies.length ?? 0)
const totalQuantity = computed(
	() => props.farmer?.supplies.reduce((sum, s) => sum + s.quantity_kg, 0) ?? 0,
)
const totalOngoing = computed(() => ongoingSupplies.value.length)
const totalOngoingQuantity = computed(() =>
	ongoingSupplies.value.reduce((sum, s) => sum + s.quantity_kg, 0),
)

/* ---------- Breadcrumbs — stable fallback until farmer loads ---------- */
const breadcrumbs = computed(() => [
	{ title: 'Admin', href: admin.dashboard().url },
	{ title: 'Farmers', href: admin.farmers.index().url },
	...(props.farmer
		? [
				{
					title: props.farmer.user.name,
					href: admin.farmers.show(props.farmer.id).url,
				},
			]
		: []),
])

function priceFlagVariant(flag: 'Low' | 'Fair' | 'High') {
	if (flag === 'Low') return 'secondary'
	if (flag === 'High') return 'destructive'
	return 'outline'
}
</script>

<template>
    <!-- Title updates reactively once farmer loads -->
    <Head :title="farmer?.user.name ?? 'Farmer'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">

            <!-- Heading: stable label before load, real name after -->
            <Heading
                :title="farmer?.user.name ?? 'Farmer'"
                description="Farmer profile and supply history"
            />

            <Deferred data="farmer">
                <template #fallback>
                    <div class="grid grid-cols-12 gap-5">
                        <div class="col-span-12 lg:col-span-3">
                            <Card class="p-5 space-y-5">
                                <div class="flex flex-col items-start gap-3">
                                    <Skeleton class="size-16 rounded-full" />
                                    <div class="space-y-1.5 w-full">
                                        <Skeleton class="h-4 w-32" />
                                        <Skeleton class="h-3 w-48" />
                                    </div>
                                </div>
                                <div class="space-y-2.5">
                                    <Skeleton class="h-4 w-full" />
                                    <Skeleton class="h-4 w-3/4" />
                                    <Skeleton class="h-4 w-1/2" />
                                </div>
                            </Card>
                        </div>
                        <div class="col-span-12 lg:col-span-9 space-y-4">
                            <Skeleton class="h-52 w-full rounded-xl" />
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <Skeleton v-for="i in 4" :key="i" class="h-20 rounded-xl" />
                            </div>
                            <Skeleton class="h-64 w-full rounded-xl" />
                        </div>
                    </div>
                </template>

                <div v-if="farmer" class="grid grid-cols-12 gap-5">

                    <!-- Sidebar: Profile -->
                    <div class="col-span-12 lg:col-span-3">
                        <Card class="p-5 space-y-5">
                            <div class="flex flex-col items-start gap-3">
                                <Avatar class="size-16">
                                    <AvatarImage
                                        v-if="farmer.user.avatar_url"
                                        :src="farmer.user.avatar_url"
                                        :alt="farmer.user.name"
                                    />
                                    <AvatarFallback class="bg-primary/10 text-base font-semibold text-primary">
                                        {{ getInitials(farmer.user.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <div>
                                    <p class="text-sm font-semibold leading-snug">{{ farmer.user.name }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ farmer.location.full_address }}</p>
                                </div>
                            </div>

                            <div class="space-y-2.5 text-sm text-muted-foreground">
                                <div class="flex items-center gap-2">
                                    <Mail class="size-4 shrink-0" />
                                    <span class="truncate">{{ farmer.user.email }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Phone class="size-4 shrink-0" />
                                    <span>{{ farmer.user.phone_number }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <CalendarDays class="size-4 shrink-0" />
                                    <span>Joined {{ farmer.joined_at_human }}</span>
                                </div>
                            </div>
                        </Card>
                    </div>

                    <!-- Main Content -->
                    <div class="col-span-12 lg:col-span-9 space-y-4">

                        <!-- Map -->
                        <Card class="gap-0 py-0 overflow-hidden">
                            <CardContent class="flex items-center gap-2 px-4 py-3 border-b">
                                <MapPin class="size-4 text-primary" />
                                <p class="text-sm font-medium">Location</p>
                            </CardContent>
                            <LeafletMap
                                :lat="farmer.location.coordinates.lat"
                                :lng="farmer.location.coordinates.lng"
                                :markers="[{
                                    lat: farmer.location.coordinates.lat,
                                    lng: farmer.location.coordinates.lng,
                                    popup: farmer.location.full_address
                                }]"
                            />
                        </Card>

                        <!-- Stats -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <SmallCard title="Total Supplies" :value="totalSupplies" />
                            <SmallCard title="Total Quantity" :value="totalQuantity" subtext="kg" />
                            <SmallCard title="Ongoing Supplies" :value="totalOngoing" />
                            <SmallCard title="Ongoing Quantity" :value="totalOngoingQuantity" subtext="kg" />
                        </div>

                        <!-- Supplies (Tabbed) -->
                        <Card>
                            <CardContent class="pt-4">
                                <Tabs default-value="ongoing">
                                    <TabsList class="mb-4">
                                        <TabsTrigger value="ongoing" class="gap-1.5">
                                            <Package class="size-4" />
                                            Ongoing
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ ongoingSupplies.length }}
                                            </Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="archived" class="gap-1.5">
                                            <Archive class="size-4" />
                                            Archived
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ archivedSupplies.length }}
                                            </Badge>
                                        </TabsTrigger>
                                        <TabsTrigger value="fulfilled" class="gap-1.5">
                                            <PackageCheck class="size-4" />
                                            Fulfilled
                                            <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                                {{ fulfilledSupplies.length }}
                                            </Badge>
                                        </TabsTrigger>
                                    </TabsList>

                                    <!-- Ongoing -->
                                    <TabsContent value="ongoing">
                                        <div v-if="ongoingSupplies.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No ongoing supplies
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="supply in ongoingSupplies" :key="supply.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="supply.image_url || supply.variety.image_url" :alt="supply.variety.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">{{ supply.variety.name }} — {{ supply.variety.category }}</ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">₱{{ supply.offered_price.toFixed(2) }}</span>
                                                        <Badge :variant="priceFlagVariant(supply.price_flag)" class="text-xs px-1.5 py-0">
                                                            {{ supply.price_flag }}
                                                        </Badge>
                                                    </ItemDescription>
                                                </ItemContent>
                                            </Item>
                                        </ItemGroup>
                                    </TabsContent>

                                    <!-- Archived -->
                                    <TabsContent value="archived">
                                        <div v-if="archivedSupplies.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No archived supplies
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="supply in archivedSupplies" :key="supply.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="supply.image_url || supply.variety.image_url" :alt="supply.variety.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">{{ supply.variety.name }} — {{ supply.variety.category }}</ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">₱{{ supply.offered_price.toFixed(2) }}</span>
                                                        <Badge :variant="priceFlagVariant(supply.price_flag)" class="text-xs px-1.5 py-0">
                                                            {{ supply.price_flag }}
                                                        </Badge>
                                                    </ItemDescription>
                                                </ItemContent>
                                            </Item>
                                        </ItemGroup>
                                    </TabsContent>

                                    <!-- Fulfilled -->
                                    <TabsContent value="fulfilled">
                                        <div v-if="fulfilledSupplies.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                            No fulfilled supplies
                                        </div>
                                        <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Item v-for="supply in fulfilledSupplies" :key="supply.id" variant="outline">
                                                <ItemMedia variant="image">
                                                    <img :src="supply.image_url || supply.variety.image_url" :alt="supply.variety.name" />
                                                </ItemMedia>
                                                <ItemContent class="min-w-0">
                                                    <ItemTitle class="line-clamp-1 text-sm">{{ supply.variety.name }} — {{ supply.variety.category }}</ItemTitle>
                                                    <ItemDescription class="flex items-center gap-1.5 mt-0.5">
                                                        <span class="text-sm font-medium text-foreground">₱{{ supply.offered_price.toFixed(2) }}</span>
                                                        <Badge :variant="priceFlagVariant(supply.price_flag)" class="text-xs px-1.5 py-0">
                                                            {{ supply.price_flag }}
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
