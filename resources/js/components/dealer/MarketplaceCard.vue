<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { Supply } from '@/types/dealer/marketplace'
import { AspectRatio } from '../ui/aspect-ratio'

interface Props {
	supply: Supply
}

const { supply } = defineProps<Props>()
</script>

<template>
  <Card class=" py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
    <AspectRatio :ratio="16/9" class="relative overflow-hidden">
      <img
        :src="supply.image_url"
        :alt="supply.variety.name"
        class="size-full object-cover transition-transform"
      />

      <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
          {{ supply.quantity_kg }} kg
      </div>
    </AspectRatio>

    <CardHeader class="p-5 py-2">
      <CardTitle>
          {{ supply.variety.name }}
      </CardTitle>
      <CardDescription class="flex justify-between">
        <p>{{ supply.variety.vegetable }}</p>
          <Badge>
            ₱ {{ supply.offered_price.toFixed(2) }}
          </Badge>
      </CardDescription>
    </CardHeader>
  </Card>
</template>
