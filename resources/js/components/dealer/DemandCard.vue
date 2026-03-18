<script setup lang="ts">
import {
	Archive,
	CalendarClock,
	CircleCheckBig,
	MoreVertical,
	Pencil,
	PhilippinePeso,
	Trash,
	Weight,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Badge } from '@/components/ui/badge'
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
import type { Demand } from '@/types/dealer/demands'

interface Props {
	demand: Demand
}

const props = defineProps<Props>()

const emit = defineEmits<{
	edit: [demand: Demand]
	archive: [demand: Demand]
	fulfill: [demand: Demand]
	delete: [demand: Demand]
}>()

const isOngoing = computed(() => props.demand.status === 'Ongoing')
const isFulfilled = computed(() => props.demand.status === 'Fulfilled')
const isArchived = computed(() => props.demand.status === 'Archived')
</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg ">
        <AspectRatio :ratio="16/9" class="relative overflow-hidden">
            <img 
                :src="demand.variety.image_url" 
                :alt="demand.variety.name" 
                class="size-full object-cover bg-gray-200"
            />
            <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                {{ demand.created_at_human }}
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end">
                    <DropdownMenuItem 
                        v-if="isOngoing"
                        @click="emit('edit', demand)"
                    >
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>

                    <DropdownMenuSeparator v-if="isOngoing" />

                    <DropdownMenuItem
                        v-if="isOngoing || isFulfilled"
                        @click="emit('archive', demand)"
                        class="text-orange-500"
                    >
                        <Archive class="mr-2 size-4" />
                        Archive
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="isOngoing || isArchived"
                        @click="emit('fulfill', demand)"
                        class="text-green-500"
                    >
                        <CircleCheckBig class="mr-2 size-4" />
                        Fulfill
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        @click="emit('delete', demand)"
                        class="text-destructive"
                    >
                        <Trash class="mr-2 size-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </AspectRatio>

        <CardHeader class="p-5 py-2">
            <CardTitle>
                {{ demand.variety.name }}
            </CardTitle>
            <CardDescription class="flex justify-between">
                <p>{{ demand.variety.vegetable }}</p>
                <Badge>
                    {{ demand.quantity_kg }} kg
                </Badge>
            </CardDescription>
            <Separator />
        </CardHeader>

        <CardContent class="p-5 pt-2 grid gap-2">
            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <PhilippinePeso :size="15" />
                    Price:
                </div>
                <span>{{ demand.offered_price.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <Weight :size="15" />
                    Kg:
                </div>
                <span>{{ demand.quantity_kg }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <CalendarClock :size="15" />
                    Transact:
                </div>
                <p>
                    {{ demand.transaction_date }}
                    <span class="text-xs text-muted-foreground">
                        ({{ demand.days_until_transaction }} days)
                    </span>
                </p>
            </div>
        </CardContent>
    </Card>
</template>
