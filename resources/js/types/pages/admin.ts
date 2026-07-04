// Admin Inertia page props
// Each interface maps to the props object in Inertia::render('admin/...')

import type { MunicipalityOption, SupplyOption } from '../resources/marketplace'
import type {
  VegetableSummary,
} from '../resources/product'
import type {
  AdminDealerSummary,
  AdminFarmerSummary,
  DealerResource,
  FarmerResource,
} from '../resources/profile'
import type { KpiStat, MapConfig, Paginated } from '../shared'

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
  vegetables: {
    total_vegetables: KpiStat
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
}

export interface AdminCategoriesProps {
  categories: CategoryStat[]
}

// ─── admin/vegetables/Index ───────────────────────────────────────────────────

export interface AdminVegetablesFilters {
  search: string | null
  category_id: number | null
}

export interface AdminVegetablesProps {
  category: { id: number; name: string; slug: string }
  summary: VegetableSummary
  filters: AdminVegetablesFilters
  vegetables: Paginated<App.Data.Vegetable.VegetableAdminData>
}

// ─── admin/farmers/Index ──────────────────────────────────────────────────────

export interface AdminFarmersFilters {
  search: string | null
  municipalities: MunicipalityOption[]
  supplies: Record<string, SupplyOption[]>
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
