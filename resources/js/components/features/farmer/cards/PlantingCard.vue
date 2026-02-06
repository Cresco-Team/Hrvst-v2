<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import {
    Pencil,
    Trash2,
    CheckCircle,
    XCircle,
    MoreVertical,
    Calendar,
    Weight,
    Clock,
    Sprout,
} from 'lucide-vue-next'

interface Planting {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_path: string
    }
    weight_kg: number
    date_planted: string
    date_planted_human: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status: 'active' | 'harvested' | 'expired' | 'cancelled'
    status_badge: string
    can_edit: boolean
    can_delete: boolean
    can_harvest: boolean
    can_cancel: boolean
}

defineProps<{
    planting: Planting
}>()

const emit = defineEmits<{
    'open-edit': [planting: Planting]
    'open-harvest': [planting: Planting]
    'open-cancel': [planting: Planting]
    'open-delete': [planting: Planting]
}>()

function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        Growing: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-900',
        Overdue: 'bg-amber-100 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-400 dark:border-amber-900',
        Harvested: 'bg-blue-100 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-400 dark:border-blue-900',
        Expired: 'bg-red-100 text-red-700 border-red-200 dark:bg-red-950/50 dark:text-red-400 dark:border-red-900',
        Cancelled: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-950/50 dark:text-slate-400 dark:border-slate-900',
    }
    return colors[status] || 'bg-slate-100 text-slate-700 border-slate-200'
}

function getCountdownColor(days: number | null) {
    if (days === null) return 'text-muted-foreground'
    if (days < 0) return 'text-red-600 dark:text-red-400'
    if (days === 0) return 'text-amber-600 dark:text-amber-400'
    if (days <= 7) return 'text-orange-600 dark:text-orange-400'
    return 'text-emerald-600 dark:text-emerald-400'
}
</script>

<template>
    <Card class="group relative overflow-hidden border-2 transition-all hover:shadow-lg hover:border-primary/20">
        <!-- Status Badge - Top Right -->
        <div class="absolute right-3 top-3 z-10">
            <Badge 
                :class="getStatusColor(planting.status_badge)"
                class="border font-medium shadow-sm"
            >
                {{ planting.status_badge }}
            </Badge>
        </div>

        <CardContent class="p-0">
            <!-- Image Header -->
            <div class="relative h-48 overflow-hidden bg-gradient-to-br from-primary/5 to-primary/10">
                <img
                    v-if="planting.variety.image_path"
                    :src="planting.variety.image_path"
                    :alt="planting.variety.name"
                    class="size-full object-cover transition-transform duration-500 group-hover:scale-110"
                />
                <div v-else class="flex size-full items-center justify-center">
                    <Sprout class="size-20 text-primary/20" />
                </div>
                
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                
                <!-- Variety Info Overlay -->
                <div class="absolute bottom-0 left-0 right-0 p-4">
                    <h3 class="text-xl font-bold text-white drop-shadow-lg">
                        {{ planting.variety.name }}
                    </h3>
                    <p class="text-sm text-white/90 drop-shadow">
                        {{ planting.variety.category }}
                    </p>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-5 space-y-4">
                <!-- Weight -->
                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                        <Weight class="size-5 text-primary" />
                    </div>
                    <div class="flex-1">
                        <p class="text-xs text-muted-foreground">Total Weight</p>
                        <p class="text-lg font-bold">{{ planting.weight_kg }} kg</p>
                    </div>
                </div>

                <!-- Dates Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <!-- Date Planted -->
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <div class="flex items-center gap-2 mb-1.5">
                            <Calendar class="size-3.5 text-muted-foreground" />
                            <p class="text-xs font-medium text-muted-foreground">Planted</p>
                        </div>
                        <p class="text-sm font-semibold">{{ planting.date_planted }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ planting.date_planted_human }}</p>
                    </div>

                    <!-- Expected Harvest -->
                    <div class="rounded-lg border bg-muted/30 p-3">
                        <div class="flex items-center gap-2 mb-1.5">
                            <Clock class="size-3.5 text-muted-foreground" />
                            <p class="text-xs font-medium text-muted-foreground">Harvest</p>
                        </div>
                        <p class="text-sm font-semibold">{{ planting.expected_harvest_date }}</p>
                        <p 
                            v-if="planting.days_until_harvest !== null" 
                            class="text-xs font-medium mt-0.5"
                            :class="getCountdownColor(planting.days_until_harvest)"
                        >
                            <span v-if="planting.days_until_harvest > 0">
                                {{ planting.days_until_harvest }} days left
                            </span>
                            <span v-else-if="planting.days_until_harvest === 0">
                                Today!
                            </span>
                            <span v-else>
                                {{ Math.abs(planting.days_until_harvest) }} days overdue
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 pt-2">
                    <!-- Quick Actions -->
                    <Button
                        v-if="planting.can_harvest"
                        variant="default"
                        size="sm"
                        class="flex-1 gap-2"
                        @click="$emit('open-harvest', planting)"
                    >
                        <CheckCircle class="size-4" />
                        Harvest
                    </Button>

                    <Button
                        v-if="planting.can_edit"
                        variant="outline"
                        size="sm"
                        class="flex-1 gap-2"
                        @click="$emit('open-edit', planting)"
                    >
                        <Pencil class="size-4" />
                        Edit
                    </Button>

                    <!-- More Actions Dropdown -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" size="icon-sm">
                                <MoreVertical class="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem
                                v-if="planting.can_edit"
                                @click="$emit('open-edit', planting)"
                            >
                                <Pencil class="mr-2 size-4" />
                                Edit Weight
                            </DropdownMenuItem>
                            
                            <DropdownMenuItem
                                v-if="planting.can_harvest"
                                @click="$emit('open-harvest', planting)"
                                class="text-green-600 dark:text-green-400"
                            >
                                <CheckCircle class="mr-2 size-4" />
                                Mark as Harvested
                            </DropdownMenuItem>
                            
                            <DropdownMenuItem
                                v-if="planting.can_cancel"
                                @click="$emit('open-cancel', planting)"
                                class="text-orange-600 dark:text-orange-400"
                            >
                                <XCircle class="mr-2 size-4" />
                                Cancel Planting
                            </DropdownMenuItem>

                            <DropdownMenuSeparator v-if="planting.can_delete" />
                            
                            <DropdownMenuItem
                                v-if="planting.can_delete"
                                @click="$emit('open-delete', planting)"
                                class="text-destructive"
                            >
                                <Trash2 class="mr-2 size-4" />
                                Delete
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
