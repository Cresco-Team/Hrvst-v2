import type { PostItemStatus } from '../enums'
import type {
	DealerPostItemResource,
	FarmerSupplyDataFixed,
	VarietyOptionsByVegetable,
	VegetableOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption, RecommendationSeverity } from '../resources/product'
import type { FarmerSupplySummary } from '../resources/profile'
import type { Paginated } from '../shared'

// ─── farmer/Dashboard ─────────────────────────────────────────────────────────

export interface FarmerDashboardProps {}

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
	vegetable_id: number | null
	date_from: string | null
	date_to: string | null
}

export interface FarmerMarketplaceProps {
	filters: FarmerMarketplaceFilters
	categoryOptions: CategoryOption[]
	demands: Paginated<DealerPostItemResource>
}
