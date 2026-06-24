import type { PostItemStatus } from '../enums'
import type {
	DealerPostItemResource,
	FarmerSupplyDataFixed,
	VarietyOptionsByVegetable,
	VegetableOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption } from '../resources/product'
import type { FarmerSupplySummary } from '../resources/profile'
import type { MapConfig, Paginated } from '../shared'

export type RecommendationSeverity = 'critical' | 'warning' | 'info'

export interface FarmerDashboardRecommendation {
	severity: RecommendationSeverity
	type: string
	title: string
	body: string
}

export interface FarmerDashboardProps {
	summary: FarmerSupplySummary
	expiringSupplies: FarmerSupplyDataFixed[]
	recommendations: FarmerDashboardRecommendation[]
}

// ─── farmer/supplies/Index ────────────────────────────────────────────────────

export interface FarmerSuppliesFilters {
	status: PostItemStatus
}

export interface FarmerSuppliesProps {
	filters: FarmerSuppliesFilters
	summary: FarmerSupplySummary
	vegetableOptions: VegetableOptionsByCategory
	varietyOptions: VarietyOptionsByVegetable
	supplies: Paginated<FarmerSupplyDataFixed> | null
}

// ─── farmer/marketplace/Index ─────────────────────────────────────────────────

export interface FarmerMarketplaceFilters {
	search: string | null
	category_id: number | null
	variety_id: number | null
	date_from: string | null
	date_to: string | null
}

export interface FarmerMarketplaceProps {
	filters: FarmerMarketplaceFilters
	categoryOptions: CategoryOption[]
	demands: Paginated<DealerPostItemResource>
}
