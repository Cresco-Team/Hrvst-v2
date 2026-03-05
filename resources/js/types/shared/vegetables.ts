export interface PriceEntry {
  price_min: number
  price_max: number
  recorded_at: string
}

export interface LatestPrice {
  price_min: number
  price_max: number
  recorded_at: string
  freshness: 'recent' | 'stable' | 'very stable' | 'stale'
}

export interface CatalogVariety {
  id: number
  name: string
  image_url: string | null
  weeks_to_harvest: number
  vegetable: {
    id: number
    name: string
    category: {
      id: number
      name: string
    }
  }
  latest_price: LatestPrice | null
  // Sorted oldest → newest — ready for chart consumption
  recent_prices: PriceEntry[]
}

export interface CategoryOption {
  id: number
  name: string
}

export interface CatalogFilters {
  search?: string | null
  category_id?: number | null
}

export interface CatalogPaginatedResponse {
  data: CatalogVariety[]
  current_page: number
  last_page: number
  per_page: number
  total: number
}
