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
	vegetable: {
		id: number
		name: string
		category: {
			id: number
			name: string
		}
	}
	latest_price: LatestPrice | null
	recent_prices: PriceEntry[]
	hearts_count: number
	is_hearted: boolean
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
