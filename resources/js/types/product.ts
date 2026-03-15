
export interface Category {
    id: number
    name: string
}

export interface Vegetable {
    id: number
    name: string
    category: Category
}

export interface Variety {
    id: number
    name: string
    vegetable?: string
    category?: string
    image_url: string
}

export interface Planting {
    id: number
    variety: Variety
    weight_kg: number
    date_planted: string
    expected_harvest_date: string
    days_until_harvest: number | null
    status_badge: string
}

