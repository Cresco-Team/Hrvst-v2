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
	is_approved: boolean
	joined_at: string // 'M d, Y'
	joined_at_human: string

	// with('user') or with('user.media')
	user?: UserResource

	// with('province', 'municipality', 'barangay') — all three must be loaded
	location?: FarmerLocation

	// with('media')
	farm_url?: string

	// with('posts.*') — posts relation on FarmerProfile is scoped to PostType::Supply
	supplies?: FarmerSupplyResource[]

	// withCount(['posts as ongoing_supplies_count' => ongoing scope])
	ongoing_supplies_count?: number
}

// ─── DealerResource ───────────────────────────────────────────────────────────

export interface DealerResource {
	// Always present
	id: number
	is_approved: boolean
	joined_at: string // 'M d, Y'
	joined_at_human: string

	// with('user') or with('user.media')
	user?: UserResource

	// with('posts.*') — posts relation on DealerProfile is scoped to PostType::Demand
	demands?: DealerDemandResource[]

	// withCount(['posts as ongoing_demands_count' => ongoing scope])
	ongoing_demands_count?: number

	// Set manually in DealerService (admin only): $dealer->document_url = route(...)
	// Served via GET /admin/dealers/{dealer}/document (DealerController::document)
	document_url?: string
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

// ─── Pending Approval Types ───────────────────────────────────────────────────
// Mirrors app/Http/Resources/Profile/FarmerResource.php and DealerResource.php
// as returned by the pending() methods in FarmerService / DealerService.

export interface PendingUser {
	id: number
	name: string
	email: string
	phone_number: string
	image_path: string | null
}

export interface PendingFarmer {
	id: number
	user: PendingUser
	location: {
		province: string
		municipality: string
		barangay: string
		full_address: string
		coordinates: { lat: number; lng: number }
	}
	farm_image: string | null
	submitted_at: string
	submitted_at_human: string
}

export interface PendingDealer {
	id: number
	user: PendingUser
	document_image: string | null
	submitted_at: string
	submitted_at_human: string
}
