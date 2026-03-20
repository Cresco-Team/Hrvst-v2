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

export type PriceTrend = 'up' | 'down' | 'flat' | null

export interface MunicipalitySupply {
	name: string
	total_kg: number
}

export interface MonthlyActivity {
	month: string
	label: string
	supply_archived_kg: number
	supply_fulfilled_kg: number
	demand_archived_kg: number
	demand_fulfilled_kg: number
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
	price_trend: PriceTrend
	price_updated_human: string | null
	supply_count: number
	demand_count: number
	hearts_count: number
	is_hearted: boolean
	recent_prices?: PriceEntry[]
}

export interface ShowVariety {
	id: number
	name: string
	display_name: string
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
	price_updated_human: string | null
	price_updated_date: string | null
	recent_prices: PriceEntry[]
	supply_count: number
	demand_count: number
	hearts_count: number
	is_hearted: boolean
	supply_municipalities: MunicipalitySupply[]
	// 12-entry array of monthly closed market volume; oldest → newest
	monthly_activity: MonthlyActivity[]
}

export interface CategoryOption {
	id: number
	name: string
}

export interface CatalogFilters {
	search?: string | null
	category_id?: number | null
}
