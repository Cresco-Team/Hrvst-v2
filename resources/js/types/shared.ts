// Shared types used across multiple domains

import type { KpiTrend } from './enums'

// ─── Inertia ──────────────────────────────────────────────────────────────────

// Mirrors HandleInertiaRequests::share()
export interface SharedProps {
  name: string
  auth: {
    user: AuthUser | null
  }
  sidebarOpen: boolean
  flash: FlashMessage | null
}

// Minimal user shape shared on every Inertia response (not the full UserResource)
export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
  user_image: string | null
}

// ─── Flash ────────────────────────────────────────────────────────────────────

// Set via ->with('flash', ['type' => '...', 'message' => '...']) in controllers
export interface FlashMessage {
  type: 'success' | 'error' | 'warning' | 'info'
  message: string
}

// ─── Pagination ───────────────────────────────────────────────────────────────

// Shape produced by LengthAwarePaginator passed through Inertia (resource collection)
export interface Paginated<T> {
  data: T[]
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
  meta: {
    current_page: number
    from: number | null
    last_page: number
    links: PaginationLink[]
    path: string
    per_page: number
    to: number | null
    total: number
  }
}

export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

// ─── Map ──────────────────────────────────────────────────────────────────────

export interface Coordinates {
  lat: number
  lng: number
}

export interface MapConfig {
  center: Coordinates
  zoom?: number        // SupplyMapService
  defaultZoom?: number // FarmerController (admin)
}

// ─── KPI ──────────────────────────────────────────────────────────────────────

// Single KPI stat shape used throughout DashboardService.
// change/trend are optional because variety KPIs don't carry trend data —
// DashboardService only computes them for farmer/dealer counts.
// formatChange() and getTrendIcon() in Dashboard.vue already guard for undefined.
export interface KpiStat {
  value: number
  change?: number
  trend?: KpiTrend
  label?: string
}
