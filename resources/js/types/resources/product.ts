
// ─── Analytics ───────────────────────────────────────────────────────────────

export type ImbalanceBand          = App.Enums.Analytics.ImbalanceBand
export type RecommendationSeverity = App.Enums.Analytics.RecommendationSeverity

// Alias — VarietyRecommendationDTO is now generated.
export type VarietyRecommendation = App.DTOs.Product.VarietyRecommendationDTO

export type VarietyAnalytics = App.DTOs.Product.VarietyAnalyticsDTO

// ─── VarietyResource ──────────────────────────────────────────────────────────
// Http Resource — not generated.
// VarietyDetailData IS generated but types complex fields as Array<any>,
// making it useless for calendar/activity data. Keep this precise version.

export interface VarietyVegetable {
	id: number
	name: string
	image_url: string
	category: VarietyCategory | null
}

export interface VarietyCategory {
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

export interface VarietyResource {
	id: number
	name: string
	vegetable: VarietyVegetable

	supply_count?: number
	demand_count?: number
	monthly_supply_kg?: number
	monthly_demand_kg?: number
	supply_municipalities?: SupplyMunicipality[]
	monthly_activity?: MonthlyActivity[]
	variety_calendar?: Record<
		string,
		Record<string, { type: 'supply' | 'demand'; total_kg: number; posts_count: number }[]>
	>
	analytics?: VarietyAnalytics | null
}

// ─── VegetableResource ────────────────────────────────────────────────────────
// Http Resource — not generated. Marketplace-facing views only.

export interface VegetableResource {
	id: number
	name: string
	is_variety: boolean
	image_url: string
	category: { id: number; name: string } | null
	varieties_count?: number
	varieties: VarietyResource[] | null
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOptions = Record<string, Record<string, string>>

export interface CategoryOption {
	id: number
	name: string
	slug: string
}

export interface VarietySummary {
	total_varieties: number
	total_vegetables: number
}

// ─── VarietyTableRow ──────────────────────────────────────────────────────────

export interface VarietyTableRow {
	id: number
	name: string
	is_variety: boolean
	vegetable_id?: number | null
	category?: { id: number; name: string } | null
	image_url?: string | null
	supply_count?: number
	demand_count?: number
	varieties?: VarietyTableRow[]
}

// ─── Admin Table shape ────────────────────────────────────────────────────────

export type Table =
	Omit<App.Data.Vegetable.VegetableAdminData, 'varieties' | 'category'> & {
		category: App.Data.Category.CategoryData | null
		varieties: App.Data.Vegetable.VarietyAdminRowData[] | null
	}

// ─── Mapper ───────────────────────────────────────────────────────────────────

export function mapVegetablesToTableRows(vegetables: Table[]): VarietyTableRow[] {
	return vegetables.map((veg) => ({
		id: veg.id,
		name: veg.name,
		is_variety: false,
		image_url: veg.image_url,
		category: veg.category,
		varieties_count: veg.varieties_count,
		varieties: (veg.varieties ?? []).map((v): VarietyTableRow => ({
			id: v.id,
			name: v.name,
			is_variety: true,
			vegetable_id: veg.id,
			supply_count: v.supply_count,
			demand_count: v.demand_count,
		})),
	}))
}

export interface ForecastPoint {
    month: string      // 'YYYY-MM'
    label: string      // 'Jan 2027'
    supply_kg: number
    demand_kg: number
}
