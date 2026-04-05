// Mirrors:
//   app/Http/Resources/Profile/FarmerResource.php
//   app/Http/Resources/Profile/DealerResource.php

import type { Coordinates } from '../shared'
import type { DealerDemandResource, FarmerSupplyResource } from './marketplace'
import type { UserResource } from './user'

// ─── FarmerResource ───────────────────────────────────────────────────────────

export interface FarmerLocation {
	province: string
	municipality: string
	barangay: string
	full_address: string
	coordinates: Coordinates
}

export interface FarmerResource {
	// Always present
	id: number
	joined_at: string // 'M d, Y'
	joined_at_human: string

	// with('user') or with('user.media')
	user?: UserResource

	// with('province', 'municipality', 'barangay') — all three must be loaded
	location?: FarmerLocation

	// with('posts.*') — posts relation on FarmerProfile is scoped to PostType::Supply
	supplies?: FarmerSupplyResource[]

	// withCount(['posts as ongoing_supplies_count' => ongoing scope])
	ongoing_supplies_count?: number
}

// ─── DealerResource ───────────────────────────────────────────────────────────

export interface DealerResource {
	// Always present
	id: number
	joined_at: string // 'M d, Y'
	joined_at_human: string

	// with('user') or with('user.media')
	user?: UserResource

	// with('posts.*') — posts relation on DealerProfile is scoped to PostType::Demand
	demands?: DealerDemandResource[]

	// withCount(['posts as ongoing_demands_count' => ongoing scope])
	ongoing_demands_count?: number
}

// ─── Service Summary Shapes ───────────────────────────────────────────────────

// FarmerService::summary() — used in admin farmers index
export interface AdminFarmerSummary {
	total_farmers: number
	new_farmers_this_month: number
	total_supplies: number
	new_supplies_this_month: number
}

// DealerService::summary() — used in admin dealers index
export interface AdminDealerSummary {
	total_dealers: number
	new_dealers_this_month: number
	total_demands: number
	new_demands_this_month: number
}

// SupplyService::summary() — used in farmer supplies index
export interface FarmerSupplySummary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
}

// DemandService::summary() — used in dealer demands index
export interface DealerDemandSummary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
}
