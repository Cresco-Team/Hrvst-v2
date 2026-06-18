<script setup lang="ts">
import {
    Calendar,
    AlarmClockCheck,
    Leaf,
    MoreVertical,
    Pencil,
    Trash,
    CalendarClock,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Button } from '@/components/ui/button'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'
import type { FarmerSupplyResource } from '@/types'

const { post } = defineProps<{ post: FarmerSupplyResource }>()

const emit = defineEmits<{
    edit: [post: FarmerSupplyResource]
    harvest: [post: FarmerSupplyResource]
    delete: [post: FarmerSupplyResource]
}>()

const canHarvest = computed(() => {
    const d = new Date()
    d.setMonth(d.getMonth() + 1)
    const nextMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`
    return post.target_month! <= nextMonth
})
</script>

<template>
    <Card class="gap-2 overflow-hidden py-0 transition-all hover:shadow-lg">
        <AspectRatio
            :ratio="16 / 9"
            class="relative flex items-center justify-center overflow-hidden bg-primary/10"
        >
            <img
                v-if="post.image_url"
                :src="post.image_url"
                :alt="post.vegetable?.name"
            />
            <img
                v-else-if="post.vegetable?.image_url"
                :src="post.vegetable.image_url"
                :alt="post.vegetable?.name"
            />

            <!-- Status badge -->
            <span
                class="absolute top-2 left-4 rounded-full bg-lime-100 px-2.5 py-0.5 text-xs font-semibold text-lime-700 dark:bg-lime-900/40 dark:text-lime-300"
            >
                Growing
            </span>

            <!-- Timestamp -->
            <div
                class="absolute right-0 bottom-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm"
            >
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div class="cursor-help">
                                {{ post.created_at_human }}
                            </div>
                        </TooltipTrigger>
                        <TooltipContent
                            >Posted on: {{ post.created_at }}</TooltipContent
                        >
                    </Tooltip>
                </TooltipProvider>
            </div>

            <!-- Actions -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        size="icon-sm"
                        class="absolute top-3 right-3"
                    >
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem
                        :disabled="!canHarvest"
                        @click="canHarvest && emit('harvest', post)"
                    >
                        <CalendarClock class="mr-2 size-4 text-lime-600" />
                        Schedule Arrival
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('edit', post)">
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                        class="text-destructive"
                        @click="emit('delete', post)"
                    >
                        <Trash class="mr-2 size-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle class="line-clamp-1">{{
                post.vegetable?.name
            }}</CardTitle>
            <CardDescription class="ml-2 line-clamp-1">{{
                post.vegetable?.category
            }}</CardDescription>
        </CardHeader>

        <div class="px-7"><Separator /></div>

        <CardContent class="grid gap-2 p-5 pt-2">
            <div class="rounded-md bg-lime-50 p-3 dark:bg-lime-900/20">
                <span
                    class="mb-1 block text-xs tracking-wider text-lime-700 dark:text-lime-300"
                    >TARGET MONTH</span
                >
                <span class="font-semibold text-lime-700 dark:text-lime-300">{{
                    post.target_month
                }}</span>
            </div>
            <div class="rounded-md bg-primary/10 p-3">
                <span class="mb-1 block text-xs tracking-wider"
                    >EST. WEIGHT</span
                >
                <span class="font-semibold text-primary"
                    >{{ post.estimated_total_weight }} kg</span
                >
            </div>
        </CardContent>
    </Card>
</template>
