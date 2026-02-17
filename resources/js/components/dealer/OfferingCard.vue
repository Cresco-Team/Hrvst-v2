<script setup lang="ts">
import { computed } from 'vue'
import { MapPin, Calendar, Package, DollarSign, PhilippinePeso } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Offering } from '@/types/dealer/marketplace'
import { getInitials } from '@/composables/useInitials'
import { AspectRatio } from '../ui/aspect-ratio'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '../ui/item'
import { Separator } from '../ui/separator'

interface Props {
  offering: Offering
}

const props = defineProps<Props>()

</script>

<template>
  <Card class=" py-0 gap-2 overflow-hidden transition-all hover:shadow-lg">
    <AspectRatio :ratio="16/9" class="relative overflow-hidden">
      <img
        :src="offering.image_url"
        :alt="offering.variety.name"
        class="size-full object-cover transition-transform"
      />

      <div class="absolute bottom-0 right-0 rounded-tl-lg bg-black/60 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm">
          {{ offering.weight_kg }} kg
      </div>
    </AspectRatio>

    <CardHeader class="p-5 py-2">
      <CardTitle>
          {{ offering.variety.name }}
      </CardTitle>
      <CardDescription class="flex justify-between">
        <p>{{ offering.variety.vegetable }}</p>
          <Badge>
            ₱ {{ offering.asking_price.toFixed(2) }}
          </Badge>
      </CardDescription>
      <Separator />
    </CardHeader>

    <CardContent class="p-5 pt-2 grid gap-2">
      <Item class="p-0">
        <ItemMedia>
          <Avatar class="size-10">
            <AvatarImage v-if="offering.farmer.farm_url" :src="offering.farmer.farm_url" />
            <AvatarFallback>{{ getInitials(offering.farmer.name) }}</AvatarFallback>
          </Avatar>
        </ItemMedia>
        <ItemContent>
          <ItemTitle>{{ offering.farmer.name }}</ItemTitle>
          <ItemDescription class="text-xs">
            Exp: {{ offering.expiration_date }} ({{ offering.days_until_expiration }} days)
          </ItemDescription>
        </ItemContent>
      </Item>
    </CardContent>
  </Card>
</template>
