// Admin Inertia page props
// Each interface maps to the props object in Inertia::render('admin/...')

import type { MunicipalityOption, SupplyOption } from '../resources/marketplace'
import type {
	VarietyResource,
	VarietySummary,
	VegetableOptions,
} from '../resources/product'
import type {
	AdminDealerSummary,
	AdminFarmerSummary,
	DealerResource,
	FarmerResource,
} from '../resources/profile'
import type { KpiStat, MapConfig, Paginated } from '../shared'

// ─── admin/Dashboard ──────────────────────────────────────────────────────────

// DashboardService::getKPIs() — deferred prop
export interface AdminDashboardKPIs {
	farmers: {
		total_farmers: KpiStat
		total_supplies: KpiStat
	}
	dealers: {
		total_dealers: KpiStat
		total_demands: KpiStat
	}
	varieties: {
		total_varieties: KpiStat
		price_updates_week: KpiStat
		needs_attention: KpiStat
	}
}

export interface AdminDashboardProps {
	kpis: AdminDashboardKPIs // Inertia::defer
}

// ─── admin/vegetables/Index ───────────────────────────────────────────────────

export interface AdminVegetablesFilters {
	price_filter: string | null
	search: string | null
}

export interface AdminVegetablesProps {
	summary: VarietySummary // Inertia::defer
	filters: AdminVegetablesFilters
	varieties: Paginated<VarietyResource> // Inertia::defer
	vegetableOptions: VegetableOptions // Inertia::defer
}

// ─── admin/farmers/Index ──────────────────────────────────────────────────────

export interface AdminFarmersFilters {
	search: string | null
	municipalities: MunicipalityOption[]
	supplies: Record<string, SupplyOption[]> // grouped by category
}

export interface AdminFarmersProps {
	view: 'list' | 'map'
	filters: AdminFarmersFilters
	mapConfig: MapConfig
	farmers: Paginated<FarmerResource> | null // Inertia::defer — null in map view
	summary: AdminFarmerSummary // Inertia::defer
}

// ─── admin/farmers/Show ───────────────────────────────────────────────────────

export interface AdminFarmerShowProps {
	farmer: FarmerResource // Inertia::defer
}

// ─── admin/dealers/Index ──────────────────────────────────────────────────────

export interface AdminDealersFilters {
	search: string | null
}

export interface AdminDealersProps {
	summary: AdminDealerSummary // Inertia::defer
	dealers: Paginated<DealerResource> // Inertia::defer
	filters: AdminDealersFilters
}

// ─── admin/dealers/Show ───────────────────────────────────────────────────────

export interface AdminDealerShowProps {
	dealer: DealerResource // Inertia::defer
}
