// Dealer Inertia page props

import type { PostStatus } from '../enums'
import type {
	DealerDemandResource,
	FarmerSupplyResource,
	SupplyMapFilterOptions,
	VegetableOption,
	VegetableOptionsByCategory,
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

export interface DealerDashboardProps {
	summary: DealerDemandSummary
	expiringDemands: DealerDemandResource[]
	recommendations: DealerDashboardRecommendation[]
}

// ─── dealer/demands/Index ─────────────────────────────────────────────────────

export interface DealerDemandsFilters {
	status: PostStatus
}

export interface DealerDemandsProps {
	filters: DealerDemandsFilters
	summary: DealerDemandSummary
	vegetableOptions: VegetableOptionsByCategory
	demands: Paginated<DealerDemandResource>
}

// ─── dealer/supply-map/Index ──────────────────────────────────────────────────

export interface DealerSupplyMapProps {
	mapConfig: MapConfig
	filterOptions: SupplyMapFilterOptions
}

// ─── dealer/marketplace/Index ─────────────────────────────────────────────────

export interface DealerMarketplaceFilters {
	search: string | null
	category_id: number | null
	vegetable_id: number | null
	municipality_id: number | null
}

export interface DealerMarketplaceProps {
	filters: DealerMarketplaceFilters
	supplies: Paginated<FarmerSupplyResource>
	categoryOptions: CategoryOption[]
}

// ─── dealer/vegetables/Index ──────────────────────────────────────────────────

export interface DealerVegetablesFilters {
	search: string | null
	category_id: number | null
}

export interface DealerVegetablesProps {
	filters: DealerVegetablesFilters
	varieties: Paginated<VarietyResource>
	categoryOptions: CategoryOption[]
}

// ─── dealer/vegetables/Show ───────────────────────────────────────────────────

export interface DealerVegetableShowProps {
	variety: VarietyResource
}
