// Mirrors:
//   app/Http/Resources/Marketplace/FarmerSupplyResource.php
//   app/Http/Resources/Marketplace/DealerDemandResource.php

import type { PostStatus, PostPriceFlag, PostTimeSlot } from '../enums'
import type { Coordinates } from '../shared'

// ─── Embedded variety shape (both supply + demand resources) ──────────────────

// Flattened variety snapshot embedded in post resources.
// Loaded via with('variety.vegetable.category', 'variety.media')
export interface PostVarietySnapshot {
  id: number
  name: string
  vegetable: string | null   // vegetable.name — null when relation not loaded
  category: string | null    // vegetable.category.name — null when relation not loaded
  image_url: string
}

// ─── FarmerSupplyResource ─────────────────────────────────────────────────────

export interface FarmerSupplyResource {
  id: number
  quantity_kg: number
  offered_price: number
  price_flag: PostPriceFlag
  status: PostStatus
  scheduled_date: string | null         // formatted 'M d, Y'
  time_slot: PostTimeSlot | null
  time_slot_label: string | null
  days_until_expiration: number | null  // negative = already past
  hearts_count: number
  is_hearted: boolean
  created_at: string                    // 'M d, Y'
  created_at_human: string

  // with('media')
  image_url?: string

  // with('variety.vegetable.category', 'variety.media')
  variety?: PostVarietySnapshot
}

// ─── DealerDemandResource ─────────────────────────────────────────────────────

export interface DealerDemandResource {
  id: number
  quantity_kg: number
  offered_price: number
  price_flag: PostPriceFlag
  status: PostStatus
  scheduled_date: string | null          // formatted 'M d, Y'
  time_slot: PostTimeSlot | null
  time_slot_label: string | null
  days_until_transaction: number | null  // negative = already past
  hearts_count: number
  is_hearted: boolean
  created_at: string                     // 'M d, Y'
  created_at_human: string

  // with('variety.vegetable.category', 'variety.media')
  variety?: PostVarietySnapshot
}

// ─── Map Markers ──────────────────────────────────────────────────────────────

// SupplyMapService::markers() — grouped by barangay
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

// FarmerMapService::getFarmersForMap() — one marker per farmer
export interface FarmerMarker {
  id: number
  coordinates: Coordinates
  farmer_name: string
  municipality: string
  ongoing_supplies_count: number
  supplies_summary: FarmerMarkerVegetableSummary[]
}

// Per-vegetable breakdown nested inside FarmerMarker — distinct from FarmerSupplySummary in profile.ts
export interface FarmerMarkerVegetableSummary {
  vegetable: string
  count: number
  varieties: string[]
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

// SupplyService::varietyOptions() + DemandService::varietyOptions()
// Grouped by category name
export type SupplyVarietyOption = {
  id: number
  name: string                                          // "Vegetable Variety"
}

export type DemandVarietyOption = SupplyVarietyOption & {
  current_price: { min: number; max: number } | null    // variety.latestPrice
}

export type VarietyOptionsByCategory<T extends SupplyVarietyOption = SupplyVarietyOption> =
  Record<string, T[]>

// SupplyMapService::filterOptions()
export interface SupplyMapFilterOptions {
  categories: Array<{ id: number; name: string }>
  varieties: Record<string, Array<{ id: number; name: string }>>
}

// Local UI state for supply-map filter controls — not a backend response type.
// Drives the axios params sent to SupplyMapService::markers().
export interface SupplyMapFilters {
  category_id: number | null
  variety_id: number | null
}

// FarmerMapService helpers surfaced to admin/farmers/Index
export interface MunicipalityOption {
  id: number
  name: string
  province: string
  label: string   // "Municipality, Province"
}

export interface SupplyOption {
  id: number
  name: string    // "Vegetable Variety"
  category: string
}
