<script setup lang="ts">
import { Check, ClipboardList, X } from 'lucide-vue-next'
import { ref, watch } from 'vue'
import PendingApprovalDialog from '@/components/features/admin/dialogs/PendingApprovalDialog.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger,
} from '@/components/ui/sheet'
import { Skeleton } from '@/components/ui/skeleton'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { getInitials } from '@/composables/useInitials'
import { usePendingApprovals } from '@/composables/usePendingApprovals'
import type { PendingDealer, PendingFarmer } from '@/types/resources/profile'

const sheetOpen = ref(false)

const {
  farmers,
  dealers,
  state,
  error,
  fetch,
  approveFarmer,
  rejectFarmer,
  approveDealer,
  rejectDealer,
} = usePendingApprovals()

// Lazy-load only when Sheet opens for the first time; re-fetch on subsequent opens.
watch(sheetOpen, (isOpen) => {
  if (isOpen) {
    fetch()
  }
})

// Dialog state
const dialogOpen = ref(false)
const dialogItem = ref<PendingFarmer | PendingDealer | null>(null)
const dialogType = ref<'farmer' | 'dealer'>('farmer')

function openDialog(item: PendingFarmer | PendingDealer, type: 'farmer' | 'dealer') {
  dialogItem.value = item
  dialogType.value = type
  dialogOpen.value = true
}

const totalPending = () => farmers.value.length + dealers.value.length
</script>

<template>
  <Sheet v-model:open="sheetOpen">
    <TooltipProvider :delay-duration="0">
      <Tooltip>
        <TooltipTrigger as-child>
          <SheetTrigger as-child>
            <Button variant="ghost" size="icon" class="group relative h-9 w-9 cursor-pointer">
              <ClipboardList class="size-5 opacity-80 group-hover:opacity-100" />
              <!-- Pending count badge -->
              <span v-if="totalPending() > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-bold text-destructive-foreground">
                {{ totalPending() }}
              </span>
            </Button>
          </SheetTrigger>
        </TooltipTrigger>

        <TooltipContent>
          <p>Pending Approvals</p>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>

    <SheetContent class="flex w-full flex-col gap-0 p-0 sm:max-w-md">
      <SheetHeader class="border-b px-6 py-4">
        <SheetTitle>Pending Approvals</SheetTitle>
        <SheetDescription>
          Review and approve or reject incoming farmer and dealer applications.
        </SheetDescription>
      </SheetHeader>

      <!-- Loading skeleton -->
      <div v-if="state === 'loading'" class="flex flex-col gap-3 p-6">
        <Skeleton v-for="i in 4" :key="i" class="h-16 w-full rounded-lg" />
      </div>

      <!-- Error state -->
      <div v-else-if="state === 'error'" class="flex flex-col items-center gap-2 p-6 text-sm text-destructive">
        <p>{{ error }}</p>
        <Button variant="outline" size="sm" @click="fetch">Retry</Button>
      </div>

      <!-- Content -->
      <Tabs v-else default-value="farmers" class="flex flex-1 flex-col overflow-hidden">
        <TabsList class="mx-6 mt-4 shrink-0">
          <TabsTrigger value="farmers" class="flex-1 gap-1.5">
            Farmers
            <Badge v-if="farmers.length" variant="secondary">{{ farmers.length }}</Badge>
          </TabsTrigger>
          <TabsTrigger value="dealers" class="flex-1 gap-1.5">
            Dealers
            <Badge v-if="dealers.length" variant="secondary">{{ dealers.length }}</Badge>
          </TabsTrigger>
        </TabsList>

        <!-- Farmers tab -->
        <TabsContent value="farmers" class="mt-0 flex-1 overflow-y-auto">
          <div v-if="farmers.length === 0"
            class="flex h-full items-center justify-center p-6 text-sm text-muted-foreground">
            No pending farmer applications.
          </div>

          <ul v-else class="divide-y">
            <li v-for="farmer in farmers" :key="farmer.id"
              class="flex items-center gap-3 px-6 py-4 transition-colors hover:bg-accent/50">
              <!-- Avatar -->
              <Avatar class="size-10 shrink-0">
                <AvatarImage v-if="farmer.user.image_path" :src="farmer.user.image_path" :alt="farmer.user.name" />
                <AvatarFallback
                  class="bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white">
                  {{ getInitials(farmer.user.name) }}
                </AvatarFallback>
              </Avatar>

              <!-- Info — clicking opens the detail Dialog -->
              <button class="flex min-w-0 flex-1 flex-col gap-0.5 text-left" @click="openDialog(farmer, 'farmer')">
                <p class="truncate text-sm font-medium">{{ farmer.user.name }}</p>
                <p class="truncate text-xs text-muted-foreground">
                  {{ farmer.location.municipality }}, {{ farmer.location.province }}
                </p>
                <p class="text-xs text-muted-foreground">{{ farmer.submitted_at_human }}</p>
              </button>

              <!-- Quick action buttons -->
              <div class="flex shrink-0 gap-1">
                <Button variant="ghost" size="icon"
                  class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                  @click.stop="rejectFarmer(farmer.id)">
                  <X class="size-4" />
                  <span class="sr-only">Reject</span>
                </Button>
                <Button variant="ghost" size="icon"
                  class="size-8 text-green-600 hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-950"
                  @click.stop="approveFarmer(farmer.id)">
                  <Check class="size-4" />
                  <span class="sr-only">Accept</span>
                </Button>
              </div>
            </li>
          </ul>
        </TabsContent>

        <!-- Dealers tab -->
        <TabsContent value="dealers" class="mt-0 flex-1 overflow-y-auto">
          <div v-if="dealers.length === 0"
            class="flex h-full items-center justify-center p-6 text-sm text-muted-foreground">
            No pending dealer applications.
          </div>

          <ul v-else class="divide-y">
            <li v-for="dealer in dealers" :key="dealer.id"
              class="flex items-center gap-3 px-6 py-4 transition-colors hover:bg-accent/50">
              <Avatar class="size-10 shrink-0">
                <AvatarImage v-if="dealer.user.image_path" :src="dealer.user.image_path" :alt="dealer.user.name" />
                <AvatarFallback
                  class="bg-neutral-200 text-xs font-semibold text-black dark:bg-neutral-700 dark:text-white">
                  {{ getInitials(dealer.user.name) }}
                </AvatarFallback>
              </Avatar>

              <button class="flex min-w-0 flex-1 flex-col gap-0.5 text-left" @click="openDialog(dealer, 'dealer')">
                <p class="truncate text-sm font-medium">{{ dealer.user.name }}</p>
                <p class="truncate text-xs text-muted-foreground">{{ dealer.user.email }}</p>
                <p class="text-xs text-muted-foreground">{{ dealer.submitted_at_human }}</p>
              </button>

              <div class="flex shrink-0 gap-1">
                <Button variant="ghost" size="icon"
                  class="size-8 text-destructive hover:bg-destructive/10 hover:text-destructive"
                  @click.stop="rejectDealer(dealer.id)">
                  <X class="size-4" />
                  <span class="sr-only">Reject</span>
                </Button>
                <Button variant="ghost" size="icon"
                  class="size-8 text-green-600 hover:bg-green-50 hover:text-green-700 dark:hover:bg-green-950"
                  @click.stop="approveDealer(dealer.id)">
                  <Check class="size-4" />
                  <span class="sr-only">Accept</span>
                </Button>
              </div>
            </li>
          </ul>
        </TabsContent>
      </Tabs>
    </SheetContent>
  </Sheet>

  <!-- Detail Dialog (rendered outside Sheet to avoid stacking context issues) -->
  <PendingApprovalDialog v-model:open="dialogOpen" :item="dialogItem" :type="dialogType"
    @approve="dialogType === 'farmer' ? approveFarmer($event) : approveDealer($event)"
    @reject="dialogType === 'farmer' ? rejectFarmer($event) : rejectDealer($event)" />
</template>
