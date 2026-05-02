import type { PostPriceFlag, PostStatus, PostTimeSlot } from '../enums'
import type { Coordinates } from '../shared'

// ─── Embedded shapes ──────────────────────────────────────────────────────────

export interface PostVegetableSnapshot {
	id: number
	name: string
	category: string | null
	image_url: string
}

export interface PostItemSnapshot {
	id: number
	variety_id: number
	variety_name: string | null
	quantity_kg: number
	unit_price: number | null
	price_flag: PostPriceFlag | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
	id: number
	status: PostStatus
	target_month: string | null           // "YYYY-MM" — growing only
	scheduled_at: string | null           // formatted — ongoing+
	estimated_total_weight: number
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	image_url?: string
	vegetable?: PostVegetableSnapshot
	items?: PostItemSnapshot[]            // empty while growing
}

// ─── DealerDemandResource ─────────────────────────────────────────────────────

export interface DealerDemandResource {
	id: number
	status: PostStatus
	scheduled_at: string | null
	days_until_transaction: number | null
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	vegetable?: PostVegetableSnapshot
	items?: PostItemSnapshot[]
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOption = {
	id: number
	name: string
}

export type VegetableOptionsByCategory = Record<string, VegetableOption[]>

export type VarietyOption = {
	id: number
	name: string
	vegetable_id: number
	current_price: { min: number; max: number } | null
}

// Grouped by vegetable name for display
export type VarietyOptionsByVegetable = Record<string, VarietyOption[]>

// ─── Map types ────────────────────────────────────────────────────────────────

export interface SupplyMarker {
	barangay_id: number
	barangay: string
	municipality_id: number
	municipality: string
	coordinates: Coordinates
	supply_count: number
	total_quantity_kg: number
}

export interface SupplyMapFilterOptions {
	categories: Array<{ id: number; name: string }>
	vegetables: Record<string, Array<{ id: number; name: string }>>
}

export interface SupplyMapFilters {
	category_id: number | null
	vegetable_id: number | null
}

export interface MunicipalityOption {
	id: number
	name: string
	province: string
	label: string
}
