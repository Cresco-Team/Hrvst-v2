import { Planting } from "../product"
import { User } from "./user"

/* Address */
export interface Municipality {
    id: number
    name: string
    province: string
    label: string
}

export interface Coordinates {
    lat: number
    lng: number
}

export interface Location {
    province: string
    municipality: string
    barangay: string
    full_address?: string
    coordinates?: Coordinates
}

export interface Farmer {
    id: number
    user: User
    location: Location
    farm_image: string | null
    active_plantings_count: number
    active_plantings: Planting[]
    joined_at: string
    joined_at_human: string
}

export interface PendingFarmer {
    id: number
    user: User
    location: Location
    farm_image: string | null
    submitted_at: string
    submitted_at_human: string
}

export interface FarmerDetails {
    id: number
    user: User
    location: Location
    farm_image: string | null
    active_plantings: Array<any>
    statistics: {
        total_active_plantings: number
        total_weight: number
        harvesting_soon: number
    }
    joined_at: string
    joined_at_human: string
}

export interface MarkerData {
    id: number
    coordinates: Coordinates
    farmer_name: string
    municipality: string
    active_plantings_count: number
    plantings_summary: Array<{
        vegetable: string
        count: number
        varieties: string[]
    }>
}