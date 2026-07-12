<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { CalendarClock, CircleCheck, Sparkles } from 'lucide-vue-next'
import { computed } from 'vue'
import Heading from '@/components/Heading.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import { cancel, subscribe } from '@/routes/billing'
import type { BreadcrumbItem } from '@/types'

interface PlanOption {
    value: 'monthly' | 'quarterly' | 'annual'
    label: string
    price_cents: number
}

interface Props {
    feature: string
    featureLabel: string
    subscription?: {
        plan: string | null
        status: string | null
        is_active: boolean
        ends_at: string | null
        ends_at_human: string | null
    }
    plans: PlanOption[]
}

const props = defineProps<Props>()

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Billing' }]

const subscribeForm = useForm({
    feature: props.feature,
    plan: '' as PlanOption['value'] | '',
})

const cancelForm = useForm({})

const hasActiveSubscription = computed(() => props.subscription?.is_active ?? false)
const isCancelled = computed(() => props.subscription?.status === 'cancelled')

function formatPrice(cents: number): string {
    return (cents / 100).toLocaleString('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    })
}

function selectPlan(plan: PlanOption['value']): void {
    subscribeForm.plan = plan
    subscribeForm.post(subscribe().url, {
        preserveScroll: true,
        onSuccess: () => subscribeForm.reset('plan'),
    })
}

function handleCancel(): void {
    cancelForm.post(cancel().url, { preserveScroll: true })
}
</script>

<template>
    <Head title="Billing" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 lg:p-6 max-w-3xl">
            <Heading
                :title="featureLabel"
                description="Manage your subscription to access this feature."
            />

            <!-- Current status -->
            <Card v-if="hasActiveSubscription">
                <CardContent class="flex items-center justify-between gap-4 pt-6">
                    <div class="flex items-start gap-3">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <CircleCheck class="size-5" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold capitalize">
                                {{ subscription?.plan }} plan
                                <Badge
                                    v-if="isCancelled"
                                    variant="outline"
                                    class="ml-1 text-xs font-normal"
                                >Not renewing</Badge>
                            </p>
                            <p class="flex items-center gap-1.5 text-xs text-muted-foreground">
                                <CalendarClock class="size-3.5" />
                                {{ isCancelled ? 'Access ends' : 'Renews' }} {{ subscription?.ends_at_human }}
                                ({{ subscription?.ends_at }})
                            </p>
                        </div>
                    </div>

                    <Button
                        v-if="!isCancelled"
                        variant="outline"
                        size="sm"
                        :disabled="cancelForm.processing"
                        @click="handleCancel"
                    >
                        <Spinner v-if="cancelForm.processing" class="size-3.5" />
                        Cancel
                    </Button>
                </CardContent>
            </Card>

            <Card v-else class="border-dashed">
                <CardContent class="flex items-center gap-3 pt-6 text-sm text-muted-foreground">
                    <Sparkles class="size-4 shrink-0" />
                    No active subscription. Choose a plan below to unlock {{ featureLabel.toLowerCase() }}.
                </CardContent>
            </Card>

            <!-- Plan selection -->
            <div class="grid gap-4 sm:grid-cols-3">
                <Card
                    v-for="plan in plans"
                    :key="plan.value"
                    :class="[
                        'flex flex-col',
                        subscribeForm.plan === plan.value && subscribeForm.processing && 'opacity-60',
                    ]"
                >
                    <CardHeader>
                        <CardTitle class="text-base">{{ plan.label }}</CardTitle>
                        <CardDescription class="text-2xl font-bold text-foreground">
                            {{ formatPrice(plan.price_cents) }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="mt-auto">
                        <Button
                            class="w-full"
                            :variant="hasActiveSubscription && subscription?.plan === plan.value ? 'outline' : 'default'"
                            :disabled="subscribeForm.processing || (hasActiveSubscription && subscription?.plan === plan.value && !isCancelled)"
                            @click="selectPlan(plan.value)"
                        >
                            <Spinner
                                v-if="subscribeForm.processing && subscribeForm.plan === plan.value"
                                class="size-3.5"
                            />
                            <template v-else>
                                {{ hasActiveSubscription && subscription?.plan === plan.value && !isCancelled ? 'Current Plan' : 'Subscribe' }}
                            </template>
                        </Button>
                    </CardContent>
                </Card>
            </div>

            <p
                v-if="subscribeForm.errors.plan"
                class="text-xs text-destructive"
            >
                {{ subscribeForm.errors.plan }}
            </p>
        </div>
    </AppLayout>
</template>
