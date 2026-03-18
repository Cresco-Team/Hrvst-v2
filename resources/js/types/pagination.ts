export interface PaginatedResponse<T> {
	data: T[]
	links: {
		first: string | null
		last: string | null
		prev: string | null
		next: string | null
	}
	meta: {
		current_page: number
		last_page: number
		per_page: number
		total: number
	}
}
