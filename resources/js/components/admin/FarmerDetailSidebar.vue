<script setup lang="ts">

import { router } from '@inertiajs/vue3'
import { Phone, Mail, Trash, Info, MapPinHouse, Calendar1, Wheat } from 'lucide-vue-next'
import { ref } from 'vue'
import { destroy, show } from '@/actions/App/Http/Controllers/Admin/FarmerController'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import DetailSheet from '@/components/DetailSheet.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { useInitials } from '@/composables/useInitials'
import type { FarmerDetails } from '@/types/admin/farmers'

const props = defineProps<{
    open: boolean
    farmer: FarmerDetails | null
    loading: boolean
}>()

defineEmits<{
    close: []
}>()

/* Dialog states */
const isDeleteDialogOpen = ref(false)

const openDeleteDialog = () => {
    isDeleteDialogOpen.value = true
}

const handleDelete = () => {
    if (!props.farmer) return

    router.delete(destroy(props.farmer.id).url)
}

const { getInitials } = useInitials()
</script>

<template>
    <DetailSheet :open="open" title="Farmer Details" @update:open="!$event && $emit('close')">
        <!-- Loading Skeleton -->
        <div v-if="loading" class="space-y-6">
            <!-- User Info Skeleton -->
            <div class="flex items-start gap-4">
                <Skeleton class="size-16 rounded-lg shrink-0" />
                <div class="flex-1 space-y-2">
                    <Skeleton class="h-5 w-40" />
                    <Skeleton class="h-4 w-56" />
                    <Skeleton class="h-4 w-36" />
                    <Skeleton class="h-4 w-32" />
                </div>
            </div>

            <Separator />

            <!-- Location Skeleton -->
            <div class="space-y-2">
                <Skeleton class="h-4 w-24" />
                <Skeleton class="h-4 w-64" />
            </div>

            <Separator />

            <!-- Stats Skeleton -->
            <div class="grid grid-cols-2 gap-3">
                <Skeleton class="h-20 rounded-lg" />
                <Skeleton class="h-20 rounded-lg" />
            </div>

            <Separator />

            <!-- Supplies Skeleton -->
            <div class="space-y-3">
                <Skeleton class="h-4 w-36" />
                <Skeleton v-for="i in 3" :key="i" class="h-24 rounded-lg" />
            </div>
        </div>

        <!-- Farmer Details -->
        <div v-else-if="farmer" class="space-y-6">
            <!-- User Info -->
            <Item variant="outline">
                <ItemMedia>
                    <Avatar class="size-16">
                        <AvatarImage v-if="farmer.user.image_url" :src="farmer?.user.image_url"
                            :alt="farmer.user.name" />
                        <AvatarFallback class="bg-primary/10 text-lg font-semibold text-primary">
                            {{ getInitials(farmer.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                </ItemMedia>

                <ItemContent>
                    <ItemTitle class="text-base font-semibold truncate">{{ farmer.user.name }}</ItemTitle>
                    <ItemDescription class="flex items-center gap-3">
                        <Calendar1 class="size-4"/>
                        Joined {{ farmer.joined_at_human }}
                    </ItemDescription>
                </ItemContent>
            </Item>

            <!-- More Info -->
            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Mail class="size-3.5 text-primary" />
                        <span>Email</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.user.email }}</p>
                </div>

                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <MapPinHouse class="size-3.5 text-primary" />
                        <span>Address</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.location.full_address }}</p>
                </div>

                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Phone class="size-3.5 text-primary" />
                        <span>Phone Number</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.user.phone_number }}</p>
                </div>
            </div>

            <Separator />

            <Item>
                <ItemMedia variant="icon" class="bg-primary/10 text-primary">
                    <Wheat />
                </ItemMedia>
                <ItemContent>
                    <ItemTitle class="flex justify-between w-full">
                        <p>Ongoing Supplies</p>
                        <Badge>{{ farmer.ongoing_supplies.length }}</Badge>
                    </ItemTitle>
                    <ItemDescription class="space-x-2 truncate">
                        <Badge v-for="supply in farmer.ongoing_supplies" :key="supply.id" class="bg-amber-300">
                            {{ supply.variety.name }}
                        </Badge>
                    </ItemDescription>
                </ItemContent>
            </Item>
        </div>
        <template #footer>
            <template v-if="loading">
                <Skeleton />
            </template>
            <div v-else-if="farmer" class="flex justify-end gap-3">
                <Button @click="router.visit(show(farmer?.id).url)" class="cursor-pointer">
                    <Info />
                    More Details
                </Button>
                <Button @click="openDeleteDialog" variant="destructive" class="cursor-pointer">
                    <Trash />
                    Delete
                </Button>                
            </div>
        </template>
    </DetailSheet>

    <ConfirmationDialog v-model:open="isDeleteDialogOpen" title="Delete Farmer"
        :description="`Are you sure you want to delete ${farmer?.user.name}?`" @action="handleDelete"
        variant="destructive" />
</template>
