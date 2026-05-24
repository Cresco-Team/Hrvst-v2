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
  /** Positive = oversupply, negative = undersupply */
  supply_demand_ratio: number
  imbalance_band: ImbalanceBand
  /** 0–1 ratio over last 3 complete months. Null when no volume exists. */
  supply_fulfillment_rate: number | null
  demand_fulfillment_rate: number | null
  /** % change from oldest to newest recorded price. Null when < 2 price records. */
  price_momentum_pct: number | null
  /** Weeks since the last price record. Null when no price has ever been set. */
  price_weeks_stale: number | null
  /** Month-over-month supply volume % change. Null when prior month has no volume. */
  supply_volume_mom_pct: number | null
  demand_volume_mom_pct: number | null
  /** Pre-sorted: critical → warning → info */
  recommendations: VarietyRecommendation[]
}

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

export interface VarietyVegetable {
  id: number
  name: string
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
  month: string              // 'Y-m'
  label: string              // 'M Y'
  supply_unsettled_kg: number
  supply_fulfilled_kg: number
  demand_unsettled_kg: number
  demand_fulfilled_kg: number
}

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

  // with('lastTwoPrices')
  price_trend?: PriceTrend | null

  // with('recentPrices') — VarietyDetailResource only
  recent_prices?: PriceHistoryResource[]

  // withCount
  supply_count?: number
  demand_count?: number

  // VarietyDetailResource only
  monthly_supply_kg?: number
  monthly_demand_kg?: number
  supply_municipalities?: SupplyMunicipality[]
  monthly_activity?: MonthlyActivity[]
  variety_calendar?: Record<string, Record<string, { type: 'supply' | 'demand'; total_kg: number; posts_count: number }[]>>

  // Computed by VarietyAnalyticsService — present when VarietyService::show() is called
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
      vegetable_id: veg.id,
      image_url: v.image_url,
      latest_price: v.latest_price ?? null,
      price_updated_human: v.price_updated_human ?? null,
      price_trend: v.price_trend ?? null,
      supply_count: v.supply_count,
      demand_count: v.demand_count,
    })),
  }))
}

// ─── Admin Table shape ────────────────────────────────────────────────────────

export interface Table {
  id: number
  name: string
  is_variety: boolean
  category: { id: number; name: string } | null
  varieties_count?: number
  varieties: VarietyResource[] | null
}
