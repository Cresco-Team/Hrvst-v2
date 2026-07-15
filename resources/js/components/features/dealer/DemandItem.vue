<script setup lang="ts">
import {
    CalendarClock,
    ChevronDown,
    MoreVertical,
    SquarePen,
    Trash,
    TriangleAlert,
} from 'lucide-vue-next'
import { fulfill, expire } from '@/actions/App/Http/Controllers/Dealer/PostItemController'
import PostActionButtons from '@/components/shared/PostActionButtons.vue'
import { Avatar, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Item, ItemActions, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import type { DealerDemandDataFixed } from '@/types'

defineProps<{ demand: DealerDemandDataFixed }>()

const emit = defineEmits<{
    edit: [demand: DealerDemandDataFixed]
    delete: [demand: DealerDemandDataFixed]
}>()
</script>

<template>
    <Item
        variant="outline"
        :class="[
            'group transition-all hover:shadow-sm',
            demand.needs_action ? 'bg-destructive/5 hover:border-l-4 hover:border-l-destructive' : 'bg-primary/10 hover:border-l-4 hover:border-l-primary',
        ]"
    >
        <ItemMedia
            variant="icon"
            :class="demand.needs_action ? 'bg-destructive/10' : 'bg-primary/10'"
        >
            <TriangleAlert
                v-if="demand.needs_action"
                class="text-destructive"
            />
            <CalendarClock v-else />
        </ItemMedia>

        <ItemContent>
            <ItemTitle class="flex flex-wrap items-center gap-1.5">
                {{ demand.scheduled_date }}
                <Badge variant="outline">{{ demand.time_slot }}</Badge>
                <Badge
                    v-if="demand.needs_action"
                    variant="destructive"
                >Action needed</Badge>
            </ItemTitle>
            <ItemDescription v-if="demand.post_items?.length">
                {{ demand.post_items.length }} {{ demand.post_items.length === 1 ? 'demand' : 'demands' }}
            </ItemDescription>
        </ItemContent>

        <ItemActions class="flex items-center gap-1">
            <Popover>
                <PopoverTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                    >
                        <ChevronDown class="size-4" />
                    </Button>
                </PopoverTrigger>
                <PopoverContent
                    align="end"
                    class="w-80 p-0"
                >
                    <div class="divide-y">
                        <div
                            v-for="item in demand.post_items"
                            :key="item.id"
                            class="flex items-center gap-3 p-3"
                        >
                            <Avatar class="size-9 shrink-0 rounded-md">
                                <AvatarImage
                                    :src="item.vegetable_image_url!"
                                    :alt="item.display_name!"
                                />
                            </Avatar>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ item.display_name }}</p>
                                <p class="text-xs text-muted-foreground">{{ item.quantity_kg }} kg</p>
                            </div>

                            <PostActionButtons
                                v-if="demand.needs_action && item.status === 'ongoing'"
                                :fulfill-url="fulfill(item.id).url"
                                :expire-url="expire(item.id).url"
                                :label="item.display_name!"
                                :only="['needsAction']"
                            />
                            <Badge
                                v-else-if="demand.needs_action"
                                variant="secondary"
                                class="shrink-0 capitalize"
                            >
                                {{ item.status }}
                            </Badge>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                    >
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                    <DropdownMenuGroup>
                        <DropdownMenuItem @click="emit('edit', demand)">
                            <SquarePen />
                            Edit Schedule
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @click="emit('delete', demand)"
                        >
                            <Trash />
                            Delete Schedule
                        </DropdownMenuItem>
                    </DropdownMenuGroup>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>
