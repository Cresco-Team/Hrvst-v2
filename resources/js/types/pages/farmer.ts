// Farmer Inertia page props

import type { PostStatus } from '../enums'
import type {
	DealerDemandResource,
	FarmerSupplyResource,
	SupplyMapFilterOptions,
	VegetableOption,
	VegetableOptionsByCategory,
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

export interface FarmerDashboardProps {
	summary: FarmerSupplySummary
	expiringSupplies: FarmerSupplyResource[]
	recommendations: FarmerDashboardRecommendation[]
}

// ─── farmer/supplies/Index ────────────────────────────────────────────────────

export interface FarmerSuppliesFilters {
	status: PostStatus
}

export interface FarmerSuppliesProps {
	filters: FarmerSuppliesFilters
	summary: FarmerSupplySummary
	vegetableOptions: VegetableOptionsByCategory
	supplies: Paginated<FarmerSupplyResource>
}

// ─── farmer/supply-map/Index ──────────────────────────────────────────────────

export interface FarmerSupplyMapProps {
	mapConfig: MapConfig
	filterOptions: SupplyMapFilterOptions
}

// ─── farmer/marketplace/Index ─────────────────────────────────────────────────

export interface FarmerMarketplaceFilters {
	category_id: number | null
	vegetable_id: number | null
	date_from: string | null
	date_to: string | null
	search: string | null
}

export interface FarmerMarketplaceProps {
	filters: FarmerMarketplaceFilters
	categoryOptions: CategoryOption[]
	demands: Paginated<DealerDemandResource>
}

// ─── farmer/vegetables/Index ──────────────────────────────────────────────────

export interface FarmerVegetablesFilters {
	search: string | null
	category_id: number | null
}

export interface FarmerVegetablesProps {
	filters: FarmerVegetablesFilters
	varieties: Paginated<VarietyResource>
	categoryOptions: CategoryOption[]
}

// ─── farmer/vegetables/Show ───────────────────────────────────────────────────

export interface FarmerVegetableShowProps {
	variety: VarietyResource
}
