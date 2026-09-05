<script setup lang="ts">
import { Deferred, Head, Link, router, usePage } from '@inertiajs/vue3'
import { BellOff } from '@lucide/vue'
import EmptyState from '@/components/EmptyState.vue'
import Heading from '@/components/Heading.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import { useCapitalize } from '@/lib/utils'
import { dashboard } from '@/routes'
import { show, unwatch } from '@/routes/vegetables'
import type { BreadcrumbItem } from '@/types'
import { Item, ItemActions, ItemContent, ItemDescription, ItemGroup, ItemMedia, ItemTitle } from '@/components/ui/item'

interface WatchRow {
    id: number
    vegetable_id: number
    vegetable_name: string
    image_url: string
    category: string
    last_notified_band: string | null
    last_evaluated_at: string | null
}

defineProps<{ watches?: WatchRow[] }>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: useCapitalize(usePage().props.auth.user.roles[0]), href: dashboard().url },
    { title: 'My Watches' },
]

function bandVariant(band: string | null): 'destructive' | 'default' | 'outline' {
    if (band === 'oversupply') return 'destructive'
    if (band === 'undersupply') return 'default'
    return 'outline'
}

function bandLabel(band: string | null): string {
    if (!band) return 'Monitoring'
    return band === 'oversupply' ? 'Oversupply expected' : band === 'undersupply' ? 'Shortage expected' : 'Balanced'
}

function stopWatching(row: WatchRow): void {
    router.delete(unwatch(row.vegetable_id).url, { preserveScroll: true })
}
</script>

<template>
    <Head title="My Watches" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="My Watches"
                description="Vegetables you're monitoring for supply/demand shifts."
            />

            <Deferred data="watches">
                <template #fallback>
                    <div class="space-y-2">
                        <Skeleton
                            v-for="i in 4"
                            :key="i"
                            class="h-16 w-full rounded-lg"
                        />
                    </div>
                </template>

                <EmptyState
                    v-if="!watches?.length"
                    title="No watches yet"
                    description="Watch a vegetable from its detail page to get notified about supply shifts."
                />

                <ItemGroup
                    v-else
                    class="flex flex-col gap-2"
                >
                    <Item
                        v-for="row in watches"
                        :key="row.id"
                        variant="outline"
                        class="transition-all hover:shadow-sm bg-primary/10 hover:bg-card hover:border-l-4 hover:border-l-primary"
                        as-child
                    >
                        <Link :href="show(row.vegetable_id).url">
                            <ItemMedia>
                                <Avatar class="size-10 shrink-0 rounded-md">
                                    <AvatarImage
                                        :src="row.image_url"
                                        :alt="row.vegetable_name"
                                    />
                                    <AvatarFallback>{{ row.vegetable_name }}</AvatarFallback>
                                </Avatar>
                            </ItemMedia>

                            <ItemContent>
                                <ItemTitle class="line-clamp-1">
                                    {{ row.vegetable_name }}
                                </ItemTitle>
                                <ItemDescription>
                                    <Badge :variant="bandVariant(row.last_notified_band)">
                                        {{ bandLabel(row.last_notified_band) }}
                                    </Badge>
                                </ItemDescription>
                            </ItemContent>

                            <ItemActions>
                                <Button 
                                    variant="ghost" 
                                    size="icon-sm"
                                    @click="stopWatching(row)"
                                >
                                    <BellOff class="size-4" />
                                </Button>
                            </ItemActions>
                        </Link>
                    </Item>
                </ItemGroup>
            </Deferred>
        </div>
    </AppLayout>
</template>