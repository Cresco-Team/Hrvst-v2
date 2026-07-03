// ─── Analytics ───────────────────────────────────────────────────────────────

import { Paginated } from "../shared"

export type ImbalanceBand          = App.Enums.Analytics.ImbalanceBand
export type RecommendationSeverity = App.Enums.Analytics.RecommendationSeverity
export type VarietyRecommendation = App.DTOs.Product.VarietyRecommendationDTO
export type VarietyAnalytics = App.DTOs.Product.VarietyAnalyticsDTO

// ─── Market calendar — mirrors VegetableCalendarService::mergeIntoSchedule() ──

export type CalendarTimeSlot = 'morning' | 'afternoon' | 'evening' | 'unscheduled'

export interface CalendarScheduleItem {
	post_id: number
	type: 'supply' | 'demand'
	variety_name: string
	quantity_kg: number
	status: string
}

export interface CalendarSlotData {
	supply_kg: number
	demand_kg: number
	net_kg: number
	supply_posts_count: number
	demand_posts_count: number
	items: CalendarScheduleItem[]
}

export type VarietyDaySchedule = Partial<Record<CalendarTimeSlot, CalendarSlotData>>

export interface VarietyCalendarFilters {
	year: number
	month: number
}

// ─── VegetableResource ────────────────────────────────────────────────────────

export interface VegetableCategory {
	id: number
	name: string
	slug: string
}

export interface SupplyMunicipality {
	name: string
	total_kg: number
}

export interface MonthlyActivity {
	month: string
	label: string
	supply_expired_kg: number
	supply_fulfilled_kg: number
	demand_expired_kg: number
	demand_fulfilled_kg: number
}

export interface VegetableResource {
	id: number
	vegetable_name: string
	variety_name: string | null
	local_name: string | null
	display_name: string
	image_url: string
	category: VegetableCategory | null

	supply_count?: number
	demand_count?: number
	supply_municipalities?: SupplyMunicipality[]
	monthly_activity?: MonthlyActivity[]
	variety_calendar?: Record<string, VarietyDaySchedule>
	analytics?: VarietyAnalytics | null
}

export type VegetableAdminData = App.Data.Vegetable.VegetableAdminData

// ─── Option Bag Types ─────────────────────────────────────────────────────────

export type VegetableOptions = Record<string, Record<string, string>>

export interface CategoryOption {
	id: number
	name: string
	slug: string
}

export interface VegetableSummary {
	total_varieties: number
	total_vegetables: number
}

// ─── Flat admin table row ───────────────────────────────────────────────────

export interface VegetableTableRow {
	id: number
	vegetable_name: string
	variety_name: string | null
	local_name: string | null
	name: string
	category: { id: number; name: string } | null
	image_url: string | null
	supply_count?: number
	demand_count?: number
}

export interface ForecastPoint {
    month: string
    label: string
    supply_kg: number
    demand_kg: number
}
