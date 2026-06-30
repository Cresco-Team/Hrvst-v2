<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import {
    CalendarCheck,
    CalendarClock,
    CalendarX,
    CheckCircle2,
    ChevronRight,
    Info,
    OctagonX,
    TriangleAlert,
} from 'lucide-vue-next'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { fulfill as fulfillItem, expire as expireItem } from '@/routes/farmer/dashboard/items'
import type {
    BreadcrumbItem,
    FarmerDashboardProps,
    RecommendationSeverity,
} from '@/types'
import SmallCard from '@/components/shared/cards/SmallCard.vue'

const props = defineProps<FarmerDashboardProps>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Farmer', href: farmer.dashboard().url },
    { title: 'Dashboard', href: farmer.dashboard().url },
]

interface SeverityConfig {
    icon: typeof OctagonX
    iconClass: string
    containerClass: string
}

function severityConfig(severity: RecommendationSeverity): SeverityConfig {
    switch (severity) {
        case 'critical':
            return {
                icon: OctagonX,
                iconClass: 'text-red-600 dark:text-red-400',
                containerClass:
                    'border-red-200 bg-red-50 dark:border-red-800/40 dark:bg-red-950/20',
            }
        case 'warning':
            return {
                icon: TriangleAlert,
                iconClass: 'text-amber-600 dark:text-amber-400',
                containerClass:
                    'border-amber-200 bg-amber-50 dark:border-amber-800/40 dark:bg-amber-950/20',
            }
        default:
            return {
                icon: Info,
                iconClass: 'text-blue-600 dark:text-blue-400',
                containerClass:
                    'border-blue-200 bg-blue-50 dark:border-blue-800/40 dark:bg-blue-950/20',
            }
    }
}

function daysUntil(dateStr: string | null): number | null {
    if (!dateStr) return null
    const diff = new Date(dateStr).getTime() - new Date().setHours(0, 0, 0, 0)
    return Math.ceil(diff / (1000 * 60 * 60 * 24))
}

function urgencyClass(days: number | null): string {
    if (days === null) return 'text-muted-foreground'
    if (days <= 1) return 'text-red-600 dark:text-red-400'
    if (days <= 2) return 'text-amber-600 dark:text-amber-400'
    return 'text-yellow-600 dark:text-yellow-400'
}

function urgencyLabel(days: number | null): string {
    if (days === null) return 'No date set'
    if (days === 0) return 'Today'
    if (days === 1) return 'Tomorrow'
    return `In ${days} days`
}

const criticalRecs = computed(
    () => props.recommendations?.filter((r) => r.severity === 'critical') ?? [],
)
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6">
            <Heading
                title="Dashboard"
                description="Your supply activity at a glance."
            />

            <!-- Critical alert banner -->
            <Deferred data="recommendations">
                <template #fallback>
                    <Skeleton class="h-14 w-full rounded-lg" />
                </template>
                <div
                    v-if="criticalRecs.length"
                    class="flex items-start gap-3 rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-700/50 dark:bg-red-950/30"
                >
                    <OctagonX
                        class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400"
                    />
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm font-semibold text-red-800 dark:text-red-200"
                        >
                            {{ criticalRecs.length }}
                            {{
                                criticalRecs.length === 1
                                    ? 'listing requires'
                                    : 'listings require'
                            }}
                            immediate attention
                        </p>
                        <p
                            class="mt-0.5 text-xs text-red-600 dark:text-red-400"
                        >
                            {{ criticalRecs.map((r) => r.title).join(' · ') }}
                        </p>
                    </div>
                </div>
            </Deferred>

            <!-- Summary cards -->
            <Deferred data="summary">
                <template #fallback>
                    <div class="grid grid-cols-3 gap-4">
                        <Skeleton
                            v-for="i in 3"
                            :key="i"
                            class="h-24 rounded-lg"
                        />
                    </div>
                </template>
                <div class="grid grid-cols-3 gap-4">
                    <SmallCard
                        title="Ongoing"
                        :value="summary.total_ongoing"
                        :icon="CalendarClock"
                    />
                    <SmallCard
                        title="Fulfilled"
                        :value="summary.total_fulfilled"
                        :icon="CalendarCheck"
                        value-class="text-green-600"
                        icon-class="text-green-600"
                    />
                    <SmallCard
                        title="Expired"
                        :value="summary.total_expired"
                        :icon="CalendarX"
                        value-class="text-destructive"
                        icon-class="text-destructive"
                    />
                </div>
            </Deferred>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Expiring supplies -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <CalendarClock class="size-4 text-amber-500" />
                                <CardTitle class="text-sm font-semibold"
                                    >Due Within 5 Days</CardTitle
                                >
                            </div>
                            <button
                                class="flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                @click="
                                    router.visit(farmer.supplies.index().url)
                                "
                            >
                                View all <ChevronRight class="size-3.5" />
                            </button>
                        </div>
                    </CardHeader>
                    <Separator />

                    <Deferred data="expiringSupplies">
                        <template #fallback>
                            <CardContent class="space-y-3 pt-4">
                                <Skeleton
                                    v-for="i in 3"
                                    :key="i"
                                    class="h-20 w-full rounded-lg"
                                />
                            </CardContent>
                        </template>

                        <CardContent class="pt-4">
                            <div
                                v-if="!expiringSupplies?.length"
                                class="flex flex-col items-center gap-2 py-8 text-center"
                            >
                                <CheckCircle2 class="size-8 text-green-500" />
                                <p class="text-sm font-medium">
                                    No upcoming deadlines
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    All supplies have healthy schedules.
                                </p>
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="supply in expiringSupplies"
                                    :key="supply.id"
                                    class="rounded-lg border bg-muted/30 p-3"
                                >
                                    <div
                                        class="flex items-center justify-between gap-3"
                                    >
                                        <div
                                            class="flex items-center gap-2 text-xs text-muted-foreground"
                                        >
                                            <CalendarClock
                                                class="size-3.5 shrink-0"
                                            />
                                            {{ supply.scheduled_date }}
                                            <Badge
                                                v-if="supply.time_slot_label"
                                                variant="outline"
                                            >
                                                {{ supply.time_slot_label }}
                                            </Badge>
                                        </div>
                                        <span
                                            class="shrink-0 text-xs font-semibold tabular-nums"
                                            :class="
                                                urgencyClass(
                                                    daysUntil(
                                                        supply.scheduled_date,
                                                    ),
                                                )
                                            "
                                        >
                                            {{
                                                urgencyLabel(
                                                    daysUntil(
                                                        supply.scheduled_date,
                                                    ),
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <Separator class="my-2.5" />

                                    <div class="space-y-2">
                                        <div
                                            v-for="item in supply.items"
                                            :key="item.id"
                                            class="flex items-center gap-2.5"
                                        >
                                            <Avatar
                                                class="size-8 shrink-0 rounded-md"
                                            >
                                                <AvatarImage
                                                    v-if="
                                                        item.vegetable_image_url
                                                    "
                                                    :src="
                                                        item.vegetable_image_url
                                                    "
                                                    :alt="
                                                        item.vegetable_name ??
                                                        ''
                                                    "
                                                />
                                                <AvatarFallback
                                                    class="rounded-md bg-primary/10 text-xs font-bold text-primary"
                                                >
                                                    {{
                                                        item.vegetable_name?.charAt(
                                                            0,
                                                        )
                                                    }}
                                                </AvatarFallback>
                                            </Avatar>

                                            <div class="min-w-0 flex-1">
                                                <p
                                                    class="truncate text-sm font-medium"
                                                >
                                                    {{ item.vegetable_name }}:
                                                    {{ item.variety_name }}
                                                </p>
                                                <p
                                                    class="text-xs text-muted-foreground"
                                                >
                                                    {{ item.quantity_kg }} kg
                                                </p>
                                            </div>

                                            <PostActionButtons
                                                :fulfill-url="
                                                    fulfillItem(item.id).url
                                                "
                                                :expire-url="
                                                    expireItem(item.id).url
                                                "
                                                :label="`${item.vegetable_name}: ${item.variety_name}`"
                                                :only="[
                                                    'expiringSupplies',
                                                    'summary',
                                                ]"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Deferred>
                </Card>

                <!-- Recommendations -->
                <Card>
                    <CardHeader class="pb-3">
                        <div class="flex items-center gap-2">
                            <Info class="size-4 text-blue-500" />
                            <CardTitle class="text-sm font-semibold"
                                >Recommendations</CardTitle
                            >
                        </div>
                        <CardDescription class="text-xs">
                            Actions to improve your listings and increase dealer
                            trust.
                        </CardDescription>
                    </CardHeader>
                    <Separator />

                    <Deferred data="recommendations">
                        <template #fallback>
                            <CardContent class="space-y-3 pt-4">
                                <Skeleton
                                    v-for="i in 3"
                                    :key="i"
                                    class="h-20 w-full rounded-lg"
                                />
                            </CardContent>
                        </template>

                        <CardContent class="pt-4">
                            <div
                                v-if="!recommendations?.length"
                                class="flex flex-col items-center gap-2 py-8 text-center"
                            >
                                <CheckCircle2 class="size-8 text-green-500" />
                                <p class="text-sm font-medium">All clear</p>
                                <p class="text-xs text-muted-foreground">
                                    No recommendations right now.
                                </p>
                            </div>

                            <div v-else class="flex flex-col gap-2">
                                <div
                                    v-for="rec in recommendations"
                                    :key="rec.type"
                                    class="flex items-start gap-3 rounded-lg border p-3 transition-colors"
                                    :class="
                                        severityConfig(rec.severity)
                                            .containerClass
                                    "
                                >
                                    <component
                                        :is="severityConfig(rec.severity).icon"
                                        class="mt-0.5 size-4 shrink-0"
                                        :class="
                                            severityConfig(rec.severity)
                                                .iconClass
                                        "
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-sm leading-snug font-semibold"
                                        >
                                            {{ rec.title }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs leading-relaxed text-muted-foreground"
                                        >
                                            {{ rec.body }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Deferred>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
