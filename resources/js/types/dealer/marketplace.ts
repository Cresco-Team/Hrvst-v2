
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
        location: string
        municipality: string
        province: string
    }
    variety: {
        id: number
        name: string
        category: string
    }
    image_url: string | null
    quantity_kg: number
    asking_price: number
    expiration_date: string
    days_until_expiration: number | null
    created_at_human: string
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