// ─── Analytics (unchanged) ──────────────────────────────────────────────────

export type ImbalanceBand          = App.Enums.Analytics.ImbalanceBand
export type RecommendationSeverity = App.Enums.Analytics.RecommendationSeverity
export type VarietyRecommendation = App.DTOs.Product.VarietyRecommendationDTO
export type VarietyAnalytics = App.DTOs.Product.VarietyAnalyticsDTO

// ─── VegetableResource — replaces old VarietyResource ────────────────────────

export interface VegetableCategory {
	id: number
	name: string
	slug: string
}

export interface SupplyMunicipality {
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

export interface VegetableResource {
	id: number
	vegetable_name: string
	variety_name: string | null
	local_name: string | null
	name: string // display name — "Tomato" or "Tomato: Cherry"
	image_url: string
	category: VegetableCategory | null

	supply_count?: number
	demand_count?: number
	supply_municipalities?: SupplyMunicipality[]
	monthly_activity?: MonthlyActivity[]
	variety_calendar?: <Record
		string,
		Record<string, { type: 'supply' | 'demand'; total_kg: number; posts_count: number }[]>
	>
	analytics?: VarietyAnalytics | null
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export interface CategoryOption {
	id: number
	name: string
	slug: string
}

export interface VegetableSummary {
	total_varieties: number
	total_vegetables: number
}

// ─── Flat admin table row — no more parent/child ──────────────────────────────

export interface VegetableTableRow {
	id: number
	vegetable_name: string
	variety_name: string | null
	local_name: string | null
	name: string
	category: { id: number; name: string } | null
	image_url: string | null
	supply_count?: number
	demand_count?: number
}

export type Table = App.Data.Vegetable.VegetableAdminData

export function mapVegetablesToTableRows(vegetables: Table[]): VegetableTableRow[] {
	return vegetables.map((veg) => ({
		id: veg.id,
		vegetable_name: veg.vegetable_name,
		variety_name: veg.variety_name,
		local_name: veg.local_name,
		name: veg.name,
		category: veg.category,
		image_url: veg.image_url,
		supply_count: veg.supply_count,
		demand_count: veg.demand_count,
	}))
}

export interface ForecastPoint {
    month: string
    label: string
    supply_kg: number
    demand_kg: number
}
