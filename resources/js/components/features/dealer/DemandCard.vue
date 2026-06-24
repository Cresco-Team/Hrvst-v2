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
    ItemTitle,
} from '@/components/ui/item'
import type { DealerDemandDataFixed } from '@/types'
import { MoreVertical, SquarePen, Trash } from 'lucide-vue-next'

const props = defineProps<{ demand: DealerDemandDataFixed }>()

const emit = defineEmits<{
    edit: [demand: DealerDemandDataFixed]
    delete: [demand: DealerDemandDataFixed]
}>()
</script>

<template>
    <Item variant="outline" class="group transition-all hover:shadow-sm">
        <ItemContent>
            <ItemTitle>
                {{ demand.scheduled_date }}
                <Badge variant="outline">{{ demand.time_slot }}</Badge>
            </ItemTitle>
            <ItemDescription v-if="demand.post_items?.length">
                {{ demand.post_items.length }}
                {{ demand.post_items.length === 1 ? 'variety' : 'varieties' }}
                needed
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
                        <DropdownMenuItem @click="emit('edit', demand)">
                            <SquarePen />
                            Edit Demand
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            class="text-destructive focus:text-destructive"
                            @click="emit('delete', demand)"
                        >
                            <Trash />
                            Delete Demand
                        </DropdownMenuItem>
                    </DropdownMenuGroup>

                    <template v-if="demand.post_items?.length">
                        <DropdownMenuSeparator />
                        <DropdownMenuLabel>Varieties Needed</DropdownMenuLabel>
                        <DropdownMenuGroup>
                            <DropdownMenuItem
                                v-for="item in demand.post_items"
                                :key="item.id"
                            >
                                {{ item.variety?.name }}
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
