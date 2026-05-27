// Mirrors:
//   app/Http/Resources/Product/PriceHistoryResource.php
//   app/Http/Resources/Product/VarietyResource.php
//   app/Http/Resources/Product/VarietyDetailResource.php
//   app/DTOs/Product/VarietyAnalyticsDTO.php
//   app/DTOs/Product/VarietyRecommendationDTO.php

import type { PriceFreshness, PriceTrend } from '../enums'

// ─── Analytics ───────────────────────────────────────────────────────────────

export type ImbalanceBand = 'oversupply' | 'balanced' | 'undersupply'

export type RecommendationSeverity = 'critical' | 'warning' | 'info'

export interface VarietyRecommendation {
  severity: RecommendationSeverity
  type: string
  title: string
  body: string
}

export interface VarietyAnalytics {
  supply_demand_ratio: number
  imbalance_band: ImbalanceBand
  supply_fulfillment_rate: number | null
  demand_fulfillment_rate: number | null
  price_momentum_pct: number | null
  price_weeks_stale: number | null
  supply_volume_mom_pct: number | null
  demand_volume_mom_pct: number | null
  recommendations: VarietyRecommendation[]
}

// ─── PriceHistoryResource ─────────────────────────────────────────────────────

export interface PriceHistoryResource {
  price_min: number
  price_max: number
  recorded_at: string
  freshness: PriceFreshness
}

// ─── VegetableResource ────────────────────────────────────────────────────────

export interface VegetableResource {
  id: number
  name: string
  is_variety: boolean
  image_url: string
  category: { id: number; name: string } | null
  varieties_count?: number
  varieties: VarietyResource[] | null
}

// ─── VarietyResource ──────────────────────────────────────────────────────────

export interface VarietyVegetable {
  id: number
  name: string
  image_url: string
  category: VarietyCategory | null
}

export interface VarietyCategory {
  id: number
  name: string
  slug: string
}

export interface SupplyMunicipality {
  name: string
  total_kg: number
}

export interface MonthlyActivity {
  month: string
  label: string
  supply_unsettled_kg: number
  supply_fulfilled_kg: number
  demand_unsettled_kg: number
  demand_fulfilled_kg: number
}

export interface VarietyResource {
  id: number
  name: string
  hearts_count: number
  is_hearted: boolean
  vegetable: VarietyVegetable

  latest_price?: PriceHistoryResource | null
  price_updated_human?: string
  price_updated_date?: string

  price_trend?: PriceTrend | null

  recent_prices?: PriceHistoryResource[]

  supply_count?: number
  demand_count?: number

  monthly_supply_kg?: number
  monthly_demand_kg?: number
  supply_municipalities?: SupplyMunicipality[]
  monthly_activity?: MonthlyActivity[]
  variety_calendar?: Record<string, Record<string, { type: 'supply' | 'demand'; total_kg: number; posts_count: number }[]>>

  analytics?: VarietyAnalytics | null
}

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOptions = Record<string, Record<string, string>>

export interface CategoryOption {
  id: number
  name: string
  slug: string
}

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
  vegetable_id?: number | null
  category?: { id: number; name: string } | null
  varieties_count?: number
  image_url?: string | null
  latest_price?: PriceHistoryResource | null
  price_updated_human?: string | null
  price_trend?: PriceTrend | null
  supply_count?: number
  demand_count?: number
  varieties?: VarietyTableRow[]
}

// ─── Admin Table shape ────────────────────────────────────────────────────────

export interface Table {
  id: number
  name: string
  is_variety: boolean
  image_url: string
  category: { id: number; name: string } | null
  varieties_count?: number
  varieties: VarietyResource[] | null
}

// ─── Mapper ───────────────────────────────────────────────────────────────────

export function mapVegetablesToTableRows(vegetables: VegetableResource[]): VarietyTableRow[] {
  return vegetables.map((veg) => ({
    id: veg.id,
    name: veg.name,
    is_variety: false,
    image_url: veg.image_url,
    category: veg.category,
    varieties_count: veg.varieties_count,
    varieties: (veg.varieties ?? []).map((v): VarietyTableRow => ({
      id: v.id,
      name: v.name,
      is_variety: true,
      vegetable_id: veg.id,
      latest_price: v.latest_price ?? null,
      price_updated_human: v.price_updated_human ?? null,
      price_trend: v.price_trend ?? null,
      supply_count: v.supply_count,
      demand_count: v.demand_count,
    })),
  }))
}
