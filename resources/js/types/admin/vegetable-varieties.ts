import type { PaginatedResponse } from '@/types/pagination'
import type { AdminVariety, VegetableOptions } from '@/types/product/variety'

// Re-export under the local alias components already reference
export type { AdminVariety as Variety, VegetableOptions }

export interface Summary {
    total_varieties: number
    total_vegetables: number
    average_weeks_to_harvest: number
    price_stats: {
        updated_week: number
        updated_month: number
        stale: number
        no_price: number
    }
}

export interface Props {
    varieties?: PaginatedResponse<AdminVariety>
    summary?: Summary
    vegetableOptions?: VegetableOptions
    filters: {
        price_filter: string | null
        search?: string | null
    }
}
