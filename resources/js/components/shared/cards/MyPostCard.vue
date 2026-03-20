<script setup lang="ts">
import { Archive, CalendarClock, MoreVertical, PackageCheck, Pencil, PhilippinePeso, SquareEqual, Trash, Weight } from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import Separator from '@/components/ui/separator/Separator.vue'
import { useTimeSlot } from '@/composables/useTimeSlot'
import type { Post } from '@/types/marketplace'

interface Props {
    post: Post
}

const { post } = defineProps<Props>()

const emit = defineEmits<{
    edit: [post: Post]
    archive: [post: Post]
    fulfill: [post: Post]
    delete: [post: Post]
}>()

const isOngoing = computed(() => post.status === 'Ongoing')
const isArchived = computed(() => post.status === 'Archived')
const isFulfilled = computed(() => post.status === 'Fulfilled')

const { getConfig } = useTimeSlot()
const slotConfig = computed(() => getConfig(post.time_slot))
</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
        <AspectRatio :ratio="16 / 9" class="relative overflow-hidden bg-primary/10 flex items-center justify-center">
            <img v-if="post.image_url" :src="post.image_url" :alt="`${post.variety.name} image`" />
            <img v-else :src="post.variety.image_url" :alt="`${post.variety.name} image`" />

            <div
                class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                {{ post.created_at_human }}
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem v-if="isOngoing" @click="emit('edit', post)">
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>
                    <DropdownMenuSeparator v-if="isOngoing" />
                    <DropdownMenuItem v-if="isOngoing || isFulfilled" @click="emit('archive', post)"
                        class="text-orange-600 dark:text-orange-400">
                        <Archive class="mr-2 size-4" />
                        Archive
                    </DropdownMenuItem>
                    <DropdownMenuItem v-if="isOngoing || isArchived" @click="emit('fulfill', post)"
                        class="text-green-500 dark:text-green-400">
                        <PackageCheck class="mr-2 size-4" />
                        Fulfill
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="emit('delete', post)" class="text-destructive">
                        <Trash class="mr-2 size-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle class="line-clamp-1">
                {{ post.variety.vegetable }} {{ post.variety.name }}
            </CardTitle>
            <CardDescription class="ml-2 line-clamp-1">
                {{ post.variety.category }}
            </CardDescription>
        </CardHeader>

        <div class="px-7">
            <Separator />
        </div>

        <CardContent class="p-5 pt-2 grid gap-2">
            <div class="flex justify-between">
                <div class="flex items-center text-xs text-muted-foreground gap-2">
                    <PhilippinePeso :size="15" />
                    Price:
                </div>
                <span class="text-sm">₱{{ post.offered_price.toFixed(2) }}/kg</span>
            </div>

            <div class="flex justify-between">
                <div class="flex items-center text-xs text-muted-foreground gap-2">
                    <Weight :size="15" />
                    Kg:
                </div>
                <span class="text-sm">{{ post.quantity_kg.toFixed(2) }} kg</span>
            </div>

            <div class="flex justify-between">
                <div class="flex items-center text-xs text-muted-foreground gap-2">
                    <SquareEqual :size="15" />
                    Total:
                </div>
                <span class="text-sm">₱{{ (post.quantity_kg * post.offered_price).toFixed(2) }}</span>
            </div>

            <div class="flex justify-between">
                <div class="flex items-center text-xs text-muted-foreground gap-2">
                    <CalendarClock :size="15" />
                    Schedule:
                </div>
                <span class="text-sm">{{ post.scheduled_date }}</span>
            </div>

            <!-- Time Slot -->
            <div v-if="post.time_slot" class="flex justify-between">
                <div class="flex items-center text-xs text-muted-foreground gap-2">
                    <component :is="slotConfig.icon" :size="20" :class="slotConfig.color" />
                </div>
                <span class="text-sm">{{ post.time_slot_label }}</span>
            </div>
        </CardContent>
    </Card>
</template>
