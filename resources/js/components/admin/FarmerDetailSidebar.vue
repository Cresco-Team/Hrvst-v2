<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Calendar1, Info, Mail, MapPinHouse, Phone, Trash, Wheat } from 'lucide-vue-next'
import { ref } from 'vue'
import { destroy, show } from '@/actions/App/Http/Controllers/Admin/FarmerController'
import DetailSheet from '@/components/dialogs/DetailSheet.vue'
import ConfirmationDialog from '@/components/dialogs/ConfirmationDialog.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '@/components/ui/item'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { useInitials } from '@/composables/useInitials'
import type { FarmerResource } from '@/types'

const props = defineProps<{
    open: boolean
    farmer: FarmerResource | null
    loading: boolean
}>()

defineEmits<{
    close: []
}>()

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
            <div class="space-y-2">
                <Skeleton class="h-4 w-24" />
                <Skeleton class="h-4 w-64" />
            </div>
            <Separator />
            <div class="grid grid-cols-2 gap-3">
                <Skeleton class="h-20 rounded-lg" />
                <Skeleton class="h-20 rounded-lg" />
            </div>
            <Separator />
            <div class="space-y-3">
                <Skeleton class="h-4 w-36" />
                <Skeleton v-for="i in 3" :key="i" class="h-24 rounded-lg" />
            </div>
        </div>

        <!-- Farmer Details -->
        <div v-else-if="farmer" class="space-y-6">
            <Item variant="outline">
                <ItemMedia>
                    <Avatar class="size-16">
                        <AvatarImage v-if="farmer.user?.avatar_url" :src="farmer.user.avatar_url"
                            :alt="farmer.user.name" />
                        <AvatarFallback class="bg-primary/10 text-lg font-semibold text-primary">
                            {{ getInitials(farmer.user?.name) }}
                        </AvatarFallback>
                    </Avatar>
                </ItemMedia>
                <ItemContent>
                    <ItemTitle class="text-base font-semibold truncate">{{ farmer.user?.name }}</ItemTitle>
                    <ItemDescription class="flex items-center gap-3">
                        <Calendar1 class="size-4" />
                        Joined {{ farmer.joined_at_human }}
                    </ItemDescription>
                </ItemContent>
            </Item>

            <div class="space-y-2">
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Mail class="size-3.5 text-primary" />
                        <span>Email</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.user?.email }}</p>
                </div>
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <MapPinHouse class="size-3.5 text-primary" />
                        <span>Address</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.location?.full_address }}</p>
                </div>
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Phone class="size-3.5 text-primary" />
                        <span>Phone Number</span>
                    </div>
                    <p class="text-muted-foreground">{{ farmer.user?.phone_number }}</p>
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
                        <Badge>{{ farmer.supplies?.length ?? 0 }}</Badge>
                    </ItemTitle>
                    <ItemDescription class="space-x-2 truncate">
                        <Badge v-for="supply in farmer.supplies" :key="supply.id" class="bg-amber-300">
                            {{ supply.variety?.name }}
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
                <Button class="cursor-pointer" @click="router.visit(show(farmer.id).url)">
                    <Info />
                    More Details
                </Button>
                <Button variant="destructive" class="cursor-pointer" @click="openDeleteDialog">
                    <Trash />
                    Delete
                </Button>
            </div>
        </template>
    </DetailSheet>

    <ConfirmationDialog v-model:open="isDeleteDialogOpen" title="Delete Farmer"
        :description="`Are you sure you want to delete ${farmer?.user?.name}?`" @action="handleDelete"
        variant="destructive" />
</template>
