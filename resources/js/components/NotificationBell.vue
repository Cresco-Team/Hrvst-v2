<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Bell, Check, Trash2, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { ScrollArea } from '@/components/ui/scroll-area'
import { useNotifications } from '@/composables/useNotifications'
import { getNotificationConfig } from '@/lib/notificationHelpers'
import { cn } from '@/lib/utils'

const {
  notifications,
  unreadCount,
  hasUnread,
  isLoading,
  fetchNotifications,
  markAsRead,
  markAllAsRead,
  deleteNotification,
} = useNotifications()

const recentNotifications = computed(() => notifications.value.slice(0, 5))

function handleOpen(open: boolean) {
  if (open && notifications.value.length === 0) {
    fetchNotifications(1, false)
  }
}

function handleNotificationClick(notificationId: string, url: string) {
  markAsRead(notificationId)
}
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <Tooltip>
      <TooltipTrigger>
        <DropdownMenu @update:open="handleOpen">
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon" class="relative h-9 w-9 cursor-pointer">
              <Bell class="size-5" />
              <Badge
                v-if="hasUnread"
                variant="destructive"
                class="absolute -right-1 -top-1 flex size-5 items-center justify-center rounded-full p-0 text-xs"
              >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </Badge>
              <span class="sr-only">Notifications</span>
            </Button>
          </DropdownMenuTrigger>

          <DropdownMenuContent align="end" class="w-80">
            <!-- Header -->
            <div class="flex items-center justify-between border-b p-4">
              <div>
                <h4 class="font-semibold">Notifications</h4>
                <p v-if="hasUnread" class="text-xs text-muted-foreground">
                  {{ unreadCount }} unread
                </p>
              </div>
              <div class="flex gap-1">
                <Button
                  v-if="hasUnread"
                  variant="ghost"
                  size="sm"
                  @click="markAllAsRead"
                  class="gap-1"
                >
                  <Check class="size-3" />
                  Mark all read
                </Button>
              </div>
            </div>

            <!-- Notifications List -->
            <ScrollArea class="max-h-[400px]">
              <div v-if="isLoading" class="space-y-2 p-4">
                <div v-for="i in 3" :key="i" class="flex gap-3">
                  <div class="size-10 animate-pulse rounded-full bg-muted" />
                  <div class="flex-1 space-y-2">
                    <div class="h-4 w-3/4 animate-pulse rounded bg-muted" />
                    <div class="h-3 w-1/2 animate-pulse rounded bg-muted" />
                  </div>
                </div>
              </div>

              <div v-else-if="recentNotifications.length === 0" class="p-8 text-center">
                <Bell class="mx-auto mb-2 size-12 text-muted-foreground/50" />
                <p class="text-sm font-medium">No notifications</p>
                <p class="text-xs text-muted-foreground">
                  You're all caught up!
                </p>
              </div>

              <div v-else class="divide-y">
                <Link
                  v-for="notification in recentNotifications"
                  :key="notification.id"
                  :href="notification.data.url"
                  class="flex gap-3 p-4 transition-colors hover:bg-muted/50"
                  :class="cn(!notification.read_at && 'bg-primary/5')"
                  @click="handleNotificationClick(notification.id, notification.data.url)"
                >
                  <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-full"
                    :class="cn(
                      getNotificationConfig(notification.type).color,
                      'bg-current/10'
                    )"
                  >
                    <component
                      :is="getNotificationConfig(notification.type).icon"
                      class="size-5"
                      :class="getNotificationConfig(notification.type).color"
                    />
                  </div>

                  <div class="flex-1 space-y-1">
                    <div class="flex items-start justify-between gap-2">
                      <p class="text-sm font-medium leading-tight">
                        {{ getNotificationConfig(notification.type).title(notification.data) }}
                      </p>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="size-6 shrink-0"
                        @click.prevent.stop="deleteNotification(notification.id)"
                      >
                        <X class="size-3" />
                      </Button>
                    </div>
                    <p class="text-xs text-muted-foreground">
                      {{ getNotificationConfig(notification.type).description(notification.data) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ notification.created_at_human }}
                    </p>
                  </div>

                  <div
                    v-if="!notification.read_at"
                    class="size-2 shrink-0 rounded-full bg-primary"
                  />
                </Link>
              </div>
            </ScrollArea>

            <!-- Footer -->
            <DropdownMenuSeparator />
            <div class="p-2">
              <Link href="/notifications">
                <Button variant="ghost" class="w-full justify-center">
                  View all notifications
                </Button>
              </Link>
            </div>
          </DropdownMenuContent>
        </DropdownMenu>
      </TooltipTrigger>
      <TooltipContent>
        <p>Notifications</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</template>
