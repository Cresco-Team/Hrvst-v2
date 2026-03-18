/**
 * All variety-related type shapes, derived from VarietyResource.
 *
 * Import from here. Do not redeclare variety shapes in feature modules.
 */

// ─── Primitives ────────────────────────────────────────────────────────────────

export type PriceFreshness = 'recent' | 'stable' | 'very stable' | 'stale'

// ─── Price shapes ──────────────────────────────────────────────────────────────

/** A single price history record. Maps to PriceHistoryResource. */
export interface PriceEntry {
	price_min: number
	price_max: number
	recorded_at: string
}

/** The most recent price record, with computed freshness label. */
export interface LatestPrice extends PriceEntry {
	freshness: PriceFreshness
}

// ─── Variety shapes ────────────────────────────────────────────────────────────

/**
 * Lightweight variety reference embedded inside Supply/Demand resources.
 * `vegetable` is a flat display string, not a relation object — it is
 * whatever the resource serializes (e.g. "Tomato"). Do not expect sub-fields.
 */
export interface VarietyRef {
	id: number
	name: string
	vegetable: string
	image_url: string | null
}

/**
 * Full variety shape for catalog pages (farmer/dealer market views).
 * Requires: with('vegetable.category', 'latestPrice', 'recentPrices')
 */
export interface CatalogVariety {
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
	/** Sorted oldest → newest. Absent when recentPrices not loaded. */
	recent_prices?: PriceEntry[]
}

/**
 * Full variety shape for admin management pages.
 * Extends CatalogVariety with admin-only aggregates.
 * Requires: withCount(['supply_count', 'demand_count']), ->through() for supply_municipalities
 */
export interface AdminVariety extends CatalogVariety {
	price_updated_human: string | null
	price_updated_date: string | null
	supply_count: number
	demand_count: number
	supply_municipalities: MunicipalitySupply[]
}

// ─── Supporting shapes ─────────────────────────────────────────────────────────

export interface MunicipalitySupply {
	name: string
	total_kg: number
}

/** Dropdown option for variety selects. current_price absent when not loaded. */
export interface VarietyOption {
	id: number
	name: string
	current_price?: {
		min: number
		max: number
	} | null
}

/**
 * Grouped vegetable options for the admin create/edit form.
 * Shape: { [categoryName]: { [vegetableId]: vegetableName } }
 */
export type VegetableOptions = Record<string, Record<number, string>>
