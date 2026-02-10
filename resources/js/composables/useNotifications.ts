import { ref, onMounted, onUnmounted, computed } from 'vue'
import type { Notification, PaginatedNotifications } from '@/types/notification'

export function useNotifications() {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  const currentPage = ref(1)
  const lastPage = ref(1)
  const total = ref(0)
  const pollInterval = ref<ReturnType<typeof setInterval> | null>(null)

  const hasUnread = computed(() => unreadCount.value > 0)

  async function fetchNotifications(page = 1, unreadOnly = false) {
    isLoading.value = true
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        per_page: '20',
      })
      
      if (unreadOnly) {
        params.append('unread_only', 'true')
      }

      const response = await fetch(`/notifications?${params}`)
      if (!response.ok) throw new Error('Failed to fetch notifications')

      const data: PaginatedNotifications = await response.json()
      
      notifications.value = data.data
      currentPage.value = data.current_page
      lastPage.value = data.last_page
      total.value = data.total
    } catch (error) {
      console.error('Error fetching notifications:', error)
    } finally {
      isLoading.value = false
    }
  }

  async function fetchUnreadCount() {
    try {
      const response = await fetch('/notifications/unread-count')
      if (!response.ok) throw new Error('Failed to fetch unread count')

      const data = await response.json()
      unreadCount.value = data.count
    } catch (error) {
      console.error('Error fetching unread count:', error)
    }
  }

  async function markAsRead(notificationId: string) {
    // Optimistic update
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification && !notification.read_at) {
      notification.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }

    try {
      const response = await fetch(`/notifications/${notificationId}/mark-as-read`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (!response.ok) throw new Error('Failed to mark as read')
    } catch (error) {
      console.error('Error marking notification as read:', error)
      // Revert optimistic update
      if (notification) {
        notification.read_at = null
        unreadCount.value++
      }
    }
  }

  async function markAllAsRead() {
    // Optimistic update
    const previousNotifications = [...notifications.value]
    const previousCount = unreadCount.value
    
    notifications.value.forEach(n => {
      if (!n.read_at) {
        n.read_at = new Date().toISOString()
      }
    })
    unreadCount.value = 0

    try {
      const response = await fetch('/notifications/mark-all-as-read', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (!response.ok) throw new Error('Failed to mark all as read')
    } catch (error) {
      console.error('Error marking all as read:', error)
      // Revert optimistic update
      notifications.value = previousNotifications
      unreadCount.value = previousCount
    }
  }

  async function deleteNotification(notificationId: string) {
    // Optimistic update
    const index = notifications.value.findIndex(n => n.id === notificationId)
    const removed = notifications.value.splice(index, 1)[0]
    
    if (removed && !removed.read_at) {
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
    total.value--

    try {
      const response = await fetch(`/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (!response.ok) throw new Error('Failed to delete notification')
    } catch (error) {
      console.error('Error deleting notification:', error)
      // Revert optimistic update
      notifications.value.splice(index, 0, removed)
      if (removed && !removed.read_at) {
        unreadCount.value++
      }
      total.value++
    }
  }

  async function clearReadNotifications() {
    try {
      const response = await fetch('/notifications/read/all', {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      })

      if (!response.ok) throw new Error('Failed to clear read notifications')

      // Refresh list
      await fetchNotifications(currentPage.value)
    } catch (error) {
      console.error('Error clearing read notifications:', error)
    }
  }

  function startPolling(intervalMs = 30000) {
    stopPolling()
    pollInterval.value = setInterval(() => {
      fetchUnreadCount()
    }, intervalMs)
  }

  function stopPolling() {
    if (pollInterval.value) {
      clearInterval(pollInterval.value)
      pollInterval.value = null
    }
  }

  onMounted(() => {
    fetchUnreadCount()
    startPolling()
  })

  onUnmounted(() => {
    stopPolling()
  })

  return {
    notifications,
    unreadCount,
    hasUnread,
    isLoading,
    currentPage,
    lastPage,
    total,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
    deleteNotification,
    clearReadNotifications,
    startPolling,
    stopPolling,
  }
}
