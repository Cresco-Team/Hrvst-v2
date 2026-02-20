
export interface DemandFilters {
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
        image_path: string | null
      }
      transaction_date: string
      days_until_transaction: number
      status: 'open' | 'fulfilled' | 'expired'
      variety: {
        id: number
        name: string
        vegetable: string
        image_url: string
      }
      quantity_kg: number
      price_offered: number
      price_flag: 'low' | 'fair' | 'premium'
      created_at_human: string
}

export interface CategoryOption {
    id: number
    name: string
}