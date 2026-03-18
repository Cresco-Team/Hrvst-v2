/* --- Enums --------------- */

export type PostType = 'supply' | 'demand'
export type PostStatus = 'Ongoing' | 'Archived' | 'Fulfilled'
export type PostPriceFlag = 'Low' | 'Fair' | 'High'

/* --- Shared sub-shapes --------------- */

export interface PostVariety {
	id: number
	name: string
	vegetable: string | null
	category: string | null
	image_url: string
}

export interface VarietyOption {
	id: number
	name: string
	current_price: { min: number; max: number } | null
}

/* --- Supply (Farmer post) --------------- */

export interface Supply {
	id: number
	scheduled_date: string
	days_until_expiration: number | null
	created_at: string
	created_at_human: string
	image_url: string
	quantity_kg: number
	offered_price: number
	price_flag: PostPriceFlag
	status: PostStatus
	variety: PostVariety
}

/* --- Demand (Dealer post) --------------- */

export interface Demand {
	id: number
	scheduled_date: string
	days_until_transaction: number | null
	created_at: string
	created_at_human: string
	quantity_kg: number
	offered_price: number
	price_flag: PostPriceFlag
	status: PostStatus
	variety: PostVariety
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
