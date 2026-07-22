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
import type { BreadcrumbItem } from '@/types'
import { dashboard } from '@/routes'
import { show, unwatch } from '@/routes/vegetables'

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

                <div
                    v-else
                    class="flex flex-col gap-2"
                >
                    <div
                        v-for="row in watches"
                        :key="row.id"
                        class="flex items-center gap-3 rounded-lg border p-3"
                    >
                        <Avatar class="size-10 shrink-0 rounded-md">
                            <AvatarImage
                                :src="row.image_url"
                                :alt="row.vegetable_name"
                            />
                            <AvatarFallback>{{ row.vegetable_name[0] }}</AvatarFallback>
                        </Avatar>

                        <div class="min-w-0 flex-1">
                            <Link
                                :href="show(row.vegetable_id).url"
                                class="truncate font-medium hover:underline"
                            >
                                {{ row.vegetable_name }}
                            </Link>
                            <p class="text-xs text-muted-foreground">{{ row.category }}</p>
                        </div>

                        <Badge :variant="bandVariant(row.last_notified_band)">{{ bandLabel(row.last_notified_band) }}</Badge>

                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="stopWatching(row)"
                        >
                            <BellOff class="size-4" />
                        </Button>
                    </div>
                </div>
            </Deferred>
        </div>
    </AppLayout>
</template>