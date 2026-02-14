export interface Dealer {
    id: number
    user: {
        id: number
        name: string
        email: string
        phone_number: string
        image_path: string | null
    }
    document_image: string | null
    open_requests_count: number
    open_requests: {
        id: number
        variety: {
            name: string
            category: string
            image_url: string
        }
        quantity_kg: number
        transaction_date: string
    }
    joined_at: string
    joined_at_human: string
}

export interface PaginatedData {
    data: Dealer[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export interface Summary {
    total_dealers: number
    new_dealers_this_month: number
    total_requests: number
    new_requests_this_month: number
}