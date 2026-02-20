
export interface Summary {
    total_open: number
    total_fulfilled: number
    total_expired: number
    upcomming_transactions: number
}

export interface VarietyOption {
    id: number
    name: string
    current_price: {
        min: number
        max: number
    }
}

export interface Demand {
    id: number
    dealer: {
        id: number
        name: string
    }
    variety: {
        id: number
        name: string
        vegetable: string
        image_url: string
    }
    quantity_kg: number
    price_offered: number
    transaction_date: string
    days_until_transaction: number
    status: string
    created_at_human: string
}

