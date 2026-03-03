import type { Supply } from "../marketplace"
import type { User } from "../users/user"

/* filters */
export interface Filters {
    municipalities: Municipality[]
    offerings: OfferingsByCategory
}

interface Municipality {
    id: number
    label: string
    name: string
    province: string
}

interface OfferingsByCategory {
    [category: string]: OfferingOption[]
}

interface OfferingOption {
    id: number
    name: string
    category: string
}

interface Coordinates {
    lat: number
    lng: number
}

export interface Location {
    barangay: string
    municipality: string
    province: string
    full_address?: string
    coordinates: Coordinates
}

export interface Farmer {
    id: number
    user: User
    location: Location
    farm_url: string | null
    ongoing_supplies_count: number
    ongoing_supplies: Supply[]
    joined_at: string
    joined_at_human: string
}

/* summary */
export interface Summary {
    total_farmers: number
    new_farmers_this_month: number
    total_supplies: number
    new_supplies_this_month: number
}

export interface MarkerData {
    id: number
    coordinates: {
        lat: number
        lng: number
    }
    farmer_name: string
    municipality: string
    ongoing_supplies_count: number
    supplies_summary: Array<{
        vegetable: string
        count: number
        varieties: string[]
    }>
}

export interface FarmerDetails {
    id: number
    user: User
    location: Location
    farm_url: string | null
    ongoing_supplies: Supply[]
    statistics: {
        total_ongoing_supplies: number
        total_quantity: number
    }
    joined_at: string
    joined_at_human: string
}

export interface ShowFarmer {
    id: number
    user: User
    location: Location
    farm_url: string
    supplies: {
        ongoing: Supply[]
        archived: Supply[]
        fulfilled: Supply[]
    }
    total_supplies: number
    total_quantity: number
    total_ongoing_supplies: number
    total_ongoing_supplies_quantity: number
    joined_at: string
    joined_at_human: string
}
