import type { PostItemStatus, PostPriceFlag, PostTimeSlot } from '../enums'
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
	status: PostItemStatus
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
	id: number
	status: 'growing' // Post is only ever growing here; items carry ongoing/fulfilled/archived
	target_month: string | null
	estimated_total_weight: number
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	image_url?: string
	vegetable?: PostVegetableSnapshot
	items?: PostItemSnapshot[]
}

// ─── DealerDemandResource ─────────────────────────────────────────────────────
// Kept for backward compat in demand form — the demand index now uses DealerPostItemResource

export interface DealerDemandResource {
	id: number
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	vegetable?: PostVegetableSnapshot
	items?: PostItemSnapshot[]
}

// ─── DealerPostItemResource ───────────────────────────────────────────────────

export interface DealerPostItemResource {
	id: number
	post_id: number
	status: PostItemStatus

	// variety
	variety_id: number
	variety_name: string
	variety_image_url: string | null
	vegetable_name: string
	category_name: string

	// pricing
	quantity_kg: number
	unit_price: number | null
	price_flag: PostPriceFlag | null

	// schedule
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number | null

	// farmer location (null for demand items)
	municipality: string | null

	// interaction
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOption = { id: number; name: string }
export type VegetableOptionsByCategory = Record<string, VegetableOption[]>
export type VarietyOption = { id: number; name: string; current_price: { min: number; max: number } | null }
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
