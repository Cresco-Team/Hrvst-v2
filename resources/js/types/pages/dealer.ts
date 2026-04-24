// Dealer Inertia page props
// Each interface maps to the props object in Inertia::render('dealer/...')

import type { PostStatus } from '../enums'
import type {
	DealerDemandResource,
	VarietyOption,
	FarmerSupplyResource,
	SupplyMapFilterOptions,
	VarietyOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption, VarietyResource } from '../resources/product'
import type { DealerDemandSummary } from '../resources/profile'
import type { MapConfig, Paginated } from '../shared'

// ─── dealer/Dashboard ─────────────────────────────────────────────────────────

export type DealerRecommendationSeverity = 'critical' | 'warning' | 'info'

export interface DealerDashboardRecommendation {
	severity: DealerRecommendationSeverity
	type: string
	title: string
	body: string
}

export interface DealerPriceSnapshot {
	variety_id: number
	variety_name: string
	vegetable_name: string
	price_min: number
	price_max: number
	freshness: 'recent' | 'stable' | 'very stable' | 'stale'
	recorded_at: string
	weeks_stale: number
}

export interface DealerDashboardProps {
	summary: DealerDemandSummary // Inertia::defer
	expiringDemands: DealerDemandResource[] // Inertia::defer
	priceSnapshots: DealerPriceSnapshot[] // Inertia::defer
	recommendations: DealerDashboardRecommendation[] // Inertia::defer
}

// ─── dealer/demands/Index ─────────────────────────────────────────────────────

export interface DealerDemandsFilters {
	status: PostStatus
}

export interface DealerDemandsProps {
	filters: DealerDemandsFilters
	summary: DealerDemandSummary // Inertia::defer
	varietyOptions: VarietyOptionsByCategory<VarietyOption> // Inertia::defer
	demands: Paginated<DealerDemandResource> // Inertia::defer
}

// ─── dealer/supply-map/Index ──────────────────────────────────────────────────

export interface DealerSupplyMapProps {
	mapConfig: MapConfig
	filterOptions: SupplyMapFilterOptions // Inertia::defer
}

// ─── dealer/marketplace/Index ─────────────────────────────────────────────────

export interface DealerMarketplaceFilters {
	search: string | null
	category_id: number | null
	variety_id: number | null
	municipality_id: number | null
}

export interface DealerMarketplaceProps {
	filters: DealerMarketplaceFilters
	supplies: Paginated<FarmerSupplyResource> // Inertia::defer
	categoryOptions: CategoryOption[] // Inertia::defer
}

// ─── dealer/vegetables/Index ──────────────────────────────────────────────────

export interface DealerVegetablesFilters {
	search: string | null
	category_id: number | null
}

export interface DealerVegetablesProps {
	filters: DealerVegetablesFilters
	varieties: Paginated<VarietyResource> // Inertia::defer
	categoryOptions: CategoryOption[] // Inertia::defer
}

// ─── dealer/vegetables/Show ───────────────────────────────────────────────────

export interface DealerVegetableShowProps {
	variety: VarietyResource // Inertia::defer
}
