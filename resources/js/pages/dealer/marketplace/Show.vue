<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, MapPin, Calendar, Package, DollarSign, Phone, User } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import ReactionBar from '@/components/shared/ReactionBar.vue'
import CommentSection from '@/components/shared/CommentSection.vue'
import FlagDialog from '@/components/shared/FlagDialog.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { FarmerOffering } from '@/types/announcement'
import dealer from '@/routes/dealer'

interface Props {
  offering: FarmerOffering
}

const props = defineProps<Props>()

const location = typeof props.offering.farmer.location === 'string'
  ? props.offering.farmer.location
  : props.offering.farmer.location.full

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

const breadcrumbs = [
  { title: 'Dealer', href: dealer.market().url },
  { title: 'Marketplace', href: dealer.marketplace.index().url },
  { title: props.offering.variety.name, href: dealer.marketplace.show(props.offering.id).url },
]
</script>

<template>
  <Head :title="offering.variety.name" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Back button -->
      <div>
        <Link :href="dealer.marketplace.index().url">
          <Button variant="ghost" size="sm" class="gap-2">
            <ArrowLeft class="size-4" />
            Back to Marketplace
          </Button>
        </Link>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left column: Image and details -->
        <div class="space-y-6 lg:col-span-2">
          <!-- Image -->
          <div class="relative aspect-video overflow-hidden rounded-xl border">
            <img
              v-if="offering.image_url"
              :src="offering.image_url"
              :alt="offering.variety.name"
              class="size-full object-cover"
            />
            <div
              v-else
              class="flex size-full items-center justify-center bg-linear-to-br from-primary/10 to-primary/5"
            >
              <Package class="size-24 text-muted-foreground/50" />
            </div>

            <!-- Category badge -->
            <div class="absolute left-4 top-4">
              <Badge variant="secondary" class="shadow-lg">
                {{ offering.variety.category }}
              </Badge>
            </div>
          </div>

          <!-- Variety info -->
          <div class="space-y-4">
            <div>
              <h1 class="text-3xl font-bold">{{ offering.variety.name }}</h1>
              <p class="mt-1 text-sm text-muted-foreground">
                Posted {{ offering.created_at_human }}
              </p>
            </div>

            <!-- Stats cards -->
            <div class="grid gap-4 sm:grid-cols-2">
              <Card>
                <CardContent class="flex items-center gap-4 p-4">
                  <div class="rounded-lg bg-primary/10 p-3">
                    <Package class="size-6 text-primary" />
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Available Quantity</p>
                    <p class="text-2xl font-bold">{{ offering.quantity_kg }} kg</p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent class="flex items-center gap-4 p-4">
                  <div class="rounded-lg bg-primary/10 p-3">
                    <DollarSign class="size-6 text-primary" />
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Asking Price</p>
                    <p class="text-2xl font-bold">₱{{ offering.price_asking }}/kg</p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardContent class="flex items-center gap-4 p-4">
                  <div class="rounded-lg bg-orange-500/10 p-3">
                    <Calendar class="size-6 text-orange-600" />
                  </div>
                  <div>
                    <p class="text-sm text-muted-foreground">Expiration Date</p>
                    <p class="font-semibold">{{ offering.expiration_date }}</p>
                    <p v-if="offering.days_until_expiration !== null" class="text-xs text-muted-foreground">
                      {{ offering.days_until_expiration === 0 ? 'Expires today' : `${offering.days_until_expiration} days remaining` }}
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <!-- Reactions -->
            <div class="space-y-2">
              <p class="text-sm font-medium">React to this offering</p>
              <ReactionBar
                reactionable-type="FarmerOffering"
                :reactionable-id="offering.id"
                :counts="offering.reaction_counts || {}"
                variant="emoji"
              />
            </div>

            <Separator />

            <!-- Comments -->
            <CommentSection :offering-id="offering.id" />
          </div>
        </div>

        <!-- Right column: Farmer info -->
        <div class="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Farmer Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <!-- Farmer profile -->
              <div class="flex items-center gap-3">
                <Avatar class="size-12">
                  <AvatarImage v-if="offering.farmer.user_image" :src="offering.farmer.user_image" />
                  <AvatarFallback>{{ getInitials(offering.farmer.name) }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-semibold">{{ offering.farmer.name }}</p>
                  <p class="text-sm text-muted-foreground">Farmer</p>
                </div>
              </div>

              <Separator />

              <!-- Contact info -->
              <div class="space-y-3">
                <div v-if="offering.farmer.phone_number" class="flex items-center gap-3">
                  <Phone class="size-4 text-muted-foreground" />
                  <div>
                    <p class="text-xs text-muted-foreground">Phone</p>
                    <p class="font-medium">{{ offering.farmer.phone_number }}</p>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <MapPin class="size-4 text-muted-foreground" />
                  <div>
                    <p class="text-xs text-muted-foreground">Location</p>
                    <p class="font-medium">{{ location }}</p>
                  </div>
                </div>
              </div>

              <Separator />

              <!-- Actions -->
              <div class="space-y-2">
                <Button class="w-full gap-2" size="lg">
                  <Phone class="size-4" />
                  Contact Farmer
                </Button>
                <FlagDialog
                  flaggable-type="FarmerOffering"
                  :flaggable-id="offering.id"
                />
              </div>
            </CardContent>
          </Card>

          <!-- Safety tips -->
          <Card class="border-orange-200 bg-orange-50 dark:border-orange-900/50 dark:bg-orange-950/20">
            <CardHeader>
              <CardTitle class="text-sm">Safety Tips</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-xs text-muted-foreground">
              <p>✓ Meet in a public place</p>
              <p>✓ Inspect produce before payment</p>
              <p>✓ Request receipt or documentation</p>
              <p>✓ Report suspicious activity</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
