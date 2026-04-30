// Mirrors:
//   app/Http/Resources/Marketplace/FarmerSupplyResource.php
//   app/Http/Resources/Marketplace/DealerDemandResource.php
//   app/Http/Resources/Marketplace/MarketplacePostResource.php
//   app/Services/Marketplace/VarietyCalendarService.php

import type { PostStatus, PostTimeSlot } from '../enums'
import type { Coordinates } from '../shared'

// ─── Embedded vegetable shape ─────────────────────────────────────────────────

export interface PostVegetableSnapshot {
	id: number
	name: string
	category: string | null
	image_url: string
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
	id: number
	quantity_kg: number
	status: PostStatus
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_expiration: number | null
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	image_url?: string
	vegetable?: PostVegetableSnapshot
}

// ─── DealerDemandResource ─────────────────────────────────────────────────────

export interface DealerDemandResource {
	id: number
	quantity_kg: number
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
}

// ─── MarketplacePost ──────────────────────────────────────────────────────────

export interface MarketplacePost {
	id: number
	type: 'supply' | 'demand'
	status: PostStatus
	quantity_kg: number
	scheduled_date: string | null
	scheduled_date_iso: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number | null
	hearts_count: number
	is_hearted: boolean
	is_own: boolean
	image_url: string | null
	municipality: string | null
	created_at: string
	created_at_human: string
	vegetable: PostVegetableSnapshot
}

// ─── Variety Calendar ─────────────────────────────────────────────────────────
// 'unscheduled' is a frontend-only bucket for posts where time_slot IS NULL.

export type CalendarTimeSlot = PostTimeSlot | 'unscheduled'

export interface VarietyCalendarEntry {
	type: 'supply' | 'demand'
	total_kg: number
	posts_count: number
}

export type VarietyDaySchedule = Partial<Record<CalendarTimeSlot, VarietyCalendarEntry[]>>
export type VarietyMonthSchedule = Record<string, VarietyDaySchedule>

export interface VarietyCalendarFilters {
	year: number
	month: number
}

// ─── Map Markers ──────────────────────────────────────────────────────────────

export interface SupplyMarker {
	barangay_id: number
	barangay: string
	municipality_id: number
	municipality: string
	coordinates: Coordinates
	supply_count: number
	total_quantity_kg: number
	supply_breakdown: SupplyBreakdown[]
}

export interface SupplyBreakdown {
	vegetable: string
	category: string
	count: number
	total_quantity_kg: number
}

export interface FarmerMarker {
	id: number
	coordinates: Coordinates
	farmer_name: string
	municipality: string
	ongoing_supplies_count: number
	supplies_summary: FarmerMarkerVegetableSummary[]
}

export interface FarmerMarkerVegetableSummary {
	vegetable: string
	count: number
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOption = {
	id: number
	name: string
}

export type VegetableOptionsByCategory = Record<string, VegetableOption[]>

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
