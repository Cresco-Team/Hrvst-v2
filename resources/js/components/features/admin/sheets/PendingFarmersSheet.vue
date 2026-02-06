<script setup lang="ts">
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { ScrollArea } from '@/components/ui/scroll-area'
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { UserCheck, X, MapPin, Phone, Mail, Calendar, AlertTriangle } from 'lucide-vue-next'
import { useInitials } from '@/composables/useInitials'
import { ref } from 'vue'
import admin from '@/routes/admin'

interface PendingFarmer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        user_image: string | null
    }
    location: {
        province: string
        municipality: string
        barangay: string
        full_address: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    submitted_at: string
    submitted_at_human: string
}

const props = defineProps<{
    farmers: PendingFarmer[]
}>()

const { getInitials } = useInitials()

const open = defineModel<boolean>('open', { default: false })
const rejectDialogOpen = ref(false)
const selectedFarmer = ref<PendingFarmer | null>(null)

const hasPending = computed(() => props.farmers.length > 0)

function handleApprove(farmer: PendingFarmer) {
    router.post(
        `/admin/farmers/${farmer.id}/approve`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                // Sheet will auto-close if no more pending farmers
                if (props.farmers.length <= 1) {
                    open.value = false
                }
            },
        }
    )
}

function openRejectDialog(farmer: PendingFarmer) {
    selectedFarmer.value = farmer
    rejectDialogOpen.value = true
}

function handleReject() {
    if (!selectedFarmer.value) return

    router.delete(`/admin/farmers/${selectedFarmer.value.id}/reject`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpen.value = false
            selectedFarmer.value = null
            
            // Close sheet if no more pending farmers
            if (props.farmers.length <= 1) {
                open.value = false
            }
        },
    })
}
</script>

<template>
    <Sheet v-model:open="open">
        <SheetTrigger as-child>
            <Button variant="outline" class="relative gap-2">
                <UserCheck class="size-4" />
                <span>Pending Approvals</span>
                <Badge
                    v-if="hasPending"
                    variant="destructive"
                    class="absolute -right-2 -top-2 size-6 rounded-full p-0 text-xs"
                >
                    {{ farmers.length }}
                </Badge>
            </Button>
        </SheetTrigger>

        <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-xl">
            <!-- Header -->
            <SheetHeader class="border-b px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <SheetTitle class="flex items-center gap-2">
                            <UserCheck class="size-5 text-primary" />
                            Pending Farmer Approvals
                        </SheetTitle>
                        <SheetDescription class="mt-1">
                            Review and approve or reject farmer registrations
                        </SheetDescription>
                    </div>
                    <Badge variant="secondary" class="shrink-0">
                        {{ farmers.length }} pending
                    </Badge>
                </div>
            </SheetHeader>

            <!-- Content -->
            <ScrollArea class="flex-1">
                <div v-if="farmers.length === 0" class="flex flex-col items-center justify-center p-12 text-center">
                    <UserCheck class="mb-4 size-12 text-muted-foreground" />
                    <h3 class="text-lg font-semibold">No Pending Approvals</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        All farmer registrations have been reviewed
                    </p>
                </div>

                <div v-else class="space-y-4 p-6">
                    <div
                        v-for="farmer in farmers"
                        :key="farmer.id"
                        class="rounded-lg border bg-card p-4 shadow-sm"
                    >
                        <!-- User Info -->
                        <div class="mb-4 flex items-start gap-3">
                            <Avatar class="size-12 rounded-lg">
                                <AvatarImage
                                    v-if="farmer.user.user_image"
                                    :src="farmer.user.user_image"
                                    :alt="farmer.user.name"
                                />
                                <AvatarFallback class="rounded-lg bg-primary/10 text-lg font-semibold text-primary">
                                    {{ getInitials(farmer.user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex-1">
                                <h4 class="font-semibold">{{ farmer.user.name }}</h4>
                                <div class="mt-1 flex flex-col gap-1 text-sm text-muted-foreground">
                                    <div class="flex items-center gap-1.5">
                                        <Mail class="size-3" />
                                        {{ farmer.user.email }}
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Phone class="size-3" />
                                        {{ farmer.user.phone_number }}
                                    </div>
                                </div>
                            </div>
                            <Badge variant="outline" class="shrink-0">
                                <Calendar class="mr-1 size-3" />
                                {{ farmer.submitted_at_human }}
                            </Badge>
                        </div>

                        <!-- Location -->
                        <div class="mb-4 rounded-lg bg-muted/50 p-3">
                            <div class="mb-1.5 flex items-center gap-2 text-sm font-medium">
                                <MapPin class="size-4 text-primary" />
                                Farm Location
                            </div>
                            <p class="text-sm text-muted-foreground">
                                {{ farmer.location.full_address }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                Coordinates: {{ farmer.location.coordinates.lat }}, {{ farmer.location.coordinates.lng }}
                            </p>
                        </div>

                        <!-- Farm Image -->
                        <div v-if="farmer.farm_image" class="mb-4">
                            <p class="mb-2 text-sm font-medium">Farm Image</p>
                            <img
                                :src="farmer.farm_image"
                                :alt="`${farmer.user.name}'s farm`"
                                class="h-48 w-full rounded-lg border object-cover"
                            />
                        </div>

                        <Separator class="my-4" />

                        <!-- Actions -->
                        <div class="flex gap-2">
                            <Button
                                variant="default"
                                class="flex-1 gap-2"
                                @click="handleApprove(farmer)"
                            >
                                <UserCheck class="size-4" />
                                Approve
                            </Button>
                            <Button
                                variant="destructive"
                                class="flex-1 gap-2"
                                @click="openRejectDialog(farmer)"
                            >
                                <X class="size-4" />
                                Reject
                            </Button>
                        </div>
                    </div>
                </div>
            </ScrollArea>
        </SheetContent>
    </Sheet>

    <!-- Reject Confirmation Dialog -->
    <AlertDialog v-model:open="rejectDialogOpen">
        <AlertDialogContent>
            <AlertDialogHeader>
                <div class="flex items-center gap-2">
                    <AlertTriangle class="size-5 text-destructive" />
                    <AlertDialogTitle>Reject Farmer Registration</AlertDialogTitle>
                </div>
                <AlertDialogDescription>
                    Are you sure you want to reject
                    <span class="font-semibold">{{ selectedFarmer?.user.name }}</span>'s registration?
                    This will permanently delete their account and all associated data.
                    This action cannot be undone.
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel>Cancel</AlertDialogCancel>
                <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="handleReject"
                >
                    Reject & Delete
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
