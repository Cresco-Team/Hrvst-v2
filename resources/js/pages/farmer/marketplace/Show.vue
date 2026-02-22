<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { Calendar, Package, Phone } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import ReactionBar from '@/components/shared/ReactionBar.vue'
import FlagDialog from '@/components/shared/FlagDialog.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import farmer from '@/routes/farmer'
import { DemandDetails } from '@/types/farmer/marketplace'
import { getInitials } from '@/composables/useInitials'
import Heading from '@/components/Heading.vue'

interface Props {
  details: DemandDetails
}

const props = defineProps<Props>()

const breadcrumbs = [
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Marketplace', href: farmer.marketplace.index().url },
  { title: `${props.details.variety.vegetable} ${props.details.variety.name}`, href: farmer.marketplace.show(props.details.id).url },
]
</script>

<template>
  <Head :title="`${details.variety.vegetable} ${details.variety.name} | ${details.dealer.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
       <Heading 
        :title="`${details.variety.vegetable} ${details.variety.name}`"
        :description="`Posted by ${details.dealer.name}`"
       />

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left column: Request details -->
        <div class="space-y-6 lg:col-span-2">

          <!-- Stats cards -->
          <div class="grid gap-4 sm:grid-cols-2">
            <Card>
              <CardContent class="flex items-center gap-4 p-4">
                <div class="rounded-lg bg-primary/10 p-3">
                  <Calendar class="size-6 text-primary" />
                </div>
                <div>
                  <p class="text-sm text-muted-foreground">Transaction Date</p>
                  <p class="text-xl font-bold">{{ details.transaction_date }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ details.days_until_transaction === 0 ? 'Today' : `In ${details.days_until_transaction} days` }}
                  </p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardContent class="flex items-center gap-4 p-4">
                <div class="rounded-lg bg-primary/10 p-3">
                  <Package class="size-6 text-primary" />
                </div>
                <div>
                  <p class="text-sm text-muted-foreground">Total Quantity</p>
                  <p class="text-xl font-bold">{{ details.quantity_kg }} kg</p>
                  <p class="text-xs text-muted-foreground">
                    varieties
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Reactions -->
          <!-- <div class="space-y-2">
            <p class="text-sm font-medium">React to this request</p>
            <ReactionBar
              reactionable-type="DealerRequest"
              :reactionable-id="details.id"
              :counts="details.reaction_counts || {}"
              variant="thumbs"
            />
          </div> -->
        </div>

        <!-- Right column: Dealer info -->
        <div class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Dealer Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Dealer profile -->
              <div class="flex items-center gap-3">
                <Avatar class="size-12">
                  <AvatarImage v-if="details.dealer.image_path" :src="details.dealer.image_path" />
                  <AvatarFallback>{{ getInitials(details.dealer.name) }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-semibold">{{ details.dealer.name }}</p>
                  <p class="text-sm text-muted-foreground">Dealer</p>
                </div>
              </div>

              <Separator />

              <!-- Contact info -->
              <div class="space-y-3">
                <div v-if="details.dealer.phone_number" class="flex items-center gap-3">
                  <Phone class="size-4 text-muted-foreground" />
                  <div>
                    <p class="text-xs text-muted-foreground">Phone</p>
                    <p class="font-medium">{{ details.dealer.phone_number }}</p>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Actions -->
              <div class="space-y-2">
                <Button class="w-full gap-2" size="lg">
                  <Phone class="size-4" />
                  Contact Dealer
                </Button>
                <FlagDialog
                  flaggable-type="DealerRequest"
                  :flaggable-id="details.id"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Business tips -->
          <Card class="border-blue-200 bg-blue-50 dark:border-blue-900/50 dark:bg-blue-950/20">
            <CardHeader>
              <CardTitle class="text-sm">Business Tips</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-xs text-muted-foreground">
              <p>✓ Compare with market prices</p>
              <p>✓ Confirm availability before committing</p>
              <p>✓ Discuss payment and delivery terms</p>
              <p>✓ Keep transaction records</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
