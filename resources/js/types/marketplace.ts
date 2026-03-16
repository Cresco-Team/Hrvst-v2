import type { Variety } from "./product"

/* ---------------Dealer Marketplace--------------- */
export interface MarketplaceFilters {
    search?: string | null
    category_id?: number | null
    variety_id?: number | null
    municipality_id?: number | null
}

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

export interface Supply {
    id: number
    variety: Variety
    image_url?: string
    quantity_kg: number
    offered_price: number
    price_flag?: 'Low' | 'Fair' | 'High'
    status?: 'Ongoing' | 'Archived' | 'Fulfilled'
    expiration_date: string
    days_until_expiration?: number
    created_at?: string
    created_at_human?: string
}

export interface Demand {
    id: number
    variety: Variety
    quantity_kg: number
    offered_price: number
    price_flag?: 'Low' | 'Fair' | 'High'
    status?: 'Ongoing' | 'Archived' | 'Fulfilled'
    transaction_date: string
    created_at?: string
    created_at_human?: string
}