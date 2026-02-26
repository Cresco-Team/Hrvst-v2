<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Supply } from '@/types/dealer/marketplace'
import { getInitials } from '@/composables/useInitials'
import { AspectRatio } from '../ui/aspect-ratio'
import { Item, ItemContent, ItemDescription, ItemMedia, ItemTitle } from '../ui/item'
import { Separator } from '../ui/separator'

interface Props {
  supply: Supply
}

const props = defineProps<Props>()

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
      <Separator />
    </CardHeader>

    <CardContent class="p-5 pt-2 grid gap-2">
      <Item class="p-0">
        <ItemMedia>
          <Avatar class="size-10">
            <AvatarImage v-if="supply.farmer.image_url" :src="supply.farmer.image_url" />
            <AvatarFallback>{{ getInitials(supply.farmer.name) }}</AvatarFallback>
          </Avatar>
        </ItemMedia>
        <ItemContent>
          <ItemTitle>{{ supply.farmer.name }}</ItemTitle>
          <ItemDescription class="text-xs">
            Exp: {{ supply.expiration_date }} ({{ supply.days_until_expiration }} days)
          </ItemDescription>
        </ItemContent>
      </Item>
    </CardContent>
  </Card>
</template>
