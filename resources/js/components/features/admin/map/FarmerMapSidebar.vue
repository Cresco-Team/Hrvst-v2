<script setup lang="ts">
import { computed } from 'vue'
import { X, MapPin, Phone, Mail, Calendar, Sprout, Weight, Clock, TrendingUp } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Separator } from '@/components/ui/separator'
import { Spinner } from '@/components/ui/spinner'
import { useInitials } from '@/composables/useInitials'
import { FarmerDetails } from '@/types/admin/farmers'

const props = defineProps<{
    open: boolean
    farmer: FarmerDetails | null
    loading: boolean
}>()

const emit = defineEmits<{
    close: []
}>()

const { getInitials } = useInitials()

</script>

<template>
    <!-- Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-300"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-300"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm"
            @click="emit('close')"
        />
    </Transition>

    <!-- Sidebar -->
    <Transition
        enter-active-class="transition-transform duration-300"
        enter-from-class="translate-x-full"
        enter-to-class="translate-x-0"
        leave-active-class="transition-transform duration-300"
        leave-from-class="translate-x-0"
        leave-to-class="translate-x-full"
    >
        <div
            v-if="open"
            class="fixed top-0 right-0 z-50 h-screen w-full overflow-y-auto bg-background shadow-2xl sm:w-[480px]"
        >
            <!-- Header with Close Button -->
            <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-background/95 p-4 backdrop-blur">
                <h2 class="text-lg font-semibold">Farmer Details</h2>
                <Button
                    variant="ghost"
                    size="icon-sm"
                    class="rounded-full"
                    @click="emit('close')"
                >
                    <X class="size-4" />
                </Button>
            </div>

            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center p-12">
                <Spinner class="size-8" />
            </div>

            <!-- Farmer Details -->
            <div v-else-if="farmer" class="space-y-6 p-4">
                <!-- User Info -->
                <div class="flex items-start gap-4">
                    <Avatar class="size-16 rounded-lg">
                        <AvatarImage 
                            v-if="farmer.user.image_path"
                            :src="farmer.user.image_path" 
                            :alt="farmer.user.name"
                        />
                        <AvatarFallback class="rounded-lg bg-primary/10 text-lg font-semibold text-primary">
                            {{ getInitials(farmer.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex-1 space-y-1">
                        <h3 class="text-xl font-semibold">{{ farmer.user.name }}</h3>
                        <div class="flex flex-col gap-1.5 text-sm text-muted-foreground">
                            <div class="flex items-center gap-1.5">
                                <Mail class="size-3.5" />
                                {{ farmer.user.email }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Phone class="size-3.5" />
                                {{ farmer.user.phone_number }}
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Calendar class="size-3.5" />
                                Joined {{ farmer.joined_at_human }}
                            </div>
                        </div>
                    </div>
                </div>

                <Separator />

                <!-- Location -->
                <div class="space-y-2">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <MapPin class="size-4 text-primary" />
                        Location
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ farmer.location.full_address }}
                    </p>
                    <div class="text-xs text-muted-foreground">
                        {{ farmer.location.coordinates?.lat }}, {{ farmer.location.coordinates?.lng }}
                    </div>
                </div>

                <Separator />

                <!-- Statistics -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="rounded-lg border bg-card p-3 text-center">
                        <div class="flex items-center justify-center gap-1 text-2xl font-bold">
                            <Sprout class="size-5 text-primary" />
                            {{ farmer.statistics.total_available_offerings }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Active</p>
                    </div>
                    <div class="rounded-lg border bg-card p-3 text-center">
                        <div class="flex items-center justify-center gap-1 text-2xl font-bold">
                            <Weight class="size-5 text-primary" />
                            {{ farmer.statistics.total_weight }}
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Total kg</p>
                    </div>
                </div>

                <Separator />

                <!-- Active Plantings -->
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-medium">
                        <TrendingUp class="size-4 text-primary" />
                        Active Plantings ({{ farmer.available_offerings.length }})
                    </div>

                    <div v-if="farmer.available_offerings.length === 0" class="rounded-lg border border-dashed p-6 text-center">
                        <p class="text-sm text-muted-foreground">No active plantings</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="offering in farmer.available_offerings"
                            :key="offering.id"
                            class="flex gap-3 rounded-lg border bg-card p-3 transition-colors hover:bg-accent"
                        >
                            <Avatar class="size-12 rounded-md shrink-0">
                                <AvatarImage 
                                    :src="offering.variety.image_path" 
                                    :alt="offering.variety.name"
                                    class="object-cover"
                                />
                                <AvatarFallback class="rounded-md bg-primary/10 text-xs font-semibold text-primary">
                                    {{ offering.variety.name.charAt(0) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex flex-1 flex-col gap-1.5">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <p class="text-sm font-medium">{{ offering.variety.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ offering.variety.category }}</p>
                                    </div>
                                </div>
                                <div class="space-y-0.5 text-xs text-muted-foreground">
                                    <div>Weight: {{ offering.weight_kg }} kg</div>
                                    <div>Planted: {{ offering.date_planted }}</div>
                                    <div>Expected: {{ offering.expected_harvest_date }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
