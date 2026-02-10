<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Bell, Check, Trash2, Filter, X } from 'lucide-vue-next'
import AppLayout from '@/layouts/AppLayout.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Card, CardContent } from '@/components/ui/card'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog'
import { useNotifications } from '@/composables/useNotifications'
import { getNotificationConfig } from '@/lib/notificationHelpers'
import { cn } from '@/lib/utils'
import type { Notification } from '@/types/notification'

interface Props {
  filters: {
    show: 'all' | 'unread'
    page: number
  }
}

const props = defineProps<Props>()

const {
  notifications,
  unreadCount,
  hasUnread,
  isLoading,
  currentPage,
  lastPage,
  total,
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
  clearReadNotifications,
} = useNotifications()

const clearDialogOpen = ref(false)
const activeTab = computed(() => props.filters.show)

const breadcrumbs = [
  { title: 'Notifications', href: '/notifications' },
]

// Load notifications on mount
fetchNotifications(props.filters.page, props.filters.show === 'unread')

// Watch for filter changes
watch(() => props.filters, (newFilters) => {
  fetchNotifications(newFilters.page, newFilters.show === 'unread')
}, { deep: true })

function handleTabChange(value: string | number) {
  router.visit('/notifications', {
    data: { show: value === 'all' ? undefined : value },
    preserveState: true,
    preserveScroll: true,
  })
}

function handlePageChange(page: number) {
  router.visit('/notifications', {
    data: {
      page,
      show: props.filters.show === 'all' ? undefined : props.filters.show,
    },
    preserveScroll: true,
  })
}

function handleNotificationClick(notification: Notification) {
  if (!notification.read_at) {
    markAsRead(notification.id)
  }
  // Navigation happens via Link component
}

function handleClearRead() {
  clearDialogOpen.value = false
  clearReadNotifications()
}
</script>

<template>
  <Head title="Notifications" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-col gap-6 p-4 lg:p-6">
      <!-- Header -->
      <div class="flex items-end justify-between">
        <Heading
          title="Notifications"
          description="Stay updated with new offerings, requests, and important alerts."
        >
          <template #icon>
            <Bell class="size-8" />
          </template>
        </Heading>

        <div class="flex gap-2">
          <Button
            v-if="hasUnread"
            variant="outline"
            @click="markAllAsRead"
            class="gap-2"
          >
            <Check class="size-4" />
            Mark all read
          </Button>
          <Button
            variant="outline"
            @click="clearDialogOpen = true"
            class="gap-2"
          >
            <Trash2 class="size-4" />
            Clear read
          </Button>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid gap-4 sm:grid-cols-3">
        <Card>
          <CardContent class="flex items-center gap-4 p-4">
            <div class="rounded-lg bg-primary/10 p-3">
              <Bell class="size-6 text-primary" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Total</p>
              <p class="text-2xl font-bold">{{ total }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-center gap-4 p-4">
            <div class="rounded-lg bg-orange-500/10 p-3">
              <Bell class="size-6 text-orange-600 dark:text-orange-500" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Unread</p>
              <p class="text-2xl font-bold">{{ unreadCount }}</p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent class="flex items-center gap-4 p-4">
            <div class="rounded-lg bg-green-500/10 p-3">
              <Check class="size-6 text-green-600 dark:text-green-500" />
            </div>
            <div>
              <p class="text-sm text-muted-foreground">Read</p>
              <p class="text-2xl font-bold">{{ total - unreadCount }}</p>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Filter Tabs -->
      <Tabs :model-value="activeTab" @update:model-value="handleTabChange">
        <TabsList>
          <TabsTrigger value="all">All ({{ total }})</TabsTrigger>
          <TabsTrigger value="unread">Unread ({{ unreadCount }})</TabsTrigger>
        </TabsList>
      </Tabs>

      <!-- Notifications List -->
      <div class="space-y-2">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-2">
          <Card v-for="i in 5" :key="i" class="animate-pulse">
            <CardContent class="flex gap-4 p-4">
              <div class="size-12 rounded-full bg-muted" />
              <div class="flex-1 space-y-3">
                <div class="h-4 w-3/4 rounded bg-muted" />
                <div class="h-3 w-1/2 rounded bg-muted" />
                <div class="h-3 w-1/4 rounded bg-muted" />
              </div>
            </CardContent>
          </Card>
        </div>

        <!-- Empty State -->
        <Card v-else-if="notifications.length === 0">
          <CardContent class="flex flex-col items-center justify-center py-16 text-center">
            <Bell class="mb-4 size-12 text-muted-foreground/50" />
            <h3 class="mb-1 font-semibold">No notifications</h3>
            <p class="text-sm text-muted-foreground">
              {{ activeTab === 'unread' ? "You're all caught up!" : 'No notifications yet' }}
            </p>
          </CardContent>
        </Card>

        <!-- Notifications -->
        <Card
          v-for="notification in notifications"
          :key="notification.id"
          :class="cn(
            'transition-all hover:shadow-md',
            !notification.read_at && 'border-primary/50 bg-primary/5'
          )"
        >
          <Link
            :href="notification.data.url"
            @click="handleNotificationClick(notification)"
          >
            <CardContent class="flex gap-4 p-4">
              <!-- Icon -->
              <div
                class="flex size-12 shrink-0 items-center justify-center rounded-full"
                :class="cn(
                  getNotificationConfig(notification.type).color,
                  'bg-current/10'
                )"
              >
                <component
                  :is="getNotificationConfig(notification.type).icon"
                  class="size-6"
                  :class="getNotificationConfig(notification.type).color"
                />
              </div>

              <!-- Content -->
              <div class="flex-1 space-y-2">
                <div class="flex items-start justify-between gap-4">
                  <div class="flex-1">
                    <div class="flex items-center gap-2">
                      <h4 class="font-semibold">
                        {{ getNotificationConfig(notification.type).title(notification.data) }}
                      </h4>
                      <Badge v-if="!notification.read_at" variant="default" class="text-xs">
                        New
                      </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">
                      {{ getNotificationConfig(notification.type).description(notification.data) }}
                    </p>
                  </div>

                  <!-- Actions -->
                  <div class="flex shrink-0 gap-1">
                    <Button
                      v-if="!notification.read_at"
                      variant="ghost"
                      size="icon"
                      @click.prevent.stop="markAsRead(notification.id)"
                    >
                      <Check class="size-4" />
                      <span class="sr-only">Mark as read</span>
                    </Button>
                    <Button
                      variant="ghost"
                      size="icon"
                      @click.prevent.stop="deleteNotification(notification.id)"
                    >
                      <X class="size-4" />
                      <span class="sr-only">Delete</span>
                    </Button>
                  </div>
                </div>

                <!-- Timestamp -->
                <p class="text-xs text-muted-foreground">
                  {{ notification.created_at_human }}
                </p>
              </div>
            </CardContent>
          </Link>
        </Card>
      </div>

      <!-- Pagination -->
      <div
        v-if="!isLoading && lastPage > 1"
        class="flex items-center justify-between border-t pt-4"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage === 1"
          @click="handlePageChange(currentPage - 1)"
        >
          Previous
        </Button>
        <span class="text-sm text-muted-foreground">
          Page {{ currentPage }} of {{ lastPage }}
        </span>
        <Button
          variant="outline"
          size="sm"
          :disabled="currentPage === lastPage"
          @click="handlePageChange(currentPage + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </AppLayout>

  <!-- Clear Read Dialog -->
  <AlertDialog :open="clearDialogOpen" @update:open="clearDialogOpen = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Clear read notifications?</AlertDialogTitle>
        <AlertDialogDescription>
          This will permanently delete all read notifications. Unread notifications will not be affected.
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>Cancel</AlertDialogCancel>
        <AlertDialogAction @click="handleClearRead" class="bg-destructive text-destructive-foreground">
          Clear read
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
