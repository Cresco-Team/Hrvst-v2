<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuShortcut,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Item,
    ItemActions,
    ItemContent,
    ItemDescription,
    ItemMedia,
    ItemTitle,
} from '@/components/ui/item'
import { getInitials } from '@/composables/useInitials'
import type { FarmerSupplyDataFixed } from '@/types'
import { CalendarClock, MoreVertical, SquarePen, Trash } from 'lucide-vue-next'

const props = defineProps<{ supply: FarmerSupplyDataFixed }>()

const emit = defineEmits<{
    edit: [supply: FarmerSupplyDataFixed]
    delete: [supply: FarmerSupplyDataFixed]
}>()
</script>

<template>
    <Item variant="outline" class="group bg-primary/10 transition-all hover:shadow-sm">
        <ItemMedia variant="icon" class="bg-primary/10">
            <CalendarClock />
        </ItemMedia>

        <ItemContent>
            <ItemTitle>
                {{ supply.scheduled_date }}
                <Badge variant="outline">{{ supply.time_slot }}</Badge>
            </ItemTitle>
            <ItemDescription v-if="supply.post_items?.length">
                {{ supply.post_items.length }}
                {{ supply.post_items.length === 1 ? 'variety' : 'varieties' }}
            </ItemDescription>
        </ItemContent>

        <ItemActions>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-sm" class="hover:bg-0">
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end">
                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                    <DropdownMenuGroup>
                        <DropdownMenuItem @click="emit('edit', supply)">
                            <SquarePen />
                            Edit Supply
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @click="emit('delete', supply)"
                        >
                            <Trash />
                            Delete Supply
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <template v-if="supply.post_items?.length">
                        <DropdownMenuSeparator />
                        <DropdownMenuLabel>Varieties</DropdownMenuLabel>
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                v-for="item in supply.post_items"
                                :key="item.id"
                            >
                                <Avatar class="size-5">
                                    <AvatarImage
                                        :src="item.vegetable_image_url!"
                                        :alt="item.vegetable_name"
                                    />
                                    <AvatarFallback>
                                        {{ getInitials(item.vegetable_name!) }}
                                    </AvatarFallback>
                                </Avatar>
                                <span class="line-clamp-1 max-w-35">
                                    {{ item.vegetable_name }}:
                                    {{ item.variety_name }}
                                </span>
                                <DropdownMenuShortcut>
                                    <Badge variant="outline">
                                        {{ item.quantity_kg }} kg
                                    </Badge>
                                </DropdownMenuShortcut>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>
