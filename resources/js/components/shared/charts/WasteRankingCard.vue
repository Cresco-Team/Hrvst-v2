<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { ArrowRight, ChevronDown, ChevronUp, Vegan } from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { show } from '@/routes/vegetables'
import type { VegetableWasteData } from '@/types/resources/product'

const props = defineProps<{
    title: string
    description: string
    items?: VegetableWasteData[]
    unitLabel?: string
    /** Omit to always show the full list (forecast usage). Set to enable expand/collapse. */
    initialVisible?: number
}>()

const expanded = ref(false)

const visible = computed(() => {
    if (!props.items?.length) return []
    if (!props.initialVisible || expanded.value) return props.items
    return props.items.slice(0, props.initialVisible)
})

const hasMore = computed(
    () => !!props.initialVisible && (props.items?.length ?? 0) > props.initialVisible,
)
const hiddenCount = computed(() => (props.items?.length ?? 0) - (props.initialVisible ?? 0))

const maxKg = computed(() => Math.max(...(props.items ?? []).map((i) => i.wasted_kg), 1))

function barPct(kg: number): string {
    return `${Math.round((kg / maxKg.value) * 100)}%`
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="text-sm font-semibold">{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div
                v-if="!items?.length"
                class="flex h-24 items-center justify-center text-center text-sm text-muted-foreground"
            >
                No data available for this period.
            </div>

            <template v-else>
                <ol class="flex flex-col gap-1">
                    <li v-for="(item, index) in visible" :key="item.id">
                        <Link
                            :href="show(item.id).url"
                            class="group -mx-2 flex items-center gap-3 rounded-lg px-2 py-1.5 transition-colors hover:bg-muted"
                        >
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground">
                                {{ index + 1 }}
                            </span>

                            <Avatar class="size-8 shrink-0 rounded-md">
                                <AvatarImage :src="item.image_url" :alt="item.display_name" />
                                <AvatarFallback class="rounded-md bg-primary/10">
                                    <Vegan class="size-4 text-primary" />
                                </AvatarFallback>
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ item.display_name }}</p>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-muted">
                                    <div class="h-full rounded-full bg-destructive/70" :style="{ width: barPct(item.wasted_kg) }" />
                                </div>
                            </div>

                            <span class="shrink-0 text-xs font-semibold tabular-nums text-muted-foreground">
                                {{ item.wasted_kg.toLocaleString() }} {{ unitLabel ?? 'kg' }}
                            </span>

                            <ArrowRight class="hidden size-4 shrink-0 text-muted-foreground transition-transform duration-300 group-hover:translate-x-1 group-hover:text-foreground sm:block" />
                        </Link>
                    </li>
                </ol>

                <Button
                    v-if="hasMore"
                    variant="ghost"
                    size="sm"
                    class="mt-2 w-fit gap-1.5 px-2 text-xs text-muted-foreground"
                    @click="expanded = !expanded"
                >
                    <component :is="expanded ? ChevronUp : ChevronDown" class="size-3.5" />
                    {{ expanded ? 'Show less' : `Show ${hiddenCount} more` }}
                </Button>
            </template>
        </CardContent>
    </Card>
</template>
