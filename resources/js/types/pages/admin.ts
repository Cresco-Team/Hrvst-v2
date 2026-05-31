// Admin Inertia page props
// Each interface maps to the props object in Inertia::render('admin/...')

import type { MunicipalityOption, SupplyOption } from '../resources/marketplace'
import type {
  CategoryOption,
  Table,
  VarietyResource,
  VarietySummary,
  VegetableOptions,
} from '../resources/product'
import type {
  AdminDealerSummary,
  AdminFarmerSummary,
  DealerResource,
  FarmerBaseResource,
  FarmerResource,
} from '../resources/profile'
import type { Coordinates, KpiStat, MapConfig, Paginated } from '../shared'

// ─── admin/Dashboard ──────────────────────────────────────────────────────────

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

// ─── admin/vegetables/Categories ─────────────────────────────────────────────

export interface CategoryStat {
  id: number
  name: string
  slug: string
  vegetables_count: number
  varieties_count: number
}

export interface AdminCategoriesProps {
  categories: CategoryStat[]
}

// ─── admin/vegetables/Index ───────────────────────────────────────────────────

export interface AdminVegetablesFilters {
  price_filter: string | null
  search: string | null
  category_id: number | null
}

export interface AdminVegetablesProps {
  category?: { id: number; name: string; slug: string } | null
  summary: VarietySummary // Inertia::defer
  filters: AdminVegetablesFilters
  vegetables: Paginated<Table> // Inertia::defer
  vegetableOptions: VegetableOptions // Inertia::defer
  categories: CategoryOption[]
}

// ─── admin/farmers/Index ──────────────────────────────────────────────────────

export interface AdminFarmersFilters {
  search: string | null
  municipalities: MunicipalityOption[]
  supplies: Record<string, SupplyOption[]>
}

export interface AdminFarmerProps {
  view: 'list' | 'map'
  filters: AdminFarmersFilters
  mapConfig: MapConfig
  farmers: Paginated<FarmerBaseResource> | null
  summary: AdminFarmerSummary
}

export interface AdminFarmerTable extends FarmerBaseResource {}

export interface AdminFarmerDetail extends FarmerBaseResource {
  coordinates: Coordinates
  ongoing_supply_items_count: number
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
