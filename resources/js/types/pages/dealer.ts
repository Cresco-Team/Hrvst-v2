import type { PostItemStatus } from '../enums'
import type {
	DealerDemandDataFixed,
	DealerPostItemResource,
	VarietyOptionsByVegetable,
	VegetableOptionsByCategory,
} from '../resources/marketplace'
import type { CategoryOption } from '../resources/product'
import type { DealerDemandSummary } from '../resources/profile'
import type { Paginated } from '../shared'

// ─── dealer/Dashboard ─────────────────────────────────────────────────────────

export type DealerRecommendationSeverity = App.Enums.Analytics.RecommendationSeverity

export type DealerDashboardRecommendation =
	Omit<App.DTOs.Dealer.DealerDashboardRecommendationDTO, 'severity'> & {
		severity: DealerRecommendationSeverity
	}

export type DealerExpiringDemandFixed =
	Omit<App.Data.Dealer.DealerExpiringDemandData, 'items'> & {
		items?: App.Data.PostItem.PostItemLightData[]
	}

export interface DealerDashboardProps {
	summary: DealerDemandSummary
	expiringDemands: DealerExpiringDemandFixed[]
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
	demands: Paginated<DealerDemandDataFixed> | null
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
	supplies: Paginated<DealerPostItemResource>
	categoryOptions: CategoryOption[]
}
