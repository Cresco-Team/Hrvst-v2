<script setup lang="ts">
import { computed } from 'vue'
import { MapPin, Calendar, Package, MessageSquare, Bookmark } from 'lucide-vue-next'
import { Card, CardContent, CardFooter } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip'

interface Planting {
    id: number
    variety: {
        id: number
        name: string
        image_path: string
        category: {
            id: number
            name: string
        }
    }
    farmer: {
        id: number
        name: string
        location: {
            barangay: string
            municipality: string
            province: string
            full: string
        }
    }
    weight_kg: number
    harvest_date: string
    harvest_date_human: string
    days_until_harvest: number | null
    urgency: 'normal' | 'soon' | 'overdue'
}

const props = defineProps<{
    planting: Planting
}>()

const urgencyColor = computed(() => {
    switch (props.planting.urgency) {
        case 'overdue':
            return 'border-red-500 bg-red-50 dark:bg-red-950/20'
        case 'soon':
            return 'border-orange-500 bg-orange-50 dark:bg-orange-950/20'
        default:
            return 'border-green-500 bg-green-50 dark:bg-green-950/20'
    }
})

const urgencyText = computed(() => {
    const days = props.planting.days_until_harvest
    if (days === null) return null
    
    if (days < 0) {
        return `${Math.abs(days)} days overdue`
    } else if (days === 0) {
        return 'Harvest today'
    } else if (days <= 6) {
        return `${days} days away`
    }
    return null
})

const categoryColor = computed(() => {
    const category = props.planting.variety.category.name
    const colors: Record<string, string> = {
        'Leafy Vegetables': 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'Root Vegetables': 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
        'Fruiting Vegetables': 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'Bulb Vegetables': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'Legumes': 'bg-lime-100 text-lime-800 dark:bg-lime-900/30 dark:text-lime-400',
        'Brassicas': 'bg-teal-100 text-teal-800 dark:bg-teal-900/30 dark:text-teal-400',
    }
    return colors[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400'
})

function handleContactFarmer() {
    // TODO: Open conversation modal
    console.log('Contact farmer:', props.planting.farmer.id)
}

function handleSaveForLater() {
    // TODO: Add to watchlist
    console.log('Save planting:', props.planting.id)
}
</script>

<template>
    <Card class="group overflow-hidden transition-all hover:shadow-lg">
        <div class="relative aspect-4/3 overflow-hidden bg-muted">
            <img
                :src="planting.variety.image_path"
                :alt="planting.variety.name"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            
            <!-- Urgency Badge (if applicable) -->
            <div
                v-if="urgencyText"
                class="absolute right-2 top-2 rounded-full border-2 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm"
                :class="urgencyColor"
            >
                {{ urgencyText }}
            </div>
        </div>

        <CardContent class="space-y-3 p-4">
            <!-- Variety Name & Category -->
            <div>
                <h3 class="text-lg font-bold leading-tight">
                    {{ planting.variety.name }}
                </h3>
                <Badge :class="categoryColor" class="mt-1.5 text-xs">
                    {{ planting.variety.category.name }}
                </Badge>
            </div>

            <!-- Farmer Info -->
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <Avatar class="size-6 rounded-md">
                    <AvatarFallback class="rounded-md text-xs">
                        {{ planting.farmer.name.charAt(0) }}
                    </AvatarFallback>
                </Avatar>
                <span class="font-medium text-foreground">{{ planting.farmer.name }}</span>
            </div>

            <!-- Location -->
            <div class="flex items-start gap-2 text-sm text-muted-foreground">
                <MapPin class="mt-0.5 size-4 shrink-0" />
                <span>{{ planting.farmer.location.full }}</span>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-2 gap-3 rounded-lg bg-muted/50 p-3">
                <div class="flex items-center gap-2">
                    <Package class="size-4 text-primary" />
                    <div>
                        <p class="text-xs text-muted-foreground">Quantity</p>
                        <p class="font-semibold">{{ planting.weight_kg }} kg</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Calendar class="size-4 text-primary" />
                    <div>
                        <p class="text-xs text-muted-foreground">Harvest</p>
                        <TooltipProvider :delay-duration="200">
                            <Tooltip>
                                <TooltipTrigger as-child>
                                    <p class="cursor-help font-semibold">
                                        {{ planting.harvest_date_human }}
                                    </p>
                                </TooltipTrigger>
                                <TooltipContent>
                                    <p class="text-xs">{{ planting.harvest_date }}</p>
                                </TooltipContent>
                            </Tooltip>
                        </TooltipProvider>
                    </div>
                </div>
            </div>
        </CardContent>

        <CardFooter class="grid grid-cols-2 gap-2 border-t p-4">
            <Button @click="handleContactFarmer" class="gap-2">
                <MessageSquare class="size-4" />
                Contact
            </Button>
            <Button variant="outline" @click="handleSaveForLater" class="gap-2">
                <Bookmark class="size-4" />
                Save
            </Button>
        </CardFooter>
    </Card>
</template>