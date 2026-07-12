import type { PostItemStatus } from '../enums'
import type {
    DealerDemandDataFixed,
    DealerPostItemResource,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '../resources/marketplace'
import type {
    CategoryOption,
    VegetableStabilityData,
    VegetableWasteData,
} from '../resources/product'
import type { DealerDemandSummary } from '../resources/profile'
import type { Paginated } from '../shared'

// ─── dealer/Dashboard ─────────────────────────────────────────────────────────

export interface DealerDashboardProps {
    topWastedSupply?: VegetableWasteData[]
    mostStableWastedSupply?: VegetableStabilityData[]
    analyticsLocked: boolean
    upgradeFeatureLabel: string
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
    needsAction?: DealerDemandDataFixed[]
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
