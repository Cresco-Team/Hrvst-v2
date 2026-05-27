import type { PostItemStatus } from '../enums'
import type {
	DealerDemandResource,
	DealerPostItemResource,
	PostItemSnapshot,
	SupplyMapFilterOptions,
	VarietyOptionsByVegetable,
	VegetableOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption } from '../resources/product'
import type { DealerDemandSummary } from '../resources/profile'
import type { MapConfig, Paginated } from '../shared'

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
	status: PostItemStatus
}

export interface DealerDemandsProps {
	filters: DealerDemandsFilters
	summary: DealerDemandSummary
	vegetableOptions: VegetableOptionsByCategory
	varietyOptions: VarietyOptionsByVegetable
	demands: Paginated<PostItemSnapshot>
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
	variety_id: number | null
	municipality_id: number | null
}

export interface DealerMarketplaceProps {
	filters: DealerMarketplaceFilters
	supplies: Paginated<DealerPostItemResource>
	categoryOptions: CategoryOption[]
}
