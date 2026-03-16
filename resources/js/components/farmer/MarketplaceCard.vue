<script setup lang="ts">

import { Badge } from '@/components/ui/badge'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { DealerDemand } from '@/types/farmer/marketplace'
import { AspectRatio } from '../ui/aspect-ratio'

interface Props {
  demand: DealerDemand
}

const { demand } = defineProps<Props>()

</script>

<template>
  <Card class="py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
    <AspectRatio :ratio="16 / 9" class="relative overflow-hidden">
      <img v-if="demand.variety.image_url" :src="demand.variety.image_url" class="size-full object-cover bg-green-100">

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
    </CardHeader>
  </Card>
</template>
