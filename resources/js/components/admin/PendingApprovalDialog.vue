<script setup lang="ts">
import { Check, Mail, MapPin, Phone, X } from 'lucide-vue-next'
import LeafletMap from '@/components/LeafletMap.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'
import { getInitials } from '@/composables/useInitials'
import type { PendingDealer, PendingFarmer } from '@/types/resources/profile'
import ActionDialog from '../ActionDialog.vue'

const props = defineProps<{
  open: boolean
  item: PendingFarmer | PendingDealer | null
  type: 'farmer' | 'dealer'
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  approve: [id: number]
  reject: [id: number]
}>()

function isFarmer(item: PendingFarmer | PendingDealer): item is PendingFarmer {
  return props.type === 'farmer'
}

function onApprove() {
  if (!props.item) return
  emit('approve', props.item.id)
  emit('update:open', false)
}

function onReject() {
  if (!props.item) return
  emit('reject', props.item.id)
  emit('update:open', false)
}
</script>

<template>
  <ActionDialog :title="`${type === 'farmer' ? 'Farmer' : 'Dealer'} Application`"
    description="Review all details before accepting or rejecting this application." :open="open"
    @update:open="emit('update:open', $event)">
    <template v-if="item">
      <!-- User identity -->
      <div class="flex items-center gap-4">
        <Avatar class="size-14">
          <AvatarImage v-if="item.user.image_path" :src="item.user.image_path" :alt="item.user.name" />
          <AvatarFallback class="bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
            {{ getInitials(item.user.name) }}
          </AvatarFallback>
        </Avatar>

        <div class="flex flex-col gap-1">
          <p class="text-base font-semibold leading-none">{{ item.user.name }}</p>
          <Badge variant="outline" class="w-fit capitalize">{{ type }}</Badge>
        </div>
      </div>

      <Separator />

      <!-- Contact details -->
      <div class="flex flex-col gap-2 text-sm text-muted-foreground">
        <div class="flex items-center gap-2">
          <Mail class="size-4 shrink-0" />
          <span>{{ item.user.email }}</span>
        </div>
        <div class="flex items-center gap-2">
          <Phone class="size-4 shrink-0" />
          <span>{{ item.user.phone_number }}</span>
        </div>
      </div>

      <!-- Farmer-specific details -->
      <template v-if="isFarmer(item)">
        <Separator />

        <div class="flex flex-col gap-3">
          <div class="flex items-start gap-2 text-sm text-muted-foreground">
            <MapPin class="mt-0.5 size-4 shrink-0" />
            <span>{{ item.location.full_address }}</span>
          </div>

          <LeafletMap :lat="item.location.coordinates.lat" :lng="item.location.coordinates.lng" :markers="[{
            lat: item.location.coordinates.lat,
            lng: item.location.coordinates.lng,
            popup: item.location.full_address,
          }]" />

          <div v-if="item.farm_image" class="overflow-hidden rounded-md border">
            <img :src="item.farm_image" alt="Farm photo" class="h-48 w-full object-cover" />
          </div>
          <p v-else class="text-sm italic text-muted-foreground">No farm image provided.</p>
        </div>
      </template>

      <!-- Dealer-specific details -->
      <template v-else>
        <Separator />

        <div class="flex flex-col gap-2">
          <p class="text-sm font-medium">Business Document</p>
          <div v-if="(item as PendingDealer).document_image" class="overflow-hidden rounded-md border">
            <img :src="(item as PendingDealer).document_image!" alt="Business document"
              class="h-48 w-full object-cover" />
          </div>
          <p v-else class="text-sm italic text-muted-foreground">No document image provided.</p>
        </div>
      </template>

      <Separator />

      <!-- Submission timestamp -->
      <p class="text-xs text-muted-foreground">
        Submitted {{ item.submitted_at_human }} &mdash; {{ item.submitted_at }}
      </p>
    </template>

    <template #footer-actions>
      <Button variant="destructive" class="gap-1.5" @click="onReject">
        <X class="size-4" />
        Reject
      </Button>
      <Button class="gap-1.5" @click="onApprove">
        <Check class="size-4" />
        Accept
      </Button>
    </template>
  </ActionDialog>
</template>
