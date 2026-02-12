
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
    expirateion_date: string
    days_until_expiration: number
    status: 'active' | 'expired'
    created_at_human: string
    can_edit: boolean
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
    total_active: number
    total_weight_active: number
    harvesting_soon: number
    harvested_this_month: number
}

export interface VarietyOptionsByCategory {
    [category: string]: Array<{
        id: number
        name: string
        weeks_to_harvest: number
    }>
}