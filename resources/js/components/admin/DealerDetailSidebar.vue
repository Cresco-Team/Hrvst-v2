<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Calendar1, Info, Mail, Phone, Trash, Wheat } from 'lucide-vue-next'
import { ref } from 'vue'
import {
	destroy,
	show,
} from '@/actions/App/Http/Controllers/Admin/DealerController'
import ConfirmationDialog from '@/components/ConfirmationDialog.vue'
import DetailSheet from '@/components/DetailSheet.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
	Item,
	ItemContent,
	ItemDescription,
	ItemMedia,
	ItemTitle,
} from '@/components/ui/item'
import { Separator } from '@/components/ui/separator'
import { Skeleton } from '@/components/ui/skeleton'
import { useInitials } from '@/composables/useInitials'
import type { Detail } from '@/types/admin/dealers'

const props = defineProps<{
	open: boolean
	dealer: Detail | null
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
	if (!props.dealer) return

	router.delete(destroy(props.dealer.id).url)
}

const { getInitials } = useInitials()
</script>

<template>
    <DetailSheet :open="open" title="Dealer Details" @update:open="!$event && $emit('close')">
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

        <!-- dealer Details -->
        <div v-else-if="dealer" class="space-y-6">
            <!-- User Info -->
            <Item variant="outline">
                <ItemMedia>
                    <Avatar class="size-16">
                        <AvatarImage v-if="dealer.user.avatar_url" :src="dealer?.user.avatar_url"
                            :alt="dealer.user.name" />
                        <AvatarFallback class="bg-primary/10 text-lg font-semibold text-primary">
                            {{ getInitials(dealer.user.name) }}
                        </AvatarFallback>
                    </Avatar>
                </ItemMedia>

                <ItemContent>
                    <ItemTitle class="text-base font-semibold truncate">{{ dealer.user.name }}</ItemTitle>
                    <ItemDescription class="flex items-center gap-3">
                        <Calendar1 class="size-4"/>
                        Joined {{ dealer.joined_at_human }}
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
                    <p class="text-muted-foreground">{{ dealer.user.email }}</p>
                </div>
                <div class="flex justify-between text-sm">
                    <div class="flex items-center gap-1.5">
                        <Phone class="size-3.5 text-primary" />
                        <span>Phone Number</span>
                    </div>
                    <p class="text-muted-foreground">{{ dealer.user.phone_number }}</p>
                </div>
            </div>

            <Separator />

            <Item>
                <ItemMedia variant="icon" class="bg-primary/10 text-primary">
                    <Wheat />
                </ItemMedia>
                <ItemContent>
                    <ItemTitle class="flex justify-between w-full">
                        <p>Ongoing Demands</p>
                        <Badge>{{ dealer.demands.length }}</Badge>
                    </ItemTitle>
                    <ItemDescription class="space-x-2 truncate">
                        <Badge v-for="demand in dealer.demands" :key="demand.id" class="bg-amber-300">
                            {{ demand.variety.name }}
                        </Badge>
                    </ItemDescription>
                </ItemContent>
            </Item>
        </div>
        <template #footer>
            <template v-if="loading">
                <Skeleton />
            </template>
            <div v-else-if="dealer" class="flex justify-end gap-3">
                <Button @click="router.visit(show(dealer?.id).url)" class="cursor-pointer">
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

    <ConfirmationDialog v-model:open="isDeleteDialogOpen" title="Delete dealer"
        :description="`Are you sure you want to delete ${dealer?.user.name}?`" @action="handleDelete"
        variant="destructive" />
</template>
