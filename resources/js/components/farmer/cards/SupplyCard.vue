<script setup lang="ts">
import {
	Archive,
	CalendarClock,
	MoreVertical,
	PackageCheck,
	Pencil,
	PhilippinePeso,
	SquareEqual,
	Trash,
	Weight,
} from 'lucide-vue-next'
import { computed } from 'vue'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
	DropdownMenu,
	DropdownMenuContent,
	DropdownMenuItem,
	DropdownMenuSeparator,
	DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Separator } from '@/components/ui/separator'
import type { Supply, VarietyOption } from '@/types/marketplace'

interface Props {
	supply: Supply
	varietyOptions?: Record<string, VarietyOption[]>
}

const props = defineProps<Props>()

const emit = defineEmits<{
	edit: [supply: Supply]
	archive: [supply: Supply]
	fulfill: [supply: Supply]
	delete: [supply: Supply]
}>()

const isOngoing = computed(() => props.supply.status === 'Ongoing')
const isArchived = computed(() => props.supply.status === 'Archived')
const isFulfilled = computed(() => props.supply.status === 'Fulfilled')
</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg ">
        <AspectRatio :ratio="16/9" class="relative overflow-hidden">
            <img 
                v-if="supply.image_url"
                :src="supply.image_url"
                :alt="supply.variety.name.charAt(0)" 
                class="size-full object-cover bg-gray-200"
            />
            <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                {{ supply.created_at_human }}
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
                        @click="emit('edit', supply)"
                    >
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>

                    <DropdownMenuSeparator v-if="isOngoing"/>
                    
                    <DropdownMenuItem
                        v-if="isOngoing || isFulfilled"
                        @click="emit('archive', supply)"
                        class="text-orange-600 dark:text-orange-400"
                    >
                        <Archive class="mr-2 size-4" />
                        Archive
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        v-if="isOngoing || isArchived"
                        @click="emit('fulfill', supply)"
                        class="text-green-500 dark:text-green-400"
                    >
                        <PackageCheck class="mr-2 size-4" />
                        Fulfill
                    </DropdownMenuItem>

                    <DropdownMenuItem
                        @click="emit('delete', supply)"
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
                {{ supply.variety.vegetable }} {{ supply.variety.name }}
            </CardTitle>
            <CardDescription>
                {{ supply.variety.category }}
            </CardDescription>
            <Separator />
        </CardHeader>

        <CardContent class="p-5 pt-2 grid gap-2">
            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <PhilippinePeso :size="15" />
                    Price:
                </div>
                <span>₱{{ supply.offered_price.toFixed(2) }}/kg</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <Weight :size="15" />
                    Kg:
                </div>
                <span>{{ supply.quantity_kg.toFixed(2) }} kg</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <SquareEqual :size="15" />
                    Total:
                </div>
                <span>₱{{ (supply.quantity_kg * supply.offered_price).toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <CalendarClock :size="15" />
                    Schedule:
                </div>
                <p>
                    {{ supply.scheduled_date }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
