
/* ---------------Dealer Marketplace--------------- */
export interface MarketplaceFilters {
    search?: string | null
    category_id?: number | null
    variety_id?: number | null
    municipality_id?: number | null
}

export interface Planting {
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