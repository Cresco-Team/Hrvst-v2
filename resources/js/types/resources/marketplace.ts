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
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
	id: number
	status: PostStatus
	target_month: string | null
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
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

export interface DealerDemandResource {
	id: number
	status: PostStatus
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number | null
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	vegetable?: PostVegetableSnapshot
	items?: PostItemSnapshot[]
}

// ─── DealerPostItemResource ───────────────────────────────────────────────────
// Dealer-facing unit: one harvested variety lot from a farmer supply post.
// A single Post with 3 varieties = 3 of these cards in the marketplace.

export interface DealerPostItemResource {
	id: number
	post_id: number

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

	// farmer location
	municipality: string | null

	// interaction (shared from parent post)
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
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
	current_price: { min: number; max: number } | null
}

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
