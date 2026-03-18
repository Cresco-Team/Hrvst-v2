import type { Planting } from '../product'
import type { User } from './user'

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
