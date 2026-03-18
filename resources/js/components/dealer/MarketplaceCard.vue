<script setup lang="ts">
import { CalendarClock, PhilippinePeso, SquareEqual, Weight } from 'lucide-vue-next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import type { Supply } from '@/types/marketplace';
import { AspectRatio } from '../ui/aspect-ratio'
import { Separator } from '../ui/separator';

interface Props {
  supply: Supply
}

const { supply } = defineProps<Props>()
</script>

<template>
  <Card class=" py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
    <AspectRatio :ratio="16 / 9" class="relative overflow-hidden">
      <img :src="supply.image_url" :alt="supply.variety.name" class="size-full object-cover transition-transform" />

      <div
        class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
        {{ supply.price_flag }} Price
      </div>
    </AspectRatio>

    <CardHeader class="p-5 py-2">
      <CardTitle class="line-clamp-1">
        {{ supply.variety.vegetable }} {{ supply.variety.name }}
      </CardTitle>
      <CardDescription class="flex justify-between">
        <p>{{ supply.variety.category }}</p>
      </CardDescription>

      <Separator />
    </CardHeader>

    <CardContent class="p-5 pt-2 grid gap-2">
      <div class="flex justify-between text-sm">
        <div class="flex items-center text-muted-foreground gap-2">
          <PhilippinePeso :size="15" />
          Price:
        </div>
        <span>₱{{ supply.offered_price.toFixed(2) }}/kg</span>
      </div>

      <div class="flex justify-between text-sm">
        <div class="flex items-center text-muted-foreground gap-2">
          <Weight :size="15" />
          Kg:
        </div>
        <span>{{ supply.quantity_kg.toFixed(2) }} kg</span>
      </div>

      <div class="flex justify-between text-sm">
        <div class="flex items-center text-muted-foreground gap-2">
          <SquareEqual :size="15" />
          Total:
        </div>
        <span>₱{{ (supply.quantity_kg * supply.offered_price).toFixed(2) }}</span>
      </div>

      <div class="flex justify-between text-sm">
        <div class="flex items-center text-muted-foreground gap-2">
          <CalendarClock :size="15" />
          Schedule:
        </div>
        <p>
          {{ supply.scheduled_date }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>
