interface Variety {
    id: number
    name: string
    category: string
    image_url: string
}

export interface Planting {
    id: number
    variety: Variety
    image_url: string
    weight_kg: number
    asking_price: number
    expiration_date: string
    days_until_expiration: number | null
    status: 'available' | 'archived'
    created_at_human: string
    can_edit: boolean
    can_archive: boolean
    can_delete: boolean
}

export interface PaginatedPlantings {
    data: Planting[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface Summary {
    total_available: number
    total_weight_available: number
    expiring_soon: number
    posted_this_month: number
}

export interface VarietyOption {
    id: number
    name: string
    weeks_to_harvest: number
}

export interface VarietyOptionsByCategory {
    [category: string]: VarietyOption[]
}
