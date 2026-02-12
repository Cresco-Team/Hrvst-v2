<script setup lang="ts">
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
    Archive,
    MoreVertical,
    Calendar,
    Weight,
    DollarSign,
    Sprout,
} from 'lucide-vue-next'
import type { Planting } from '@/types/farmer/garden'

defineProps<{
    planting: Planting
}>()

const emit = defineEmits<{
    'open-edit': [planting: Planting]
    'open-archive': [planting: Planting]
    'open-delete': [planting: Planting]
}>()

function getStatusColor(status: string) {
    const colors: Record<string, string> = {
        available: 'bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-400 dark:border-emerald-900',
        archived: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-950/50 dark:text-slate-400 dark:border-slate-900',
    }
    return colors[status] || 'bg-slate-100 text-slate-700 border-slate-200'
}

function getExpirationColor(days: number | null) {
    if (days === null) return 'text-muted-foreground'
    if (days < 0) return 'text-red-600 dark:text-red-400'
    if (days === 0) return 'text-amber-600 dark:text-amber-400'
    if (days <= 7) return 'text-orange-600 dark:text-orange-400'
    return 'text-emerald-600 dark:text-emerald-400'
}

function formatDaysRemaining(days: number | null): string {
    if (days === null) return 'Archived'
    if (days < 0) return `${Math.abs(days)} days overdue`
    if (days === 0) return 'Expires today!'
    return `${days} days left`
}
</script>

<template>
    <Card class="group relative overflow-hidden border-2 transition-all hover:shadow-lg hover:border-primary/20">
        <!-- Status Badge - Top Right -->
        <div class="absolute right-3 top-3 z-10">
            <Badge 
                :class="getStatusColor(planting.status)"
                class="border font-medium shadow-sm capitalize"
            >
                {{ planting.status }}
            </Badge>
        </div>

        <CardContent class="p-0">
            <!-- Image Header -->
            <div class="relative h-48 overflow-hidden bg-gradient-to-br from-primary/5 to-primary/10">
                <img
                    v-if="planting.variety.image_url"
                    :src="planting.variety.image_url"
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
                <!-- Weight & Price -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                            <Weight class="size-5 text-primary" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground">Weight</p>
                            <p class="text-lg font-bold">{{ planting.weight_kg }} kg</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30">
                            <DollarSign class="size-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="flex-1">
                            <p class="text-xs text-muted-foreground">Price/kg</p>
                            <p class="text-lg font-bold">₱{{ planting.asking_price }}</p>
                        </div>
                    </div>
                </div>

                <!-- Expiration -->
                <div class="rounded-lg border bg-muted/30 p-3">
                    <div class="flex items-center gap-2 mb-1.5">
                        <Calendar class="size-3.5 text-muted-foreground" />
                        <p class="text-xs font-medium text-muted-foreground">Expires</p>
                    </div>
                    <p class="text-sm font-semibold">{{ planting.expiration_date }}</p>
                    <p 
                        class="text-xs font-medium mt-0.5"
                        :class="getExpirationColor(planting.days_until_expiration)"
                    >
                        {{ formatDaysRemaining(planting.days_until_expiration) }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-2 pt-2">
                    <!-- Edit Button (available only) -->
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

                    <!-- Archive Button (available only) -->
                    <Button
                        v-if="planting.can_archive"
                        variant="outline"
                        size="sm"
                        class="flex-1 gap-2"
                        @click="$emit('open-archive', planting)"
                    >
                        <Archive class="size-4" />
                        Archive
                    </Button>

                    <!-- Delete Button (archived only) -->
                    <Button
                        v-if="planting.can_delete"
                        variant="destructive"
                        size="sm"
                        class="flex-1 gap-2"
                        @click="$emit('open-delete', planting)"
                    >
                        <Trash2 class="size-4" />
                        Delete
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
                                Edit Details
                            </DropdownMenuItem>
                            
                            <DropdownMenuItem
                                v-if="planting.can_archive"
                                @click="$emit('open-archive', planting)"
                                class="text-orange-600 dark:text-orange-400"
                            >
                                <Archive class="mr-2 size-4" />
                                Archive Planting
                            </DropdownMenuItem>

                            <DropdownMenuSeparator v-if="planting.can_delete" />
                            
                            <DropdownMenuItem
                                v-if="planting.can_delete"
                                @click="$emit('open-delete', planting)"
                                class="text-destructive"
                            >
                                <Trash2 class="mr-2 size-4" />
                                Delete Permanently
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
