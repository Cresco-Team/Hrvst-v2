import type { VarietyRef } from '../product/variety'

export interface Supply {
	id: number
	farmer: {
		id: number
		name: string
	}
	variety: VarietyRef
	image_url: string
	quantity_kg: number
	offered_price: number
	expiration_date: string
	days_until_expiration: number
	status: 'Ongoing' | 'Archived' | 'Fulfilled'
	created_at_human: string
}

export interface Summary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
	expiring_this_week: number
}
