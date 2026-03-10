import type { VarietyRef } from "../product/variety"

export interface DemandFilters {
    search?: string | null
    category_id?: number | null
    variety_id?: number | null
    date_from?: string | null
    date_to?: string | null
}

export interface DealerDemand {
      id: number
      dealer: {
        id: number
        name: string
        phone_number: string
        image_url: string | null
      }
      variety: VarietyRef
      title: string
      quantity_kg: number
      offered_price: number
      price_flag: 'Low' | 'Fair' | 'High'
      transaction_date: string
      days_until_transaction: number
      status: 'Ongoing' | 'Archived' | 'Fulfilled'
      created_at_human: string
}

export interface DemandDetails {
  id: number
  dealer: {
    id: number
    name: string
    phone_number: string
    image_path: string
  }
  transaction_date: string
  days_until_transaction: number
  status: 'open' | 'fulfilled' | 'expired'
  variety: VarietyRef
  quantity_kg: number
  price_offered: number
  price_flag: 'low' | 'fair' | 'premium'
  market_price: {
    min: number
    max: number
  }
  created_at: string
  created_at_human: string
}