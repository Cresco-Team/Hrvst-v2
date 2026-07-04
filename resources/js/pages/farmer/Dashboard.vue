<script setup lang="ts">
import { Deferred, Head, router } from '@inertiajs/vue3'
import {
    CalendarCheck,
    CalendarClock,
    CalendarX,
    CheckCircle2,
    ChevronDown,
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
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { usePostItemUrgency } from '@/composables/usePostItemUrgency'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { fulfill as fulfillItem, expire as expireItem } from '@/routes/farmer/dashboard/items'
import type {
    BreadcrumbItem,
    FarmerDashboardProps,
    RecommendationSeverity,
} from '@/types'
import SmallCard from '@/components/shared/cards/SmallCard.vue'
import SupplyActionCard from '@/components/features/farmer/SupplyActionCard.vue'

const props = defineProps<FarmerDashboardProps>()

const { daysOverdue, isDueToday, urgencyClass, urgencyLabel } = usePostItemUrgency()

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

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <!-- Action needed -->
                <SupplyActionCard
                    :expiring-supplies="expiringSupplies"
                />
            </div>
        </div>
    </AppLayout>
</template>
