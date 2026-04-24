// Farmer Inertia page props
// Each interface maps to the props object in Inertia::render('farmer/...')

import type { PostStatus } from '../enums'
import type {
	DealerDemandResource,
	FarmerSupplyResource,
	SupplyMapFilterOptions,
	VarietyOption,
	VarietyOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption, VarietyResource } from '../resources/product'
import type { FarmerSupplySummary } from '../resources/profile'
import type { MapConfig, Paginated } from '../shared'

// ─── farmer/Dashboard ─────────────────────────────────────────────────────────

export type RecommendationSeverity = 'critical' | 'warning' | 'info'

export interface FarmerDashboardRecommendation {
	severity: RecommendationSeverity
	type: string
	title: string
	body: string
}

export interface FarmerPriceSnapshot {
	variety_id: number
	variety_name: string
	vegetable_name: string
	price_min: number
	price_max: number
	freshness: 'recent' | 'stable' | 'very stable' | 'stale'
	recorded_at: string
	weeks_stale: number
}

export interface FarmerDashboardProps {
	summary: FarmerSupplySummary // Inertia::defer
	expiringSupplies: FarmerSupplyResource[] // Inertia::defer
	priceSnapshots: FarmerPriceSnapshot[] // Inertia::defer
	recommendations: FarmerDashboardRecommendation[] // Inertia::defer
}

// ─── farmer/supplies/Index ────────────────────────────────────────────────────

export interface FarmerSuppliesFilters {
	status: PostStatus
}

export interface FarmerSuppliesProps {
	filters: FarmerSuppliesFilters
	summary: FarmerSupplySummary // Inertia::defer
	varietyOptions: VarietyOptionsByCategory<VarietyOption> // Inertia::defer
	supplies: Paginated<FarmerSupplyResource> // Inertia::defer
}

// ─── farmer/supply-map/Index ──────────────────────────────────────────────────

export interface FarmerSupplyMapProps {
	mapConfig: MapConfig
	filterOptions: SupplyMapFilterOptions // Inertia::defer
}

// ─── farmer/marketplace/Index ─────────────────────────────────────────────────

export interface FarmerMarketplaceFilters {
	category_id: number | null
	variety_id: number | null
	date_from: string | null // ISO date
	date_to: string | null // ISO date
	search: string | null
}

export interface FarmerMarketplaceProps {
	filters: FarmerMarketplaceFilters
	categoryOptions: CategoryOption[] // Inertia::defer (group 'options')
	demands: Paginated<DealerDemandResource> // Inertia::defer (group 'demands')
}

// ─── farmer/vegetables/Index ──────────────────────────────────────────────────

export interface FarmerVegetablesFilters {
	search: string | null
	category_id: number | null
}

export interface FarmerVegetablesProps {
	filters: FarmerVegetablesFilters
	varieties: Paginated<VarietyResource> // Inertia::defer
	categoryOptions: CategoryOption[] // Inertia::defer
}

// ─── farmer/vegetables/Show ───────────────────────────────────────────────────

export interface FarmerVegetableShowProps {
	variety: VarietyResource // Inertia::defer
}
