// Mirrors backend summary shapes returned by SupplyService / DemandService

export interface FarmerSupplySummary {
	total_growing: number
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
}

export interface DealerDemandSummary {
	total_ongoing: number
	total_fulfilled: number
	total_archived: number
}
