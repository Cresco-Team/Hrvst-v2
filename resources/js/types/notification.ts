// Notification System Types

export interface NotificationData {
    offering_id?: number
    request_id?: number
    variety_name: string
    farmer_name?: string
    dealer_name?: string
    quantity_kg: number
    price_asking?: number
    price_offered?: number
    url: string
  }
  
  export interface Notification {
    id: string
    type: string
    data: NotificationData
    read_at: string | null
    created_at: string
    created_at_human: string
  }
  
  export interface PaginatedNotifications {
    data: Notification[]
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
  
  export interface UnreadCountResponse {
    count: number
  }
  
  export interface MarkAsReadResponse {
    message: string
    notification: {
      id: string
      read_at: string
    }
  }
  
  export interface BulkActionResponse {
    message: string
    count: number
  }
  
  // Notification type helpers
  export type NotificationType = 
    | 'MatchingOfferingAvailable'
    | 'MatchingRequestAvailable'
    | 'OfferingExpiringSoon'
    | 'RequestExpiringSoon'
  
  export interface NotificationTypeConfig {
    icon: any
    color: string
    title: (data: NotificationData) => string
    description: (data: NotificationData) => string
  }
