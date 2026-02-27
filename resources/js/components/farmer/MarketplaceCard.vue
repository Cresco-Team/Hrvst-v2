<script setup lang="ts">

import { Link } from '@inertiajs/vue3'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { getInitials } from '@/composables/useInitials'
import type { DealerDemand } from '@/types/farmer/marketplace'
import { AspectRatio } from '../ui/aspect-ratio'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '../ui/item'
import { Separator } from '../ui/separator'

interface Props {
  demand: DealerDemand
  href: string
}

const { demand } = defineProps<Props>()

</script>

<template>
  <Link :href="href">
    <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
      <AspectRatio :ratio="16 / 9" class="relative overflow-hidden">
        <img :src="demand.variety.image_url" class="size-full object-cover bg-green-100">

        <Badge class="absolute top-3 right-3">
          {{ demand.transaction_date }}
        </Badge>

        <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white">
          {{ demand.quantity_kg }}kg needed
        </div>
      </AspectRatio>

      <CardHeader class="p-5 py-2">
        <CardTitle>
          <p>{{ demand.variety.vegetable }} {{ demand.variety.name }}</p>
        </CardTitle>
        <CardDescription class="flex justify-between">
          <p>₱ {{ demand.offered_price.toFixed(2) }}<span class="text-xs">/kg</span></p>
          <Badge>{{ demand.price_flag }}</Badge>
        </CardDescription>
        <Separator />
      </CardHeader>

      <CardContent class="space-y-4 p-4 pt-0">
        <Item class="p-0">
          <ItemMedia>
            <Avatar class="size-10">
              <AvatarImage v-if="demand.dealer.image_url" :src="demand.dealer.image_url" />
              <AvatarFallback>{{ getInitials(demand.dealer.name) }}</AvatarFallback>
            </Avatar>
          </ItemMedia>

          <ItemContent>
            <ItemTitle>{{ demand.dealer.name }}</ItemTitle>
            <ItemDescription>{{ demand.dealer.phone_number }}</ItemDescription>
          </ItemContent>
        </Item>
      </CardContent>
    </Card>
  </Link>
</template>
