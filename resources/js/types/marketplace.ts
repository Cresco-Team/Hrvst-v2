/* --- Enums --------------- */

export type PostType = 'supply' | 'demand'
export type PostStatus = 'Ongoing' | 'Archived' | 'Fulfilled'
export type PostPriceFlag = 'Low' | 'Fair' | 'High'
export type PostTimeSlot = 'morning' | 'afternoon' | 'evening'

/* --- Shared sub-shapes --------------- */

export interface PostVariety {
	id: number
	name: string
	vegetable: string | null
	category: string | null
	image_url: string
}

export interface Post {
	id: number
	scheduled_date: string
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_expiration: number | null
	created_at: string
	created_at_human: string
	image_url?: string
	quantity_kg: number
	offered_price: number
	price_flag: PostPriceFlag
	status: PostStatus
	variety: PostVariety
}

export interface VarietyOption {
	id: number
	name: string
	current_price: { min: number; max: number } | null
}

export interface CategoryOption {
	id: number
	name: string
}

export interface MarketplaceFilters {
	search?: string | null
	category_id?: number | null
	variety_id?: number | null
	municipality_id?: number | null
}

/* --- Summaries --------------- */

export interface SupplySummary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
	scheduled_this_week: number
}

export interface DemandSummary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
	scheduled_this_week: number
}
