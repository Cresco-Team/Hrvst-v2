<script setup lang="ts">

import { Head } from '@inertiajs/vue3';
import { Archive, CalendarDays, Mail, MapPin, Package, PackageCheck, Phone } from 'lucide-vue-next';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import LeafletMap from '@/components/LeafletMap.vue';
import SmallCard from '@/components/shared/cards/SmallCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Item, ItemDescription, ItemGroup, ItemMedia, ItemTitle, ItemContent } from '@/components/ui/item';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import admin from '@/routes/admin';
import type { ShowFarmer } from '@/types/admin/farmers';

interface Props {
    farmer: ShowFarmer;
}

const props = defineProps<Props>();

const breadcrumbs = computed(() => [
    { title: 'Admin', href: admin.dashboard().url },
    { title: 'Farmers', href: admin.farmers.index().url },
    { title: props.farmer.user.name, href: admin.farmers.show(props.farmer.id).url },
]);

function priceFlagVariant(flag: 'Low' | 'Fair' | 'High') {
    if (flag === 'Low') return 'secondary';
    if (flag === 'High') return 'destructive';
    return 'outline';
}

</script>

<template>

    <Head :title="farmer.user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading :title="farmer.user.name" description="Farmer profile and supply history" />

            <div class="grid grid-cols-12 gap-5">

                <!-- Sidebar: Profile -->
                <div class="col-span-12 lg:col-span-3">
                    <Card class="p-5 space-y-5">
                        <div class="flex flex-col items-start gap-3">
                            <Avatar class="size-16">
                                <AvatarImage
                                    v-if="farmer.user.image_url"
                                    :src="farmer.user.image_url"
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
                            :markers="[{ lat: farmer.location.coordinates.lat, lng: farmer.location.coordinates.lng, popup: farmer.location.full_address }]"
                        />
                    </Card>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        <SmallCard
                            title="Total Supplies"
                            :value="farmer.total_supplies"
                        />
                        <SmallCard
                            title="Total Quantity"
                            :value="farmer.total_quantity"
                            subtext="kg"
                        />
                        <SmallCard
                            title="Ongoing Supplies"
                            :value="farmer.total_ongoing_supplies"
                        />
                        <SmallCard
                            title="Ongoing Quantity"
                            :value="farmer.total_ongoing_supplies_quantity"
                            subtext="kg"
                        />
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
                                            {{ farmer.supplies.ongoing.length }}
                                        </Badge>
                                    </TabsTrigger>
                                    <TabsTrigger value="archived" class="gap-1.5">
                                        <Archive class="size-4" />
                                        Archived
                                        <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                            {{ farmer.supplies.archived.length }}
                                        </Badge>
                                    </TabsTrigger>
                                    <TabsTrigger value="fulfilled" class="gap-1.5">
                                        <PackageCheck class="size-4" />
                                        Fulfilled
                                        <Badge variant="secondary" class="ml-1 px-1.5 py-0 text-xs">
                                            {{ farmer.supplies.fulfilled.length }}
                                        </Badge>
                                    </TabsTrigger>
                                </TabsList>

                                <!-- Ongoing -->
                                <TabsContent value="ongoing">
                                    <div v-if="farmer.supplies.ongoing.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                        No ongoing supplies
                                    </div>
                                    <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <Item v-for="supply in farmer.supplies.ongoing" :key="supply.id" variant="outline">
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
                                    <div v-if="farmer.supplies.archived.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                        No archived supplies
                                    </div>
                                    <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <Item v-for="supply in farmer.supplies.archived" :key="supply.id" variant="outline">
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
                                    <div v-if="farmer.supplies.fulfilled.length === 0" class="flex items-center justify-center h-24 text-sm text-muted-foreground">
                                        No fulfilled supplies
                                    </div>
                                    <ItemGroup v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <Item v-for="supply in farmer.supplies.fulfilled" :key="supply.id" variant="outline">
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
        </div>
    </AppLayout>
</template>
