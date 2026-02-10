<script setup lang="ts">
import { computed } from 'vue'
import { Calendar, Package, User, ThumbsUp, ThumbsDown } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import type { DealerRequest } from '@/types/announcement'

interface Props {
  request: DealerRequest
}

const props = defineProps<Props>()

const urgencyVariant = computed(() => {
  const days = props.request.days_until_transaction
  if (days <= 3) return 'destructive'
  if (days <= 7) return 'default'
  return 'secondary'
})

const urgencyLabel = computed(() => {
  const days = props.request.days_until_transaction
  if (days === 0) return 'Today'
  if (days === 1) return 'Tomorrow'
  return `In ${days} days`
})

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const totalReactions = computed(() => {
  const counts = props.request.reaction_counts
  if (!counts) return 0
  return (counts.thumbs_up || 0) + (counts.thumbs_down || 0)
})
</script>

<template>
  <Card class="group overflow-hidden transition-all hover:shadow-lg">
    <CardContent class="space-y-4 p-4">
      <!-- Header -->
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3">
          <Avatar class="size-10">
            <AvatarImage v-if="request.dealer.user_image" :src="request.dealer.user_image" />
            <AvatarFallback>{{ getInitials(request.dealer.name) }}</AvatarFallback>
          </Avatar>
          <div>
            <p class="font-semibold">{{ request.dealer.name }}</p>
            <p class="text-xs text-muted-foreground">Dealer</p>
          </div>
        </div>

        <Badge :variant="urgencyVariant">
          {{ urgencyLabel }}
        </Badge>
      </div>

      <!-- Transaction date -->
      <div class="flex items-center gap-2 text-sm text-muted-foreground">
        <Calendar class="size-4" />
        <span>Transaction date: {{ request.transaction_date }}</span>
      </div>

      <!-- Items summary -->
      <div class="space-y-2">
        <p class="text-sm font-medium">Requested varieties ({{ request.items.length }})</p>
        <div class="flex flex-wrap gap-2">
          <Badge
            v-for="(item, idx) in request.items.slice(0, 5)"
            :key="idx"
            variant="outline"
            class="text-xs"
          >
            {{ item.variety.name }}
          </Badge>
          <Badge v-if="request.items.length > 5" variant="outline" class="text-xs">
            +{{ request.items.length - 5 }} more
          </Badge>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 gap-3">
        <div class="rounded-lg bg-muted/50 p-3">
          <div class="flex items-center gap-2">
            <Package class="size-4 text-muted-foreground" />
            <div>
              <p class="text-xs text-muted-foreground">Total Quantity</p>
              <p class="font-semibold">{{ request.total_quantity }} kg</p>
            </div>
          </div>
        </div>

        <div class="rounded-lg bg-muted/50 p-3">
          <div class="flex items-center gap-2">
            <ThumbsUp class="size-4 text-muted-foreground" />
            <div>
              <p class="text-xs text-muted-foreground">Reactions</p>
              <p class="font-semibold">{{ totalReactions }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Posted time -->
      <p class="text-xs text-muted-foreground">
        Posted {{ request.created_at_human }}
      </p>
    </CardContent>
  </Card>
</template>
