import type { Demand, Supply } from "../marketplace"
import type { User } from "../users/user"

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
    total_demands: number
    new_demands_this_month: number
}

export interface Dealer {
    id: number
    user: User
    document_image: string | null
    ongoing_demands_count: number
    ongoing_demands: Demand[]
    joined_at: string
    joined_at_human: string
}

export interface DealerSidebar {
    id: number
    user: User
    documentation_image: string
    ongoing_demands: Supply[]
    statistics: {
        total_ongoing_demands: number
        total_quantity: number
    }
    joined_at: string
    joined_at_human: string
}

export interface DealerShow {
    id: number
    user: User
    document_image: string
    demands: {
        ongoing: Demand[]
        archived: Demand[]
        fulfilled: Demand[]
    }
    total_demands: number
    total_quantity: number
    total_ongoing_demands: number
    total_ongoing_demands_quantity: number
    joined_at: string
    joined_at_human: string
}