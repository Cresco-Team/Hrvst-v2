// Mirrors:
//   app/Http/Resources/Product/PriceHistoryResource.php
//   app/Http/Resources/Product/VarietyResource.php

import type { PriceFreshness, PriceTrend } from '../enums'

// ─── PriceHistoryResource ─────────────────────────────────────────────────────

export interface PriceHistoryResource {
  price_min: number
  price_max: number
  recorded_at: string        // formatted 'M d, Y'
  freshness: PriceFreshness
}

// ─── VegetableResource ──────────────────────────────────────────────────────────

export interface VegetableResource {
  id: number
  name: string
  is_variety: boolean
  category: { id: number; name: string } | null
  varieties_count?: number
  varieties: VarietyResource[] | null
}

// ─── VarietyResource ──────────────────────────────────────────────────────────

// Nested vegetable + category shape (with('vegetable.category'))
export interface VarietyVegetable {
  id: number
  name: string
  category: VarietyCategory | null // null when vegetable loaded but category not
}

export interface VarietyCategory {
  id: number
  name: string
}

// Per-municipality ongoing supply summary — admin paginated + show views
export interface SupplyMunicipality {
  name: string
  total_kg: number
}

// Per-month activity row — set on VarietyService::show()
export interface MonthlyActivity {
  month: string              // 'Y-m'
  label: string              // 'M Y'
  supply_archived_kg: number
  supply_fulfilled_kg: number
  demand_archived_kg: number
  demand_fulfilled_kg: number
}

// Full VarietyResource shape.
// Optional fields are only present when the corresponding relation/count is loaded.
// See VarietyResource::toArray() for exact whenLoaded / whenCounted guards.
export interface VarietyResource {
  // Always present
  id: number
  name: string
  image_url: string
  hearts_count: number
  is_hearted: boolean
  vegetable: VarietyVegetable

  // with('latestPrice')
  latest_price?: PriceHistoryResource | null
  price_updated_human?: string
  price_updated_date?: string

  // with('lastTwoPrices') — null when fewer than 2 price records exist
  price_trend?: PriceTrend | null

  // with('recentPrices')
  recent_prices?: PriceHistoryResource[]

  // withCount(['posts as supply_count' => supply scope])
  supply_count?: number
  // withCount(['posts as demand_count' => demand scope])
  demand_count?: number

  monthly_supply_kg?: number
  monthly_demand_kg?: number

  // Set manually on Variety model in VarietyService::paginated() and ::show()
  supply_municipalities?: SupplyMunicipality[]

  // Set manually on Variety model in VarietyService::show()
  monthly_activity?: MonthlyActivity[]

  variety_calendar?: Record<string, Record<string, { type: 'supply' | 'demand'; total_kg: number; posts_count: number }[]>>
}

// ─── Option Bag Types (from VarietyService) ───────────────────────────────────

// VarietyService::vegetableOptions()
// Grouped by category name; keys are vegetable IDs (as strings after JSON serialisation)
export type VegetableOptions = Record<string, Record<string, string>>

// VarietyService::categoryOptions()
export interface CategoryOption {
  id: number
  name: string
}

// VarietyService::summary()
export interface VarietySummary {
  total_varieties: number
  total_vegetables: number
  price_stats: {
    updated_week: number
    updated_month: number
    stale: number
    no_price: number
  }
}

// ─── VarietyTableRow ──────────────────────────────────────────────────────────

export interface VarietyTableRow {
  id: number
  name: string
  is_variety: boolean
  // vegetable-level
  category?: { id: number; name: string } | null
  varieties_count?: number
  // variety-level
  image_url?: string | null
  latest_price?: PriceHistoryResource | null
  price_updated_human?: string | null
  price_trend?: PriceTrend | null
  supply_count?: number
  demand_count?: number
  // self-referential sub-rows — satisfies TanStack's getSubRows constraint
  varieties?: VarietyTableRow[]
}

// ─── Mapper ───────────────────────────────────────────────────────────────────

export function mapVegetablesToTableRows(vegetables: VegetableResource[]): VarietyTableRow[] {
  return vegetables.map((veg) => ({
    id: veg.id,
    name: veg.name,
    is_variety: false,
    category: veg.category,
    varieties_count: veg.varieties_count,
    varieties: (veg.varieties ?? []).map((v): VarietyTableRow => ({
      id: v.id,
      name: v.name,
      is_variety: true,
      image_url: v.image_url,
      latest_price: v.latest_price ?? null,
      price_updated_human: v.price_updated_human ?? null,
      price_trend: v.price_trend ?? null,
      supply_count: v.supply_count,
      demand_count: v.demand_count,
    })),
  }))
}
