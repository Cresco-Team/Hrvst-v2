import { Package, ShoppingBag, Clock, AlertCircle } from 'lucide-vue-next'
import type { NotificationData, NotificationTypeConfig } from '@/types/notification'

export const notificationTypeConfig: Record<string, NotificationTypeConfig> = {
  MatchingOfferingAvailable: {
    icon: Package,
    color: 'text-green-600 dark:text-green-500',
    title: (data: NotificationData) => 'New Offering Available',
    description: (data: NotificationData) => 
      `${data.farmer_name} has ${data.quantity_kg}kg of ${data.variety_name} at ₱${data.price_asking}/kg`,
  },
  
  MatchingRequestAvailable: {
    icon: ShoppingBag,
    color: 'text-blue-600 dark:text-blue-500',
    title: (data: NotificationData) => 'New Request Posted',
    description: (data: NotificationData) => 
      `${data.dealer_name} wants ${data.quantity_kg}kg of ${data.variety_name} at ₱${data.price_offered}/kg`,
  },
  
  OfferingExpiringSoon: {
    icon: Clock,
    color: 'text-orange-600 dark:text-orange-500',
    title: (data: NotificationData) => 'Offering Expiring Soon',
    description: (data: NotificationData) => 
      `Your ${data.variety_name} offering (${data.quantity_kg}kg) expires soon`,
  },
  
  RequestExpiringSoon: {
    icon: Clock,
    color: 'text-orange-600 dark:text-orange-500',
    title: (data: NotificationData) => 'Request Expiring Soon',
    description: (data: NotificationData) => 
      `Your request for ${data.variety_name} (${data.quantity_kg}kg) expires soon`,
  },
}

export function getNotificationConfig(type: string): NotificationTypeConfig {
  return notificationTypeConfig[type] || {
    icon: AlertCircle,
    color: 'text-muted-foreground',
    title: () => 'Notification',
    description: () => 'You have a new notification',
  }
}
