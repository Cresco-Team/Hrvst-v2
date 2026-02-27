
export interface Supply {
  id: number
  farmer: {
    id: number
    name: string
  }
  variety: {
    id: number
    name: string
    vegetable: string
    image_url: string
  }
  title: string
  image_url: string
  quantity_kg: number
  offered_price: number
  expiration_date: string
  days_until_expiration: number
  status: 'Ongoing' | 'Archived' | 'Fulfilled'
  created_at_human: string
}

export interface Summary {
  total_ongoing: number
  total_fulfilled: number
  total_archived: number
  expiring_this_week: number
}

export interface VarietyOption {
  id: number
  name: string
  current_price?: {
    min: number
    max: number
  } | null
}