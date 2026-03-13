import type { User } from "../users/user"


export interface Summary {
    total_dealers: number
    new_dealers_this_month: number
    total_demands: number
    new_demands_this_month: number
}

export interface DealerDemand {
    id: number
    variety: {
        id: number
        name: string
        category: string
        image_url: string
    }
    title: string | null
    offered_price: number
    price_flag: 'Low' | 'Fair' | 'High'
    quantity_kg: number
    transaction_date: string
    days_until_transaction: number
    status: 'Ongoing' | 'Archived' | 'Fulfilled'
    created_at: string
    created_at_human: string
}

export interface Dealer {
    id: number
    user: User
    document_image: string | null
    ongoing_demands_count: number
    ongoing_demands: DealerDemand[]
    joined_at: string
    joined_at_human: string
}

export interface Detail {
    id: number
    user: User
    demands: DealerDemand[]
    joinedd_at: string
    joined_at_human: string
}

export interface Show {
    id: number
    user: User
    document_image: string
    demands: DealerDemand[]
    joined_at: string
    joined_at_human: string
}