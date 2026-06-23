<script setup lang="ts">
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
import type { FarmerSupplyDataFixed } from '@/types'
import { MoreVertical, SquarePen, Trash } from 'lucide-vue-next'

const props = defineProps<{ supply: FarmerSupplyDataFixed }>()

const emit = defineEmits<{
    edit: [supply: FarmerSupplyDataFixed]
    delete: [supply: FarmerSupplyDataFixed]
}>()
</script>

<template>
    <Item variant="outline">
        <ItemMedia variant="image">
            <img
                v-if="supply.vegetable?.image_url"
                :src="supply.vegetable.image_url"
                :alt="supply.vegetable.name"
            />
        </ItemMedia>

        <ItemContent>
            <ItemTitle>
                {{ supply.vegetable?.name }}
                <Badge variant="outline">{{ supply.time_slot }}</Badge>
            </ItemTitle>
            <ItemDescription>{{ supply.scheduled_date }}</ItemDescription>
            <ItemDescription v-if="supply.post_items?.length">
                {{ supply.post_items.length }}
                {{ supply.post_items.length === 1 ? 'variety' : 'varieties' }}
            </ItemDescription>
        </ItemContent>

        <ItemActions>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-sm">
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
                                {{ item.variety?.name }}
                                <DropdownMenuShortcut>
                                    <Badge variant="outline"
                                        >{{ item.quantity_kg }} kg</Badge
                                    >
                                </DropdownMenuShortcut>
                            </DropdownMenuItem>
                        </DropdownMenuGroup>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>
        </ItemActions>
    </Item>
</template>
