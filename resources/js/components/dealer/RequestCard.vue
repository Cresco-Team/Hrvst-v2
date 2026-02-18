<script setup lang="ts">
import { computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Archive, PhilippinePeso, Weight, CalendarClock, Pencil, MoreVertical, Trash } from 'lucide-vue-next'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Separator } from '@/components/ui/separator'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Request } from '@/types/dealer/requests'

interface Props {
  request: Request
}

const props = defineProps<Props>()

const emit = defineEmits<{
  edit: [request: Request]
  archive: [request: Request]
  delete: [request: Request]
}>()

const isAvailable = computed(() => props.request.status === 'available')
const isArchived = computed(() => props.request.status === 'archived')
</script>

<template>
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg ">
        <AspectRatio :ratio="16/9" class="relative overflow-hidden">
            <img 
                :src="request.variety.image_url" 
                :alt="request.variety.name" 
                class="size-full object-cover bg-gray-200"
            />
            <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
                {{ request.created_at_human }}
            </div>

            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="icon-sm" class="absolute top-3 right-3">
                        <MoreVertical class="size-4" />
                    </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent v-if="isAvailable" align="end">
                    <DropdownMenuItem
                        v-if="isAvailable"
                        @click="emit('edit', request)"
                    >
                        <Pencil class="mr-2 size-4" />
                        Edit Details
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />
                    
                    <DropdownMenuItem
                        v-if="isAvailable"
                        @click="emit('archive', request)"
                        class="text-orange-600 dark:text-orange-400"
                    >
                        <Archive class="mr-2 size-4" />
                        Archive
                    </DropdownMenuItem>
                </DropdownMenuContent>

                <DropdownMenuContent v-if="isArchived" align="end">
                    <DropdownMenuItem
                        v-if="isArchived"
                        @click="emit('delete', request)"
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
                {{ request.variety.name }}
            </CardTitle>
            <CardDescription class="flex justify-between">
                <p>{{ request.variety.vegetable }}</p>
                <Badge>
                    {{ request.quantity_kg }} kg
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
                <span>{{ request.price_offered.toFixed(2) }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <Weight :size="15" />
                    Kg:
                </div>
                <span>{{ request.quantity_kg }}</span>
            </div>

            <div class="flex justify-between text-sm">
                <div class="flex items-center text-muted-foreground gap-2">
                    <CalendarClock :size="15" />
                    Transact:
                </div>
                <p>
                    {{ request.transaction_date }}
                    <span class="text-xs text-muted-foreground">
                        ({{ request.days_until_transaction }} days)
                    </span>
                </p>
            </div>
        </CardContent>
    </Card>
</template>
