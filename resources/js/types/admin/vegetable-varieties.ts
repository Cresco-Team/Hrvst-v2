
export interface Variety {
    id: number
    vegetable_id: number
    name: string
    image_url: string
    weeks_to_harvest: number
    vegetable: {
        id: number
        name: string
        category: {
            id: number
            name: string
        }
    }
    latest_price: {
        price_min: string
        price_max: string
    } | null
    price_updated_human?: string
    price_updated_date?: string
    price_freshness?: 'recent' | 'stable' | 'very stable' | 'stale'
}

export interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
    price_stats: {
        updated_week: number
        updated_month: number
        stale: number
        no_price: number
    }
}

export interface VegetableOptions {
    [categoryName: string]: {
        [vegetableId: number]: string
    }
}

export interface PaginatedData {
    data: Variety[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface Props {
    varieties?: {
        data: Variety[]
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
    summary?: Summary
    vegetableOptions?: VegetableOptions
    filters: {
        price_filter: string | null
        search?: string | null
    }
}
