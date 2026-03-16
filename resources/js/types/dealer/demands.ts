
export interface Summary {
    total_ongoing: number
    total_archived: number
    total_fulfilled: number
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
    offered_price: number
    price_flag: 'Low' | 'Fair' | 'High'
    transaction_date: string
    days_until_transaction: number
    status: 'Ongoing' | 'Archived' | 'Fulfilled'
    created_at_human: string
}

