import type { VarietyRef } from '../product/variety'

export interface MarketplaceFilters {
	search?: string | null
	category_id?: number | null
	variety_id?: number | null
	municipality_id?: number | null
}

export interface Supply {
	id: number
	farmer: {
		id: number
		name: string
		image_url: string
	}
	variety: VarietyRef
	image_url: string
	quantity_kg: number
	offered_price: number
	price_flag: 'Low' | 'Fair' | 'High'
	expiration_date: string
	days_until_expiration: number | null
	status: 'Ongoing' | 'Archived' | 'Fulfilled'
	created_at_human: string
}

export interface CategoryOption {
	id: number
	name: string
}
