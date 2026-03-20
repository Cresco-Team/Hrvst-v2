<script setup lang="ts">
import { AlarmClockCheck, Calendar } from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import type { Post } from '@/types/marketplace'

interface Props {
    post: Post
}

const { post } = defineProps<Props>()

</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
        <AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
            <img v-if="post.image_url" :src="post.image_url" :alt="`${post.variety.name} image`">
            <img v-else :src="post.variety.image_url" :alt="`${post.variety.name} image`">

            <Badge class="absolute top-2 left-4 tracking-wider font-mono font-semibold">
                {{ post.offered_price.toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) }}/kg
            </Badge>

            <div class=" absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white
                backdrop-blur-sm">
                {{ post.price_flag }} Price
            </div>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle class="line-clamp-1">
                {{ post.variety.vegetable }} {{ post.variety.name }}
            </CardTitle>
            <CardDescription class="ml-2 line-clamp-1">
                <p>{{ post.variety.category }}</p>
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
                    <span class="font-body font-semibold text-primary">{{ (post.quantity_kg *
                        post.offered_price).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' }) }}</span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Calendar :size="20" class="text-muted-foreground" />
                <span class="text-xs">{{ post.scheduled_date }}</span>
            </div>

            <!-- Time Slot -->
            <div v-if="post.time_slot" class="flex items-center gap-2">
                <AlarmClockCheck :size="20" class="text-muted-foreground" />
                <span class="text-xs">{{ post.time_slot_label }}</span>
            </div>
        </CardContent>
    </Card>
</template>