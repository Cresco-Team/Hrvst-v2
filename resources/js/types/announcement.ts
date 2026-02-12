// Announcement System Types

export interface DealerRequestItem {
  id?: number
  variety: {
    id: number
    name: string
    category: string
    image_url?: string
  }
  quantity_kg: number
  price_offered: number
  price_flag?: 'cheap' | 'fair' | 'high' | 'unknown'
  market_price?: {
    min: number
    max: number
  } | null
}

export interface DealerRequest {
  id: number
  dealer: {
    id: number
    name: string
    phone_number?: string
    user_image: string | null
  }
  transaction_date: string
  days_until_transaction: number
  status: 'open' | 'fulfilled' | 'expired'
  items: DealerRequestItem[]
  total_quantity: number
  created_at?: string
  created_at_human: string
  reaction_counts?: {
    thumbs_up: number
    thumbs_down: number
  }
}

export interface FarmerOffering {
  id: number
  farmer: {
    id: number
    name: string
    phone_number?: string
    user_image: string | null
    location: string | {
      barangay: string
      municipality: string
      province: string
      full: string
    }
  }
  variety: {
    id: number
    name: string
    category: string
  }
  image_url: string | null
  quantity_kg: number
  price_asking: number
  expiration_date: string
  days_until_expiration: number | null
  status: 'active' | 'expired' | 'archived'
  created_at?: string
  created_at_human: string
  reaction_counts?: Record<string, number>
  comment_count?: number
}

export interface AnnouncementComment {
  id: number
  user: {
    id: number
    name: string
    avatar: string | null
    role: 'dealer' | 'farmer' | 'user'
  }
  comment: string
  created_at: string
  created_at_human: string
  can_delete: boolean
}

export interface AnnouncementReaction {
  reactionable_type: 'DealerRequest' | 'FarmerOffering'
  reactionable_id: number
  reaction_type: string
}

export interface AnnouncementFlag {
  flaggable_type: 'DealerRequest' | 'FarmerOffering' | 'AnnouncementComment'
  flaggable_id: number
  reason: string
  description?: string
}

// Filter types
export interface MarketplaceFilters {
  search?: string | null
  category_id?: number | null
  variety_id?: number | null
  municipality_id?: number | null
}

export interface RequestFilters {
  category_id?: number | null
  variety_id?: number | null
  date_from?: string | null
  date_to?: string | null
}

// Option types for filters
export interface CategoryOption {
  id: number
  name: string
}

export interface MunicipalityOption {
  id: number
  name: string
  province: string
  label: string
}

export interface VarietyOption {
  id: number
  name: string
  current_price?: {
    min: number
    max: number
  } | null
}
