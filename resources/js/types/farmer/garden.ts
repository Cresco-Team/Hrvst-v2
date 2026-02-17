
export interface Offering {
  id: number
  farmer: {
    id: number
    name: string
  }
  variety: {
    id: number
    name: string
    vegetable: string
  }
  image_url: string
  weight_kg: number
  asking_price: number
  expiration_date: string
  days_until_expiration: number
  status: string
  created_at_human: string
}

export interface Summary {
  total_available: number
  total_archived: number
  expiring_this_week: number
  total_value: number
}

export interface VarietyOption {
  id: number
  name: string
  current_price?: {
    min: number
    max: number
  } | null
}