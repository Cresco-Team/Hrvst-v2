// resources/js/types/resources/profile.ts — admin-relevant section
// Add/update FarmerResource and DealerResource to include PostItem-based fields

import type { PostItemStatus } from '../enums'
import { Coordinates } from '../shared'
import type { DealerPostItemResource, PostItemSnapshot } from './marketplace'

// ─── Shared supply/demand item summary (sidebar) ──────────────────────────────

export interface PostItemSummary {
	id: number
	variety_name: string | null
	quantity_kg: number
	status: PostItemStatus
}

// ─── FarmerResource ───────────────────────────────────────────────────────────

export interface FarmerLocation {
	full_address: string
	barangay: string | null
	municipality: string | null
	province: string | null
}

export interface FarmerUser {
	id: number
	name: string
	email: string
	phone_number: string | null
	avatar_url: string | null
}

export interface FarmerResource {
	id: number
	joined_at: string
	joined_at_human: string
	user?: FarmerUser
	location?: FarmerLocation

	// list view (paginated)
	ongoing_supplies_count?: number

	// sidebar detail view (details())
	supplies?: PostItemSummary[]

	// full show view (show())
	supply_items?: PostItemSnapshot[]
	growing_posts_count?: number
}

export interface FarmerBaseResource {
	id: number
	joined_at: string
	joined_at_human: string
	user: FarmerUser
	location: FarmerLocation
}

// ─── DealerResource ───────────────────────────────────────────────────────────

export interface DealerUser {
	id: number
	name: string
	email: string
	phone_number: string | null
	avatar_url: string | null
}

export interface DealerResource {
	id: number
	joined_at: string
	joined_at_human: string
	user?: DealerUser

	// list view
	ongoing_demands_count?: number

	// sidebar detail view
	demands?: PostItemSummary[]

	// full show view
	demand_items?: PostItemSnapshot[]
}

// ─── Summary shapes ───────────────────────────────────────────────────────────

export interface FarmerSupplySummary {
	total_growing: number
	total_ongoing: number
	total_fulfilled: number
	total_unsettled: number
}

export interface DealerDemandSummary {
	total_ongoing: number
	total_fulfilled: number
	total_unsettled: number
}

export interface AdminFarmerSummary {
	total_farmers: number
	new_farmers_this_month: number
	new_supplies_this_month: number
}

export interface AdminDealerSummary {
	total_dealers: number
	new_dealers_this_month: number
	total_demands: number
	new_demands_this_month: number
}
