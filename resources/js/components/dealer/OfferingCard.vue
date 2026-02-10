<script setup lang="ts">
import { computed } from 'vue'
import { MapPin, Calendar, Package, DollarSign, User } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import type { FarmerOffering } from '@/types/announcement'

interface Props {
  offering: FarmerOffering
}

const props = defineProps<Props>()

const urgencyVariant = computed(() => {
  const days = props.offering.days_until_expiration
  if (days === null) return 'outline'
  if (days <= 3) return 'destructive'
  if (days <= 7) return 'default'
  return 'secondary'
})

const urgencyLabel = computed(() => {
  const days = props.offering.days_until_expiration
  if (days === null) return 'Expired'
  if (days === 0) return 'Expires today'
  if (days === 1) return 'Expires tomorrow'
  return `Expires in ${days} days`
})

const location = computed(() => {
  if (typeof props.offering.farmer.location === 'string') {
    return props.offering.farmer.location
  }
  return props.offering.farmer.location.municipality
})

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}
</script>

<template>
  <Card class="group overflow-hidden transition-all hover:shadow-lg">
    <div class="relative aspect-4/3 overflow-hidden bg-muted">
      <img
        v-if="offering.image_url"
        :src="offering.image_url"
        :alt="offering.variety.name"
        class="size-full object-cover transition-transform group-hover:scale-105"
      />
      <div
        v-else
        class="flex size-full items-center justify-center bg-linear-to-br from-primary/10 to-primary/5"
      >
        <Package class="size-16 text-muted-foreground/50" />
      </div>

      <!-- Urgency badge -->
      <div class="absolute right-2 top-2">
        <Badge :variant="urgencyVariant" class="shadow-lg">
          {{ urgencyLabel }}
        </Badge>
      </div>

      <!-- Category badge -->
      <div class="absolute left-2 top-2">
        <Badge variant="secondary" class="shadow-lg">
          {{ offering.variety.category }}
        </Badge>
      </div>
    </div>

    <CardContent class="space-y-4 p-4">
      <!-- Variety name -->
      <div>
        <h3 class="text-lg font-bold">{{ offering.variety.name }}</h3>
        <p class="text-sm text-muted-foreground">{{ offering.created_at_human }}</p>
      </div>

      <!-- Farmer info -->
      <div class="flex items-center gap-2">
        <Avatar class="size-8">
          <AvatarImage v-if="offering.farmer.user_image" :src="offering.farmer.user_image" />
          <AvatarFallback>{{ getInitials(offering.farmer.name) }}</AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium">{{ offering.farmer.name }}</p>
          <div class="flex items-center gap-1 text-xs text-muted-foreground">
            <MapPin class="size-3" />
            <span class="truncate">{{ location }}</span>
          </div>
        </div>
      </div>

      <!-- Stats grid -->
      <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="flex items-center gap-2 rounded-lg bg-muted/50 p-2">
          <Package class="size-4 shrink-0 text-muted-foreground" />
          <div>
            <p class="text-xs text-muted-foreground">Quantity</p>
            <p class="font-semibold">{{ offering.quantity_kg }} kg</p>
          </div>
        </div>

        <div class="flex items-center gap-2 rounded-lg bg-muted/50 p-2">
          <DollarSign class="size-4 shrink-0 text-muted-foreground" />
          <div>
            <p class="text-xs text-muted-foreground">Price</p>
            <p class="font-semibold">₱{{ offering.price_asking }}/kg</p>
          </div>
        </div>
      </div>

      <!-- Expiration -->
      <div class="flex items-center gap-2 text-xs text-muted-foreground">
        <Calendar class="size-3" />
        <span>Expires {{ offering.expiration_date }}</span>
      </div>
    </CardContent>
  </Card>
</template>
