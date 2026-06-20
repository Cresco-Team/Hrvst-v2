import type { PostItemStatus, PostTimeSlot } from '../enums'
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
	name: string
	image_url: string
	variety_id: number
	category_name: string
	quantity_kg: number
	status: PostItemStatus
	post_id: number

	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number

	created_at: string
	created_at_human: string
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export type FarmerSupplyDataFixed =
    Omit<App.Data.Post.FarmerSupplyData, 'post_items'> & {
        post_items: App.Data.PostItem.PostItemLightData[]
    }

// ─── DealerDemandResource ─────────────────────────────────────────────────────

export interface DealerDemandResource {
	id: number
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
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

	variety_id: number
	variety_name: string
	variety_image_url: string | null
	vegetable_id: number
	vegetable_name: string
	category_name: string

	quantity_kg: number

	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number | null

	municipality: string | null

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

export interface SupplyOption {
	id: number
	name: string
	category: string
}

// ─── Farmer map marker ────────────────────────────────────────────────────────

export interface FarmerMarker {
	id: number
	coordinates: Coordinates
	farmer_name: string
	province_id: number
	province: string | null
	municipality_id: number
	municipality: string
	barangay_id: number
	barangay: string | null
	ongoing_supplies_count: number
	supplies_summary: Array<{
		vegetable: string
		count: number
		varieties: string[]
	}>
}
