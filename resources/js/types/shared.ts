// Shared types used across multiple domains

import type { KpiTrend } from './enums'

// ─── Inertia ──────────────────────────────────────────────────────────────────

export interface SharedProps {
  name: string
  auth: {
    user: AuthUser | null
  }
  sidebarOpen: boolean
  flash: FlashMessage | null
}

export interface AuthUser {
  id: number
  name: string
  email: string
  roles: string[]
  user_image: string | null
}

// ─── Flash ────────────────────────────────────────────────────────────────────

// Matches the shape controllers send via ->with('flash', [...])
export interface FlashMessage {
  type: 'success' | 'error' | 'warning' | 'info' | 'pin'
  message: string
  /** Only present when type === 'pin'. Display in a modal, never log or toast. */
  pin?: string
}

// ─── Pagination ───────────────────────────────────────────────────────────────

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
  current_page: number
  from: number | null
  last_page: number
  path: string
  per_page: number
  to: number | null
  total: number
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
  zoom?: number
  defaultZoom?: number
}

// ─── KPI ──────────────────────────────────────────────────────────────────────

export interface KpiStat {
  value: number
  change?: number
  trend?: KpiTrend
  label?: string
}
