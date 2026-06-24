
export interface MunicipalitySupply {
	name: string
	total_kg: number
}

export interface MonthlyActivity {
	month: string
	label: string
	supply_expired_kg: number
	supply_fulfilled_kg: number
	demand_expired_kg: number
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
	supply_count: number
	demand_count: number
}

export interface ShowVariety {
	id: number
	name: string
	display_name: string
	vegetable: {
		id: number
		name: string
		category: {
			id: number
			name: string
		}
	}
	supply_count: number
	demand_count: number
	supply_municipalities: MunicipalitySupply[]
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
