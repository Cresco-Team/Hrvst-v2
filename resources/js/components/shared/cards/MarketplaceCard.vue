<script setup lang="ts">
import axios from 'axios'
import { AlarmClockCheck, Calendar, Heart } from 'lucide-vue-next'
import { ref } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { cn } from '@/lib/utils'
import type { DealerDemandResource, FarmerSupplyResource } from '@/types'

// Used by both farmer/marketplace (viewing DealerDemandResource) and
// dealer/marketplace (viewing FarmerSupplyResource). Both shapes share all
// fields this component reads.
type PostItem = FarmerSupplyResource | DealerDemandResource

const { post } = defineProps<{
    post: PostItem
}>()

const localHearted = ref(post.is_hearted)
const localCount = ref(post.hearts_count)
const isPending = ref(false)

// image_url only on FarmerSupplyResource (supply posts carry an image)
const displayImageUrl = 'image_url' in post ? post.image_url : undefined

async function toggleHeart(event: MouseEvent): Promise<void> {
    event.stopPropagation()

    if (isPending.value) return

    const wasHearted = localHearted.value
    localHearted.value = !wasHearted
    localCount.value += wasHearted ? -1 : 1
    isPending.value = true

    try {
        const { data } = await axios.post<{ hearted: boolean; hearts_count: number }>(
            `/posts/${post.id}/heart`,
        )
        localHearted.value = data.hearted
        localCount.value = data.hearts_count
    } catch {
        localHearted.value = wasHearted
        localCount.value += wasHearted ? 1 : -1
    } finally {
        isPending.value = false
    }
}
</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
        <AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
            <img v-if="displayImageUrl" :src="displayImageUrl" :alt="`${post.variety?.name} image`" />
            <img v-else-if="post.variety?.image_url" :src="post.variety.image_url"
                :alt="`${post.variety?.name} image`" />

            <Badge class="absolute top-2 left-4 tracking-wider font-mono font-semibold">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span class="cursor-help">{{ post.offered_price.toLocaleString('en-PH', {
                                style: 'currency',
                                currency: 'PHP'
                            }) }}/kg</span>
                        </TooltipTrigger>
                        <TooltipContent>
                            {{ post.price_flag }} Price
                        </TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </Badge>

            <div
                class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                <TooltipProvider :delay-duration="200">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <div class="cursor-help">{{ post.created_at_human }}</div>
                        </TooltipTrigger>
                        <TooltipContent>{{ post.created_at }}</TooltipContent>
                    </Tooltip>
                </TooltipProvider>
            </div>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle class="line-clamp-1">
                {{ post.variety?.vegetable }} {{ post.variety?.name }}
            </CardTitle>
            <CardDescription class="ml-2 line-clamp-1">
                {{ post.variety?.category }}
            </CardDescription>
        </CardHeader>

        <div class="px-7">
            <Separator />
        </div>

        <CardContent class="p-5 pt-2 grid gap-2">
            <div class="grid grid-cols-2 gap-2 mb-2">
                <div class="bg-primary/10 p-3 rounded-md">
                    <span class="text-xs tracking-wider block mb-1">QUANTITY</span>
                    <span class="font-body font-semibold text-primary">{{ post.quantity_kg }} kg</span>
                </div>
                <div class="bg-primary/10 p-3 rounded-md">
                    <span class="text-xs tracking-wider block mb-1">TOTAL</span>
                    <span class="font-body font-semibold text-primary">
                        {{ (post.quantity_kg * post.offered_price).toLocaleString('en-PH', {
                            style: 'currency',
                            currency: 'PHP',
                        }) }}
                    </span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Calendar :size="20" class="text-muted-foreground" />
                <span class="text-xs">{{ post.scheduled_date }}</span>
            </div>

            <div v-if="post.time_slot" class="flex items-center gap-2">
                <AlarmClockCheck :size="20" class="text-muted-foreground" />
                <span class="text-xs">{{ post.time_slot_label }}</span>
            </div>

            <div class="flex items-center justify-end pt-1">
                <button
                    class="flex items-center gap-1.5 text-sm text-muted-foreground cursor-pointer transition-colors hover:text-rose-500 disabled:pointer-events-none disabled:opacity-50"
                    :disabled="isPending" @click="toggleHeart">
                    <Heart class="size-4 transition-all"
                        :class="cn(localHearted ? 'fill-rose-500 text-rose-500 scale-110' : 'fill-none')" />
                    <span class="tabular-nums">{{ localCount }}</span>
                </button>
            </div>
        </CardContent>
    </Card>
</template>
