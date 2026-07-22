<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import axios from 'axios'
import { Bell, Lock } from '@lucide/vue'
import { onMounted, onUnmounted, ref } from 'vue'
import AppTooltip from '@/components/templates/AppTooltip.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { show as billingShow } from '@/routes/billing'
import notifications from '@/routes/notifications'

interface NotificationItem {
    id: string
    vegetable_id: number
    vegetable_name: string
    message: string
    detail_locked: boolean
    read_at: string | null
    created_at: string
}

const items = ref<NotificationItem[]>([])
const unreadCount = ref(0)

// No websocket/broadcast wiring exists in this app yet (no Echo/Reverb in
// package.json). Polling is the honest choice here, not a stopgap — alert
// cadence is weekly, so 60s is already far more "live" than the underlying
// signal needs, while staying well clear of any rate limiting.
const POLL_INTERVAL_MS = 60_000
let pollHandle: ReturnType<typeof setInterval> | null = null

async function load(): Promise<void> {
    const { data } = await axios.get(notifications.index().url)
    items.value = data.notifications
    unreadCount.value = data.unread_count
}

async function markRead(item: NotificationItem): Promise<void> {
    if (item.read_at) return
    await axios.post(`/notifications/${item.id}/read`)
    item.read_at = new Date().toISOString()
    unreadCount.value = Math.max(0, unreadCount.value - 1)
}

onMounted(() => {
    load()
    pollHandle = setInterval(load, POLL_INTERVAL_MS)
})

onUnmounted(() => {
    if (pollHandle) clearInterval(pollHandle)
})
</script>

<template>
    <Popover>
        <AppTooltip content="Alerts">
            <PopoverTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="group relative h-9 w-9 cursor-pointer"
                >
                    <span class="sr-only">Alerts</span>
                    <Bell class="size-5 opacity-80 group-hover:opacity-100" />
                    <Badge
                        v-if="unreadCount"
                        variant="destructive"
                        class="absolute -top-1 -right-1 size-4 justify-center rounded-full p-0 text-[10px] leading-none"
                    >
                        {{ unreadCount > 9 ? '9+' : unreadCount }}
                    </Badge>
                </Button>
            </PopoverTrigger>
        </AppTooltip>

        <PopoverContent
            align="end"
            class="w-80 p-0"
        >
            <div
                v-if="!items.length"
                class="p-4 text-sm text-muted-foreground"
            >
                No alerts yet.
            </div>

            <div
                v-else
                class="max-h-96 divide-y overflow-y-auto"
            >
                <div
                    v-for="item in items"
                    :key="item.id"
                    class="flex cursor-pointer flex-col gap-1 p-3 text-sm hover:bg-accent"
                    :class="!item.read_at && 'bg-muted/40'"
                    @click="markRead(item)"
                >
                    <p class="font-medium">{{ item.vegetable_name }}</p>
                    <p class="text-muted-foreground">{{ item.message }}</p>
                    <Link
                        v-if="item.detail_locked"
                        :href="billingShow().url"
                        class="flex items-center gap-1 text-xs text-primary"
                        @click.stop
                    >
                        <Lock class="size-3" />
                        See exact timing
                    </Link>
                    <span class="text-xs text-muted-foreground/70">{{ item.created_at }}</span>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>