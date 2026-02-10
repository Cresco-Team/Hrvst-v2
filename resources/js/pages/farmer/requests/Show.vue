<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import { ArrowLeft, Calendar, Package, DollarSign, Phone, User, TrendingUp, TrendingDown, Minus } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import ReactionBar from '@/components/shared/ReactionBar.vue'
import FlagDialog from '@/components/shared/FlagDialog.vue'
import AppLayout from '@/layouts/AppLayout.vue'
import type { DealerRequest } from '@/types/announcement'
import farmer from '@/routes/farmer'

interface Props {
  request: DealerRequest
}

const props = defineProps<Props>()

function getInitials(name: string) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase()
}

function getPriceFlagVariant(flag: string) {
  if (flag === 'cheap') return 'destructive'
  if (flag === 'high') return 'default'
  return 'secondary'
}

function getPriceFlagLabel(flag: string) {
  if (flag === 'cheap') return 'Below Market'
  if (flag === 'high') return 'Above Market'
  if (flag === 'fair') return 'Fair Price'
  return 'No Data'
}

const breadcrumbs = [
  { title: 'Farmer', href: farmer.garden.index().url },
  { title: 'Dealer Requests', href: farmer.requests.index().url },
  { title: `Request #${props.request.id}`, href: farmer.requests.show(props.request.id).url },
]
</script>

<template>
  <Head :title="`Request #${request.id}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Back button -->
      <div>
        <Link :href="farmer.requests.index().url">
          <Button variant="ghost" size="sm" class="gap-2">
            <ArrowLeft class="size-4" />
            Back to Requests
          </Button>
        </Link>
      </div>

      <div class="grid gap-6 lg:grid-cols-3">
        <!-- Left column: Request details -->
        <div class="space-y-6 lg:col-span-2">
          <!-- Header -->
          <div>
            <h1 class="text-3xl font-bold">Purchase Request #{{ request.id }}</h1>
            <p class="mt-1 text-sm text-muted-foreground">
              Posted {{ request.created_at_human }}
            </p>
          </div>

          <!-- Stats cards -->
          <div class="grid gap-4 sm:grid-cols-2">
            <Card>
              <CardContent class="flex items-center gap-4 p-4">
                <div class="rounded-lg bg-primary/10 p-3">
                  <Calendar class="size-6 text-primary" />
                </div>
                <div>
                  <p class="text-sm text-muted-foreground">Transaction Date</p>
                  <p class="text-xl font-bold">{{ request.transaction_date }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ request.days_until_transaction === 0 ? 'Today' : `In ${request.days_until_transaction} days` }}
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
                  <p class="text-xl font-bold">{{ request.total_quantity }} kg</p>
                  <p class="text-xs text-muted-foreground">
                    {{ request.items.length }} varieties
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>

          <!-- Items table -->
          <Card>
            <CardHeader>
              <CardTitle>Requested Varieties</CardTitle>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Variety</TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead class="text-right">Quantity</TableHead>
                    <TableHead class="text-right">Price Offered</TableHead>
                    <TableHead>Price Flag</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-for="item in request.items" :key="item.variety.id">
                    <TableCell class="font-medium">{{ item.variety.name }}</TableCell>
                    <TableCell>
                      <Badge variant="outline">{{ item.variety.category }}</Badge>
                    </TableCell>
                    <TableCell class="text-right">{{ item.quantity_kg }} kg</TableCell>
                    <TableCell class="text-right font-semibold">₱{{ item.price_offered }}/kg</TableCell>
                    <TableCell>
                      <Badge :variant="getPriceFlagVariant(item.price_flag || 'unknown')">
                        {{ getPriceFlagLabel(item.price_flag || 'unknown') }}
                      </Badge>
                      <div v-if="item.market_price" class="mt-1 text-xs text-muted-foreground">
                        Market: ₱{{ item.market_price.min }}-{{ item.market_price.max }}
                      </div>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>

          <!-- Reactions -->
          <div class="space-y-2">
            <p class="text-sm font-medium">React to this request</p>
            <ReactionBar
              reactionable-type="DealerRequest"
              :reactionable-id="request.id"
              :counts="request.reaction_counts || {}"
              variant="thumbs"
            />
          </div>
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
                  <AvatarImage v-if="request.dealer.user_image" :src="request.dealer.user_image" />
                  <AvatarFallback>{{ getInitials(request.dealer.name) }}</AvatarFallback>
                </Avatar>
                <div>
                  <p class="font-semibold">{{ request.dealer.name }}</p>
                  <p class="text-sm text-muted-foreground">Dealer</p>
                </div>
              </div>

              <Separator />

              <!-- Contact info -->
              <div class="space-y-3">
                <div v-if="request.dealer.phone_number" class="flex items-center gap-3">
                  <Phone class="size-4 text-muted-foreground" />
                  <div>
                    <p class="text-xs text-muted-foreground">Phone</p>
                    <p class="font-medium">{{ request.dealer.phone_number }}</p>
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
                  :flaggable-id="request.id"
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
