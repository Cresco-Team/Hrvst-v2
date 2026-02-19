
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

/* farmers */
interface User {
    email: string
    id: number
    image_path: string | null
    name: string
    phone_number: string
}

interface Coordinates {
    lat: number
    lng: number
}

export interface Location {
    barangay: string
    coordinates: Coordinates
    municipality: string
    province: string
}

export interface Farmer {
    available_offerings: Offering[]
    available_offerings_count: number
    farm_image: string | null
    id: number
    joined_at: string
    joined_at_human: string
    location: Location
    user: User
}

export interface Offering {
    id: number
    variety: {
        name: string
        category: string
        image_path: string
    }
}

/* summary */
export interface Summary {
    total_farmers: number
    new_farmers_this_month: number
    total_offerings: number
    new_offerings_this_month: number
}

export interface MarkerData {
    id: number
    coordinates: {
        lat: number
        lng: number
    }
    farmer_name: string
    municipality: string
    available_offerings_count: number
    offerings_summary: Array<{
        vegetable: string
        count: number
        varieties: string[]
    }>
}

export interface FarmerDetails {
    id: number
    user: User
    location: {
        province: string
        municipality: string
        barangay: string
        full_address: string
        coordinates: {
            lat: number
            lng: number
        }
    }
    farm_image: string | null
    available_offerings: Array<any>
    statistics: {
        total_available_offerings: number
        total_weight: number
    }
    joined_at: string
    joined_at_human: string
}

export interface PaginatedData {
    data: Farmer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}