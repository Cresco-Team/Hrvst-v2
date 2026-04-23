// Mirrors:
//   app/Http/Resources/Marketplace/FarmerSupplyResource.php
//   app/Http/Resources/Marketplace/DealerDemandResource.php
//   app/Http/Resources/Marketplace/MarketplacePostResource.php
//   app/Services/Marketplace/VarietyCalendarService.php

import type { PostPriceFlag, PostStatus, PostTimeSlot } from '../enums'
import type { Coordinates } from '../shared'

// ─── Embedded variety shape (both supply + demand resources) ──────────────────

export interface PostVarietySnapshot {
	id: number
	name: string
	vegetable: string | null
	category: string | null
	image_url: string
}

export interface MarketplacePostVariety extends PostVarietySnapshot {
	latest_price: {
		price_min: number
		price_max: number
		recorded_at: string
		freshness: 'recent' | 'stable' | 'very stable' | 'stale'
	} | null
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
	id: number
	quantity_kg: number
	offered_price: number
	price_flag: PostPriceFlag
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
	variety?: PostVarietySnapshot
}

// ─── DealerDemandResource ─────────────────────────────────────────────────────

export interface DealerDemandResource {
	id: number
	quantity_kg: number
	offered_price: number
	price_flag: PostPriceFlag
	status: PostStatus
	scheduled_date: string | null
	time_slot: PostTimeSlot | null
	time_slot_label: string | null
	days_until_transaction: number | null
	hearts_count: number
	is_hearted: boolean
	created_at: string
	created_at_human: string
	variety?: PostVarietySnapshot
}

// ─── MarketplacePost ──────────────────────────────────────────────────────────

export interface MarketplacePost {
	id: number
	type: 'supply' | 'demand'
	status: PostStatus
	quantity_kg: number
	offered_price: number | null
	price_flag: PostPriceFlag | null
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
	variety: MarketplacePostVariety
}

// ─── Variety Calendar ─────────────────────────────────────────────────────────
// Mirrors app/Services/Marketplace/VarietyCalendarService::forMonth()
//
// 'unscheduled' is a backend-only bucket for posts where time_slot IS NULL.
// It is intentionally NOT part of the PostTimeSlot enum — that enum reflects
// real DB column values, and NULL is not one of them. This is purely a
// frontend display concept to prevent NULL posts silently disappearing.

export type CalendarTimeSlot = PostTimeSlot | 'unscheduled'

export interface VarietyCalendarEntry {
	type: 'supply' | 'demand'
	total_kg: number
	posts_count: number
}

/** Slot → entries for one calendar day */
export type VarietyDaySchedule = Partial<Record<CalendarTimeSlot, VarietyCalendarEntry[]>>

/** ISO date string "YYYY-MM-DD" → day schedule */
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
	varieties: string[]
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
	varieties: string[]
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VarietyOption = {
	id: number
	name: string
	current_price: { min: number; max: number } | null
}

export type VarietyOptionsByCategory<T extends VarietyOption = VarietyOption> = Record<
	string,
	T[]
>

export interface SupplyMapFilterOptions {
	categories: Array<{ id: number; name: string }>
	varieties: Record<string, Array<{ id: number; name: string }>>
}

export interface SupplyMapFilters {
	category_id: number | null
	variety_id: number | null
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
