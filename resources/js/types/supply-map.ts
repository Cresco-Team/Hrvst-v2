export interface MapConfig {
	center: {
		lat: number
		lng: number
	}
	zoom: number
}

export interface SupplyBreakdownItem {
	vegetable: string
	category: string
	count: number
	total_quantity_kg: number
	varieties: string[]
}

export interface BarangayMarker {
	barangay_id: number
	barangay: string
	municipality_id: number
	municipality: string
	coordinates: {
		lat: number
		lng: number
	}
	supply_count: number
	total_quantity_kg: number
	supply_breakdown: SupplyBreakdownItem[]
}

export interface MapFilters {
	category_id: number | null
	variety_id: number | null
}

export interface FilterOptions {
	categories: Array<{ id: number; name: string }>
	varieties: Record<string, Array<{ id: number; name: string }>>
}
